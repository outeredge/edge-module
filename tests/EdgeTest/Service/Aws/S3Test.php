<?php

namespace Edge\Service;

use PHPUnit_Framework_TestCase;

class S3Test extends PHPUnit_Framework_TestCase
{
//    /**
//     * @var AmazonS3
//     */
//    protected $service;

//    public function setUp()
//    {
//        $config = $this->getServiceManager()->get('Configuration');
//        $config = $config['zebreco']['amazon'];
//
//        $s3     = $this->getServiceManager()->get('aws')->get('s3');
//
//        $this->service = new AmazonS3($s3, $config);
//    }
//
//    protected function getService()
//    {
//        return $this->service;
//    }
//
    public function testUploadFile()
    {
//        $this->getService()->uploadFile('attachment_0000.txt', 1, __DIR__ . '/Assets/attachment_0000.txt');
    }
//
//    /**
//     * @depends testUploadFile
//     */
//    public function testDeleteFile()
//    {
//        $this->getService()->deleteFile('attachment_0000.txt', 1);
//    }
//
//    public function testGetDownloadPath()
//    {
//        $uniquename = 'a79fsdfabbd_test+file.txt';
//        $filename   = 'test david.txt';
//        $account    = 1;
//
//        $this->assertContains(
//           sprintf('https://s3-eu-west-1.amazonaws.com/zebreco-test/%s/%s?response-content-disposition=attachment;filename="%s"&AWSAccessKeyId=', $account, $uniquename, $filename),
//           urldecode($this->getService()->getDownloadPath($uniquename, $filename, $account))
//        );
//    }
}