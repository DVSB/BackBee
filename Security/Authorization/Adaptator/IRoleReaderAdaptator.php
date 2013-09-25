<?php
namespace BackBuilder\Security\Authorization\Adaptator;

use BackBuilder\BBApplication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface IRoleReaderAdaptator
{
    /**
     * Object Constructor
     * 
     * @param \BackBuilder\BBApplication $application
     */
    public function __construct(BBApplication $application);
    
    /**
     * retrieve the users role thanks to the Token
     * 
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token;
     * @return Array of \BackBuilder\Security\Role\Role
     */
    public function extractRoles(TokenInterface $token);
}