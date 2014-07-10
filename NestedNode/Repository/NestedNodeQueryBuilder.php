<?php

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\ANestedNode;
use Doctrine\ORM\QueryBuilder;

class NestedNodeQueryBuilder extends QueryBuilder
{

    /**
     * The root alias of this query
     * @var string
     */
    private $_root_alias;

    /**
     * Add query part to exclude $node from selection
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsNot(ANestedNode $node, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._uid != :uid' . $suffix)
                        ->setParameter('uid' . $suffix, $node->getUid());
    }

    /**
     * Add query part to select a specific tree (by its root)
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRootIs(ANestedNode $node, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._root = :root' . $suffix)
                        ->setParameter('root' . $suffix, $node);
    }

    /**
     * Add query part to select a specific subbranch of tree
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andParentIs(ANestedNode $node, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._parent = :parent' . $suffix)
                        ->setParameter('parent' . $suffix, $node);
    }

    /**
     * Add query part to select nodes by level
     * @param int $level
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelEquals($level, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._level = :level' . $suffix)
                        ->setParameter('level' . $suffix, $level);
    }

    /**
     * Add query part to select nodes having level lower than or equal to $level
     * @param int $level        the level to test
     * @param boolean $strict   if TRUE, having strictly level lower than $level
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelIsLowerThan($level, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._level <= :level' . $suffix)
                        ->setParameter('level' . $suffix, $level - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having level upper than or equal to $level
     * @param int $level        the level to test
     * @param boolean $strict   if TRUE, having strictly level upper than $level
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelIsUpperThan($level, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._level >= :level' . $suffix)
                        ->setParameter('level' . $suffix, $level + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select node with leftnode equals to $leftnode
     * @param int $leftnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeEquals($leftnode, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._leftnode = :leftnode' . $suffix)
                        ->setParameter('leftnode' . $suffix, $leftnode);
    }

    /**
     * Add query part to select nodes having leftnode lower than or equal to $leftnode
     * @param int $leftnode
     * @param boolean $strict   If TRUE, having strictly leftnode lower than $leftnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeIsLowerThan($leftnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._leftnode <= :leftnode' . $suffix)
                        ->setParameter('leftnode' . $suffix, $leftnode - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having leftnode upper than or equal to $leftnode
     * @param int $leftnode
     * @param boolean $strict   If TRUE, having strictly leftnode upper than $leftnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeIsUpperThan($leftnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._leftnode >= :leftnode' . $suffix)
                        ->setParameter('leftnode' . $suffix, $leftnode + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select node with rightnode equals to $rightnode
     * @param int $rightnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeEquals($rightnode, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._rightnode = :rightnode' . $suffix)
                        ->setParameter('rightnode' . $suffix, $rightnode);
    }

    /**
     * Add query part to select nodes having rightnode lower than or equal to $rightnode
     * @param int $rightnode
     * @param boolean $strict   If TRUE, having strictly rightnode lower than $rightnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeIsLowerThan($rightnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._rightnode <= :rightnode' . $suffix)
                        ->setParameter('rightnode' . $suffix, $rightnode - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having rightnode upper than or equal to $rightnode
     * @param int $rightnode
     * @param boolean $strict   If TRUE, having strictly rightnode upper than $rightnode
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeIsUpperThan($rightnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._rightnode >= :rightnode' . $suffix)
                        ->setParameter('rightnode' . $suffix, $rightnode + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select siblings of $node
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param boolean $includeNode  if TRUE, include $node in result array
     * @param array $order          ordering spec
     * @param int $limit            max number of results
     * @param int $start            first result index
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsSiblingsOf(ANestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0, $alias = null)
    {
        list($alias, $suffix) = $this->_getAliasAndSuffix($alias);

        if (null === $order) {
            $order = array('_leftnode' => 'asc');
        }

        foreach ($order as $col => $sort) {
            $this->orderBy($alias . '.' . $col, $sort);
        }

        if (null !== $limit) {
            $this->setMaxResults($limit)
                    ->setFirstResult($start);
        }

        if (false === $includeNode) {
            $this->andIsNot($node, $alias);
        }

        if (true === $node->isRoot()) {
            return $this->andWhere($alias . '._uid = :uid' . $suffix)
                            ->setParameter('uid' . $suffix, $node->getUid());
        }

        return $this->andParentIs($node->getParent(), $alias);
    }

    /**
     * Add query part to select previous sibling of node
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsPreviousSiblingOf(ANestedNode $node, $alias = null)
    {
        return $this->andRootIs($node->getRoot(), $alias)
                        ->andRightnodeEquals($node->getLeftnode() - 1, $alias);
    }

    /**
     * Add query part to select next sibling of node
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsNextSiblingOf(ANestedNode $node, $alias = null)
    {
        return $this->andRootIs($node->getRoot(), $alias)
                        ->andLeftnodeEquals($node->getRightnode() + 1, $alias);
    }

    /**
     * Add query part to select ancestors of $node
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param boolean $strict   If TRUE, $node is excluded from the selection
     * @param int $at_level     Filter ancestors by their level
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsAncestorOf(ANestedNode $node, $strict = false, $at_level = null, $alias = null)
    {
        $this->andRootIs($node->getRoot(), $alias)
                ->andLeftnodeIsLowerThan($node->getLeftnode(), $strict, $alias)
                ->andRightnodeIsUpperThan($node->getRightnode(), $strict, $alias);

        if (null !== $at_level) {
            $this->andLevelEquals($at_level);
        }

        return $this->orderBy('n._rightnode', 'desc');
    }

    /**
     * Add query part to select descendants of $node
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param boolean $strict   If TRUE, $node is excluded from the selection
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsDescendantOf(ANestedNode $node, $strict = false, $alias = null)
    {
        return $this->andRootIs($node->getRoot(), $alias)
                        ->andLeftnodeIsUpperThan($node->getLeftnode(), $strict, $alias)
                        ->andRightnodeIsLowerThan($node->getRightnode(), $strict, $alias);
    }

    /**
     * Try to retreive the root alias for this builder
     * @return string
     * @throws \BackBuilder\Exception\BBException
     */
    private function _getRootAlias()
    {
        if (null === $this->_root_alias) {
            $aliases = $this->getRootAliases();
            if (0 === count($aliases)) {
                throw new \BackBuilder\Exception\BBException('Cnanot access to root alias');
            }

            $this->_root_alias = $aliases[0];
        }

        return $this->_root_alias;
    }

    /**
     * Compute suffix and alias used by query part
     * @param string $alias
     * @return array
     */
    private function _getAliasAndSuffix($alias = null)
    {
        $suffix = count($this->getParameters());
        $alias = true === empty($alias) ? $this->_getRootAlias() : $alias;

        return array($alias, $suffix);
    }

}
