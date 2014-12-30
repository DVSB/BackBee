<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Services\Local;

use BackBee\ClassContent\Exception\ClassContentException;
use BackBee\Services\Exception\ServicesException;

/**
 * RPC services for content revisions
 *
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Revision extends AbstractServiceLocal
{
    /**
     * Return current list of revisionned content for authenticated user
     * @exposed(secured=true)
     */
    public function getAllDrafts()
    {
        if (null === $token = $this->bbapp->getBBUserToken()) {
            throw new ServicesException('Authenticated user missing');
        }

        $this->_incRunningVariables();

        $result = array();
        $revisions = $this->em->getRepository('\BackBee\ClassContent\Revision')->getAllDrafts($token);

        foreach ($revisions as $revision) {
            $result[] = json_decode($revision->serialize());
        }

        return $result;
    }

    /**
     * Update current revisionned contents for authenticated user
     * @exposed(secured=true)
     */
    public function update()
    {
        if (null === $token = $this->bbapp->getBBUserToken()) {
            throw new ServicesException('Authenticated user missing', ServicesException::UNAUTHORIZED_USER);
        }

        $result = array();
        $revisions = $this->em->getRepository('\BackBee\ClassContent\Revision')->getAllDrafts($token);
        foreach ($revisions as $revision) {
            try {
                $revision->getContent()->setDraft($revision);
                $this->bbapp->getEventDispatcher()->triggerEvent('update', $revision->getContent());
                $this->em->flush();

                $result[] = array('reverted' => true,
                    'content' => json_decode($revision->getContent()->serialize()), );
            } catch (ClassContentException $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'content' => json_decode($revision->getContent()->serialize()), );
            } catch (\Exception $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(), );
            }
        }

        return $result;
    }

    /**
     * Commit revisionned content for authenticated user
     * @exposed(secured=true)
     */
    public function commit()
    {
        if (null === $token = $this->bbapp->getBBUserToken()) {
            throw new ServicesException('Authenticated user missing', ServicesException::UNAUTHORIZED_USER);
        }

        $this->_incRunningVariables();

        $result = array();
        $revisions = $this->em->getRepository('\BackBee\ClassContent\Revision')->getAllDrafts($token);
        foreach ($revisions as $revision) {
            try {
                $this->bbapp->getEventDispatcher()->triggerEvent('commit', $revision->getContent());

                if (null !== $revision->getContent()) {
                    $result[] = array('commited' => true,
                        'content' => json_decode($revision->getContent()->serialize()), );
                }
            } catch (ClassContentException $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'content' => json_decode($revision->getContent()->serialize()), );
            } catch (\Exception $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(), );
            }
        }

        $this->em->flush();

        return $result;
    }

    /**
     * Revert revisionned content for authenticated user
     * @exposed(secured=true)
     */
    public function revert()
    {
        if (null === $token = $this->bbapp->getBBUserToken()) {
            throw new ServicesException('Authenticated user missing', ServicesException::UNAUTHORIZED_USER);
        }

        $this->_incRunningVariables();

        $result = array();
        $revisions = $this->em->getRepository('BackBee\ClassContent\Revision')->getAllDrafts($token);
        foreach ($revisions as $revision) {
            try {
                $revision = $this->em->getRepository('BackBee\ClassContent\Revision')->revert($revision);
                $this->em->flush();

                $result[] = array('reverted' => true,
                    'content' => json_decode($revision->getContent() ? $revision->getContent()->serialize() : ''), );
            } catch (ClassContentException $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'content' => json_decode($revision->getContent() ? $revision->getContent()->serialize() : ''), );
            } catch (\Exception $e) {
                $result[] = array('error' => $e->getCode(),
                    'message' => $e->getMessage(), );
                break;
            }
        }

        $this->em->getRepository('\BackBee\NestedNode\Page')->removeEmptyPages($this->bbapp->getSite());
        $this->em > flush();

        return $result;
    }

    /**
     * Increase memory limit and disable time limit in order
     * to treate huge commit or revert
     */
    private function _incRunningVariables()
    {
        // To do popen() ?
        set_time_limit(0);
        ini_set('memory_limit', '512M');
    }
}
