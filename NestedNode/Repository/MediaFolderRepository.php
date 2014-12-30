<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\NestedNode\Repository;

/**
 * Media folder repository
 *
 * @category    BackBee
 * @package     BackBee/NestedNode
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
            return;
        } catch (Exception $e) {
            return;
        }
    }

    private function preloadMediaType(\BackBee\BBApplication $bbApp)
    {
        $classnames = $bbApp->getAutoloader()->glob('Media'.DIRECTORY_SEPARATOR.'*');
        foreach ($classnames as $classname) {
            class_exists($classname);
        }
    }

    private function deleteMediaContent(\BackBee\BBApplication $bbapp, $media)
    {
        $em = $this->_em;
        if ($media) {
            $token = $bbapp->getBBUserToken();
            $content = $media->getContent();

            if ($content instanceof AClassContent) {
                foreach ($content->getData() as $element => $value) {
                    $subcontent = $content->$element;

                    if (!($subcontent instanceof AClassContent)) {
                        continue;
                    }

                    if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($subcontent, $token)) {
                        $draft->setContent(null);
                        $draft->setState(Revision::STATE_DELETED);
                    }

                    $subcontent->releaseDraft();
                    $em->remove($subcontent); //title - description - copyright - image
                    unset($content->$element);
                }

                $content->releaseDraft();
                $em->remove($content);
            }

            $media->setContent(null);
            $em->remove($media);
        }
    }
}
