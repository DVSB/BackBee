<?php

namespace BackBuilder\Security\Encoder;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\Util\StringUtils;

use BackBuilder\ApiClient\Auth\PrivateKeyAuth;

/**
 * Request signature encoder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RequestSignatureEncoder
{

    /**
     * 
     * @param string $signaturePresented
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $privateKey
     * @return bool
     */
   public function isApiSignatureValid($signaturePresented, Request $request, $privateKey)
   {
       $signatureRequest = $this->createSignature($request, $privateKey);
       
       var_dump($signatureRequest);
       var_dump($signaturePresented);
       var_dump($privateKey);exit;
       
       return StringUtils::equals($signatureRequest, $signaturePresented);

   }
   
   /**
    * 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @param string $privateKey
    * @return string
    */
   public function createSignature(Request $request, $privateKey)
   {
        $encoder = new PrivateKeyAuth();
        $encoder->setPrivateKey($privateKey);
        $signature = $encoder->getRequestSignature($request->getMethod(), $request->getUri());

        return $signature;
   }
}
