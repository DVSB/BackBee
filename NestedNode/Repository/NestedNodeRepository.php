<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\ANestedNode;
use BackBuilder\Util\Buffer;

use Doctrine\ORM\EntityRepository;

/**
 * NestedNode repository
 * 
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeRepository extends EntityRepository
{
    /**
     * [updateTreeNatively description]
     * @param  [type] $node_uid [description]
     * @param  [type] $leftnode [description]
     * @param  [type] $level    [description]
     * @return [type]           [description]
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
            $leftnode = $node->rightnode;
        }

        $this->_em->getConnection()->exec(sprintf(
            'update page set leftnode = %d, rightnode = %d, level = %d where uid = "%s";',
            $node->leftnode,
            $node->rightnode,
            $node->level,
            $node->uid
        ));

        return $node;
    }

    /**
     * [getNativelyNodeChildren description]
     * @param  [type] $node_uid [description]
     * @return [type]           [description]
     */
    private function getNativelyNodeChildren($node_uid)
    {
        return $this->_em->getConnection()->executeQuery(sprintf(
            'select uid from page where parent_uid = "%s" order by modified desc',
            $node_uid
        ))->fetchAll();
    }

    public function updateTreeNativelyWithProgressMessage($node_uid)
    {
        $node_uid = (array) $node_uid;
        if (0 === count($node_uid)) {
            Buffer::dump("\n##### Nothing to update. ###\n");

            return;
        }

        $convert_memory = function($size) {
            $unit = array('B','KB','MB','GB');

            return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
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
                . (microtime(true) - $starttime) . 's (memory status: ' . $convert_memory(memory_get_usage()) . ')' 
                . "\n"
            );
        }

        Buffer::dump("\n##### Update tree (natively) in " . (microtime(true) - $starttime) . "s #####\n\n");
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

    public function insertNodeAsFirstChildOf(ANestedNode $node, ANestedNode $parent)
    {
        if (FALSE === $node->isLeaf())
            throw new \Exception("Only a leaf can be inserted");

        if (null === $parent) {
            throw new \BackBuilder\Exception\BBException('Can\'t insert node to null');
        }

        if (!$this->_em->contains($node))
            $this->_em->persist($node);
        if (!$this->_em->contains($parent))
            $this->_em->persist($parent);
        $node->setLeftnode($parent->getLeftnode() + 1);
        $node->setRightnode($node->getLeftnode() + 1);
        $node->setParent($parent);
        $node->setRoot($parent->getRoot());
        $node->setLevel($parent->getLevel() + 1);
        $this->shiftRlValues($parent, $node->getLeftnode(), 2);

        return $node;
    }

    public function insertNodeAsLastChildOf(ANestedNode $node, ANestedNode $parent)
    {
        if (FALSE === $node->isLeaf())
            throw new \Exception("Only a leaf can be inserted");

        if (null === $parent) {
            throw new \BackBuilder\Exception\BBException('Can\'t insert node to null');
        }

        if (!$this->_em->contains($node))
            $this->_em->persist($node);
        if (!$this->_em->contains($parent))
            $this->_em->persist($parent);
        $node->setLeftnode($parent->getRightnode());
        $node->setRightnode($node->getLeftnode() + 1);
        $node->setParent($parent);
        $node->setRoot($parent->getRoot());
        $node->setLevel($parent->getLevel() + 1);
        $this->shiftRlValues($parent, $node->getLeftnode(), 2);
        return $node;
    }

    /**
     * 
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param type $left
     * @param type $right
     * @param type $levelDiff
     * @return type
     * Met à jour la valeur du level des noeuds compris entre [left && right] en utilisant $levelDiff
     */
    protected function _updateNodeLevel(ANestedNode $node, $left, $right, $levelDiff)
    {

        return $this->createQueryBuilder('n')
                        ->update()
                        ->set("n._level", "n._level + :levelDiffValue")
                        ->where("n._leftnode >= :leftNodeValue AND n._rightnode <= :rightNodeValue")
                        ->andWhere("n._root=:root")
                        ->setParameters(array(
                            "levelDiffValue" => $levelDiff,
                            "leftNodeValue" => $left,
                            "rightNodeValue" => $right,
                            "root" => $node->getRoot()
                        ))->getQuery()->execute();
    }

    protected function _getPrevSiblingQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._rightnode = :rightnode')
                        ->setParameters(array(
                            'root' => $node->getRoot(),
                            'rightnode' => $node->getLeftnode() - 1
        ));
    }

    public function getPrevSibling(ANestedNode $node)
    {
        return $this->_getPrevSiblingQuery($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    protected function _getPrevSiblingsQuery(ANestedNode $node)
    {
        if (null === $parent = $node->getParent()) {
            return null;
        }

        return $this->createQueryBuilder('n')
                        ->andWhere('n._parent = :parent')
                        ->andWhere('n._level = :level')
                        ->andWhere('n._rightnode < :rightnode')
                        ->setParameters(array(
                            'parent' => $parent,
                            'level' => $node->getLevel(),
                            'rightnode' => $node->getLeftnode()
        ));
    }

    protected function _getNextSiblingsQuery(ANestedNode $node)
    {
        if (null === $parent = $node->getParent()) {
            return null;
        }

        return $this->createQueryBuilder('n')
                        ->andWhere('n._parent = :parent')
                        ->andWhere('n._level = :level')
                        ->andWhere('n._leftnode > :leftnode')
                        ->setParameters(array(
                            'parent' => $parent,
                            'level' => $node->getLevel(),
                            'leftnode' => $node->getRightnode()
        ));
    }

    protected function _getNextSiblingQuery(ANestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._leftnode = :leftnode')
                        ->setParameters(array(
                            'root' => $node->getRoot(),
                            'leftnode' => $node->getRightnode() + 1
        ));
    }

    public function getNextSibling(ANestedNode $node)
    {
        return $this->_getNextSiblingQuery($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    protected function _getSiblingsQuery(ANestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        if (null === $order) {
            $order = array('_leftnode' => 'asc');
        }

        $qb = $this->createQueryBuilder('n')
            ->andWhere('n._parent = :parent')
        ->setParameter('parent', $node->getParent());

        if (false === $includeNode) {
            $qb->andWhere('n._uid != :uid')
                ->setParameter('uid', $node->getUid());
        }

        foreach ($order as $col => $sort) {
            $qb->orderBy('n.' . $col, $sort);    
        }


        if (null !== $limit) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult($start);
        }

        return $qb;
    }

    public function getSiblings(ANestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->_getSiblingsQuery($node, $includeNode, $order, $limit, $start)->getQuery()->getResult();
    }

    public function getFirstChild(ANestedNode $node)
    {
        $children = $node->getChildren();
        if (0 < count($children)) {
            return $children[0];
        }
        return null;
    }

    public function getLastChild(ANestedNode $node)
    {
        $children = $node->getChildren();
        if (0 < count($children)) {
            return $children[count($children) - 1];
        }
        return null;
    }

    protected function _getAncestorQuery(ANestedNode $node, $level = 0)
    {
        return $this->createQueryBuilder('n')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._leftnode <= :leftnode')
                        ->andWhere('n._rightnode >= :rightnode')
                        ->andWhere('n._level = :level')
                        ->setParameters(array(
                            'root' => $node->getRoot(),
                            'leftnode' => $node->getLeftnode(),
                            'rightnode' => $node->getRightnode(),
                            'level' => $level
        ));
    }

    public function getAncestor(ANestedNode $node, $level = 0)
    {
        if ($node->getLevel() < $level)
            return NULL;

        if ($node->getLevel() == $level)
            return $node;

        return $this->_getAncestorQuery($node, $level)
                        ->getQuery()
                        ->getSingleResult();
    }

    protected function _getAncestorsQuery(ANestedNode $node, $depth = NULL, $includeNode = FALSE)
    {
        $q = $this->createQueryBuilder('n')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode < :leftnode')
                ->andWhere('n._rightnode > :rightnode')
                ->orderBy('n._rightnode', 'desc')
                ->setParameters(array(
            'root' => $node->getRoot(),
            'leftnode' => $node->getLeftnode() + ($includeNode ? 1 : 0),
            'rightnode' => $node->getRightnode() - ($includeNode ? 1 : 0)
        ));

        if (!is_null($depth) && is_int($depth) && $depth > 0) {
            $q = $q->andWhere('n._level >= :level')
                    ->setParameter('level', $node->getLevel() - $depth);
        }

        return $q;
    }

    public function getAncestors(ANestedNode $node, $depth = NULL, $includeNode = FALSE)
    {
        return $this->_getAncestorsQuery($node, $depth, $includeNode)
                        ->getQuery()
                        ->getResult();
    }

    protected function _getDescendantsQuery(ANestedNode $node, $depth = NULL, $includeNode = FALSE)
    {
        $q = $this->createQueryBuilder('n')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode >= :leftnode')
                ->andWhere('n._rightnode <= :rightnode')
                ->orderBy('n._leftnode', 'asc')
                ->setParameters(array(
            'root' => $node->getRoot(),
            'leftnode' => $node->getLeftnode() + ($includeNode ? 0 : 1),
            'rightnode' => $node->getRightnode() - ($includeNode ? 0 : 1)
        ));

        if (!is_null($depth) && is_int($depth) && $depth > 0) {
            $q = $q->andWhere('n._level <= :level')
                    ->setParameter('level', $node->getLevel() + $depth);
        }
        return $q;
    }

    public function getDescendants(ANestedNode $node, $depth = NULL, $includeNode = FALSE)
    {
        return $this->_getDescendantsQuery($node, $depth, $includeNode)
                        ->getQuery()
                        ->getResult();
    }

    public function moveAsPrevSiblingOf(ANestedNode $node, ANestedNode $dest)
    {
        if ($dest->isRoot())
            return false; //ne rien mettre avant root;
        if ($dest == $node)
            return false;

        if ($node->getRightnode() + 1 == $dest->getLeftnode())
            return false; /* $node is already the prev of $dest -> do nothing */

        if ($node->getParent() == $dest->getParent() && $dest->getLeftnode() < $node->getLeftnode()) {
            $newLeft = $dest->getLeftnode();
        } else {
            $newLeft = $dest->getLeftnode() - $node->getWeight();
        }
        $newRight = $newLeft + $node->getWeight() - 1;

        /* detach && room for subtree */
        $this->_detachFromTree($node)->shiftRlValues($dest, $newLeft, $node->getWeight());
        /* move the removed subtree back to the main tree */
        $delta = $newLeft - 1; /* newleft - currentSubtreeleft always starts at 1) */
        $levelDiff = $dest->getLevel() - 1; /* -1 as substree level starts with 1 */
        $node->setLeftnode($newLeft)
                ->setRightnode($newRight)
                ->setLevel($dest->getLevel())
                ->setParent($dest->getParent())
                ->setRoot($dest->getRoot());
        return true;
    }


    public function moveAsNextSiblingOf(ANestedNode $node, ANestedNode $dest)
    {
        if ($dest->isAncestorOf($node))
            return FALSE;
        $newLeft = $dest->getLeftnode() + $dest->getWeight();
        $newRight = $newLeft + ($node->getRightnode() - $node->getLeftnode());
        $this->shiftRLValues($dest, $newLeft, $node->getRightnode() - $node->getLeftnode() + 1);
        $node->setLeftnode($newLeft)
                ->setRightnode($newRight)
                ->setLevel($dest->getLevel())
                ->setParent($dest->getParent())
                ->setRoot($dest->getRoot());
        return true;
    }

    public function moveAsFirstChildOf(ANestedNode $node, ANestedNode $dest)
    {
        if ($dest->isAncestorOf($node))
            return FALSE;
        $newLeft = $dest->getLeftnode() + 1;
        $newRight = $newLeft + ($node->getRightnode() - $node->getLeftnode());
        $this->shiftRLValues($dest, $newLeft, $node->getRightnode() - $node->getLeftnode() + 1);
        $node->setLeftnode($newLeft)
                ->setRightnode($newRight)
                ->setLevel($dest->getLevel() + 1)
                ->setParent($dest)
                ->setRoot($dest->getRoot());
        return true;
    }

    /**
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param \BackBuilder\NestedNode\ANestedNode $dest
     * don't use 
     */
    public function moveAsLastChildOf(ANestedNode $node, ANestedNode $dest)
    {
//        if ($nodet->isAncestorOf($dest))
//            return FALSE;

        $newLeft = $dest->getRightnode();
        $newRight = $newLeft + ($node->getRightnode() - $node->getLeftnode());
        $this->shiftRLValues($dest, $newLeft, $node->getRightnode() - $node->getLeftnode() + 1);
        $node->setLeftnode($newLeft)
                ->setRightnode($newRight)
                ->setLevel($dest->getLevel() + 1)
                ->setParent($dest)
                ->setRoot($dest->getRoot());
        return true;
    }

    /**
     * deletes node and it's descendants
     * @todo Delete more efficiently. Wrap in transaction if needed.
     */
    public function delete(ANestedNode $node)
    {
        if (true === $node->isRoot())
            return false;
        $q = $this->createQueryBuilder('n')
                ->set('n._parent', 'NULL')
                ->andWhere("n._leftnode >= :leftNodeValue")
                ->andWhere("n._rightnode <= :rightNodeValue")
                ->andWhere("n._root = :root")
                ->setParameters(array(
                    "leftNodeValue" => $node->getLeftnode(),
                    "rightNodeValue" => $node->getRightnode(),
                    "root" => $node->getRoot()
                ))
                ->update()
                ->getQuery()
                ->execute();
        $rightNode = $node->getRightnode();
        $weight = $node->getWeight();
        $q = $this->createQueryBuilder('n')
                ->delete()
                ->andWhere("n._leftnode >= :leftNodeValue")
                ->andWhere("n._rightnode <= :rightNodeValue")
                ->andWhere("n._root = :root")
                ->setParameters(array(
                    "leftNodeValue" => $node->getLeftnode(),
                    "rightNodeValue" => $node->getRightnode(),
                    "root" => $node->getRoot()
                ))
                ->getQuery()
                ->execute();
        $first = $rightNode + 1;
        $delta = - $weight;
        $this->shiftRLValues($node, $first, $delta);
        return true;
    }

    private function shiftRlValues(ANestedNode $node, $first, $delta)
    {
        $q = $this->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode >= :leftnode')
                ->setParameters(array(
                    'delta' => $delta,
                    'root' => $node->getRoot(),
                    'leftnode' => $first))
                ->update()
                ->getQuery()
                ->execute();

        $q = $this->createQueryBuilder('n')
                ->set('n._rightnode', 'n._rightnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._rightnode >= :rightnode')
                ->setParameters(array(
                    'delta' => $delta,
                    'root' => $node->getRoot(),
                    'rightnode' => $first))
                ->update()
                ->getQuery()
                ->execute();
    }

    private function _detachFromTree(ANestedNode $node)
    {
        if ($node->isRoot())
            return $node;
        $currentRoot = $node->getRoot();
        $currentLeft = $node->getLeftnode();
        $currentRight = $node->getRightnode();
        $q = $this->createQueryBuilder('n')
                        ->set('n._leftnode', 'n._leftnode - :delta')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._leftnode > :left')
                        ->setParameters(array(
                            'delta' => $node->getWeight(),
                            'root' => $currentRoot,
                            'left' => $currentLeft
                        ))
                        ->update()->getQuery()->execute();
        $q = $this->createQueryBuilder('n')
                        ->set('n._rightnode', 'n._rightnode - :delta')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._rightnode > :right')
                        ->setParameters(array(
                            'delta' => $node->getWeight(),
                            'root' => $currentRoot,
                            'right' => $currentRight
                        ))
                        ->update()->getQuery()->execute();
        return $this;
    }

}
