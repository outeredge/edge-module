<?php

namespace Edge\Serializer;

use Zend\Paginator\Paginator;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class Serializer
{
    protected $serializer;

    protected $serializeNull = true;

    public function __construct(SerializerInterface $serializer)
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
    public function serialize($data, array $groups = null, $format = 'array', $key = null)
    {
        if ($data instanceof Paginator) {
            return $this->serializePaginator($data, $groups, $format, $key);
        }
        return $this->getSerializer()->serialize($data, $format, $this->createNewContext($groups));
    }

    /**
     * Serialize a paginator to the desired format
     *
     * @param Paginator $paginator
     * @param array|null $groups serialization groups
     * @param string $format serialization format
     * @param string $key root key for items, leave null to return items in root
     * @return string|array
     */
    protected function serializePaginator(Paginator $paginator, array $groups = null, $format = 'array', $key = null)
    {
        $items = iterator_to_array($paginator->getCurrentItems());

        if (null === $key) {
            return $this->getSerializer()->serialize($items, $format, $this->createNewContext($groups));
        }

        return $this->getSerializer()->serialize(
            array(
                'pages'   => $paginator->count(),
                'current' => $paginator->getCurrentPageNumber(),
                'count'   => $paginator->getTotalItemCount(),
                $key      => $items
            ),
            $format,
            $this->createNewContext($groups)
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