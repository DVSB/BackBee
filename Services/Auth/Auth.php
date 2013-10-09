<?php

namespace BackBuilder\Services\Auth;

use BackBuilder\BBApplication;
use BackBuilder\Exception\BBException;

class Auth
{
    private $secret_key;
    private $validity_time;
    private $public_token;
    private $bbapp;
    private $request;


    public function __construct(BBApplication $bbapp = null)
    {
         try {
            if (NULL === $bbapp)
                throw new BBException("You must intanced Auth class with BBApplication object");
            else
                $this->bbapp = $bbapp;
        }catch(BBException $e){
            print $e->getMessage();
        }
        
        $authConfig              = $this->bbapp->getConfig()->getAuthConfig();
        $this->request          = $this->bbapp->getRequest();
        $this->secret_key       = $authConfig['secret'];
        $this->validity_time    = $authConfig['timevalidate'];
        
        $this->generatePublicToken();
    }
    
    public function getToken($id_user)
    {
        return  $this->initAuth($id_user);
    }
    
    public function generatePublicToken()
    {
        $publicToken = $this->secret_key."http://".$this->request->server->get("SERVER_NAME").
                $this->request->server->get('HTTP_USER_AGENT');
        
        $this->public_token = $publicToken;
    }
    
    public function initInformation($user_id)
    {
        $this->bbapp->getSession()->set("session_informations", time()."-".$user_id);
    }
    
    public function encodeToken()
    {
        return hash('sha256', $this->public_token.$this->bbapp->getSession()->get("session_informations"));
    }
    
    public function initAuth($id_user)
    {
        $this->generatePublicToken();
        $this->initInformation($id_user);
        return $this->encodeToken();
        
        
    }
    
    public function isAuth($token)
    {
        //var_dump($token); var_dump($this->secret_key); die('token');
        if(strcmp($token, $this->encodeToken()) === 0)
        {
            // S'ils sont identiques on peut récupérer les informations
            //echo "signature ok<br>\n";
            list($date, $user) = explode('-', $this->bbapp->getSession()->get("session_informations"));

            // On vérifie que la session n'est pas expirée
            if($date + $this->validity_time > time() AND $date <= time())
            {
                // On peut aussi vérifier que l'url en referer est cohérente avec l'action entreprise
                // Par exemple que l'action suppression a bien été précédé de l'action de confirmation
                //echo "session en cour de validité<br>";
                //echo "user id:".$user."<br>\n";
                return true;
            }
            else
            {
                //echo "wrong timing<br>";
                //exit;
                return false;
            }
        }
        else
        {
            //echo "token check failed<br>";
            //exit;
            return false;
        }
    }
}
