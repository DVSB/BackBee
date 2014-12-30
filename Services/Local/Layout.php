<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Services\Local;

use BackBee\Services\Exception\ServicesException;
use BackBee\Site\Layout as SiteLayout;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class Layout extends AbstractServiceLocal
{
    /**
     * @exposed(secured=true)
     *
     */
    public function getLayoutsFromSite($siteId = null)
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        $site = $this->bbapp->getSite();
        if (null === $siteId) {
            $site = $this->bbapp->getEntityManager()->getRepository('\BackBee\Site\Site')->find($siteId);
        }

        if (null === $site) {
            throw new ServicesException("Current BackBee Application has to be start with a valid site", ServicesException::UNDEFINED_SITE);
        }

        $response = array();
        foreach ($site->getLayouts() as $layout) {
            $response[] = $layout->__toJson();
        }

        return $response;
    }

    /**
     * @exposed(secured=true)
     *
     */
    public function getModels()
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        $response = array();
        $layouts = $this->bbapp->getEntityManager()->getRepository('\BackBee\Site\Layout')->getModels();
        foreach ($layouts as $layout) {
            $response[] = $layout->__toJson();
        }

        return $response;
    }

    /**
     * @exposed(secured=true)
     *
     */
    public function putTemplate($data)
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBee\Site\Layout', $data['uid']);
        if (null === $layout) {
            $layout = new SiteLayout();
        }

        $layout->setLabel($data['templateTitle']);
        $std = new \stdClass();
        $std->templateLayouts = $data['templateLayouts'];
        $layout->setDataObject($std);
        $currentSiteUid = (isset($data['site']["uid"])) ? $data['site']["uid"] : ($this->bbapp->getContainer()->has('site') ? $this->bbapp->getContainer()->get('site')->getUid() : null);
        $layout->setSite($em->find('\BackBee\Site\Site', $currentSiteUid));
        $layout->setPicPath($data['picpath']);

        $em->persist($layout);
        $em->flush();

        return $layout->__toJson();
    }

    /**
     * @exposed(secured=true)
     *
     */
    public function getLayoutFromUid($uid)
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBee\Site\Layout', $uid);

        if (null === $layout) {
            throw new ServicesException(sptrinf('Unfound Layout with uid %s', $uid));
        }

        return $layout->__toJson();
    }

    /**
     * @exposed(secured=true)
     *
     */
    public function deleteLayout($uid)
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        try {
            $em = $this->bbapp->getEntityManager();
            $layout = $em->find('\BackBee\Site\Layout', $uid);

            $em->remove($layout);
            $em->flush();
        } catch (\PDOException $e) {
            if (23000 == $e->getCode()) {
                throw new ServicesException("One or more pages use this layout, you have to previously remove them");
            }
        } catch (\Exception $e) {
            //Nothing to do
        }

        return "layout : uid=".$uid." has been removed";
    }
}
