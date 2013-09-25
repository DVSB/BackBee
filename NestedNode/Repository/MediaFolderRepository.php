<?php

namespace BackBuilder\NestedNode\Repository;


use BackBuilder\NestedNode\ANestedNode;

class MediaFolderRepository extends NestedNodeRepository {

    public function getRoot() {
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
    
    private function preloadMediaType(\BackBuilder\BBApplication $bbApp) {
        $classnames = $bbApp->getAutoloader()->glob('Media' . DIRECTORY_SEPARATOR . '*');
        foreach ($classnames as $classname) {
            class_exists($classname);
        }
    }

    private function deleteMediaContent(\BackBuilder\BBApplication $bbapp, $media) {
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