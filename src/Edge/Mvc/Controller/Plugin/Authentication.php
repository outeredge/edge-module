<?php

namespace Edge\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Authentication\AuthenticationService;

class Authentication extends AbstractPlugin {

    /**
     * @var \Zend\Authentication\Adapter\AdapterInterface
     */
    protected $authAdapter;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * Proxy convenience method
     *
     * @return bool
     */
    public function hasIdentity() {
        return $this->getAuthService()->hasIdentity();
    }

    /**
     * Proxy convenience method
     *
     * @return mixed
     */
    public function getIdentity() {
        return $this->getAuthService()->getIdentity();
    }

    /**
     * Get authAdapter.
     *
     * @return \Zend\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter() {
        if (null === $this->authAdapter && !$this->authAdapter = $this->getAuthService()->getAdapter()) {
            throw new \Exception('No authentication adapter available');
        }

        return $this->authAdapter;
    }

    /**
     * Set authAdapter.
     *
     * @param \Zend\Authentication\Adapter\AdapterInterface $authAdapter
     */
    public function setAuthAdapter(AuthAdapter $authAdapter) {
        $this->authAdapter = $authAdapter;
        return $this;
    }

    /**
     * Get authService.
     *
     * @return AuthenticationService
     */
    public function getAuthService() {
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