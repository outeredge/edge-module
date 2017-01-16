<?php

namespace Edge\Doctrine\Fixtures;

use Interop\Container\ContainerInterface;

trait LoaderContainerTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * Set container
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}