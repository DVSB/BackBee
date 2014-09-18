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

namespace BackBuilder\Cache;

use BackBuilder\Cache\QueryParamStrategyManagerInterface;

use Symfony\Component\HttpFoundation\Request;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class QueryParamStrategyManager implements QueryParamStrategyManagerInterface
{
    public function process(Request $request, $strategy, array $options = array())
    {
        $computed_cache_id = array();
        switch ($strategy) {
            case self::INCLUDE_EVERY_QPARAMS_STRATEGY:
                foreach ($request->query->all() as $name => $value) {
                    if (true === is_scalar($value)) {
                        $computed_cache_id[] = "$name=$value";
                    }
                }

                break;
            case self::INCLUDE_CLASSCONTENT_QPARAMS_STRATEGY:
                if (
                    isset($options['query_params'])
                    && is_array($options['query_params'])
                    && isset($options['object_uid'])
                ) {
                    foreach ($options['query_params'] as $query) {
                        if (null !== $value = $request->get(str_replace('#uid#', $options['object_uid'], $query))) {
                            $computed_cache_id[] = "$query=$value";
                        }
                    }
                }

                break;
            case self::NONE_QPARAMS_STRATEGY:
            default:
                break;
        }

        return implode('-', $computed_cache_id);
    }
}
