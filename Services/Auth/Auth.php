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

namespace BackBuilder\Services\Auth;

use BackBuilder\BBApplication;
use BackBuilder\Exception\BBException;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Auth
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class Auth
{

    private $secret_key;
    private $validity_time;
    private $public_token;
    private $application;
    private $request;

    public function __construct(BBApplication $application = null)
    {
        try {
            if (NULL === $application)
                throw new BBException("You must intanced Auth class with BBApplication object");
            else
                $this->application = $application;
        } catch (BBException $e) {
            print $e->getMessage();
        }

        $authConfig = $this->application->getConfig()->getAuthConfig();
        $this->request = $this->application->getRequest();
        $this->secret_key = $authConfig['secret'];
        $this->validity_time = $authConfig['timevalidate'];

        $this->generatePublicToken();
    }

    /**
     * @codeCoverageIgnore
     * @param type $id_user
     * @return type
     */
    public function getToken($id_user)
    {
        return $this->initAuth($id_user);
    }

    public function generatePublicToken()
    {
        $publicToken = $this->secret_key . "http://" . $this->request->server->get("SERVER_NAME") .
                $this->request->server->get('HTTP_USER_AGENT');

        $this->public_token = $publicToken;
    }

    /**
     * @codeCoverageIgnore
     * @param type $user_id
     */
    public function initInformation($user_id)
    {
        $this->application->getSession()->set("session_informations", time() . "-" . $user_id);
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function encodeToken()
    {
        return hash('sha256', $this->public_token . $this->application->getSession()->get("session_informations"));
    }

    public function initAuth($id_user)
    {
        $this->generatePublicToken();
        $this->initInformation($id_user);
        return $this->encodeToken();
    }

    /**
     * [isAuth description]
     * @param  [type]  $token [description]
     * @return boolean        [description]
     *
     * @todo complete doc and remove french commentaries
     */
    public function isAuth($token)
    {
        if (strcmp($token, $this->encodeToken()) === 0) {
            // S'ils sont identiques on peut récupérer les informations
            //echo "signature ok<br>\n";
            list($date, $user) = explode('-', $this->application->getSession()->get("session_informations"));

            // On vérifie que la session n'est pas expirée
            if ($date + $this->validity_time > time() AND $date <= time()) {
                // On peut aussi vérifier que l'url en referer est cohérente avec l'action entreprise
                // Par exemple que l'action suppression a bien été précédé de l'action de confirmation
                //echo "session en cour de validité<br>";
                //echo "user id:".$user."<br>\n";
                return true;
            } else {
                //echo "wrong timing<br>";
                //exit;
                return false;
            }
        } else {
            //echo "token check failed<br>";
            //exit;
            return false;
        }
    }

}
