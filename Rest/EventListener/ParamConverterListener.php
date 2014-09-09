<?php
namespace BackBuilder\Rest\EventListener;

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

use BackBuilder\Event\Listener\APathEnabledListener;
use BackBuilder\Rest\Exception\ValidationException;

use Metadata\MetadataFactory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator;

/**
 * Pagination listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ParamConverterListener extends APathEnabledListener
{
    /**
     * @var Metadata\MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var Symfony\Component\Validator\Validator
     */
    private $validator;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(MetadataFactory $metadataFactory, Validator $validator)
    {
        $this->metadataFactory = $metadataFactory;
        $this->validator = $validator;
    }

    /**
     * Controller
     *
     * @param FilterControllerEvent $event The event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $this->request = $event->getRequest();
        if (false === $this->isEnabled()) {
            return;
        }

        $controller = $event->getController();

        $metadata = $this->getControllerActionMetadata($controller);
        if (null === $metadata || 0 === count($metadata->param_converter_bag)) {
            // no annotations defined for this controller
            return;
        }

        foreach ($metadata->param_converter_bag as $param_converter) {
            $class = $param_converter->class;
            $bag = $param_converter->id_source;
            $unique_identifier = $request->$bag->get($param_converter->id_name, null);
            $entity = null;
            try {
                if (null === $unique_identifier) {
                    throw new \InvalidArgumentException(
                        'Unable to find identifier with provided attribute: ' . $param_converter->id_name
                    );
                }
            } catch(\InvalidArgumentException $e) {
                if (true === $param_converter->required) {
                    throw $e;
                }
            }

            if (null !== $unique_identifier) {
                $entity = $event->getKernel()->getApplication()->getEntityManager()->find($class, $unique_identifier);
                if (null === $entity) {
                    throw new NotFoundHttpException("No `$class` exists with uid `$unique_identifier`.");
                }
            }

            $request->attributes->set($param_converter->name, $entity);
        }
    }

    /**
     *
     * @param mixed $controller
     * @return \BackBuilder\Rest\Mapping\ActionMetadata
     */
    protected function getControllerActionMetadata($controller)
    {
        $controllerClass = get_class($controller[0]);

        $metadata = $this->metadataFactory->getMetadataForClass($controllerClass);

        $controllerMetadata = $metadata->getOutsideClassMetadata();

        $action_metadatas = null;
        if(array_key_exists($controller[1], $controllerMetadata->methodMetadata)) {
            $action_metadatas = $controllerMetadata->methodMetadata[$controller[1]];
        }

        return $action_metadatas;
    }
}
