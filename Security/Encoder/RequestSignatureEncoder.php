<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Security\Encoder;

use BackBee\Security\Token\BBUserToken;
use Symfony\Component\Security\Core\Util\StringUtils;

/**
 * Request signature encoder
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Encoder
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RequestSignatureEncoder
{
    /**
     * Checks if the presented signature is valid or not according to token
     *
     * @param BBUserToken $token
     * @param string      $signaturePresented signature we want to check if it's correct
     *
     * @return boolean true if signature is valid, else false
     */
   public function isApiSignatureValid(BBUserToken $token, $signaturePresented)
   {
       return StringUtils::equals($this->createSignature($token), $signaturePresented);
   }

   /**
    * Create a signature for a given user
    *
    * @param BackBee\Security\Token\BBUserToken the token we want to generate API signature key
    *
    * @return string the generated signature
    */
   public function createSignature(BBUserToken $token)
   {
       return md5($token->getUser()->getApiKeyPublic().$token->getUser()->getApiKeyPrivate().$token->getNonce());
   }
}
