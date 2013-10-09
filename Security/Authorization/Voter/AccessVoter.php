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
class AccessVoter implements VoterInterface
{
    private $_application;
    private $_adaptator;
    private $_prefix;
    private $_class;
    
    /**
     * Constructor.
     *
     * @param IRoleReaderAdaptator $adaptator
     * @param string $prefix The role prefix
     */
    public function __construct(BBApplication $application, IRoleReaderAdaptator $adaptator, $class, $prefix = 'BB_')
    {
        $this->_adaptator = $adaptator;
        $this->_prefix = $prefix;
        $this->_class = $class;
        $this->_application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return 0 === strpos($attribute, $this->_prefix);
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
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (get_class($object) === $this->_class) {
            $result = $this->voteForSomething($token, $object, $attributes);
        } else {
            $result = $this->voteForAccess($token, $attributes);
        }

        return $result;
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

    private function voteForAccess(TokenInterface $token, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        
    }

    private function voteForSomething(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param TokenInterface $token
     * @return array
     */
    private function extractRoles(TokenInterface $token)
    {
        return $this->_adaptator->extractRoles($token);
    }
    
    private function getAccessRole()
    {
        $classPath = explode('\\', $this->_class);
        $config = $this->_application->getConfig()->getSecurityConfig();
        
        foreach ($array as $value) {
            
        }
        
    }
}