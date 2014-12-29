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

namespace BackBee\ClassContent\Repository;

use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Revision;
use BackBee\ClassContent\AClassContent;
use BackBee\ClassContent\Exception\ClassContentException;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Revision repository
 *
 * @category    BackBee
 * @package     BackBee\ClassContent
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RevisionRepository extends EntityRepository
{
    /**
     * Checks the content state of a revision
     * @param  Revision              $revision
     * @return AClassContent         the valid content according to revision state
     * @throws ClassContentException Occurs when the revision is orphan
     */
    private function _checkContent(Revision $revision)
    {
        $content = $revision->getContent();

        if (null === $content || !($content instanceof AClassContent)) {
            $this->_em->remove($revision);
            throw new ClassContentException('Orphan revision, deleted', ClassContentException::REVISION_ORPHAN);
        }

        if ($revision->getRevision() != $content->getRevision()) {
            throw new ClassContentException('Content is out of date', ClassContentException::REVISION_OUTOFDATE);
        }

        return $content;
    }

    public function checkout(AClassContent $content, \BackBee\Security\Token\BBUserToken $token)
    {
        $revision = new Revision();
        $revision->setAccept($content->getAccept());
        $revision->setContent($content);
        $revision->setData($content->getDataToObject());
        $revision->setLabel($content->getLabel());

        $maxEntry = (array) $content->getMaxEntry();
        $minEntry = (array) $content->getMinEntry();
        $revision->setMaxEntry($maxEntry);
        $revision->setMinEntry($minEntry);

        $revision->setOwner($token->getUser());
        $revision->setParam(null, $content->getParam());
        $revision->setRevision($content->getRevision() ? $content->getRevision() : 0);
        $revision->setState($content->getRevision() ? Revision::STATE_MODIFIED : Revision::STATE_ADDED);

        return $revision;
    }

    /**
     * Update user revision
     * @param  Revision              $revision
     * @throws ClassContentException Occurs on illegal revision state
     */
    public function update(Revision $revision)
    {
        switch ($revision->getState()) {
            case Revision::STATE_ADDED :
                throw new ClassContentException('Content is not revisionned yet', ClassContentException::REVISION_ADDED);
                break;

            case Revision::STATE_MODIFIED :
                try {
                    $this->_checkContent($revision);
                    throw new ClassContentException('Content is already up to date', ClassContentException::REVISION_UPTODATE);
                } catch (ClassContentException $e) {
                    if (ClassContentException::REVISION_OUTOFDATE == $e->getCode()) {
                        return $this->loadSubcontents($revision);
                    } else {
                        throw $e;
                    }
                }
                break;

            case Revision::STATE_CONFLICTED :
                throw new ClassContentException('Content is in conflict, please resolve or revert it', ClassContentException::REVISION_CONFLICTED);
                break;
        }

        throw new ClassContentException('Content is already up to date', ClassContentException::REVISION_UPTODATE);
    }

    /**
     * Commit user revision
     * @param  Revision              $revision
     * @throws ClassContentException Occurs on illegal revision state
     */
    public function commit(Revision $revision)
    {
        switch ($revision->getState()) {
            case Revision::STATE_ADDED :
            case Revision::STATE_MODIFIED :
                $revision->setRevision($revision->getRevision() + 1)
                        ->setState(Revision::STATE_COMMITTED);

                return $this->loadSubcontents($revision);
                break;

            case Revision::STATE_CONFLICTED :
                throw new ClassContentException('Content is in conflict, please resolve or revert it', ClassContentException::REVISION_CONFLICTED);
                break;
        }

        throw new ClassContentException(sprintf('Content can not be commited (state : %s)', $revision->getState()), ClassContentException::REVISION_UPTODATE);
    }

    /**
     * Revert (ie delete) user revision
     * @param  Revision              $revision
     * @throws ClassContentException Occurs on illegal revision state
     */
    public function revert(Revision $revision)
    {
        switch ($revision->getState()) {
            case Revision::STATE_ADDED :
            case Revision::STATE_MODIFIED :
            case Revision::STATE_CONFLICTED :
                if (null !== $content = $revision->getContent()) {
                    $content->releaseDraft();
                    if (AClassContent::STATE_NEW == $content->getState()) {
                        $this->_em->remove($content);
                    }
                }

                $this->_em->remove($revision);

                return $revision;
                break;
        }

        throw new ClassContentException(sprintf('Content can not be reverted (state : %s)', $revision->getState()), ClassContentException::REVISION_UPTODATE);
    }

    public function loadSubcontents(Revision $revision)
    {
        $content = $revision->getContent();
        if ($content instanceof ContentSet) {
            while ($subcontent = $revision->next()) {
                if (!($subcontent instanceof AClassContent)) {
                    continue;
                }

                if ($this->_em->contains($subcontent)) {
                    continue;
                }

                $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
                echo "Subcontent ".get_class($subcontent)."(".$subcontent->getUid().") loaded\n";
            }
        } else {
            foreach ($revision->getData() as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as &$val) {
                        if ($val instanceof AClassContent) {
                            if (null !== $entity = $this->_em->find(get_class($val), $val->getUid())) {
                                echo "Subcontent ".get_class($entity)."(".$entity->getUid().") loaded\n";
                                $val = $entity;
                            }
                        }
                    }
                    unset($val);
                } elseif ($value instanceof AClassContent) {
                    if (null !== $entity = $this->_em->find(get_class($value), $value->getUid())) {
                        echo "Subcontent ".get_class($entity)."(".$entity->getUid().") loaded\n";
                        $value = $entity;
                    }
                }
                $revision->$key = $value;
            }
        }

        $revision->setSubcontentsLoaded(true);

        return $revision;
    }

    /**
     * Return the user's draft of a content, optionally checks out a new one if not exists
     * @param  \BackBee\ClassContent\AClassContent $content
     * @param  \BackBee\Security\Token\BBUserToken $token
     * @param  boolean                             $checkoutOnMissing If true, checks out a new revision if none was found
     * @return \BackBee\ClassContent\Revision|null
     */
    public function getDraft(AClassContent $content, \BackBee\Security\Token\BBUserToken $token, $checkoutOnMissing = false)
    {
        if (null === $revision = $content->getDraft()) {
            try {
                if (false === $this->_em->contains($content)) {
                    $content = $this->_em->find(get_class($content), $content->getUid());
                    if (null === $content) {
                        return;
                    }
                }
                $q = $this->createQueryBuilder('r')
                                ->andWhere('r._content = :content')
                                ->andWhere('r._owner = :owner')
                                ->andWhere('r._state IN (:states)')
                                ->orderBy('r._revision', 'desc')
                                ->orderBy('r._modified', 'desc')
                                ->setParameters(array(
                                    'content' => $content,
                                    'owner' => ''.UserSecurityIdentity::fromToken($token),
                                    'states' => array(Revision::STATE_ADDED, Revision::STATE_MODIFIED, Revision::STATE_CONFLICTED),
                                ))->getQuery();

                $revision = $q->getSingleResult();
            } catch (\Exception $e) {
                if ($checkoutOnMissing) {
                    $revision = $this->checkout($content, $token);
                    $this->_em->persist($revision);
                } else {
                    $revision = null;
                }
            }
        }

        return $revision;
    }

    /**
     * Returns all current drafts for authenticated user
     * @param TokenInterface $token
     */
    public function getAllDrafts(TokenInterface $token)
    {
        $revisions = $this->_em->getRepository('\BackBee\ClassContent\Revision')
                ->findBy(array('_owner' => UserSecurityIdentity::fromToken($token),
            '_state' => array(Revision::STATE_ADDED, Revision::STATE_MODIFIED), ));

        return $revisions;
    }

    public function getRevisions(AClassContent $content)
    {
        $revisions = $this->_em->getRepository('\BackBee\ClassContent\Revision')
                ->findBy(array('_content' => $content));

        return $revisions;
    }
}
