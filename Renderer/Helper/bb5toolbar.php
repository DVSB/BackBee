<?php

namespace BackBuilder\Renderer\Helper;

class bb5toolbar extends AHelper
{
    /**
     * Possibilité d'ajouter des fichiers javascript dans l'entête de bb5toolbar
     * cf : _toolbars.bb5.phtml
     */
    public function __invoke($params = null)
    {
        $token = $this->_renderer->getApplication()->getBBUserToken();
        $alohapluginstable = $this->_renderer->getApplication()->getConfig()->getSection('alohapluginstable');
        
        $tokenArr = array('token' => $token);
        $params = ( !is_null($params) && is_array($params) ) ? array_merge($tokenArr, $params) : $tokenArr;
        $params = array_merge(array('aloha_extraplugins' => ((NULL !== $alohapluginstable) && (isset($alohapluginstable['extraplugins'])) && (NULL !== $alohapluginstable['extraplugins'])) ? $alohapluginstable['extraplugins'] : '' ), $params);
        
        return $this->_renderer->partial('bb5/_toolbars.bb5.phtml',$params);
    }

}