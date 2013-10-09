<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBuilder\Security\Authorization\Voter;

use BackBuilder\Security\Authorization\Adaptator\IRoleReaderAdaptator;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class BBRoleVoter implements VoterInterface
{
    private $_prefix;
    /**
     * @var \BackBuilder\Security\Authentication\Adaptator\IRoleReaderAdaptator 
     */
    private $_adaptator;

    /**
     * Constructor.
     *
     * @param string $prefix The role prefix
     */
    public function __construct(IRoleReaderAdaptator $adaptator, $prefix = 'BB_')
    {
        $this->_prefix = $prefix;
        $this->_adaptator = $adaptator;
        
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
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            foreach ($roles as $role) {
                if ($attribute === $role->getRole()) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        return $result;
    }

    protected function extractRoles(TokenInterface $token)
    {
        return $this->_adaptator->extractRoles($token);
    }
}
