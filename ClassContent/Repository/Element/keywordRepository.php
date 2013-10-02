<?php
namespace BackBuilder\ClassContent\Repository\Element;

use BackBuilder\ClassContent\Repository\ClassContentRepository,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\keyword as elementKeyword;

/**
 * keyword repository
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class keywordRepository extends ClassContentRepository
{
    /**
     * Do stuf on update by post of the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return \BackBuilder\ClassContent\Element\file
     * @throws ClassContentException Occures on invalid content type provided
     */
    public function getValueFromPost(AClassContent $content, $value, AClassContent $parent = null)
    {
        if (false === ($content instanceof elementKeyword)) {
            throw new ClassContentException('Invalid content type');
        }

        if (true === property_exists($value, 'value')) {
            $content->value = $value->value;

            if (null !== $realkeyword = $this->_em->find('BackBuilder\NestedNode\KeyWord', $value->value)) {
                if (null === $parent) {
                    throw new ClassContentException('Invalid parent content');
                }

                if (null === $realkeyword->getContent() || false === $realkeyword->getContent()->contains($parent)) {
                    $realkeyword->addContent($parent);
                }
            }
        }

        return $content;
    }

    /**
     * Do stuf removing content from the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param type $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return type
     * @throws ClassContentException
     */
    public function removeFromPost(AClassContent $content, $value = null, AClassContent $parent = null)
    {
        if (false === ($content instanceof elementKeyword)) {
            throw new ClassContentException('Invalid content type');
        }

        $content = parent::removeFromPost($content);

        if (true === property_exists($value, 'value')) {
            if (null === $parent) {
                throw new ClassContentException('Invalid parent content');
            }

            if (null !== $realkeyword = $this->_em->find('BackBuilder\NestedNode\KeyWord', $value->value)) {
                if (true === $realkeyword->getContent()->contains($parent)) {
                    $realkeyword->removeContent($parent);
                }
            }
        }

        return $content;
    }
}