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

namespace BackBuilder\Util\Doctrine\CustomFunction;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\Parser,
    Doctrine\ORM\Query\SqlWalker,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * Implements Regexp for Sqlite
 *
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Doctrine\CustomFunction
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class Regexp extends FunctionNode
{
    public $regex;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);

        $this->firstDateExpression = $parser->StringPrimary();
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        return 'REGEXP ' . $sqlWalker->walkStringPrimary($this->regex);
    }
}