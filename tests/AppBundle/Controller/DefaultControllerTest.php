<?php

namespace Tests\AppBundle\Controller;

use AppBundle\DataFixtures\ORM\LoadUserData;
use AppBundle\Utils\FileManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class DefaultControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Создает тестового юзера TestUser:TestPassword
     */
    private function createTestUser()
    {
        $factory = $this->client->getContainer()->get('security.encoder_factory');
        $userManager = $this->client->getContainer()->get('fos_user.user_manager');

        $user = $userManager->findUserByUsername('test_user');
        if ($user) {
            if (!$factory->getEncoder($user)->isPasswordValid($user->getPassword(), 'test_password', $user->getSalt())) {
                $user->setPlainPassword('test_password');
                $userManager->updateUser($user);
            }
        }
        else {
            $user = $userManager->createUser();
            $user->setUsername('test_user');
            $user->setEmail('test_user@email.com');
            $user->setPlainPassword('test_password');
            $user->setEnabled(true);
            $userManager->updateUser($user);
        }
    }

    public function setUp()
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test_user',
            'PHP_AUTH_PW'   => 'test_password',
        ));

        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->createTestUser();
    }

    public function tearDown()
    {
    }

    public function testFilesList()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('listDir')
            ->with('C:\\test')
            ->willReturn(['asd.ttt']);

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
            ->willReturn(['x' => 'y']);

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

    public function testPutFileOK()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->once())
            ->method('saveFile')
            ->with($this->anything(), 'C:\\test', '2.txt');

        $this->client->getContainer()->set('file_manager', $fakeManager);

        $file = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($file, '12345');
        $upload = new UploadedFile($file, '1.txt');

        $this->client->request('PUT', '/files/2.txt',
            array('content' => $upload)
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPutFileWrongName()
    {
        $fakeManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeManager->expects($this->never())
            ->method('saveFile');

        $this->client->getContainer()->set('file_manager', $fakeManager);

        $file = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($file, '12345');
        $upload = new UploadedFile($file, '1.txt');

        $this->client->request('PUT', '/files/ddds',
            array('content' => $upload)
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('error', json_decode($this->client->getResponse()->getContent()));
    }
}
