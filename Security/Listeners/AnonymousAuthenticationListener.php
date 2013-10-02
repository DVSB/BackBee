<?php
namespace BackBuilder\Security\Listeners;

use BackBuilder\Security\Token\AnonymousToken;

use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface;

use Psr\Log\LoggerInterface;

class AnonymousAuthenticationListener {
    private $context;
    private $key;
    private $logger;

    public function __construct(SecurityContextInterface $context, $key, LoggerInterface $logger = null) {
        $this->context = $context;
        $this->key     = $key;
        $this->logger  = $logger;
    }

    /**
     * Handles anonymous authentication.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event) {
        if (null !== $this->context->getToken()) {
           return;
        }
        
        
        $this->context->setToken(new AnonymousToken($this->key, 'anon.', array()));
        
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Populated SecurityContext with an anonymous Token'));
        }
    }
}