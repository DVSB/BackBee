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

namespace BackBuilder\Security\Authentication\Provider;

use BackBuilder\Security\Encoder\RequestSignatureEncoder;
use BackBuilder\Security\Exception\SecurityException;
use BackBuilder\Security\Token\PublicKeyToken;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Authentication provider for username/password firewall
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authentication\Provider
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PublicKeyAuthenticationProvider extends BBAuthenticationProvider
{
    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (false === $this->supports($token)) {
            return null;
        }

        $publicKey = $token->getUsername();


        // test of nonce, signature and created
        if (null === $nonce = $this->readNonceValue($token->getNonce())) {
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        $user = $this->user_provider->loadUserByPublicKey($publicKey);
        $token->setUser($user);

        $signature_encoder = new RequestSignatureEncoder();
        if (false === $signature_encoder->isApiSignatureValid($token, $nonce[1])) {
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        if (time() > $nonce[0] + $this->lifetime) {
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }

        $authenticated_token = new PublicKeyToken($user->getRoles());
        $authenticated_token->setUser($user);

        return $authenticated_token;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PublicKeyToken;
    }
}
