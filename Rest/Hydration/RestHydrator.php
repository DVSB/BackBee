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

namespace BackBuilder\Rest\Hydration;

use Doctrine\ORM\EntityManager;

use Doctrine\DBAL\Types\Type;

/**
 * The ObjectHydrator constructs an object graph out of a solr result set.
  */
class RestHydrator
{
    protected $em;
    
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    
    public function hydrateEntity($entity, array $values)
    {
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        
        foreach($values as $fieldName => $value) {
            try {
                $classMetadata->getFieldMapping($fieldName);
                
                $type = Type::getType($classMetadata->fieldMappings[$fieldName]['type']);
                $value = $type->convertToPHPValue($value, $this->em->getConnection()->getDatabasePlatform());
                
                $classMetadata->setFieldValue($entity, $fieldName, $value);
            } catch(\Exception $e) {
                throw new HydrationException($fieldName, $e);
            }
        }
    }
}