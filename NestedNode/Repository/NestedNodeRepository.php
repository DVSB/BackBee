<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\NestedNode\Repository;

use Doctrine\ORM\EntityRepository;
use BackBee\Exception\InvalidArgumentException;
use BackBee\NestedNode\ANestedNode;
use BackBee\Util\Buffer;

/**
 * NestedNode repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeRepository extends EntityRepository
{
    public static $config = array(
        // calculate nested node values asynchronously via a CLI command
        'nestedNodeCalculateAsync' => false,
    );

    /**
     * [updateTreeNatively description].
     *
     * @param [type] $node_uid [description]
     * @param [type] $leftnode [description]
     * @param [type] $level    [description]
     *
     * @return [type] [description]
     */
    public function updateTreeNatively($node_uid, $leftnode = 1, $level = 0)
    {
        $node = new \StdClass();
        $node->uid = $node_uid;
        $node->leftnode = $leftnode;
        $node->rightnode = $leftnode + 1;
        $node->level = $level;

        foreach ($children = $this->getNativelyNodeChildren($node_uid) as $row) {
            $child = $this->updateTreeNatively($row['uid'], $leftnode + 1, $level + 1);
            $node->rightnode = $child->rightnode + 1;
            $leftnode = $child->rightnode;
        }

        $this->_em->getConnection()->exec(sprintf(
                        'update page set leftnode = %d, rightnode = %d, level = %d where uid = "%s";', $node->leftnode, $node->rightnode, $node->level, $node->uid
        ));

        return $node;
    }

    /**
     * [getNativelyNodeChildren description].
     *
     * @param [type] $node_uid [description]
     *
     * @return [type] [description]
     */
    private function getNativelyNodeChildren($node_uid)
    {
        return $this->_em->getConnection()->executeQuery(sprintf(
                                'select uid from page where parent_uid = "%s" order by leftnode asc, modified desc', $node_uid
                ))->fetchAll();
    }

    public function updateTreeNativelyWithProgressMessage($node_uid)
    {
        $node_uid = (array) $node_uid;
        if (0 === count($node_uid)) {
            Buffer::dump("\n##### Nothing to update. ###\n");

            return;
        }

        $convert_memory = function ($size) {
            $unit = array('B', 'KB', 'MB', 'GB');

            return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
        };

        $starttime = microtime(true);

        Buffer::dump("\n##### Update tree (natively) started ###\n");

        foreach ($node_uid as $uid) {
            $this->_em->clear();

            $starttime = microtime(true);
            Buffer::dump("\n   [START] update tree of $uid in progress\n\n");

            $this->updateTreeNatively($uid);

            Buffer::dump(
                    "\n   [END] update tree of $uid in "
                    .(microtime(true) - $starttime).'s (memory status: '.$convert_memory(memory_get_usage()).')'
                    ."\n"
            );
        }

        Buffer::dump("\n##### Update tree (natively) in ".(microtime(true) - $starttime)."s #####\n\n");
    }

    public function updateHierarchicalDatas(ANestedNode $node, $leftnode = 1, $level = 0)
    {
        $node->setLeftnode($leftnode)->setLevel($level);

        if (0 < $node->getChildren()->count()) {
            $children = $this->createQueryBuilder('n')
                    ->andWhere("n._parent = :parent")
                    ->setParameters(array("parent" => $node))
                    ->orderBy('n._leftnode', 'asc')
                    ->getQuery()
                    ->getResult();

            foreach ($children as $child) {
                $child = $this->updateHierarchicalDatas($child, $leftnode + 1, $level + 1);
                $leftnode = $child->getRightnode();
            }
        }

        $node->setRightnode($leftnode + 1);
        $this->createQueryBuilder('n')
                ->update()
                ->set('n._leftnode', $node->getLeftnode())
                ->set('n._rightnode', $node->getRightnode())
                ->set('n._level', $node->getLevel())
                ->where('n._uid = :uid')
                ->setParameter('uid', $node->getUid())
                ->getQuery()
                ->execute();

        $this->_em->detach($node);

        return $node;
    }

    /**
     * Inserts a leaf node in a tree as first child of the provided parent node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node   The node to be inserted
     * @param \BackBee\NestedNode\ANestedNode $parent The parent node
     *
     * @return \BackBee\NestedNode\ANestedNode The inserted node
     *
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is not a leaf or $parent is not flushed yet
     */
    public function insertNodeAsFirstChildOf(ANestedNode $node, ANestedNode $parent)
    {
        return $this->_insertNode($node, $parent, $parent->getLeftnode() + 1);
    }

    /**
     * Inserts a leaf node in a tree as last child of the provided parent node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node   The node to be inserted
     * @param \BackBee\NestedNode\ANestedNode $parent The parent node
     *
     * @return \BackBee\NestedNode\ANestedNode The inserted node
     *
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is not a leaf or $parent is not flushed yet
     */
    public function insertNodeAsLastChildOf(ANestedNode $node, ANestedNode $parent)
    {
        return $this->_insertNode($node, $parent, $parent->getRightnode());
    }

    /**
     * Inserts a leaf node in a tree.
     *
     * @param \BackBee\NestedNode\ANestedNode $node   The node to be inserted
     * @param \BackBee\NestedNode\ANestedNode $parent The parent node
     * @param int                                           The new left node of the inserted node
     *
     * @return \BackBee\NestedNode\ANestedNode The inserted node
     *
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is not a leaf or $parent is not flushed yet
     */
    protected function _insertNode(ANestedNode $node, ANestedNode $parent, $new_leftnode)
    {
        if (false === $node->isLeaf()) {
            throw new InvalidArgumentException('Only a leaf can be inserted');
        }

        if ($node === $parent) {
            throw new InvalidArgumentException('Cannot insert node in itself');
        }

        $this->_detachOrPersistNode($node)
                ->_refreshExistingNode($parent);

        $node->setLeftnode($new_leftnode)
                ->setRightnode($node->getLeftnode() + 1)
                ->setLevel($parent->getLevel() + 1)
                ->setParent($parent)
                ->setRoot($parent->getRoot());

        $this->shiftRlValues($node, $node->getLeftnode(), 2);

        $this->_em->refresh($parent);

        return $node;
    }

    /**
     * Returns the query build to get the previous sibling of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getPrevSiblingQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsPreviousSiblingOf($node);
    }

    /**
     * Returns the previous sibling node for $node or NULL if $node is the first one in its branch or root.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\ANestedNode|NULL
     */
    public function getPrevSibling(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsPreviousSiblingOf($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * @deprecated since version 0.10.0
     */
    protected function _getPrevSiblingsQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andParentIs($node->getParent())
                        ->andLevelEquals($node->getLevel())
                        ->andRightnodeIsLowerThan($node->getLeftnode(), true);
    }

    /**
     * @deprecated since version 0.10.0
     */
    protected function _getNextSiblingsQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andParentIs($node->getParent())
                        ->andLevelEquals($node->getLevel())
                        ->andLeftnodeIsUpperThan($node->getLeftnode(), true);
    }

    /**
     * Returns the query build to get the next sibling of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getNextSiblingQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsNextSiblingOf($node);
    }

    /**
     * Returns the next sibling node for $node or NULL if $node is the last one in its branch.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\ANestedNode|NULL
     */
    public function getNextSibling(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsNextSiblingOf($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the query build to get the siblings of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param boolean                         $includeNode if TRUE, include $node in result array
     * @param array                           $order       ordering spec
     * @param int                             $limit       max number of results
     * @param int                             $start       first result index
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getSiblingsQuery(ANestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('n')
                        ->andIsSiblingsOf($node, !$includeNode, $order, $limit, $start);
    }

    /**
     * Returns the siblings of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param boolean                         $includeNode if TRUE, include $node in result array
     * @param array                           $order       ordering spec
     * @param int                             $limit       max number of results
     * @param int                             $start       first result index
     *
     * @return \BackBee\NestedNode\ANestedNode[]
     */
    public function getSiblings(ANestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('n')
                        ->andIsSiblingsOf($node, !$includeNode, $order, $limit, $start)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the first child of node if exists.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\ANestedNode|NULL
     */
    public function getFirstChild(ANestedNode $node)
    {
        $children = $this->getDescendants($node, 1);
        if (0 < count($children)) {
            return $children[0];
        }

        return;
    }

    /**
     * Returns the first child of node if exists.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\ANestedNode|NULL
     */
    public function getLastChild(ANestedNode $node)
    {
        $children = $this->getDescendants($node, 1);
        if (0 < count($children)) {
            return $children[count($children) - 1];
        }

        return;
    }

    /**
     * Returns the query build to get ancestor at level $level of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $level
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getAncestorQuery(ANestedNode $node, $level = 0)
    {
        return $this->createQueryBuilder('n')
                        ->andIsAncestorOf($node, false, $level);
    }

    /**
     * Returns the ancestor at level $level of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $level
     *
     * @return \BackBee\NestedNode\ANestedNode|NULL
     */
    public function getAncestor(ANestedNode $node, $level = 0)
    {
        if ($node->getLevel() < $level) {
            return;
        }

        if ($node->getLevel() == $level) {
            return $node;
        }

        try {
            return $this->createQueryBuilder('n')
                            ->andIsAncestorOf($node, false, $level)
                            ->getQuery()
                            ->getSingleResult();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the query build to get ancestors of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $depth       Returns only ancestors from $depth number of generation
     * @param boolean                         $includeNode Returns also the node itsef if TRUE
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getAncestorsQuery(ANestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsAncestorOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsUpperThan($node->getLevel() - $depth);
        }

        return $q;
    }

    /**
     * Returns the ancestors of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $depth       Returns only ancestors from $depth number of generation
     * @param boolean                         $includeNode Returns also the node itsef if TRUE
     *
     * @return array
     */
    public function getAncestors(ANestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsAncestorOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsUpperThan($node->getLevel() - $depth);
        }

        return $q->orderBy('n._rightnode', 'desc')
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the query build to get descendants of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $depth       Returns only descendants from $depth number of generation
     * @param boolean                         $includeNode Returns also the node itsef if TRUE
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     *
     * @deprecated since version 0.10.0
     */
    protected function _getDescendantsQuery(ANestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsDescendantOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsLowerThan($node->getLevel() + $depth);
        }

        return $q;
    }

    /**
     * Returns the descendants of the provided node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $depth       Returns only decendants from $depth number of generation
     * @param boolean                         $includeNode Returns also the node itsef if TRUE
     *
     * @return array
     */
    public function getDescendants(ANestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsDescendantOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsLowerThan($node->getLevel() + $depth);
        }

        return $q->orderBy('n._leftnode', 'asc')
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Move node as previous sibling of $dest.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param \BackBee\NestedNode\ANestedNode $dest
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $dest is a root
     */
    public function moveAsPrevSiblingOf(ANestedNode $node, ANestedNode $dest)
    {
        if (true === $dest->isRoot()) {
            throw new InvalidArgumentException('Cannot move node as sibling of a root');
        }

        return $this->_moveNode($node, $dest, 'before');
    }

    /**
     * Move node as next sibling of $dest.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param \BackBee\NestedNode\ANestedNode $dest
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $dest is a root
     */
    public function moveAsNextSiblingOf(ANestedNode $node, ANestedNode $dest)
    {
        if (true === $dest->isRoot()) {
            throw new InvalidArgumentException('Cannot move node as sibling of a root');
        }

        return $this->_moveNode($node, $dest, 'after');
    }

    /**
     * Move node as first child of $dest.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param \BackBee\NestedNode\ANestedNode $dest
     *
     * @return \BackBee\NestedNode\ANestedNode
     */
    public function moveAsFirstChildOf(ANestedNode $node, ANestedNode $dest)
    {
        return $this->_moveNode($node, $dest, 'firstin');
    }

    /**
     * Move node as last child of $dest.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param \BackBee\NestedNode\ANestedNode $dest
     *
     * @return \BackBee\NestedNode\ANestedNode
     */
    public function moveAsLastChildOf(ANestedNode $node, ANestedNode $dest)
    {
        return $this->_moveNode($node, $dest, 'lastin');
    }

    /**
     * Move node regarding $dest.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param \BackBee\NestedNode\ANestedNode $dest
     * @param string                          $position
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $node is ancestor of $dest
     */
    protected function _moveNode(ANestedNode $node, ANestedNode $dest, $position)
    {
        if (true === $node->isAncestorOf($dest, false)) {
            throw new InvalidArgumentException('Cannot move node as child of one of its descendants');
        }

        $this->_refreshExistingNode($node)
                ->_detachFromTree($node)
                ->_refreshExistingNode($dest);

        $newleft = $this->_getNewLeftFromPosition($dest, $position);
        $newlevel = $this->_getNewLevelFromPosition($dest, $position);
        $newparent = $this->_getNewParentFromPosition($dest, $position);

        $node->setRightnode($newleft + $node->getWeight() - 1)
                ->setLeftnode($newleft)
                ->setLevel($newlevel)
                ->setRoot($dest->getRoot())
                ->setParent($newparent);

        $this->shiftRlValues($node, $newleft, $node->getWeight());

        $this->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta_node')
                ->set('n._rightnode', 'n._rightnode + :delta_node')
                ->set('n._level', 'n._level + :delta_level')
                ->set('n._root', ':root')
                ->andWhere('n._root = :node')
                ->setParameters(array(
                    'delta_node' => $newleft - 1,
                    'delta_level' => $newlevel,
                    'root' => $dest->getRoot(),
                    'node' => $node,
                ))
                ->update()
                ->getQuery()
                ->execute();

        return $node;
    }

    /**
     * Returns the new left node from $dest node and position.
     *
     * @param \BackBee\NestedNode\ANestedNode $dest
     * @param string                          $position
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $position is unknown
     */
    private function _getNewLeftFromPosition(ANestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
                $newleft = $dest->getLeftnode();
                break;
            case 'after':
                $newleft = $dest->getRightnode() + 1;
                break;
            case 'firstin':
                $newleft = $dest->getLeftnode() + 1;
                break;
            case 'lastin':
                $newleft = $dest->getRightnode();
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newleft;
    }

    /**
     * Returns the new level of node from $dest node and position.
     *
     * @param \BackBee\NestedNode\ANestedNode $dest
     * @param string                          $position
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $position is unknown
     */
    private function _getNewLevelFromPosition(ANestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
            case 'after':
                $newlevel = $dest->getLevel();
                break;
            case 'firstin':
            case 'lastin':
                $newlevel = $dest->getLevel() + 1;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newlevel;
    }

    /**
     * Returns the new parent node from $dest node and position.
     *
     * @param \BackBee\NestedNode\ANestedNode $dest
     * @param string                          $position
     *
     * @return \BackBee\NestedNode\ANestedNode
     *
     * @throws InvalidArgumentException Occurs if $position is unknown
     */
    private function _getNewParentFromPosition(ANestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
            case 'after':
                $newparent = $dest->getParent();
                break;
            case 'firstin':
            case 'lastin':
                $newparent = $dest;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newparent;
    }

    /**
     * Deletes node and it's descendants.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return boolean TRUE on success, FALSE if try to delete a root
     */
    public function delete(ANestedNode $node)
    {
        if (true === $node->isRoot()) {
            return false;
        }

        $this->createQueryBuilder('n')
                ->set('n._parent', 'NULL')
                ->andIsDescendantOf($node)
                ->update()
                ->getQuery()
                ->execute();

        $this->createQueryBuilder('n')
                ->delete()
                ->andIsDescendantOf($node)
                ->getQuery()
                ->execute();

        $this->shiftRlValues($node->getParent(), $node->getLeftnode(), - $node->getWeight());

        return true;
    }

    private function shiftRlValuesByJob(ANestedNode $target, $first, $delta)
    {
        $job = new \BackBee\Job\NestedNodeLRCalculateJob();

        $job->args = array(
            'nodeId' => $target->getUid(),
            'nodeClass' => get_class($target),
            'first' => $first,
            'delta' => $delta,
        );

        $queue = new \BackBee\Job\Queue\RegistryQueue('NESTED_NODE');
        $queue->setEntityManager($this->getEntityManager());

        $queue->enqueue($job);
    }

    private function startDetachedRLValuesJob(ANestedNode $target, $first, $delta)
    {
        $this->_em->flush($target);
        exec(sprintf(
            '%s %s nestednode:job:lrcalculate --nodeId="%s" --nodeClass="%s" --first="%s" --delta="%s" %s &',
            self::$config['script_command'],
            self::$config['console_command'],
            $target->getUid(),
            get_class($target),
            $first,
            $delta,
            false === empty(self::$config['environment']) ? '--env='.self::$config['environment'] : ''
        ));
    }

    /**
     * Shift part of a tree.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     * @param int                             $first
     * @param ont                             $delta
     * @param \BackBee\NestedNode\ANestedNode $target
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    private function shiftRlValues(ANestedNode $node, $first, $delta)
    {
        if (self::$config['nestedNodeCalculateAsync']) {
            // $this->shiftRlValuesByJob($target, $first, $delta);
            $this->startDetachedRLValuesJob($node, $first, $delta);
        } else {
            $this->createQueryBuilder('n')
                    ->set('n._leftnode', 'n._leftnode + :delta')
                    ->andRootIs($node->getRoot())
                    ->andLeftnodeIsUpperThan($first)
                    ->setParameter('delta', $delta)
                    ->update()
                    ->getQuery()
                    ->execute();

            $this->createQueryBuilder('n')
                    ->set('n._rightnode', 'n._rightnode + :delta')
                    ->andRootIs($node->getRoot())
                    ->andRightnodeIsUpperThan($first)
                    ->setParameter('delta', $delta)
                    ->update()
                    ->getQuery()
                    ->execute();
        }

        return $this;
    }

    /**
     * Detach node from its tree, ie create a new tree from node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    protected function _detachFromTree(ANestedNode $node)
    {
        if (true === $node->isRoot()) {
            return $this;
        }

        $this->_refreshExistingNode($node)
                ->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode - :delta_node')
                ->set('n._rightnode', 'n._rightnode - :delta_node')
                ->set('n._level', 'n._level - :delta_level')
                ->set('n._root', ':node')
                ->andIsDescendantOf($node)
                ->setParameter('delta_node', $node->getLeftnode() - 1)
                ->setParameter('delta_level', $node->getLevel())
                ->setParameter('node', $node)
                ->update()
                ->getQuery()
                ->execute();

        $this->shiftRlValues($node, $node->getLeftnode(), - $node->getWeight());

        $node->setRightnode($node->getWeight())
                ->setLeftnode(1)
                ->setLevel(0)
                ->setRoot($node);

        return $this;
    }

    /**
     * Refresh an existing node.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    protected function _refreshExistingNode(ANestedNode $node)
    {
        if (true === $this->_em->contains($node)) {
            $this->_em->refresh($node);
        } elseif (null === $node = $this->find($node->getUid())) {
            $this->_em->persist($node);
        }

        return $this;
    }

    /**
     * Persist a new node or detach it from tree if already exists.
     *
     * @param \BackBee\NestedNode\ANestedNode $node
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    protected function _detachOrPersistNode(ANestedNode $node)
    {
        if (null !== $refreshed = $this->find($node->getUid())) {
            return $this->_detachFromTree($refreshed)
                            ->_refreshExistingNode($node);
        }

        if (false === $this->_em->contains($node)) {
            $this->_em->persist($node);
        }

        return $this;
    }

    /**
     * Creates a new NestedNode QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $qb = new NestedNodeQueryBuilder($this->_em);

        return $qb->select($alias)->from($this->_entityName, $alias, $indexBy);
    }
}
