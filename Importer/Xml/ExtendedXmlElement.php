<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Importer\Xml;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ExtendedXmlElement extends \SimpleXMLElement
{
    /**
     *
     * @param  type                    $xpath
     * @param  type                    $convertTo
     * @return string|SimpleXMLElement
     * @throws \Exception
     */
    public function xpathFirstResult($xpath, $convertTo = null)
    {
        $result = parent::xpath($xpath);

        if (1 === count($result)) {
            $value = $result[0];

            if ('string' === $convertTo) {
                $value = (string) $value;
            } elseif ('xml' === $convertTo) {
                $value = (string) $value->asXML();
            }

            return $value;
        } elseif (0 === count($result)) {
            return;
        }

        throw new \Exception('More than 1 result was returned for xpath: '.$xpath);
    }
}
