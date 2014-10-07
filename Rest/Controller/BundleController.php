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

namespace BackBuilder\Rest\Controller;

use BackBuilder\Bundle\BundleInterface;
use BackBuilder\Rest\Controller\ARestController;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * REST API for application bundles
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BundleController extends ARestController
{

    /**
     * Returns a collection of declared bundles
     */
    public function getCollectionAction()
    {
        $bundles = array();
        foreach ($this->getApplication()->getBundles() as $bundle) {
            if ($this->isGranted('EDIT', $bundle) || ($bundle->isEnabled() && $this->isGranted('VIEW', $bundle))) {
                $bundles[] = $bundle;
            }
        }

        return $this->createResponse(json_encode($bundles));
    }

    /**
     * Returns the bundle with id $id if it exists, else a 404 response will be generated
     *
     * @param  string $id the id of the bundle we are looking for
     */
    public function getAction($id)
    {
        $bundle = $this->getBundleById($id);

        try {
            $this->granted('EDIT', $bundle);
        } catch (\Exception $e) {
            if ($bundle->isEnabled()) {
                $this->granted('VIEW', $bundle);
            } else {
                throw $e;
            }
        }

        return $this->createResponse(json_encode($bundle));
    }

    /**
     * Patch the bundle
     *
     * @param  string $id the id of the bundle we are looking for
     */
    public function patchAction($id)
    {
        $bundle = $this->getBundleById($id);
        $this->granted('EDIT', $bundle);

        $do_save = false;
        $bundle_config = $bundle->getConfig()->getSection('bundle');
        foreach ($this->getApplication()->getRequest()->request->all() as $key => $value) {
            if ('enable' === $key || 'config_per_site' === $key) {
                $bundle_config[$key] = (boolean) $value;
                $do_save = true;
            } elseif ('category' === $key) {
                $bundle_config[$key] = (array) $value;
            }
        }

        if (true === $do_save) {
            $bundle->getConfig()->setSection('bundle', $bundle_config);
            $this->getApplication()->getContainer()->get('config.persistor')->persist(
                $bundle->getConfig(),
                true === array_key_exists('config_per_site', $bundle_config)
                    ? $bundle_config['config_per_site']
                    : false
            );
        }

        return $this->createResponse('', 204);
    }

    /**
     * Returns a bundle by id
     *
     * @param  string $id
     *
     * @throws NotFoundHttpException is raise if no bundle was found with provided id
     *
     * @return BundleInterface
     */
    private function getBundleById($id)
    {
        if (null === $bundle = $this->getApplication()->getBundle($id)) {
            throw new NotFoundHttpException("No bundle exists with id `$id`");
        }

        return $bundle;
    }

    /**
     * @see BackBuilder\Rest\Controller\ARestController::granted
     */
    protected function granted($attributes, $object = null)
    {
        try {
            parent::granted($attributes, $object);
        } catch (AccessDeniedHttpException $e) {
            throw new AccessDeniedHttpException(
                'Acces denied: no "'
                . (is_array($attributes) ? implode(', ', $attributes) : $attributes)
                . '" rights for bundle ' . get_class($object) . '.'
            );
        }

        return true;
    }
}
