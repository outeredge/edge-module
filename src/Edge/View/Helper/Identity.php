<?php

namespace Edge\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Authentication\AuthenticationService;

class Identity extends AbstractHelper
{
    /**
     * @var AuthenticationService
     */
    protected $authService;


    /**
     * Get current users identity
     *
     * @access public
     */
    public function __invoke()
    {
        if (null === $this->getAuthService()) {
            return null;
        }

        if (!$this->getAuthService()->hasIdentity()) {
            return null;
        }

        return $this->getAuthService()->getIdentity();
    }

    /**
     * Get authService.
     *
     * @return AuthenticationService
     */
    protected function getAuthService() {
        return $this->authService;
    }

    /**
     * Set authService.
     *
     * @param AuthenticationService $authService
     */
    public function setAuthService(AuthenticationService $authService) {
        $this->authService = $authService;
        return $this;
    }
}