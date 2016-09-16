<?php

namespace Edge\Markdown\View\Helper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MarkdownFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Markdown
     */
    public function createService(ServiceLocatorInterface $views)
    {
        return new Markdown($views->getServiceLocator()->get('Edge\Markdown\Markdown'));
    }
}