<?php

namespace BackBuilder\Workflow\Repository;

use BackBuilder\Site\Layout;
use Doctrine\ORM\EntityRepository;

/**
 * Workflow state repository
 * @category    BackBuilder
 * @package     BackBuilder\Workflow\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class StateRepository extends EntityRepository
{

    /**
     * Returns an array of available workflow states for the provided layout
     * @param \BackBuilder\Site\Layout $layout
     * @return array
     */
    public function getWorkflowStatesForLayout(Layout $layout)
    {
        $states = array();
        foreach ($this->findBy(array('_layout' => null)) as $state) {
            $states[$state->getCode()] = $state;
        }

        foreach ($this->findBy(array('_layout' => $layout)) as $state) {
            $states[$state->getCode()] = $state;
        }

        ksort($states);

        return $states;
    }

}