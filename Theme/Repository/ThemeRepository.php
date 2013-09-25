<?php
namespace BackBuilder\Theme\Repository;

use BackBuilder\Theme\PersonalThemeEntity;

use Doctrine\ORM\EntityRepository;

/**
 * ThemeRepository object in BackBuilder 5
 *
 * Theme persistence
 *
 * @category    BackBuilder
 * @package     BackBuilder\Theme\
 * @copyright   Lp digital system
 * @author      n.dufreche
 */
class ThemeRepository extends EntityRepository {

    /**
     * Retrieve the theme by site uid.
     *
     * @param string $site_uid
     * @return PersonalThemeEntity object
     */
    public function retrieveBySiteUid($site_uid)
    {
        $q = $this->createQueryBuilder('t')
                  ->andWhere('t._site_uid = :site_uid')
                  ->setParameters(array('site_uid' => $site_uid));
        $theme = $q->getQuery()->getOneOrNullResult();

        return $theme;
    }

    /**
     * Retrieve the theme by site uid and save the current theme.
     *
     * @param string $site_uid
     * @param PersonalThemeEntity $theme
     * @return Theme object
     */
    public function setCurrentTheme($site_uid, PersonalThemeEntity $theme) {
        $current = $this->retrieveBySiteUid($site_uid);
        if ($current != null) {
            foreach ($theme->toArray() as $key => $value) {
                $current->{'set'.ucfirst($key)}($value);
            }
        } else {
            $current = $theme;
            $current->setSiteUid($site_uid);
        }
 
        $this->_em->persist($current);
        $this->_em->flush();

        return $current;
    }
}