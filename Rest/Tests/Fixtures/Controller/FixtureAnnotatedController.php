<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBuilder\Rest\Tests\Fixtures\Controller;

use BackBuilder\Rest\Controller\Annotations\Pagination;

class FixtureAnnotatedController 
{
    /**
     * @Pagination
     */
    public function defaultPaginationAction()
    {} 
    
    /**
     * @Pagination(startName="from", limitName="max", limitDefault=20, limitMax=100, limitMin=10)
     */
    public function customPaginationAction()
    {} 

}
