<?php
namespace BackBuilder\Site\Repository;

use BackBuilder\Site\UserPreferences;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * UserPreferences object in BackBuilder 5
 *
 * User preferences persistence
 *
 * @category    BackBuilder
 * @package     BackBuilder\Site\
 * @copyright   Lp system
 * @author      n.dufreche
 */
class UserPreferencesRepository extends EntityRepository {

    /**
     * Retrieve the user prefernces by security token.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return UserPreferences object
     */
    public function loadPreferences($token)
    {
        if ($token instanceof TokenInterface) {
            $uid = $this->retrieveUserPreferencesUid($token);
        } else {
            $uid = $token;
        }
        return $this->retrieveByUid($uid, $token->getUser());
    }

    /**
     * Calculate the unique user preferences key.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return String
     */
    public function retrieveUserPreferencesUid(TokenInterface $token)
    {
        return md5((string)$token->getUser());
    }

    /**
     * Set the user preferences.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param string $preferences
     */
    public function setPreferences(TokenInterface $token, $preferences)
    {
        $user_preferences = $this->loadPreferences($token);
        return $user_preferences->setPreferences($preferences);

    }

    /**
     * Find the UserPreferences object by uid.
     *
     * @param string $uid
     * @param BackBuilder\Security\User $user
     * @return \BackBuilder\Site\UserPreferences
     */
    private function retrieveByUid($uid, $user)
    {
        try {
            $q = $this->createQueryBuilder('up')
                    ->andWhere('up._uid = :uid')
                    ->setParameters(array(
                        'uid' => $uid
                    ));
            return $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            unset($e);
            $user_preference = new UserPreferences();
            $user_preference->setUid($uid)->setOwner($user)->setPreferences('{"updated_at": 0}');
            $this->_em->persist($user_preference);
            $this->_em->flush();

            return $user_preference;
        }
    }
}