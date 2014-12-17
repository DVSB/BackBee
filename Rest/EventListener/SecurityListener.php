<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Rest\EventListener;

use BackBee\Security\Authorization\ExpressionLanguage;
use Metadata\MetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * SecurityListener handles security restrictions on controllers.
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>, k.golovin
 */
class SecurityListener
{
    private $securityContext;
    private $language;
    private $trustResolver;
    private $roleHierarchy;
    private $metadataFactory;

    public function __construct(SecurityContextInterface $securityContext, ExpressionLanguage $language, AuthenticationTrustResolverInterface $trustResolver, RoleHierarchyInterface $roleHierarchy)
    {
        $this->securityContext = $securityContext;
        $this->language = $language;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
    }

    public function setMetadataFactory(MetadataFactoryInterface $factory)
    {
        $this->metadataFactory = $factory;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $token = $this->securityContext->getToken();

        if (null === $token) {
            throw new AuthenticationCredentialsNotFoundException('The security context contains no authentication token. One possible reason may be that there is no firewall configured for this URL.');
        }

        $request = $event->getRequest();
        $controller = $event->getController();
        $metadata = $this->getControllerActionMetadata($controller);
        if (null === $metadata || 0 === count($metadata->security)) {
            return;
        }

        foreach ($metadata->security as $annotation) {
            if (!$this->language->evaluate($annotation->expression, $this->getVariables($request))) {
                throw new AccessDeniedHttpException(sprintf('Expression "%s" denied access.', $annotation->expression));
            }
        }
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(Request $request)
    {
        $token = $this->securityContext->getToken();

        if (null !== $this->roleHierarchy) {
            $roles = $this->roleHierarchy->getReachableRoles($token->getRoles());
        } else {
            $roles = $token->getRoles();
        }

        $variables = array(
            'token' => $token,
            'user' => $token->getUser(),
            'object' => $request,
            'request' => $request,
            'roles' => array_map(function ($role) { return $role->getRole(); }, $roles),
            'trust_resolver' => $this->trustResolver,
            'security_context' => $this->securityContext,
        );

        // controller variables should also be accessible
        return array_merge($request->attributes->all(), $variables);
    }

    /**
     *
     * @param  mixed                                    $controller
     * @return \BackBee\Rest\Mapping\ActionMetadata
     */
    protected function getControllerActionMetadata($controller)
    {
        $controllerClass = get_class($controller[0]);

        $metadata = $this->metadataFactory->getMetadataForClass($controllerClass);

        $controllerMetadata = $metadata->getOutsideClassMetadata();

        $action_metadatas = null;
        if (array_key_exists($controller[1], $controllerMetadata->methodMetadata)) {
            $action_metadatas = $controllerMetadata->methodMetadata[$controller[1]];
        }

        return $action_metadatas;
    }
}
