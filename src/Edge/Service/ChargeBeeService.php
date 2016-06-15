<?php

namespace Edge\Service;

use ChargeBee_Environment;
use ChargeBee_Event;
use ChargeBee_HostedPage;
use ChargeBee_PortalSession;
use ChargeBee_Subscription;

class ChargeBeeService
{
    /**
     * Default subscription planId
     *
     * @var string
     */
    protected $defaultPlan;

    /**
     * @var ChargeBee_Environment
     */
    protected $environment;


    public function __construct($site, $apikey, $defaultPlan = null)
    {
        $this->environment = new ChargeBee_Environment($site, $apikey);
        $this->defaultPlan = $defaultPlan;
    }

    public function eventRetrieve($id)
    {
        return ChargeBee_Event::retrieve($id, $this->getEnvironment());
    }

    public function subscriptionCreate($params)
    {
        return ChargeBee_Subscription::create($params, $this->getEnvironment());
    }

    public function subscriptionGet($id)
    {
        return ChargeBee_Subscription::retrieve($id, $this->getEnvironment());
    }

    public function subscriptionUpdate($id, $params = array())
    {
        return ChargeBee_Subscription::update($id, $params, $this->getEnvironment());
    }

    public function hostedPageCheckoutExisting($params)
    {
        return ChargeBee_HostedPage::checkoutExisting($params, $this->getEnvironment());
    }

    public function hostedPageRetrieve($id)
    {
        return ChargeBee_HostedPage::retrieve($id, $this->getEnvironment());
    }

    public function portalSessionCreate($params)
    {
        return ChargeBee_PortalSession::create($params, $this->getEnvironment());
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getSite()
    {
        return $this->getEnvironment()->getSite();
    }

    public function getDefaultPlan()
    {
        return $this->defaultPlan;
    }
}
