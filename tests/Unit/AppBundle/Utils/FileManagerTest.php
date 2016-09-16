<?php
/**
 * Created by PhpStorm.
 * User: Maksim
 * Date: 14.09.2016
 * Time: 18:54
 */

namespace tests\AppBundle\Utils;


use AppBundle\Utils\FileManager;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManagerTest extends \PHPUnit_Framework_TestCase
{
    private $dir;

    /**
     * @var FileManager
     */
    private $manager;

    public function setUp()
    {
        $this->dir = vfsStream::setup('exampleDir');
        $this->manager = new FileManager();
    }

    public function testListDirManyFiles()
    {
        vfsStream::newFile('1.txt')->at($this->dir)->setContent("11");
        vfsStream::newFile('2.txt')->at($this->dir)->setContent("222");

        $files = $this->manager->listDir(vfsStream::url('exampleDir'));

        $this->assertEquals(['1.txt', '2.txt'], $files);
    }

    public function testListDirEmpty()
    {
        $files = $this->manager->listDir(vfsStream::url('exampleDir'));

        $this->assertEquals([], $files);
    }

    public function testGetMeta()
    {
        vfsStream::newFile('1.txt')->at($this->dir)->setContent("13");

        $meta = $this->manager->getMeta(vfsStream::url('exampleDir/1.txt'));

        $keys = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $meta);
        }

        $wrongKeys = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        foreach ($wrongKeys as $key) {
            $this->assertArrayNotHasKey($key, $meta);
        }
    }

    public function testGetMetaNotExist()
    {
        $meta = $this->manager->getMeta(vfsStream::url('exampleDir/1.txt'));
        $this->assertNull($meta);
    }

    public function testGetContent()
    {
        vfsStream::newFile('1.txt')->at($this->dir)->setContent('13');
        $content = $this->manager->getContent(vfsStream::url('exampleDir/1.txt'));
        $this->assertEquals('13', $content);
    }

    public function testGetContentNotExist()
    {
        $content = $this->manager->getContent(vfsStream::url('exampleDir/1.txt'));
        $this->assertNull($content);
    }

    public function testGetMIME()
    {
        vfsStream::newFile('1.txt')->at($this->dir)->setContent('13');
        $content = $this->manager->getMIME(vfsStream::url('exampleDir/1.txt'));
        $this->assertEquals('text/plain', $content);
    }

    public function testGetMIMENotExist()
    {
        $content = $this->manager->getContent(vfsStream::url('exampleDir/1.txt'));
        $this->assertNull($content);
    }

    public function testSaveFile()
    {
        $this->manager->saveFile(vfsStream::url('exampleDir/1.txt'), 'inputFileContent__');
        $this->assertEquals(
            'inputFileContent__',
            file_get_contents(vfsStream::url('exampleDir/1.txt')));
    }
}
