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

use PHPUnit_Framework_TestCase;
use Exception as NativePhpException;

use NiallKennedy\OpenGraphProtocolTools\Legacy\ClassGenerator;

/**
 * Test class for Legacy class generator
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class ClassGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function testNamespacesAndInheritence()
    {
        $generator = new ClassGenerator();
        $this->assertInstanceOf('NiallKennedy\\OpenGraphProtocolTools\\Legacy\\ClassGenerator', $generator, 'correct class');
        $classBuilder1 = $generator->getClassBuilder('GlobalNamespaceClass');
        $this->assertNotNull($classBuilder1, 'value');
        $classBuilder1->setParent('Acme\\WidgetApp\\BaseClass');
        $expectedSource = 
            "use Acme\\WidgetApp\\BaseClass;\n" .
            "\n" .
            "class GlobalNamespaceClass extends BaseClass\n" .
            "{\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $generator = new ClassGenerator();
        $this->assertInstanceOf('NiallKennedy\\OpenGraphProtocolTools\\Legacy\\ClassGenerator', $generator, 'correct class');
        $classBuilder1 = $generator->getClassBuilder('GlobalNamespaceClass');
        $this->assertNotNull($classBuilder1, 'value');
        $expectedSource = 
            "class GlobalNamespaceClass\n" .
            "{\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilder2 = $generator->getClassBuilder('Acme\\WidgetApp\\BaseClass');
        $expectedSource = 
            "namespace {\n" .
            "    class GlobalNamespaceClass\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace Acme\\WidgetApp {\n" .
            "    class BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilder1->setParent('Acme\\WidgetApp\\BaseClass');
        $expectedSource = 
            "namespace Acme\\WidgetApp {\n" .
            "    class BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace {\n" .
            "    use Acme\\WidgetApp\\BaseClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilder3 = $generator->getClassBuilder('Acme\\WidgetApp\\ClassThree');
        $classBuilder4 = $generator->getClassBuilder('Acme\\WidgetApp\\ClassFour');
        $expectedSource = 
            "namespace Acme\\WidgetApp {\n" .
            "    class BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassThree\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassFour\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace {\n" .
            "    use Acme\\WidgetApp\\BaseClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilder4->setParent('GlobalNamespaceClass');
        $expectedSource = 
            "namespace Acme\\WidgetApp {\n" .
            "    class BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassThree\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace {\n" .
            "    use Acme\\WidgetApp\\BaseClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace Acme\\WidgetApp {\n" .
            "    use GlobalNamespaceClass;\n" .
            "\n" .
            "    class ClassFour extends GlobalNamespaceClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        try {
            $classBuilder2->setParent('Acme\\WidgetApp\\ClassFour');
            $this->fail('This should throw an exception');
        } catch (NativePhpException $e) {
            $this->assertEquals('Exception', get_class($e), 'Native PHP Exception');
            $this->assertEquals('Inheritence cycles are illegal', $e->getMessage(), 'expected error');
        }
        $classBuilder5 = $generator->getClassBuilder('GlobalFive');
        $classBuilder6 = $generator->getClassBuilder('GlobalSix');
        $classBuilder7 = $generator->getClassBuilder('GlobalSeven');
        $classBuilder5->setParent('OtherVendor\\WidgetApp\\BaseClass');
        $classBuilder6->setParent('Acme\\WidgetApp\\Wingbat');
        $classBuilder7->setParent('OtherVendor\\FooBarApp\\Wingbat');
        $expectedSource = 
            "namespace Acme\\WidgetApp {\n" .
            "    class BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassThree\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace {\n" .
            "    use Acme\\WidgetApp\\BaseClass as AcmeBaseClass;\n" .
            "    use Acme\\WidgetApp\\Wingbat as WidgetAppWingbat;\n" .
            "\n" .
            "    use OtherVendor\\WidgetApp\\BaseClass as OtherVendorBaseClass;\n" .
            "    use OtherVendor\\FooBarApp\\Wingbat as FooBarAppWingbat;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends AcmeBaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalFive extends OtherVendorBaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalSix extends WidgetAppWingbat\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalSeven extends FooBarAppWingbat\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace Acme\\WidgetApp {\n" .
            "    use GlobalNamespaceClass;\n" .
            "\n" .
            "    class ClassFour extends GlobalNamespaceClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
    }
    
    public function testExistingClass()
    {
        $generator = new ClassGenerator();
        try {
            $generator->getClassBuilder('DateTime');
            $this->fail('This should throw an exception');
        } catch (NativePhpException $e) {
            $this->assertEquals('Exception', get_class($e), 'Native PHP Exception');
            $this->assertEquals('Class DateTime already defined', $e->getMessage(), 'expected error');
        }
    }
    
    public function testSameObjectForClass()
    {
        $generator = new ClassGenerator();
        $classBuilder1 = $generator->getClassBuilder('Acme\\WidgetApp\\Class');
        $classBuilder2 = $generator->getClassBuilder('Acme\\WidgetApp\\Class');
        $this->assertEquals(spl_object_hash($classBuilder1), spl_object_hash($classBuilder2), 'same instance');
    }
}