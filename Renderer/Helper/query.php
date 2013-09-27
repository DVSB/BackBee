<?php

namespace BackBuilder\Renderer\Helper;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Helper returning current query
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer\Helper
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class query extends AHelper
{

    /**
     * Return the current request parameter bag
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function __invoke()
    {
        if (null !== $this->_renderer
                && null !== $this->_renderer->getApplication()
                && null !== $this->_renderer->getApplication()->getController()
                && null !== $this->_renderer->getApplication()->getController()->getRequest()) {
            return $this->_renderer->getApplication()->getController()->getRequest()->query;
        }

        return new ParameterBag();
    }

}