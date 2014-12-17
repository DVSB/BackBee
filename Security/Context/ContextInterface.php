<?php

namespace BackBee\Security\Context;


/**
 * Description of ContextInterface
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface ContextInterface
{
    /**
     * Load the who depends this context.
     *
     * @param  array $config Security config section
     * @return array of security listeners
     */
    public function loadListeners($config);
}
