<?php

namespace BackBuilder\Renderer\Adapter;

use Twig_Loader_Filesystem;

/**
 * Extends twig default filesystem loader to override some behaviors
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class TwigLoaderFilesystem extends Twig_Loader_Filesystem
{
	/**
     * Do same stuff than Twig_Loader_Filesystem::exists() plus check if the file is
     * readable
     * 
     * @see  Twig_Loader_Filesystem::exists()
     */
    public function exists($name)
    {
    	$exists = parent::exists($name);
    	$readable = false;
    	if (true === $exists) {
    		$readable = is_readable($this->cache[$name]);
    	}

    	return $readable;
    }
}
