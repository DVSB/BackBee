<?php
namespace BackBuilder\Theme;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp system
 * @author      n.dufreche
 */
interface IThemeEntity {
    /**
     * object constructor
     *
     * @param array $values
     */
    public function __construct(array $values = null);

    /**
     * transform the current object in array
     *
     * @return array
     */
    public function toArray();
}