<?php

namespace Edge\Markdown\View\Helper;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MarkdownFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Markdown
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Markdown($container->get('Edge\Markdown\Markdown'));
    }
}