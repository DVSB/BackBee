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

namespace BackBuilder\Renderer\Helper;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
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
        $application = $this->_renderer->getApplication();

        $token = $application->getBBUserToken();

        $customParams = array('token' => $token, 'current_page' => $this->_renderer->getCurrentPage());

        if (null !== $params && true === is_array($params)) {
            $params = array_merge($customParams, $params);
        } else {
            $params = $customParams;
        }

        return $this->_renderer->partial('bb5/toolbar.phtml', $params);
    }
}
