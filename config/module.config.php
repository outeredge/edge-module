<?php

return array(
    'edge' => array(
        'doctrine' => array(
            'fixtures' => []
        ),
        'serializer' => array(
            'debug'     => false,
            'cache_dir' => null
        )
    ),
    'service_manager' => array(
        'aliases' => array(
            'EntityManager' => 'Doctrine\ORM\EntityManager'
        ),
        'factories' => array(
            'Edge\Service\Aws\S3'             => 'Edge\Service\Aws\S3Factory',
            'Edge\Serializer\Serializer'      => 'Edge\Serializer\SerializerFactory',
            'Edge\Filter\File\AmazonUpload'   => 'Edge\Filter\File\AmazonUploadFactory',
            'Edge\Mail\Api\Transport\MailGun' => 'Edge\Mail\Api\Transport\MailGunFactory',
            'Edge\Doctrine\Fixtures\Loader'   => 'Edge\Doctrine\Fixtures\LoaderFactory',
            'Edge\Rest\JsonRenderer'          => 'Edge\Rest\JsonRendererFactory',
            'Edge\Rest\JsonStrategy'          => 'Edge\Rest\JsonStrategyFactory'
        ),
        'invokables' => array(
            'Edge\Markdown\Markdown'          => 'Edge\Markdown\Markdown'
        )
    ),
    'view_helpers' => array(
        'factories' => array(
            'Edge\Serializer\View\Helper\Serialize' => 'Edge\Serializer\View\Helper\SerializeFactory',
            'Edge\Markdown\View\Helper\Markdown' => 'Edge\Markdown\View\Helper\MarkdownFactory'
        ),
        'aliases' => array(
            'serialize' => 'Edge\Serializer\View\Helper\Serialize',
            'markdown'  => 'Edge\Markdown\View\Helper\Markdown'
        )
    ),
    'controller_plugins' => array(
        'factories' => array(
            'Edge\Serializer\Mvc\Controller\Plugin\Serialize' => 'Edge\Serializer\Mvc\Controller\Plugin\SerializeFactory'
        ),
        'aliases' => array(
            'serialize' => 'Edge\Serializer\Mvc\Controller\Plugin\Serialize',
            'outputCsv' => 'Edge\Mvc\Controller\Plugin\OutputCsv'
        ),
        'invokables' => array(
            'Edge\Mvc\Controller\Plugin\OutputCsv' => 'Edge\Mvc\Controller\Plugin\OutputCsv'
        )
    )
);
