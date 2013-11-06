<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * RPC services for User management
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Services\Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptistat@lp-digital.fr>
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
