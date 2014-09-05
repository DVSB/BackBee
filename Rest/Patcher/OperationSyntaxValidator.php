<?php
namespace BackBuilder\Rest\Patcher;

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

use BackBuilder\Rest\Patcher\PatcherInterface;
use BackBuilder\Rest\Patcher\Exception\InvalidOperationSyntaxException;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class OperationSyntaxValidator
{
    /**
     * [validate description]
     *
     * @param  array  $operations [description]
     *
     * @throws InvalidOperationSyntaxException
     */
    public function validate(array $operations)
    {
        foreach ($operations as $operation) {
            if (false === array_key_exists('op', $operation)) {
                throw new InvalidOperationSyntaxException('`op` key is missing.');
            }

            switch ($operation['op']) {
                case PatcherInterface::TEST_OPERATION:
                case PatcherInterface::ADD_OPERATION:
                case PatcherInterface::REPLACE_OPERATION:
                    if (false === isset($operation['path']) || false === isset($operation['value'])) {
                        throw new InvalidOperationSyntaxException('`path` and/or `value` key is missing.');
                    }

                    break;
                case PatcherInterface::TEST_OPERATION:
                case PatcherInterface::TEST_OPERATION:
                    if (false === isset($operation['from'])) {
                        throw new InvalidOperationSyntaxException('`from` key is missing.');
                    }
                case PatcherInterface::TEST_OPERATION:
                    if (false === isset($operation['path'])) {
                        throw new InvalidOperationSyntaxException('`path` key is missing.');
                    }

                    break;
                default:
                    throw new InvalidOperationSyntaxException('Invalid operation name: ' . $operation['op']);
            }
        }
    }
}