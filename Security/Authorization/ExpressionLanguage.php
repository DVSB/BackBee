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

namespace BackBuilder\Security\Authorization;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

/**
 * Adds some function to the default Symfony Security ExpressionLanguage.
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>, k.golovin
 */
class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register('is_anonymous', function () {
            return '$trust_resolver->isAnonymous($token)';
        }, function (array $variables) {
            return $variables['trust_resolver']->isAnonymous($variables['token']);
        });

        $this->register('is_authenticated', function () {
            return '$token && !$trust_resolver->isAnonymous($token)';
        }, function (array $variables) {
            return $variables['token'] && !$variables['trust_resolver']->isAnonymous($variables['token']);
        });

        $this->register('is_fully_authenticated', function () {
            return '$trust_resolver->isFullFledged($token)';
        }, function (array $variables) {
            return $variables['trust_resolver']->isFullFledged($variables['token']);
        });

        $this->register('is_remember_me', function () {
            return '$trust_resolver->isRememberMe($token)';
        }, function (array $variables) {
            return $variables['trust_resolver']->isRememberMe($variables['token']);
        });

        $this->register('has_role', function ($role) {
            return sprintf('in_array(%s, $roles)', $role);
        }, function (array $variables, $role) {
            return in_array($role, $variables['roles']);
        });

        $this->register('is_granted', function ($attributes, $object = 'null') {
            return sprintf('$security_context->isGranted(%s, %s)', $attributes, $object);
        }, function (array $variables, $attributes, $object = null) {
            return $variables['security_context']->isGranted($attributes, $object);
        });
    }
}
