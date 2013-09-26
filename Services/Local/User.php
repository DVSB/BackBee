<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal;

/**
 * Description of User
 *
 * @copyright   Lp system
 * @author      m.baptista
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
            if (false === $securityContext->isGranted('VIEW', $this->bbapp->getsite())) {
                $response = new \Symfony\Component\HttpFoundation\Response();
                $response->setStatusCode(403);
                $response->send();
                die();
            }

            return json_decode($token->getUser()->serialize());
        }

        return NULL;
    }

    /**
     * @exposed(secured=false)
     */
    public function logoff()
    {
        if (NULL !== $this->bbapp->getEventDispatcher()) {
            $event = new \Symfony\Component\HttpKernel\Event\GetResponseEvent($this->bbapp->getController(), $this->bbapp->getController()->getRequest(), 1);
            $this->bbapp->getEventDispatcher()->dispatch('frontcontroller.request.logout', $event);

            if ($event->hasResponse())
                $event->getResponse()->send();
        }
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
