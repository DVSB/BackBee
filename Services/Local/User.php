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

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * RPC services for User management
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class User extends AbstractServiceLocal
{

    /**
     * @exposed(secured=true)
     */
    public function getConfigLayout()
    {
        $lessService = new \BackBuilder\Services\Local\Less($this->bbapp);

        $result = new \stdClass();
        $result->gridColumns = $lessService->getGridColumns();
        $result->gridConstants = $lessService->getGridConstant();

        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getUser()
    {
        $securityContext = $this->bbapp->getSecurityContext();
        if (NULL !== $token = $securityContext->getToken()) {
            return json_decode($token->getUser()->serialize());
        }

        return NULL;
    }

    /**
     * @exposed(secured=false)
     */
    public function logoff()
    {
        $application = $this->getApplication();
        if (NULL !== $application->getEventDispatcher()) {
            $event = new GetResponseEvent($application->getController(), $application->getController()->getRequest(), 1);
            $application->getEventDispatcher()->dispatch('frontcontroller.request.logout', $event);
        }

        return NULL;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBUserPreferences()
    {
        $securityContext = $this->bbapp->getSecurityContext();
        $userPreferencesRepository = $this->bbapp->getEntityManager()->getRepository('BackBuilder\Site\UserPreferences');
        if (NULL !== $token = $securityContext->getToken()) {
            $userPreferences = $userPreferencesRepository->loadPreferences($token);
            $values = array('identity' => $userPreferences->getUid(), 'preferences' => $userPreferences->getPreferences());
            return $values;
        }
    }

    /**
     * @exposed(secured=true)
     */
    public function setBBUserPreferences($identity, $preferences)
    {
        $securityContext = $this->bbapp->getSecurityContext();
        $userPreferencesRepository = $this->bbapp->getEntityManager()->getRepository('BackBuilder\Site\UserPreferences');
        $token = $securityContext->getToken();
        if (NULL !== $token && $userPreferencesRepository->retrieveUserPreferencesUid($token) == $identity) {
            $userPreferencesRepository->setPreferences($token, $preferences);
            $this->bbapp->getEntityManager()->flush();
        }
    }

}
