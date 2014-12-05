<?php
namespace BackBuilder\NestedNode\Builder;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class KeywordBuilder
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * KeywordBuilder's constructor
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create new entity BackBuilder\NestedNode\KeyWord with $keyword if not exists
     *
     * @param  string                         $keyword
     * @return BackBuilder\NestedNode\KeyWord
     */
    public function createKeywordIfNotExists($keyword, $do_persist = true)
    {
        if (null === $keyword_object = $this->em->getRepository('BackBuilder\NestedNode\KeyWord')->exists($keyword)) {
            $keyword_object = new \BackBuilder\NestedNode\KeyWord();
            $keyword_object->setRoot($this->em->find('BackBuilder\NestedNode\KeyWord', md5('root')));
            $keyword_object->setKeyWord(preg_replace('#[/\"]#', '', trim($keyword)));

            if (true === $do_persist) {
                $this->em->persist($keyword_object);
                $this->em->flush($keyword_object);
            }
        }

        return $keyword_object;
    }
}
