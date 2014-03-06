<?php

namespace BackBuilder\Translation;

use BackBuilder\BBApplication;

use Symfony\Component\Translation\Loader\XliffFileLoader,
    Symfony\Component\Translation\Translator as sfTranslator;

/**
 * Extends Symfony\Component\Translation\Translator to allow lazy load of BackBee catalogues
 * 
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class Translator extends sfTranslator
{
    /**
     * Override Symfony\Component\Translation\Translator to lazy load every catalogues from:
     *     - BackBuilder\Resources\translations
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
            $application->getBBDir() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'translations',
            $application->getRepository() . DIRECTORY_SEPARATOR . 'Ressources' . DIRECTORY_SEPARATOR . 'translations'
        );

        if ($application->getRepository() !== $application->getBaseRepository()) {
            $dirToLookingAt[] = $application->getBaseRepository() . 'Ressources' . DIRECTORY_SEPARATOR . 'translations';
        }

        // loop in every directory we should looking at and load catalogue from file which match to the pattern
        foreach ($dirToLookingAt as $dir) {
            if (true === is_dir($dir)) {
                foreach (scandir($dir) as $filename) {
                    preg_match('/(.+)\.(.+)\.xlf$/', $filename, $matches);
                    if (0 < count($matches)) {
                        $this->addResource('xliff', $dir . DIRECTORY_SEPARATOR . $filename, $matches[2], $matches[1]);
                    }
                }
            }
        }
    }
}
