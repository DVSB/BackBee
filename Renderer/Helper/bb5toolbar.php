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

namespace BackBee\Renderer\Helper;

/**
 * @category    BackBee
 * @package     BackBee\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
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
        $params = (!is_null($params) && is_array($params)) ? array_merge($tokenArr, $params) : $tokenArr;
        $params = array_merge(array('aloha_extraplugins' => ((null !== $alohapluginstable) && (isset($alohapluginstable['extraplugins'])) && (null !== $alohapluginstable['extraplugins'])) ? $alohapluginstable['extraplugins'] : ''), $params);

        return $this->_renderer->partial('bb5/_toolbars.bb5.phtml', $params);
    }
}
