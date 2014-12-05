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

use BackBuilder\Services\Exception\ServicesException;
use BackBuilder\Site\Layout as SiteLayout;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
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
        if (NULL === $this->bbapp) {
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        }

        $site = $this->bbapp->getSite();
        if (NULL === $siteId) {
            $site = $this->bbapp->getEntityManager()->getRepository('\BackBuilder\Site\Site')->find($siteId);
        }

        if (NULL === $site) {
            throw new ServicesException("Current BackBuilder Application has to be start with a valid site", ServicesException::UNDEFINED_SITE);
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
        if (NULL === $this->bbapp) {
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        }

        $response = array();
        $layouts = $this->bbapp->getEntityManager()->getRepository('\BackBuilder\Site\Layout')->getModels();
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
        if (NULL === $this->bbapp) {
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        }

        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBuilder\Site\Layout', $data['uid']);
        if (NULL === $layout) {
            $layout = new SiteLayout();
        }

        $layout->setLabel($data['templateTitle']);
        $std = new \stdClass();
        $std->templateLayouts = $data['templateLayouts'];
        $layout->setDataObject($std);
        $currentSiteUid = (isset($data['site']["uid"])) ? $data['site']["uid"] : ($this->bbapp->getContainer()->has('site') ? $this->bbapp->getContainer()->get('site')->getUid() : null);
        $layout->setSite($em->find('\BackBuilder\Site\Site', $currentSiteUid));
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
        if (NULL === $this->bbapp) {
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        }

        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBuilder\Site\Layout', $uid);

        if (NULL === $layout) {
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
        if (NULL === $this->bbapp) {
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        }

        try {
            $em = $this->bbapp->getEntityManager();
            $layout = $em->find('\BackBuilder\Site\Layout', $uid);

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
