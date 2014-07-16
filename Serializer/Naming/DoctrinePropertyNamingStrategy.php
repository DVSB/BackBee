<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace BackBuilder\Serializer\Naming;

use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\Metadata\PropertyMetadata;

use BackBuilder\Doctrine\Registry;

class DoctrinePropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     *
     * @var BackBuilder\Doctrine\Registry
     */
    private $doctrine;
    
    private $delegate;
    
    private static $metadataCache = array();
    
    public function __construct(Registry $doctrine, PropertyNamingStrategyInterface $namingStrategy) 
    {
        $this->doctrine = $doctrine;
        $this->delegate = $namingStrategy;
    }
    
    public function translateName(PropertyMetadata $property)
    {
        $metadata = $this->getDoctrineMetadata($property);
        
        if(isset($metadata->fieldNames[$property->class])) {
            return $metadata->fieldNames[$property->class];
        }
        
        return $this->delegate->translateName($property);
    }
    
    protected function getDoctrineMetadata(PropertyMetadata $property)
    {
        if(!isset(self::$metadataCache[$property->class])) {
            $em = $this->doctrine->getManagerForClass($property->class);
            /* @var $em  \Doctrine\Common\Persistence\ObjectManager */
            self::$metadataCache[$property->class] = $em->getClassMetadata($property->class);
        }
        
        return self::$metadataCache[$property->class];
    }
}
