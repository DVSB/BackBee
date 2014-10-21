<?php

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

namespace BackBuilder\Rest\Controller;

use BackBuilder\ClassContent\AClassContent;
use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Controller\ARestController;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ClassContentController extends ARestController
{
    /**
     * Get classcontent
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getAction($type, $uid)
    {
        $classname = 'BackBuilder\ClassContent\\' . str_replace('/', NAMESPACE_SEPARATOR, $type);
        $content = $this->getApplication()->getEntityManager()->find($classname, $uid);

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$uid`");
        }

        $this->granted('VIEW', $content);

        if (null !== $draft = $this->getClassContentRevision($content)) {
            $content->setDraft($draft);
        }

        return $this->createResponse($content->toJson());
    }

    /**
     * delete a classcontent
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($type, $uid)
    {
        $content = $this->getClassContentByTypeAndUid($type, $uid);

        try {
            $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\AClassContent')
                ->deleteContent($content)
            ;
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createResponse('', 204);
    }

    /**
     *
     *
     * @param  string $type
     * @param  string $uid
     *
     * @return
     */
    private function getClassContentByTypeAndUid($type, $uid)
    {
        $classname = 'BackBuilder\ClassContent\\' . str_replace('/', NAMESPACE_SEPARATOR, $type);
        $content = $this->getApplication()->getEntityManager()->find($classname, $uid);

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$uid`");
        }

        return $content;
    }

    private function getClassContentRevision(AClassContent $content)
    {
        return $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')
            ->getDraft($content, $this->getApplication()->getBBUserToken())
        ;
    }
}
