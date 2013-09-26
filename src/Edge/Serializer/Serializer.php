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
     * Serializes the given data to the specified output format
     *
     * @param object|array|scalar $data
     * @param array $groups
     * @param string $format
     *
     * @return string|array
     */
    public function serialize($data, array $groups = null, $format = 'array')
    {
        return $this->getSerializer()->serialize($data, $format, $this->createNewContext($groups));
    }

    /**
     * Serialize a paginator to the desired format
     *
     * @param Paginator $paginator
     * @param array|null $groups serialization groups
     * @param string $key root key for items, leave null to return items in root
     * @param string $format serialization format
     * @return string|array
     */
    public function serializePaginator(Paginator $paginator, array $groups = null, $key = null, $format = 'array')
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