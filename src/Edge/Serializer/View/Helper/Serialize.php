<?php

namespace Edge\Serializer\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Edge\Serializer\Serializer;

class Serialize extends AbstractHelper
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
    public function __invoke($data, array $groups = null, $format = Serializer::FORMAT_JSON, $key = null)
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