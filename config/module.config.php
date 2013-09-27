<?php

return array(
    'edge' => array(
        // put your options here
    ),
    'service_manager' => array(
        'factories' => array(
            'Edge\Service\Aws\S3'           => 'Edge\Service\Aws\S3Factory',
            'Edge\Serializer\Serializer'    => 'Edge\Serializer\SerializerFactory',
            'Edge\Filter\File\AmazonUpload' => 'Edge\Filter\File\AmazonUploadFactory',
        )
    ),
//    'filters' => array(
//        'factories' => array(
//            'Aws\Filter\File\S3RenameUpload' => 'Aws\Factory\S3RenameUploadFactory'
//        ),
//        'aliases' => array(
//            'amazonupload' => 'Aws\Filter\File\S3RenameUpload'
//        )
//    ),
);