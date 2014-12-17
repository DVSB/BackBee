<?php

namespace bbUnit\Util;

use BackBee\Util\String;

class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \BackBee\Util\String::toAscii
     *
     */
    public function testToASCII()
    {
        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII('test')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII('te90-+st\\')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII('accentu�')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("-100")));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("l’avocat", 'UTF-8')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("1345623", 'ISO-8859-1')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("é123", 'UTF-8')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("-100", 'ISO-8859-1')));

        $this->assertEquals('ASCII', mb_detect_encoding(String::toASCII("©ÉÇáñ", 'UTF-8')));
    }

    /**
     * @covers \BackBee\Util\String::toUTF8
     *
     */
    public function testToUTF8()
    {
        $this->assertEquals('UTF-8', mb_detect_encoding(String::toUTF8('aaa'), 'UTF-8', true));

        $this->assertEquals('UTF-8', mb_detect_encoding(String::toUTF8('accentu�'), 'UTF-8', true));

        $this->assertEquals('UTF-8', mb_detect_encoding(String::toUTF8('reçoivent'), 'UTF-8', true));

        $this->assertEquals('UTF-8', mb_detect_encoding(String::toUTF8('©ÉÇáñ'), 'UTF-8', true));
    }

    /**
     *
     * @covers \BackBee\Util\String::toPath
     *
     */
    public function testToPath()
    {
        $options1 = array(
            'extension' => '.txt',
            'spacereplace' => '_',
        );

        $this->assertEquals('test_path.txt', String::toPath('test path', $options1));

        $options2 = array(
            'extension' => '.txt',
            'spacereplace' => '_',
            'lengthlimit' => 5,
        );

        $this->assertEquals('test_.txt', String::toPath('test path', $options2));

        $options3 = array();

        $this->assertEquals('testpath', String::toPath('test path', $options3));

        $options4 = array(
            'extension' => '.jpg',
        );

        $this->assertEquals('testpath.jpg', String::toPath('test path', $options4));

        $options5 = array(
            'spacereplace' => '+',
        );

        $this->assertEquals('test+path', String::toPath('test path', $options5));

        $options6 = array(
            'lengthlimit' => 3,
        );

        $this->assertEquals('tes', String::toPath('test path', $options6));

        $options7 = array(
            'new' => 'aaa',
        );

        $this->assertEquals('testpath', String::toPath('test path', $options7));

        $this->assertEquals('foodefaut.yml', String::toPath('foo/défaut.yml'));
    }

    /**
     *
     * @covers \BackBee\Util\String::urlize
     *
     */
    public function testUrlize()
    {
        $this->assertEquals('test-s-url', String::urlize('test’s url'));

        $this->assertEquals('test-s-url', String::urlize('test\'s url'));

        $this->assertEquals('test-euro-url', String::urlize('test € url'));

        $this->assertEquals('percent-euro', String::urlize('® % € “ ” …'));

        $this->assertEquals('', String::urlize('“ ” …'));

        $this->assertEquals('tests_url.com', String::urlize('test`s url', array(
                    'extension' => '.com',
                    'spacereplace' => '_',
        )));

        $this->assertEquals('tests_u.com', String::urlize('test`s url', array(
                    'extension' => '.com',
                    'spacereplace' => '_',
                    'lengthlimit' => 7,
        )));

        $this->assertEquals('tests#the#url#this#one.com', String::urlize('test`s the.url:this\'one', array(
                    'extension' => '.com',
                    'separators' => '/[.\'’:]+/',
                    'spacereplace' => '#',
        )));
    }

    /**
     *
     * @covers \BackBee\Util\String::toXmlCompliant
     *
     */
    public function testToXmlCompliant()
    {
        $this->assertEquals(' test line ', String::toXmlCompliant('<a> test line </a>'));
        $this->assertEquals('&amp;lt;a&amp;gt; test line &amp;lt;/a&amp;gt;', String::toXmlCompliant('<a> test line </a>', false));
    }

    /**
     *
     * @covers \BackBee\Util\String::br2nl
     *
     */
    public function testBr2nl()
    {
        $this->assertEquals("test aaa \r\ntest bbb \r\ntest ccc \r\n", String::br2nl("test aaa <br> test bbb <br> test ccc <br>"));
        $this->assertEquals("test aaa \r\ntest bbb \r\ntest ccc \r\n", String::br2nl("test aaa <br\> test bbb <br> test ccc <br\>"));
        $this->assertEquals("test aaa \r\ntest bbb \r\ntest ccc", String::br2nl("test aaa <br \> test bbb <br \> test ccc"));
    }

    /**
     *
     * @covers \BackBee\Util\String::truncateText
     */
    public function testTruncateText()
    {
        $this->assertEquals('text +newstring', String::truncateText('text of test ', 2, '+newstring'));
        $this->assertEquals('text of test ', String::truncateText('text of test ', 30, '+newstring'));
        $this->assertEquals('text ', String::truncateText('text ', 10, '+newstring'));
        $this->assertEquals('text+newstring', String::truncateText('text of test', 5, '+newstring', true));
    }

    /**
     *
     * @covers \BackBee\Util\String::formatBytes
     *
     */
    public function testFormatBytes()
    {
        $this->assertEquals('1.953 kb', String::formatBytes(2000, 3));
        $this->assertEquals('553.71094 kb', String::formatBytes(567000, 5));
        $this->assertEquals('553.71 kb', String::formatBytes(567000));
        $this->assertEquals('5.28 gb', String::formatBytes(5670008902));
    }
}
