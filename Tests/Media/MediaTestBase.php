<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Tests\Media;

use PHPUnit_Framework_TestCase;

/**
 * Common base for exahustive test of new and legacy OpenGraphProtocol classes
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
abstract class MediaTestBase extends PHPUnit_Framework_TestCase
{
    abstract protected function createTestMediaObject();

    abstract protected function expectFailure($operation, $failure);

    public function testGettersAndSetters()
    {
        $testObj = $this->createTestMediaObject();
        $this->assertNull($testObj->toString(), 'expected');
        $this->assertEquals(
            array(
                'url' => null,
                'secure_url' => null,
                'type' => null,
                'foo' => null
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertNull($testObj->getURL(), 'expected');
        $this->assertNull($testObj->getSecureURL(), 'expected');
        $this->assertNull($testObj->getType(), 'expected');
        $this->assertNull($testObj->foo, 'expected');
        $this->assertNull($testObj->removeURL(), 'no return value');
        $this->assertEquals(
            array(
                'url' => null,
                'secure_url' => null,
                'type' => null,
                'foo' => null
            ),
            $testObj->toArray(),
            "'url' null but still present"
        );
        $this->assertNull($testObj->getURL(), 'still null');
        $this->assertEquals($testObj, $testObj->setURL('http://www.google.com/images/srpr/logo4w.png'), 'returns self');
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->toString(), 'expected');
        $this->assertEquals(
            array(
                'url' => 'http://www.google.com/images/srpr/logo4w.png',
                'secure_url' => null,
                'type' => null,
                'foo' => null
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->getURL(), 'expected');
        $this->assertNull($testObj->getSecureURL(), 'expected');
        $this->assertNull($testObj->getType(), 'expected');
        $this->assertNull($testObj->foo, 'expected');
        $this->assertEquals($testObj, $testObj->setSecureURL('https://www.google.com/images/srpr/logo4w.png'), 'returns self');
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->toString(), 'no change');
        $this->assertEquals(
            array(
                'url' => 'http://www.google.com/images/srpr/logo4w.png',
                'secure_url' => 'https://www.google.com/images/srpr/logo4w.png',
                'type' => null,
                'foo' => null
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->getURL(), 'no change');
        $this->assertEquals('https://www.google.com/images/srpr/logo4w.png', $testObj->getSecureURL(), 'expected');
        $this->assertNull($testObj->getType(), 'expected');
        $this->assertNull($testObj->foo, 'expected');
        $testObj->foo = 'bar';
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->toString(), 'no change');
        $this->assertEquals(
            array(
                'url' => 'http://www.google.com/images/srpr/logo4w.png',
                'secure_url' => 'https://www.google.com/images/srpr/logo4w.png',
                'type' => null,
                'foo' => 'bar'
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->getURL(), 'no change');
        $this->assertEquals('https://www.google.com/images/srpr/logo4w.png', $testObj->getSecureURL(), 'no change');
        $this->assertNull($testObj->getType(), 'expected');
        $this->assertEquals('bar', $testObj->foo, 'expected');
        $testObj->setType('test/plain');
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->toString(), 'no change');
        $this->assertEquals(
            array(
                'url' => 'http://www.google.com/images/srpr/logo4w.png',
                'secure_url' => 'https://www.google.com/images/srpr/logo4w.png',
                'type' => 'test/plain',
                'foo' => 'bar'
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->getURL(), 'no change');
        $this->assertEquals('https://www.google.com/images/srpr/logo4w.png', $testObj->getSecureURL(), 'no change');
        $this->assertEquals('test/plain', $testObj->getType(), 'expected');
        $this->assertEquals('bar', $testObj->foo, 'no change');
        $this->expectFailure(
                function () use ($testObj) {
                    $testObj->setURL(array());
                },
                "Invalid url: array (\n)"
        );
        $this->assertEquals('http://www.google.com/images/srpr/logo4w.png', $testObj->getURL(), 'no change');
        $this->expectFailure(
                function () use ($testObj) {
                    $testObj->setSecureURL(array());
                },
                "Invalid secure url: array (\n)"
        );
        $this->assertEquals('https://www.google.com/images/srpr/logo4w.png', $testObj->getSecureURL(), 'no change');
        $this->assertNull($testObj->removeURL(), 'no return value');
        $this->assertNull($testObj->getURL(), 'sucessfully cleared');
        $this->assertEquals(
            array(
                'secure_url' => 'https://www.google.com/images/srpr/logo4w.png',
                'type' => 'test/plain',
                'foo' => 'bar'
            ),
            $testObj->toArray(),
            'expected'
        );
        $this->assertEquals($testObj, $testObj->setURL('http://www.yahoo.com/'), 'returns self');
        $this->assertEquals('http://www.yahoo.com/', $testObj->getURL(), 'still setable');
        $this->assertEquals(
            array(
                'url' => 'http://www.yahoo.com/',
                'secure_url' => 'https://www.google.com/images/srpr/logo4w.png',
                'type' => 'test/plain',
                'foo' => 'bar'
            ),
            $testObj->toArray(),
            'expected'
        );
    }
}
