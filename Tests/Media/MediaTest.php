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

use ReflectionClass;

use NiallKennedy\OpenGraphProtocolTools\Media\Media;
use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception as OgptException;

class TestMediaObject extends Media
{
    public $foo;

    public function setType($value)
    {
        $this->type = $value;
    }
}

/**
 * Exahustive test of OpenGraphProtocol class
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class MediaTest extends MediaTestBase
{
    protected function createTestMediaObject()
    {
        return new TestMediaObject();
    }

    protected function expectFailure($operation, $failure)
    {
        try {
            $operation();
            $this->fail('expected invalid type exception');
        } catch (OgptException $e) {
            $this->assertInstanceOf('NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception', $e, 'correct exception');
            $this->assertEquals($failure, $e->getMessage(), 'correct exception');
        }
    }

    public function testBaseClassAbstract()
    {
        $reflection = new ReflectionClass('NiallKennedy\\OpenGraphProtocolTools\\Media\\Media');
        $this->assertTrue($reflection->isAbstract(), 'should be abstract');
    }
}
