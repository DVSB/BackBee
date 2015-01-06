<?php

namespace BackBuilder\TestUnit\BackBuilder\Util;

use BackBuilder\Util\File;
use org\bovigo\vfs\vfsStream;

class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @covers \BackBuilder\Util\File::realpath
     *
     */
    public function testRealpath()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0777, array('copyfile' => 'copy data'));

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR."FileTest.php", File::realpath(__DIR__.DIRECTORY_SEPARATOR."/FileTest.php"));
        $this->assertEquals(false, File::realpath(DIRECTORY_SEPARATOR."FileTest.php"));

        $path1 = vfsStream::url('dircopy'.DIRECTORY_SEPARATOR.'copyfile');
        $this->assertEquals('vfs://dircopy/copyfile', File::realpath($path1));

        $vfs_dir = vfsStream::setup('dircopy', 0000, array('copyfile' => 'copy data'));

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR."FileTest.php", File::realpath(__DIR__.DIRECTORY_SEPARATOR."FileTest.php"));
        $this->assertEquals(false, File::realpath(DIRECTORY_SEPARATOR."FileTest.php"));

        $path1 = vfsStream::url('dircopy'.DIRECTORY_SEPARATOR.'copyfile');
        $this->assertEquals('vfs://dircopy/copyfile', File::realpath($path1));
    }

    /**
     * @covers \BackBuilder\Util\File::normalizePath
     */
    public function testNormalizePath()
    {
        $dir_mode = 0777;
        $vfs_dir = vfsStream::setup('dircopy', $dir_mode, array('copyfile' => 'copy data'));
        $path = vfsStream::url('dircopy');

        $this->assertEquals('vfs://dircopy', File::normalizePath($path));
        $this->assertEquals('vfs://dircopy/copyfile', File::normalizePath(vfsStream::url('dircopy'.DIRECTORY_SEPARATOR.'copyfile')));
        $this->assertEquals('vfs:'.DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'dircopy'.DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'copyfile', File::normalizePath(vfsStream::url('dircopy//copyfile'), DIRECTORY_SEPARATOR, false));
        $this->assertEquals('vfs:////dircopy////copyfile', File::normalizePath(vfsStream::url('dircopy'.DIRECTORY_SEPARATOR.'copyfile'), '////', false));
        $this->assertEquals('vfs:////dircopy////copyfile', File::normalizePath(vfsStream::url('dircopy'.DIRECTORY_SEPARATOR.'copyfile'), '////'));
        $this->assertEquals('vfs:\\\dircopy', File::normalizePath(vfsStream::url('dircopy'), '\\'));

        $this->assertEquals('vfs://dircopy/copyfile', File::normalizePath('vfs://dircopy/copyfile'));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::readableFilesize
     *
     */
    public function testReadableFilesize()
    {
        $this->assertEquals('1.953 kB', File::readableFilesize(2000, 3));
        $this->assertEquals('553.71094 kB', File::readableFilesize(567000, 5));
        $this->assertEquals('553.71 kB', File::readableFilesize(567000));
        $this->assertEquals('5.28 GB', File::readableFilesize(5670008902));
        $this->assertEquals('0.00 B', File::readableFilesize(0));
    }

    /**
     *  @covers \BackBuilder\Util\File::getExtension
     */
    public function testGetExtension()
    {
        $this->assertEquals('.txt', File::getExtension('test.txt', true));
        $this->assertEquals('jpg', File::getExtension('test.jpg', false));
        $this->assertEquals('', File::getExtension('test', false));
        $this->assertEquals('', File::getExtension('test', true));
        $this->assertEquals('', File::getExtension('', true));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::removeExtension
     *
     */
    public function testRemoveExtension()
    {
        $this->assertEquals('test', File::removeExtension('test.txt'));
        $this->assertEquals('', File::removeExtension('.txt'));
        $this->assertEquals('', File::removeExtension(''));
        $this->assertEquals('test', File::removeExtension('test'));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::mkdir
     *
     */
    public function testExistingDirMkdir()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0755, array('copyfile' => 'copy data'));
        $path = vfsStream::url('dircopy');
        $this->assertTrue(File::mkdir($path));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::mkdir
     * @expectedException \BackBuilder\Exception\InvalidArgumentsException
     */
    public function testExistingDirMkdirWithBadRights()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0000, array('copyfile' => 'copy data'));
        $path = vfsStream::url('dircopy');

        File::mkdir($path);
    }

    /**
     *
     * @covers \BackBuilder\Util\File::mkdir
     * @expectedException \BackBuilder\Exception\InvalidArgumentsException
     */
    public function testUnknownDirMkdir()
    {
        File::mkdir('');
        File::mkdir(null);
    }

    /**
     *
     * @covers \BackBuilder\Util\File::copy
     * @expectedException \BackBuilder\Exception\InvalidArgumentsException
     *
     */
    public function unreadbleCopy()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0000, array('copyfile' => 'copy data'));

        $start_path = vfsStream::url('dircopy');

        $unreadable = 'file.txt';
        File::copy($start_path, $unreadable);
    }

    /**
     *
     * @covers \BackBuilder\Util\File::getFilesRecursivelyByExtension
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testUnredableGetFilesRecursivelyByExtension()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0000, array('copyfile' => 'copy data'));
        $path = vfsStream::url('dircopy');
        File::getFilesRecursivelyByExtension($path, '.txt');
        File::getFilesRecursivelyByExtension('', '');
    }

    /**
     *
     * @covers \BackBuilder\Util\File::getFilesRecursivelyByExtension
     */
    public function testGetFilesRecursivelyByExtension()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0775, array(
            'copyfile.txt' => 'copy data', 'file2.txt' => 'copy data',
            'file3.php' => 'copy data', 'file4.yml' => 'copy data',
            'noextension' => 'data',
        ));
        $path = vfsStream::url('dircopy');

        $this->assertEquals(array('vfs://dircopy'.DIRECTORY_SEPARATOR.'copyfile.txt', 'vfs://dircopy'.DIRECTORY_SEPARATOR.'file2.txt'), File::getFilesRecursivelyByExtension($path, 'txt'));
        $this->assertEquals(array('vfs://dircopy'.DIRECTORY_SEPARATOR.'file3.php'), File::getFilesRecursivelyByExtension($path, 'php'));
        $this->assertEquals(array('vfs://dircopy'.DIRECTORY_SEPARATOR.'file4.yml'), File::getFilesRecursivelyByExtension($path, 'yml'));
        $this->assertEquals(['vfs://dircopy'.DIRECTORY_SEPARATOR.'noextension'], File::getFilesRecursivelyByExtension($path, ''));
        $this->assertEquals([], File::getFilesRecursivelyByExtension($path, 'aaa'));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::getFilesByExtension
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testUnredableGetFilesByExtension()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0000, array('copyfile' => 'copy data'));
        $path = vfsStream::url('dircopy');
        File::getFilesByExtension($path, '.txt');
        File::getFilesByExtension('', '');
    }

    /**
     *
     * @covers \BackBuilder\Util\File::getFilesByExtension
     */
    public function testGetFilesByExtension()
    {
        $vfs_dir = vfsStream::setup('dircopy', 0775, array('copyfile.txt' => 'copy data', 'file2.txt' => 'copy data', 'file3.php' => 'copy data', 'file4.yml' => 'copy data'));
        $path = vfsStream::url('dircopy');

        $this->assertEquals(array('vfs://dircopy/copyfile.txt', 'vfs://dircopy/file2.txt'), File::getFilesByExtension($path, 'txt'));
        $this->assertEquals(array('vfs://dircopy/file3.php'), File::getFilesByExtension($path, 'php'));
        $this->assertEquals(array('vfs://dircopy/file4.yml'), File::getFilesByExtension($path, 'yml'));
        $this->assertEquals(array(), File::getFilesByExtension($path, ''));
        $this->assertEquals(array(), File::getFilesByExtension($path, 'aaa'));
    }

    /**
     *
     * @covers \BackBuilder\Util\File::extractZipArchive
     * @expectedException Exception
     */
    public function testExtractZipArchiveNonexistentDir()
    {
        File::extractZipArchive('test', 'test');
    }

    /**
     *
     * @covers \BackBuilder\Util\File::extractZipArchive
     * @expectedException Exception
     */
    public function testExtractZipArchiveUnreadableDir()
    {
        $vfs_dir = vfsStream::setup('dirzip', 0000);
        $path_zip = vfsStream::url('dirzip');
        File::extractZipArchive('test', $vfs_dir->path());
    }

    /**
     *
     * @covers \BackBuilder\Util\File::extractZipArchive
     * @expectedException Exception
     */
    public function testExtractZipArchiveExistingDir()
    {
        /**
         * @todo ext/zip PHP extension does not work with vfsStream
         * @link https://github.com/mikey179/vfsStream/wiki/Known-Issues
         */
        $this->markTestSkipped();

        vfsStream::setup('dirzip', 0777);
        $path_zip = vfsStream::url('dirzip');
        File::extractZipArchive('test', $path_zip, true);
    }

    public function test_resolveFilepath()
    {
        $path = "vfs://test/file.twig";
        File::resolveFilepath($path);
        $this->assertEquals("vfs://test/file.twig", $path);
    }
}
