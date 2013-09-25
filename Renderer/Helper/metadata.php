<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\MetaData\MetaDataBag;

/**
 * Helper generating <META> tag for the page being rendered
 * if none available, the default metadata are generaed
 * @category    BackBuilder
 * @package     BackBuilder\Renderer\Helper
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class metadata extends AHelper {
    public function __invoke() {
        if (NULL === $renderer = $this->_renderer) return '';
        if (null === $page = $renderer->getCurrentPage()) return '';

        if (null === $metadata = $page->getMetaData()) {
            $metadata = new MetaDataBag($renderer->getApplication()->getConfig()->getMetadataConfig(), $page);
        }

        $result = '';
        foreach($metadata as $meta) {
            if (0 < $meta->count()) {
                $result .= '<meta ';
                foreach($meta as $attribute => $value) $result .= $attribute.'="'.htmlentities($value, ENT_COMPAT, 'UTF-8').'" ';
                $result .= '/>';
            }
        }

        return $result;
    }
}