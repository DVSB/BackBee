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

namespace BackBee\Translation;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator as sfTranslator;

use BackBee\BBApplication;

/**
 * Extends Symfony\Component\Translation\Translator to allow lazy load of BackBee catalogues
 *
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class Translator extends sfTranslator
{
    /**
     * Override Symfony\Component\Translation\Translator to lazy load every catalogues from:
     *     - BackBee\Resources\translations
     *     - PATH_TO_REPOSITORY\Ressources\translations
     *     - PATH_TO_CONTEXT_REPOSITORY\Ressources\translations
     *
     * @param BBApplication $application
     * @param string        $locale
     */
    public function __construct(BBApplication $application, $locale)
    {
        parent::__construct($locale);

        // retrieve default fallback from container and set it
        $fallback = $application->getContainer()->getParameter('translator.fallback');
        $this->setFallbackLocale($fallback);

        // xliff is recommended by Symfony so we register its loader as default one
        $this->addLoader('xliff', new XliffFileLoader());

        // define in which directory we should looking at to find xliff files
        $dirToLookingAt = array(
            $application->getBBDir().DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'translations',
            $application->getRepository().DIRECTORY_SEPARATOR.'Ressources'.DIRECTORY_SEPARATOR.'translations',
        );

        if ($application->getRepository() !== $application->getBaseRepository()) {
            $dirToLookingAt[] = $application->getBaseRepository().'Ressources'.DIRECTORY_SEPARATOR.'translations';
        }

        // loop in every directory we should looking at and load catalogue from file which match to the pattern
        foreach ($dirToLookingAt as $dir) {
            if (true === is_dir($dir)) {
                foreach (scandir($dir) as $filename) {
                    preg_match('/(.+)\.(.+)\.xlf$/', $filename, $matches);
                    if (0 < count($matches)) {
                        $this->addResource('xliff', $dir.DIRECTORY_SEPARATOR.$filename, $matches[2], $matches[1]);
                    }
                }
            }
        }
    }
}
