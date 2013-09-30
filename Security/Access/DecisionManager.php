<?php

namespace BackBuilder\Security\Access;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DecisionManager extends AccessDecisionManager
{

    /**
     * The current BackBuilder application
     * @var \BackBuilder\BBApplication 
     */
    private $_application;

    /**
     *
     * @var Boolean
     */
    private $_tryBBTokenOnDenied;

    public function __construct(array $voters, $strategy = 'affirmative', $allowIfAllAbstainDecisions = false, $allowIfEqualGrantedDeniedDecisions = true, $tryBBTokenOnDenied = true)
    {
        parent::__construct($voters, $strategy, $allowIfAllAbstainDecisions, $allowIfEqualGrantedDeniedDecisions);

        $this->_tryBBTokenOnDenied = $tryBBTokenOnDenied;
    }

    public function setApplication(\BackBuilder\BBApplication $application)
    {
        $this->_application = $application;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function decide(TokenInterface $token, array $attributes, $object = null)
    {
        $result = parent::decide($token, $attributes, $object);

        if (false === $result
                && true === $this->_tryBBTokenOnDenied
                && null !== $this->_application
                && null !== $this->_application->getBBUserToken()) {
            $result = parent::decide($this->_application->getBBUserToken(), $attributes, $object);
        }

        return $result;
    }

}