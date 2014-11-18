<?php

namespace BackBuilder\TestUnit\BackBuilder\Util;

use BackBuilder\Util\Dir;
use org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamFile,
    org\bovigo\vfs\vfsStreamDirectory,
    org\bovigo\vfs\vfsStreamWrapper;

class DirTest extends \PHPUnit_Framework_TestCase {

    private $copy_path;

    public function setUp() {
        $this->copy_path = 'file.txt';
    }

    /**
     *
     * @covers \BackBuilder\Util\Dir::copy
     * @covers \BackBuilder\Util\Dir::getContent
     *
     */
    public function testCopy() {
        $dir_mode = 0777;
        $vfs_dir = vfsStream::setup('startpath', $dir_mode, array('startfile' => 'start data'));
        $start_path = vfsStream::url('startpath');

        $copy_path = $this->copy_path;

        $dir_path = Dir::copy($start_path, $copy_path);

        $this->assertEquals(true, $dir_path);

        $this->assertEquals(array_values(Dir::getContent($copy_path)), array_values(Dir::getContent($start_path)));
    }

    /**
     *
     * @covers \BackBuilder\Util\Dir::copy
     * @covers \BackBuilder\Util\Dir::getContent
     *
     */
    public function unreadbleCopy() {
        $dir_mode = 0000;
        $vfs_dir = vfsStream::setup('dircopy', $dir_mode, array('copyfile' => 'copy data'));

        $start_path = vfsStream::url('dircopy');

        $unreadable = vfsStream::setup('copydir', 0000);
        $dir_path1 = Dir::copy($start_path, $unreadable);


        $this->assertEquals(false, $dir_path1);
    }

    /**
     * @covers \BackBuilder\Util\Dir::getContent
     * @expectedException \Exception
     */
    public function testUnknownGetContent() {
        $this->markTestSkipped('Exception catching don\'t work');
        Dir::getContent('unknow');
    }

    /**
     * @covers \BackBuilder\Util\Dir::getContent
     * @expectedException \Exception
     */
    public function testFileGetContent() {
        $this->markTestSkipped('Exception catching don\'t work');
        Dir::getContent('/DirTest.php');
    }

    /**
     * @covers \BackBuilder\Util\Dir::getContent
     *
     */
    public function testGetContent() {
        $test_dir = vfsStream::setup('test_dir');
        $path_dir = vfsStream::url('test_dir');

        $arr1 = array_diff(scandir($path_dir), array('.', '..'));
        $arr2 = Dir::getContent($path_dir);

        $this->assertTrue(is_array($arr2));
        $this->assertEquals($arr2, $arr1);
    }

    /**
     * @covers \BackBuilder\Util\Dir::getContent
     * @expectedException \Exception
     *
     */
    public function testModeFilesGetContent() {
        $this->markTestSkipped('Exception catching don\'t work');
        $test_dir = vfsStream::setup('test_dir', 0000, array('file1' => 'file1 data', 'file2' => 'file2 data'));
        $path_dir = vfsStream::url('test_dir');

        $res = Dir::getContent($path_dir);
        $this->assertEmpty($res);
    }

    /**
     * @covers \BackBuilder\Util\Dir::getContent
     *
     */
    public function testIsArrayGetContent() {
        $test_dir = vfsStream::setup('test_dir', 0777, array('file1' => 'file1 data', 'file2' => 'file2 data'));
        $path_dir = vfsStream::url('test_dir');

        $res = Dir::getContent($path_dir);
        $this->assertTrue(is_array($res));
    }

    /**
     * @covers \BackBuilder\Util\Dir::delete
     *
     */
    public function testDelete() {
        vfsStream::setup('test_dir');
        $path_dir = vfsStream::url('test_dir');

        $res = Dir::delete($path_dir);

        $this->assertEquals(TRUE, $res);
        $this->assertFileNotExists($path_dir);
    }

    /**
     * @covers \BackBuilder\Util\Dir::move
     *
     */
    public function testSimpleMove() {
        $dir_mode = 0777;
        $vfs_dir = vfsStream::setup('dirstart', $dir_mode, array('startfile' => 'start data'));
        $start_path = vfsStream::url('dirstart');

        $copy_path = $this->copy_path;

        $dir_path = Dir::move($start_path, $copy_path, $dir_mode);

        $this->assertEquals(true, $dir_path);
    }

    /**
     * @covers \BackBuilder\Util\Dir::move
     *
     */
    public function testCallback2ParamsMove() {
        $dir_mode = 0777;
        $vfs_dir = vfsStream::setup('dircopy', $dir_mode, array('copyfile' => 'copy data'));

        $start_path = vfsStream::url('dircopy');
        $copy_path = $this->copy_path;

        $dir_path = Dir::move($start_path, $copy_path, $dir_mode, array('self', 'getContent', $start_path));
        $this->assertEquals(true, $dir_path);
    }

    /**
     * @covers \BackBuilder\Util\Dir::move
     *
     */
    public function testCallback3ParamsMove() {
        $dir_mode = 0777;
        $vfs_dir = vfsStream::setup('dircopy', $dir_mode, array('copyfile' => 'copy data'));

        $start_path = vfsStream::url('dircopy');
        $copy_path = $this->copy_path;

        $dir_path = Dir::move($start_path, $copy_path, $dir_mode, array('getContent', $start_path, $copy_path, $dir_mode));
        $this->assertEquals(true, $dir_path);
    }

    public function tearDown() {
        if (is_dir($this->copy_path)) {
            $allfiles = scandir($this->copy_path);
            foreach ($allfiles as $file) {
                if (!is_dir($file)) {
                    unlink($this->copy_path . DIRECTORY_SEPARATOR . $file);
                }
            }

            rmdir($this->copy_path);
        }
    }

}
