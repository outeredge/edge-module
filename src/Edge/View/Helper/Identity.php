<?php

namespace Edge\View\Helper;

use Edge\Service\IdentityProviderInterface;
use Zend\View\Helper\AbstractHelper;

class Identity extends AbstractHelper {

    /**
     * @var IdentityProviderInterface
     */
    protected $identityProvider;

    /**
     * Returns the current identity (i.e. Entity), if any
     *
     * @access public
     */
    public function __invoke() {
        if (null === $this->getIdentityProvider()) {
            return null;
        }
        return $this->getIdentityProvider()->getActiveIdentity();
    }

    /**
     * Get identity provider
     *
     * @return IdentityProviderInterface
     */
    protected function getIdentityProvider() {
        return $this->identityProvider;
    }

    /**
     * Set identity provider
     *
     * @param IdentityProviderInterface $provider
     */
    public function setIdentityProvider(IdentityProviderInterface $provider) {
        $this->identityProvider = $provider;
        return $this;
    }

}