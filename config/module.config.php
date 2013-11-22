<?php

return array(
    'edge' => array(
        // put your options here
    ),
    'service_manager' => array(
        'factories' => array(
            'Edge\Service\Aws\S3'             => 'Edge\Service\Aws\S3Factory',
            'Edge\Serializer\Serializer'      => 'Edge\Serializer\SerializerFactory',
            'Edge\Filter\File\AmazonUpload'   => 'Edge\Filter\File\AmazonUploadFactory',
            'Edge\Mail\Api\Transport\MailGun' => 'Edge\Mail\Api\Transport\MailGunFactory'
        )
    ),
    'view_helpers' => array(
        'factories' => array(
            'Edge\Serializer\View\Helper\Serialize' => 'Edge\Serializer\View\Helper\SerializeFactory'
        ),
        'aliases' => array(
            'serialize' => 'Edge\Serializer\View\Helper\Serialize'
        )
    ),
    'controller_plugins' => array(
        'factories' => array(
            'Edge\Serializer\Mvc\Controller\Plugin\Serialize' => 'Edge\Serializer\Mvc\Controller\Plugin\SerializeFactory'
        ),
        'aliases' => array(
            'serialize' => 'Edge\Serializer\Mvc\Controller\Plugin\Serialize',
            'outputcsv' => 'Edge\Mvc\Controller\Plugin\OutputCsv'
        ),
        'invokables' => array(
            'Edge\Mvc\Controller\Plugin\OutputCsv' => 'Edge\Mvc\Controller\Plugin\OutputCsv'
        )
    )
);