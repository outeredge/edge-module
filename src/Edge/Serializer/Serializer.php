<?php

namespace Edge\Serializer;

use Zend\Paginator\Paginator;
use JMS\Serializer\Serializer as JMSSerializer;
use JMS\Serializer\SerializationContext;

class Serializer
{
    const FORMAT_ARRAY = 'array';
    const FORMAT_JSON  = 'json';

    protected $serializer;

    protected $serializeNull = true;

    public function __construct(JMSSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Serialize data to desired format
     *
     * @param object|array|scalar|Paginator $data
     * @param array|null $groups serialization groups
     * @param string $format serialization format
     * @param string $key root key for items, leave null to return items in root
     * @return mixed
     */
    public function serialize($data, array $groups = null, $format = self::FORMAT_ARRAY, $key = null)
    {
        if ($data instanceof Paginator) {
            $data = $this->paginatorToArray($data, $key);
        } elseif ($key !== null) {
            $data = array($key => $data);
        }

        if ($format == self::FORMAT_ARRAY) {
            return $this->getSerializer()->toArray($data, $this->createNewContext($groups));
        }

        return $this->getSerializer()->serialize($data, $format, $this->createNewContext($groups));
    }

    /**
     * Convert a paginator to array
     *
     * @param Paginator $paginator
     * @param string $key root key for items, leave null to return items in root
     * @return array
     */
    protected function paginatorToArray(Paginator $paginator, $key = null)
    {
        $items = iterator_to_array($paginator->getCurrentItems());

        if (null === $key) {
            return $items;
        }

        return array(
            'pages'   => $paginator->count(),
            'current' => $paginator->getCurrentPageNumber(),
            'count'   => $paginator->getTotalItemCount(),
            $key      => $items
        );
    }

    /**
     * Create a new serialization context
     *
     * @param array $groups [optional]
     * @return SerializationContext
     */
    protected function createNewContext(array $groups = null)
    {
        $context = new SerializationContext();
        $context->setSerializeNull($this->serializeNull);

        if (null !== $groups) {
            $context->setGroups($groups);
        }

        return $context;
    }

    public function setSerializeNull($bool)
    {
        $this->serializeNull = (boolean) $bool;
        return $this;
    }

    protected function getSerializer()
    {
        return $this->serializer;
    }
}