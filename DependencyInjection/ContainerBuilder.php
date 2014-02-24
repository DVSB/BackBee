<?php

namespace BackBuilder\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder,
	Symfony\Component\DependencyInjection\ContainerInterface,
	Symfony\Component\DependencyInjection\Exception\RuntimeException;


class ContainerBuilder extends SfContainerBuilder
{
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
