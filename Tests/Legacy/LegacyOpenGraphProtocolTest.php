<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Tests\Legacy;

use OpenGraphProtocol;

use NiallKennedy\OpenGraphProtocolTools\Tests\OpenGraphProtocolTestBase;

/**
 * Exahustive test of legacy OpenGraphProtocol class
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class LegacyOpenGraphProtocolTest extends OpenGraphProtocolTestBase
{
    const TEST_CLASS_NAME = 'OpenGraphProtocol';

    public function loadTestClasses()
    {
        $legacyLoader = new LegacyClassLoader();
        $legacyLoader->testLoad($this);
    }

    public function setup()
    {
        $this->loadTestClasses();
    }

    protected function callStaticBuildHTML()
    {
        $this->loadTestClasses();

        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'buildHTML'), func_get_args());
    }

    protected function callStaticSupportedTypes()
    {
        $this->loadTestClasses();

        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'supported_types'), func_get_args());
    }

    protected function callStaticSupportedLocales()
    {
        $this->loadTestClasses();

        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'supported_locales'), func_get_args());
    }

    protected function callStaticIsValidUrl()
    {
        $this->loadTestClasses();

        return forward_static_call_array(array(self::TEST_CLASS_NAME, 'is_valid_url'), func_get_args());
    }

    protected function createOpenGraphProtocol()
    {
        $this->loadTestClasses();

        return new OpenGraphProtocol();
    }

    protected function getOpenGraphProtocolConstant($name)
    {
        $this->loadTestClasses();

        return constant(self::TEST_CLASS_NAME . '::' . $name);
    }

    protected function expectFailure($operation, $failure)
    {
        $operation();
    }

    public function getMediaToAdd()
    {
        return array(
            array('OpenGraphProtocolImage', 'png', 'image'),
            array('OpenGraphProtocolAudio', 'mp3', 'audio'),
            array('OpenGraphProtocolVideo', 'mov', 'video')
        );
    }

    /**
     * @dataProvider getLengthLimitedProperties
     */
    public function testDefaultLengthLimitedPropertyTruncation($setter, $getter, $humanReadable, $property, $maxLength)
    {
        $ogpt = new OpenGraphProtocol();
        $this->assertEquals($ogpt->$setter(str_repeat('c', ($maxLength + 1))), $ogpt, 'should return self');
        $this->assertEquals(str_repeat('c', $maxLength), $ogpt->$getter(), 'correct value');
    }
}
