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

namespace BackBuilder\Annotations;

use Doctrine\Common\Annotations\Reader;

use Doctrine\Common\Annotations\AnnotationException;

/**
 * Chain annotation reader
 *
 * @category    BackBuilder
 * @package     BackBuilder\Annotations
 * @copyright   Lp digital system
 * @author      k.golovin
 */
final class ChainAnnotationReader implements Reader
{
    /**
     * @var array<Reader>
     */
    private $delegates = array();

    /**
     * Constructor.
     *
     * @param array<Reader> $readers
     */
    public function __construct(array $readers)
    {
        $this->delegates = $readers;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        $e = null;
        $annotations = array();
        
        foreach($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getClassAnnotations($class);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }
        
        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $e = null;
        
        $annotations = array();
        
        foreach($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getPropertyAnnotations($property);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }
        
        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $e = null;

        $annotations = array();
        
        foreach($this->delegates as $delegateReader) {
            try {
                $annot = $delegateReader->getMethodAnnotations($method);
                $annotations = array_merge_recursive($annotations, $annot);
            } catch (AnnotationException $e) {
                continue;
            }
        }
        
        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }


}
