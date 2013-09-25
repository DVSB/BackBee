<?php

namespace BackBuilder\Rewriting;

use BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent;

/**
 * Interface for the rewriting url generation
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rewriting
 * @copyright   Lp system
 * @author      c.rouillon
 */
interface IUrlGenerator
{
    public function getDescriminators();
    public function generate(Page $page, AClassContent $content = NULL, $exceptionOnMissingScheme = true);
}