<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Rewriting;

use BackBee\BBApplication;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\Exception\RewritingException;
use BackBee\Utils\String;

/**
 * Utility class to generate page URL according config rules.
 *
 * Available options are:
 *    * preserve-online  : if true, forbid the URL updating for online page
 *    * preserve-unicity : if true check for unique computed URL
 *
 * Available rules are:
 *    * _root_      : scheme for root node
 *    * _default_   : default scheme
 *    * _content_   : array of schemes indexed by content classname
 *
 * Available params are:
 *    * $parent     : page parent url
 *    * $uid        : page uid
 *    * $title      : the urlized form of the title
 *    * $date       : the creation date formated to YYYYMMDD
 *    * $datetime   : the creation date formated to YYYYMMDDHHII
 *    * $time       : the creation date formated to HHIISS
 *    * $content->x : the urlized form of the 'x' property of content
 *    * $ancestor[x]: the ancestor of level x url
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * Current BackBee application.
     *
     * @var BackBee\BBApplication
     */
    private $application;

    /**
     * if true, forbid the URL updating for online page.
     *
     * @var boolean
     */
    private $preserveOnline = true;

    /**
     * if true, check for unique computed URL.
     *
     * @var boolean
     */
    private $preserveUnicity = true;

    /**
     * Available rewriting schemes.
     *
     * @var array
     */
    private $schemes = array();

    /**
     * Array of class content used by one of the schemes.
     *
     * @var array
     */
    private $descriminators;

    /**
     * Class constructor.
     *
     * @param \BackBee\BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->application = $application;

        if (null !== $rewritingConfig = $this->application->getConfig()->getRewritingConfig()) {
            if (true === array_key_exists('preserve-online', $rewritingConfig)) {
                $this->preserveOnline = (true === $rewritingConfig['preserve-online']);
            }

            if (true === array_key_exists('preserve-unicity', $rewritingConfig)) {
                $this->preserveUnicity = (true === $rewritingConfig['preserve-unicity']);
            }

            if (true === isset($rewritingConfig['scheme']) && true === is_array($rewritingConfig['scheme'])) {
                $this->schemes = $rewritingConfig['scheme'];
            }
        }
    }

    /**
     * Returns the list of class content names used by one of schemes
     * Dynamically add a listener on descrimator.onflush event to RewritingListener.
     *
     * @return array
     */
    public function getDiscriminators()
    {
        if (null === $this->descriminators) {
            $this->descriminators = array();

            if (true === array_key_exists('_content_', $this->schemes)) {
                foreach (array_keys($this->schemes['_content_']) as $descriminator) {
                    $this->descriminators[] = 'BackBee\ClassContent\\'.$descriminator;

                    if (null !== $this->application->getEventDispatcher()) {
                        $this->application
                             ->getEventDispatcher()
                             ->addListener(str_replace(NAMESPACE_SEPARATOR, '.', $descriminator).'.onflush', array('BackBee\Event\Listener\RewritingListener', 'onFlushContent'))
                        ;
                    }
                }
            }
        }

        return $this->descriminators;
    }

    /**
     * Returns the URL of the page.
     *
     * @param \BackBee\NestedNode\Page            $page    The page
     * @param  \BackBee\ClassContent\AbstractClassContent $content The optionnal main content of the page
     * @return string                              The URL                                  The generated URL
     */
    public function generate(Page $page, AbstractClassContent $content = null, $exceptionOnMissingScheme = true)
    {
        if (
            null !== $page->getUrl(false)
            && $this->preserveOnline
            && (null === $page->getOldState() || ($page->getOldState() & Page::STATE_ONLINE))
            && $page->getState() & Page::STATE_ONLINE
        ) {
            return $page->getUrl(false);
        }

        if ($page->isRoot() && true === array_key_exists('_root_', $this->schemes)) {
            return $this->doGenerate($this->schemes['_root_'], $page, $content);
        }

        if (true === isset($this->schemes['_layout_']) && true === is_array($this->schemes['_layout_'])) {
            if (true === array_key_exists($page->getlayout()->getUid(), $this->schemes['_layout_'])) {
                return $this->doGenerate($this->schemes['_layout_'][$page->getlayout()->getUid()], $page);
            }
        }

        if (null !== $content && true === array_key_exists('_content_', $this->schemes)) {
            $shortClassname = str_replace('BackBee\ClassContent\\', '', get_class($content));
            if (true === array_key_exists($shortClassname, $this->schemes['_content_'])) {
                return $this->doGenerate($this->schemes['_content_'][$shortClassname], $page, $content);
            }
        }

        $url = $page->getUrl(false);
        if (false === empty($url)) {
            return $url;
        }

        if (true === array_key_exists('_default_', $this->schemes)) {
            return $this->doGenerate($this->schemes['_default_'], $page, $content);
        }

        if (true === $exceptionOnMissingScheme) {
            throw new RewritingException(sprintf('No rewriting scheme found for Page(%s)', $page->getUid()), RewritingException::MISSING_SCHEME);
        }

        return '/'.$page->getUid();
    }

    /**
     * Computes the URL of a page according to a scheme.
     *
     * @param array         $scheme  The scheme to apply
     * @param Page          $page    The page
     * @param  AbstractClassContent $content The optionnal main content of the page
     * @return string        The generated URL
     */
    private function doGenerate($scheme, Page $page, AbstractClassContent $content = null)
    {
        $replacement = array(
            '$parent' => ($page->isRoot()) ? '' : $page->getParent()->getUrl(false),
            '$title' => String::urlize($page->getTitle()),
            '$datetime' => $page->getCreated()->format('ymdHis'),
            '$date' => $page->getCreated()->format('ymd'),
            '$time' => $page->getCreated()->format('His'),
        );

        $matches = array();
        if (preg_match_all('/(\$content->[a-z]+)/i', $scheme, $matches)) {
            foreach ($matches[1] as $pattern) {
                $property = explode('->', $pattern);
                $property = array_pop($property);

                try {
                    $replacement[$pattern] = String::urlize($content->$property);
                } catch (\Exception $e) {
                    $replacement[$pattern] = '';
                }
            }
        }

        $matches = array();
        if (preg_match_all('/(\$ancestor\[([0-9]+)\])/i', $scheme, $matches)) {
            foreach ($matches[2] as $level) {
                $ancestor = $this->application
                    ->getEntityManager()
                    ->getRepository('BackBee\NestedNode\Page')
                    ->getAncestor($page, $level)
                ;
                if (null !== $ancestor && $page->getLevel() > $level) {
                    $replacement['$ancestor['.$level.']'] = $ancestor->getUrl(false);
                } else {
                    $replacement['$ancestor['.$level.']'] = '';
                }
            }
        }

        $url = preg_replace('/\/+/', '/', str_replace(array_keys($replacement), array_values($replacement), $scheme));
        if (true === $this->preserveUnicity) {
            $this->checkUniqueness($page, $url);
        }

        return $url;
    }

    /**
     * Checks for the uniqueness of the URL and postfixe it if need.
     *
     * @param \BackBee\NestedNode\Page $page The page
     * @param string                   &$url The reference of the generated URL
     */
    private function checkUniqueness(Page $page, &$url)
    {
        $baseurl = $url.'-%d';
        $page_repository = $this->application->getEntityManager()->getRepository('BackBee\NestedNode\Page');

        $count = 1;
        $existings = array();
        if (1 === preg_match('#(.*)\/$#', $baseurl, $matches)) {
            $baseurl = $matches[1].'-%d/';
            $existings = $page_repository->createQueryBuilder('p')
                ->where('p._root = :root')
                ->setParameter('root', $page->getRoot())
                ->andWhere('p._url LIKE :url')
                ->setParameter('url', $matches[1].'%/')
                ->getQuery()
                ->getResult()
            ;
        } else {
            $existings = $this->application->getEntityManager()->getConnection()->executeQuery(
                'SELECT uid FROM page WHERE `root_uid` = :root AND url REGEXP :regex',
                array(
                    'regex' => $url.'(-[0-9]+)?$',
                    'root'  => $page->getRoot()->getUid(),
                )
            )->fetchAll();

            $uids = array();
            foreach ($existings as $existing) {
                $uids[] = $existing['uid'];
            }

            $existings = $page_repository->findBy(array('_uid' => $uids));
        }

        $existings_url = array();
        foreach ($existings as $existing) {
            if (!$existing->isDeleted() && $existing->getUid() != $page->getUid()) {
                $existings_url[] = $existing->getUrl(false);
                $url = sprintf($baseurl, $count++);
            }
        }

        while (true === in_array($url, $existings_url)) {
            $url = sprintf($baseurl, $count++);
        }
    }
}
