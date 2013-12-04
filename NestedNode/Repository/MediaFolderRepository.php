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

namespace BackBuilder\NestedNode\Repository;

/**
 * Media folder repository
 * 
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class MediaFolderRepository extends NestedNodeRepository
{

    public function getRoot()
    {
        try {
            $q = $this->createQueryBuilder('mf')
                    ->andWhere('mf._parent is null')
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function preloadMediaType(\BackBuilder\BBApplication $bbApp)
    {
        $classnames = $bbApp->getAutoloader()->glob('Media' . DIRECTORY_SEPARATOR . '*');
        foreach ($classnames as $classname) {
            class_exists($classname);
        }
    }

    private function deleteMediaContent(\BackBuilder\BBApplication $bbapp, $media)
    {
        $em = $this->_em;
        if ($media) {
            $token = $bbapp->getBBUserToken();
            $content = $media->getContent();

            if ($content instanceof AClassContent) {
                foreach ($content->getData() as $element => $value) {
                    $subcontent = $content->$element;

                    if (!($subcontent instanceof AClassContent))
                        continue;

                    if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($subcontent, $token)) {
                        $draft->setContent(NULL);
                        $draft->setState(Revision::STATE_DELETED);
                    }

                    $subcontent->releaseDraft();
                    $em->remove($subcontent); //title - description - copyright - image
                    unset($content->$element);
                }

                $content->releaseDraft();
                $em->remove($content);
            }

            $media->setContent(NULL);
            $em->remove($media);
        }
    }

}