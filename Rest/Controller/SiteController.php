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

namespace BackBuilder\Rest\Controller;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Validator\ConstraintViolationList,
    Symfony\Component\HttpFoundation\JsonResponse;

use BackBuilder\Rest\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use BackBuilder\Security\Token\UsernamePasswordToken;

use BackBuilder\Rest\Exception\ValidationException;

/**
 * Site Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SiteController extends ARestController 
{
   
    /**
     * Get site layouts
     * 
     * 
     */
    public function getLayoutsAction($uid, Request $request, ConstraintViolationList $violations = null) 
    {
        if(!$this->isGranted("ROLE_API_USER")) {
            throw new AccessDeniedHttpException('You have no permissions to access ' . $request->getPathInfo());
        }
                
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $em = $this->getEntityManager();

        $site = $em->getRepository('BackBuilder\Site\Site')->find($uid);
        
        if(!$site) {
            return $this->create404Response(sprintf('Site not found: %s', $uid));
        }
        
        if($this->isGranted('VIEW', $site)) {
            throw new AccessDeniedHttpException(sprintf('You are not authrozied to view site %s', $site->getLabel()));
        }
        
        // TODO
        $layouts = array();

        foreach($site->getLayouts() as $layout) {
            $layouts[] = array(
                'uid' => $layout->getUid(),
                'site' => array(
                    'uid' => $layout->getSite()->getUid()
                ),
                'label' => $layout->getLabel(),
                'path' => $layout->getPath(),
                'data' => json_decode($layout->getData(), true),
                'picpath' => $layout->getPicpath()
            );
        }
        
        return new Response($this->formatCollection($layouts), 200, array('Content-Type' => 'application/json'));
    }
    
}