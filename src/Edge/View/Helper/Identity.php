<?php

namespace Edge\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Authentication\AuthenticationService;

class Identity extends AbstractHelper {

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * Returns the current identity (i.e. Entity), if any
     *
     * @access public
     */
    public function __invoke() {
        if ($this->getAuthService()->hasIdentity()) {
            return $this->getAuthService()->getIdentity();
        } else {
            return false;
        }
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