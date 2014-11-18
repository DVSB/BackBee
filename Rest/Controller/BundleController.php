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
use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Controller\ARestController;
use BackBuilder\Rest\Patcher\EntityPatcher;
use BackBuilder\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBuilder\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBuilder\Rest\Patcher\OperationSyntaxValidator;
use BackBuilder\Rest\Patcher\RightManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Rest\RequestParam(name="0", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="Request must contain at least one operation")
     * })
     *
     * @param  string $id the id of the bundle we are looking for
     */
    public function patchAction($id)
    {
        $bundle = $this->getBundleById($id);
        $this->granted('EDIT', $bundle);
        $operations = $this->getRequest()->request->get('operations');

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: ' . $e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));
        $entity_patcher->getRightManager()->addAuthorizationMapping($bundle, array(
            'category'        => array('replace'),
            'config_per_site' => array('replace'),
            'enable'          => array('replace')
        ));

        try {
            $entity_patcher->patch($bundle, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new AccessDeniedHttpException('Invalid patch operation: ' . $e->getMessage());
        }

        $this->getApplication()->getContainer()->get('config.persistor')->persist(
            $bundle->getConfig(),
            null !== $bundle->getConfig()->getProperty('config_per_site')
                ? $bundle->getConfig()->getProperty('config_per_site')
                : false
        );

        return $this->createResponse('', 204);
    }

    /**
     * This method is the front controller of every bundles exposed actions
     *
     * @param  string $bundle_name     name of bundle we want to reach its exposed actions
     * @param  string $controller_name controller name
     * @param  string $action_name     name of exposed action we want to reach
     * @param  string  $parameters     optionnal, action's parameters
     */
    public function accessBundleExposedRoutesAction($bundle_name, $controller_name, $action_name, $parameters)
    {
        $bundle = $this->getBundleById($bundle_name);
        if (null === $callback = $bundle->getExposedActionCallback($controller_name, $action_name)) {
            throw new NotFoundHttpException('Not found');
        }

        if (false === empty($parameters)) {
            $parameters = array_filter(explode('/', $parameters));
        }

        $response = call_user_func_array($callback, $parameters);

        return is_object($response) && $response instanceof Response
            ? $response
            : $this->createResponse($response)
        ;
    }

    /**
     * @see BackBuilder\Rest\Controller\ARestController::granted
     */
    protected function granted($attributes, $object = null, $message = 'Access denied')
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
}
