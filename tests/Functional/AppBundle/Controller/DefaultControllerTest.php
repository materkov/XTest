<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Utils\FileManager;
use Doctrine\ORM\EntityManager;
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
     * @var
     */
    private $dir;

    /**
     * Создает тестового юзера test_user:test_password
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

        $this->createTestUser();
        $this->dir = $this->client->getContainer()->getParameter('files_dir');
    }

    public function tearDown()
    {
    }

    public function testPutFileNotExists()
    {
        // Clear dir
        array_map('unlink', glob($this->dir.'/*'));
        $path = $this->dir.DIRECTORY_SEPARATOR.'1.txt';

        $this->client->request('PUT', '/files/1.txt', array('x'=>'10'), array(), array(), 'FileContent');
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('FileContent', file_get_contents($path));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @depends testPutFileNotExists
     */
    public function testPutFileAlreadyExists()
    {
        // File 1.txt already exists in this test
        $path = $this->dir.DIRECTORY_SEPARATOR.'1.txt';

        $this->client->request('PUT', '/files/1.txt', array(), array(), array(), '222FileContent');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('222FileContent', file_get_contents($path));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testPutFileEmpty()
    {
        $this->client->request('PUT', '/files/1.txt', array(), array(), array(), '');
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('error', (array)json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testPutFileWrongName()
    {
        $this->client->request('PUT', '/files/WRONG_NAME', array(), array(), array(), '');
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('error', (array)json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @depends testPutFileNotExists
     */
    public function testFilesList()
    {
        $this->client->request('GET', '/files');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(['1.txt'], json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @depends testPutFileNotExists
     */
    public function testGetFileMetaAction()
    {
        $this->client->request('GET', '/files/1.txt/meta');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('dev', (array)json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testGetFileMetaActionNotExists()
    {
        $this->client->request('GET', '/files/not_existing_file.txt/meta');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('error', (array)json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @depends testPutFileNotExists
     */
    public function testGetFileContentAction()
    {
        $this->client->request('GET', '/files/1.txt');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('222FileContent', $this->client->getResponse()->getContent());
        $this->assertContains('text/plain', $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testGetFileContentActionNotExists()
    {
        $this->client->request('GET', '/files/not_existing_file.txt/meta');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertArrayHasKey('error', (array)json_decode($this->client->getResponse()->getContent()));
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }
}
