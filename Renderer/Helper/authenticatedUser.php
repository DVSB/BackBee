<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\ClassContent\AClassContent;

class authenticatedUser extends AHelper {
    public function __invoke($userClassname = NULL) {
        if (NULL === $application = $this->_renderer->getApplication()) return NULL;
        if (NULL === $token = $application->getSecurityContext()->getToken()) return NULL;
        if (NULL === $user = $token->getUser()) return NULL;
        if (NULL !== $userClassname && !is_a($user, $userClassname)) return NULL;
        
        return $user;
    }
}