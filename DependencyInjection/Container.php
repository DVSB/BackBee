<?php

namespace BackBuilder\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
	Symfony\Component\DependencyInjection\ContainerInterface,
	Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Extended Symfony Dependency injection component
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Container extends ContainerBuilder
{
	/**
	 * Change current method default behavior: if we try to get a synthetic service it will return
	 * null instead of throwing an exception;
	 * 
	 * @see Symfony\Component\DependencyInjection\ContainerBuilder::get()
	 */
	public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
	{
		$service = null;
		try {
			$service = parent::get($id, $invalidBehavior);
		} catch (RuntimeException $e) {
			$definition = $this->getDefinition($id);
			if (false === $definition->isSynthetic()) {
				throw $e;
			}
		}

		return $service;
	}
}
