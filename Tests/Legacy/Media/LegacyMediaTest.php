<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Tests\Legacy\Media;

use ReflectionClass;

use OpenGraphProtocolMedia;
use NiallKennedy\OpenGraphProtocolTools\Tests\Media\MediaTestBase;

class TestMediaObject extends OpenGraphProtocolMedia
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
class LegacyMediaTest extends MediaTestBase
{
    protected function createTestMediaObject()
    {
        return new TestMediaObject();
    }

    protected function expectFailure($operation, $failure)
    {
        $operation();
    }

    public function testBaseClassAbstract()
    {
        $reflection = new ReflectionClass('OpenGraphProtocolMedia');
        $this->assertTrue($reflection->isAbstract(), 'should be abstract');
    }
}
