<?php

namespace Edge\Doctrine\Fixtures;

use Interop\Container\ContainerInterface;

interface LoaderContainerAwareInterface
{
    /**
     * Set container
     *      *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container);

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer();
}