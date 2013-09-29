<?php

namespace BackBuilder\Security\Authorization\Voter;

use BackBuilder\NestedNode\ANestedNode;
use Symfony\Component\Security\Core\Util\ClassUtils,
    Symfony\Component\Security\Acl\Voter\AclVoter,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This voter is used to check user's permissions on provided object
 *
 * @category    BackBuilder
 * @package     BackBuilder\Authorization\Voter
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class BBAclVoter extends AclVoter
{

    /**
     * Returns the vote for the given parameters.
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token A TokenInterface instance
     * @param object $object The object to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($object instanceof ANestedNode) {
            return $this->_voteForNestedNode($token, $object, $attributes);
        }

        return $this->_vote($token, $object, $attributes);
    }

    /**
     * Returns the vote for the cuurent object, if denied try the vote for the general object
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param object $object
     * @param array $attributes
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function _vote(TokenInterface $token, $object, array $attributes)
    {
        if (self::ACCESS_DENIED === $result = parent::vote($token, $object, $attributes)) {
            $classname = ClassUtils::getRealClass($object);
            $result = parent::vote($token, new $classname('*'), $attributes);
        }

        return $result;
    }

    /**
     * Returns the vote for nested node object, recursively till root
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param array $attributes
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function _voteForNestedNode(TokenInterface $token, ANestedNode $node, array $attributes)
    {
        if (self::ACCESS_DENIED === $result = $this->_vote($token, $node, $attributes)) {
            if (null !== $node->getParent()) {
                $result = $this->_voteForNestedNode($token, $node->getParent(), $attributes);
            }
        }

        return $result;
    }

}