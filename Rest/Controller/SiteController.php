<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Rest\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Site Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SiteController extends ARestController
{
    /**
     * Get site layouts.
     */
    public function getLayoutsAction($uid)
    {
        if (!$this->isGranted("ROLE_API_USER")) {
            throw new AccessDeniedHttpException("Your account's api access is disabled");
        }

        $site = $this->getEntityManager()->getRepository('BackBee\Site\Site')->find($uid);

        if (!$site) {
            return $this->create404Response(sprintf('Site not found: %s', $uid));
        }

        if (!$this->isGranted('VIEW', $site)) {
            throw new AccessDeniedHttpException(sprintf('You are not authorized to view site %s', $site->getLabel()));
        }

        $layouts = array();

        foreach ($site->getLayouts() as $layout) {
            if ($this->isGranted('VIEW', $layout)) {
                $layouts[] = [
                    'uid' => $layout->getUid(),
                    'site' => [
                        'uid' => $layout->getSite()->getUid(),
                    ],
                    'label' => $layout->getLabel(),
                    'path' => $layout->getPath(),
                    'data' => json_decode($layout->getData(), true),
                    'picpath' => $layout->getPicpath(),
                ];
            }
        }

        return new Response($this->formatCollection($layouts), 200, ['Content-Type' => 'application/json']);
    }
}
