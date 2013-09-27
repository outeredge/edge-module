<?php

namespace Edge\Serializer\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Edge\Serializer\Serializer;

class Serialize extends AbstractPlugin
{
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Serialize data to desired format
     *
     * @param object|array|scalar|\Zend\Paginator\Paginator $data
     * @param array|null $groups serialization groups
     * @param string $format serialization format
     * @param string $key root key for items, leave null to return items in root
     * @return mixed
     */
    public function __invoke($data, array $groups = null, $format = 'json', $key = null)
    {
        return $this->getSerializer()->serialize($data, $groups, $format, $key);
    }

    /**
     * Get Serializer
     *
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }
}