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
    public static $counter = 0;
    public static $updated_node = 0;
    private function _hasValidHierarchicalDatas($node)
    {
        if ($node->getLeftnode() >= $node->getRightnode())
            return false;
        if ($node->getLeftnode() <= $node->getRoot()->getLeftnode())
            return false;
        if ($node->getRightnode() >= $node->getRoot()->getRightnode())
            return false;
        if ($node->getLevel() <= $node->getRoot()->getLevel())
            return false;
        if (NULL === $node->getParent())
            return true;
        if ($node->getLeftnode() <= $node->getParent()->getLeftnode())
            return false;
        if ($node->getRightnode() >= $node->getParent()->getRightnode())
            return false;
        if ($node->getLevel() <= $node->getParent()->getLevel())
            return false;
        return true;
    }

    public function updateHierarchicalDatas(ANestedNode $node, $leftnode = 1, $level = 0)
    {
        $node->setLeftnode($leftnode)
                ->setLevel($level);
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
        self::$updated_node++;

        if (self::$counter === 0) {
            \BackBuilder\Util\Buffer::dump(
                '      memory usage during update hierarchical data:' 
                . \BackBuilder\Importer\Importer::convertMemorySize(memory_get_usage()) 
                . ' (updated node count: ' . self::$updated_node . ")\n"
            );
            self::$counter++;
        } elseif (self::$counter === 500) {
            self::$counter = 0;
        } else {
            self::$counter++;
        }

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

    private function isLastChildOf(ANestedNode $node, ANestedNode $root)
    {
        return ($node->getRightnode() + 1 == $root->getRightnode());
    }

    /**
     * Insérer un noeud à un certain niveau
     * @param type ANestedNode $node
     * @param type ANestedNode $dest
     * @param type Int $delta décalage
     */
    private function _insertSubtreeAt(ANestedNode $node, ANestedNode $dest, $delta, $levelDiff)
    {
        $q = $this->createQueryBuilder("n")
                        ->set("n._root", ":nwRoot")
                        ->set("n._leftnode", "n._leftnode + :delta")
                        ->set("n._rightnode", "n._rightnode + :delta")
                        ->set("n._level", "n._level + :levelDiff")
                        ->where("n._root = :subtreeroot")
                        ->setParameters(array(
                            "nwRoot" => $dest->getRoot(),
                            "delta" => $delta,
                            "levelDiff" => $levelDiff,
                            "subtreeroot" => $node
                        ))
                        ->update()->getQuery()->execute();
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

    /**
     * Déplace le noeud et ses enfants à la destination $destLeft et met à jour le reste de l'arbre
     *
     * @param int     $destLeft     Noeud gauche de la destination
     * @param int     $levelDiff    Différence de niveau entre les deux noeuds
     * @param int     $parent       Futur parent du noeud à déplacer
     * @ don't use
     */
    private function updateNode($destLeft, $levelDiff, $parent)
    {
        $left = $this->getLeftnode();
        $right = $this->getRightnode();
        $rootId = $this->getRootValue();
        $treeSize = $right - $left + 1;
        $this->createQuery()->startTrans();

        // Crée de la place dans la nouvelle branche
        $this->shiftRLValues($destLeft, $treeSize);
        if ($left >= $destLeft) { // Si la source a été déplacée, on met à jour les valeurs
            $left += $treeSize;
            $right += $treeSize;
        }
        // On met à jour les descendants
        $this->getTree()
                ->getBaseQuery()
                ->update()
                ->set($this->getE('level'), $this->getE('level') . ' + :levelDiffValue')
                ->where($this->getE('leftNode') . " > :leftNodeValue AND " . $this->getE('rightNode') . " < :rightNodeValue")
                ->execute(array(
                    "levelDiffValue" => $levelDiff,
                    "leftNodeValue" => $left,
                    "rightNodeValue" => $right
        ));

        // Maintenant que l'espace est libéré, on déplace l'arbre
        $this->shiftRLRange($left, $right, $destLeft - $left);

        // Et on corrige les valeurs en ramenant l'arbre
        $this->shiftRLValues($right + 1, -$treeSize);

        $this->initialize();
        $this->setParentValue($parent->_ID);
        $this->update();
        $this->createQuery()->completeTrans();
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

    /**
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param type $first
     * @param type $last
     * @param type $delta
     * Décaler la position des noeuds compris entre [first et last] d'un pas égal à delta
     */
    private function shiftRlRange(ANestedNode $node, $first, $last, $delta)
    {
        $q = $this->createQueryBuilder('n')
                        ->set('n._leftnode', 'n._leftnode + :delta')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._leftnode >= :first')
                        ->andWhere('n._leftnode <= :last')
                        ->setParameters(array(
                            'delta' => $delta,
                            'root' => $node->getRoot(),
                            'first' => $first,
                            'last' => $last
                        ))
                        ->update()->getQuery()->execute();

        $q = $this->createQueryBuilder('n')
                        ->set('n._rightnode', 'n._rightnode + :delta')
                        ->andWhere('n._root = :root')
                        ->andWhere('n._rightnode >= :first')
                        ->andWhere('n._rightnode <= :last')
                        ->setParameters(array(
                            'delta' => $delta,
                            'root' => $node->getRoot(),
                            'first' => $first,
                            'last' => $last
                        ))
                        ->update()->getQuery()->execute();
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

    /**
     * 
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param \BackBuilder\NestedNode\ANestedNode $dest
     * @return \BackBuilder\NestedNode\Repository\NestedNodeRepository
     * don't use
     */
    private function _insertIntoTree(ANestedNode $node, ANestedNode $dest)
    {
        if (!$node->isRoot())
            $this->_detachFromTree($node);
        $root = $dest->getRoot();
        if ($node == $root)
            return $this;
        $q = $this->createQueryBuilder('n')
                ->set('n._root', ':root')
                ->set('n._leftnode', 'n._leftnode + :delta')
                ->set('n._rightnode', 'n.rightnode + :delta')
                ->set('n._level', 'n._level + 1')
                ->andWhere('n._root = :croot')
                ->setParameters(array(
                    'root' => $root,
                    'delta' => $root->getRightnode(),
                    'croot' => $node
                ))
                ->update();
        $node->setParent($root);
        $root->setRightNode($node->getRightnode() + 1);
        return $this;
    }

}
