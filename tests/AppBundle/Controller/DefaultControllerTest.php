<?php

namespace Tests\AppBundle\Controller;

use AppBundle\DataFixtures\ORM\LoadUserData;
use AppBundle\Utils\FileManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class DefaultControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW'   => 'password',
        ));
        $em = $this->client->getContainer()->get('doctrine')->getManager();

        $fixture = new LoadUserData();
        $fixture->setContainer($this->client->getContainer());
        //$fixture->load($em);

        $loader = new Loader();
        $loader->addFixture($fixture);

        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testFilesList()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('listDir')
            ->with('C:\\test')
            ->willReturn(['asd.ttt'])
        ;

        $this->client->getContainer()->set('file_manager', $fakeManager);
        $this->client->request('GET', '/files');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            ["asd.ttt"],
            json_decode($this->client->getResponse()->getContent()));
    }

    public function testGetFileMetaAction()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getMeta')
            ->with('C:\\test\\myfile')
            ->willReturn(['x' => 'y'])
        ;

        $this->client->getContainer()->set('file_manager', $fakeManager);
        $this->client->request('GET', '/files/myfile/meta');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            (object) array('x' => 'y'),
            json_decode($this->client->getResponse()->getContent()));
    }

    public function testGetFileMetaActionNotExist()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getMeta')
            ->with('C:\\test\\myfile')
            ->willReturn(null);

        $this->client->getContainer()->set('file_manager', $fakeManager);
        $this->client->request('GET', '/files/myfile/meta');

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            (object) array('error' => 'File not found'),
            json_decode($this->client->getResponse()->getContent()));
    }

    public function testGetFileContentActionTxt()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getContent')
            ->with('C:\\test\\myfile')
            ->willReturn('blabla');
        $fakeManager->expects($this->once())
            ->method('getMIME')
            ->with('C:\\test\\myfile')
            ->willReturn('MimeType');

        $this->client->getContainer()->set('file_manager', $fakeManager);
        $this->client->request('GET', '/files/myfile');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            'blabla',
            $this->client->getResponse()->getContent());
        $this->assertEquals(
            'MimeType',
            $this->client->getResponse()->headers->get('Content-Type'));

    }

    public function testGetFileContentActionNotExist()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getContent')
            ->with('C:\\test\\myfile')
            ->willReturn(null);

        $this->client->getContainer()->set('file_manager', $fakeManager);
        $this->client->request('GET', '/files/myfile');

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            (object) array('error' => 'File not found'),
            json_decode($this->client->getResponse()->getContent()));
    }

    public function testCreateFileOK()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getMeta')
            ->with('C:\\test\\2.txt')
            ->willReturn(null);
        $fakeManager->expects($this->once())
            ->method('saveFile')
            ->with($this->anything(), 'C:\\test', '2.txt');

        $this->client->getContainer()->set('file_manager', $fakeManager);

        $file = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($file, '12345');
        $upload = new UploadedFile($file, '1.txt');

        // Первый раз - ОК
        $this->client->request('POST', '/files',
            array('filename' => '2.txt'),
            array('content' => $upload)
        );
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateFileAlreadyExists()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('getMeta')
            ->with('C:\\test\\2.txt')
            ->willReturn(array('dev' => 10));
        $fakeManager->expects($this->never())
            ->method('saveFile');

        $this->client->getContainer()->set('file_manager', $fakeManager);

        $file = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($file, '12345');
        $upload = new UploadedFile($file, '1.txt');

        // Первый раз - ОК
        $this->client->request('POST', '/files',
            array('filename' => '2.txt'),
            array('content' => $upload)
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            (object) array('error' => 'This file already exists. Please, choose another file name.'),
            json_decode($this->client->getResponse()->getContent()));
    }
}
