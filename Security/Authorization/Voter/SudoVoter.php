<?php
namespace BackBuilder\Security\Authorization\Voter;

use BackBuilder\BBApplication;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class SudoVoter implements VoterInterface
{
    private $_application;
    private $_sudoers;
    
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_sudoers = $this->_application->getConfig()->getSecurityConfig('sudoers');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return $attribute === 'sudo';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === 'BackBuilder\Security\Token\BBUserToken';
    }

    /**
     * {@inheritdoc}
     */
    function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            } 
            if (
                array_key_exists($token->getUsername(), $this->_sudoers) &&
                $token->getUser()->getId() === $this->_sudoers[$token->getUsername()]
            ) {
                $result = VoterInterface::ACCESS_GRANTED;
            }
            break;
        }

        return $result;
    }
}