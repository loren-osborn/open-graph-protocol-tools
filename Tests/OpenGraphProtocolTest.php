<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Tests;

use NiallKennedy\OpenGraphProtocolTools\OpenGraphProtocol;
use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception as OgptException;

/**
 * Exahustive test of OpenGraphProtocol class
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class OpenGraphProtocolTest extends OpenGraphProtocolTestBase
{
    const TEST_CLASS_NAME = 'NiallKennedy\\OpenGraphProtocolTools\\OpenGraphProtocol';

    protected function callStaticBuildHTML()
    {
        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'buildHTML'), func_get_args());
    }

    protected function callStaticSupportedTypes()
    {
        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'supportedTypes'), func_get_args());
    }

    protected function callStaticSupportedLocales()
    {
        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'supportedLocales'), func_get_args());
    }

    protected function callStaticIsValidUrl()
    {
        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'isValidUrl'), func_get_args());
    }

    protected function createOpenGraphProtocol()
    {
        return new OpenGraphProtocol();
    }

    protected function getOpenGraphProtocolConstant($name)
    {
        return constant(self::TEST_CLASS_NAME . '::' . $name);
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

    /**
     * @dataProvider getLengthLimitedProperties
     */
    public function testDefaultLengthLimitedPropertyTruncation($setter, $getter, $humanReadable, $property, $maxLength)
    {
        $ogpt = new OpenGraphProtocol();
        $ogpt->$setter(str_repeat('a', $maxLength));
        $this->assertEquals(str_repeat('a', $maxLength), $ogpt->$getter(), 'correct value');
        try {
            $ogpt->$setter(str_repeat('b', ($maxLength + 1)));
            $this->fail('expected length limit exception');
        } catch (OgptException $e) {
            $this->assertInstanceOf('NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception', $e, 'correct exception');
            $this->assertEquals(ucfirst($humanReadable) . ' too long: \'' . str_repeat('b', ($maxLength + 1)) . '\'', $e->getMessage(), 'correct exception');
        }
        $this->assertEquals(str_repeat('a', $maxLength), $ogpt->$getter(), 'did not change');
    }

    public function getMediaToAdd()
    {
        return array(
            array('NiallKennedy\\OpenGraphProtocolTools\\Media\\Image', 'png', 'image'),
            array('NiallKennedy\\OpenGraphProtocolTools\\Media\\Audio', 'mp3', 'audio'),
            array('NiallKennedy\\OpenGraphProtocolTools\\Media\\Video', 'mov', 'video')
        );
    }
}
