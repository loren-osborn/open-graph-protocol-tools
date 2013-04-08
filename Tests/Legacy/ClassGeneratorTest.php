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
use ReflectionProperty;

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
        $classBuilder8 = $generator->getClassBuilder('GlobalEight');
        $classBuilder8->setParent('BozoInc\\SomePackage\\OtherVendorBaseClass');
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
            "    use Acme\\WidgetApp\\Wingbat as WidgetAppWingbat;\n" .
            "\n" .
            "    use OtherVendor\\FooBarApp\\Wingbat as FooBarAppWingbat;\n" .
            "\n" .
            "    use BozoInc\\SomePackage\\OtherVendorBaseClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends \\Acme\\WidgetApp\\BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalFive extends \\OtherVendor\\WidgetApp\\BaseClass\n" .
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
            "\n" .
            "    class GlobalEight extends OtherVendorBaseClass\n" .
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
        $classBuilder8->setParent('BozoInc\\SomePackage\\NonCollidingClass');
        $classBuilderClassAliaisCollision = $generator->getClassBuilder('OtherVendorBaseClass');
        $classBuilder9 = $generator->getClassBuilder('Acme\\WidgetApp\\ClassNine');
        $classBuilder9->setParent('OtherVendorBaseClass');
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
            "    use Acme\\WidgetApp\\Wingbat as WidgetAppWingbat;\n" .
            "\n" .
            "    use OtherVendor\\FooBarApp\\Wingbat as FooBarAppWingbat;\n" .
            "\n" .
            "    use BozoInc\\SomePackage\\NonCollidingClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends \\Acme\\WidgetApp\\BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalFive extends \\OtherVendor\\WidgetApp\\BaseClass\n" .
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
            "\n" .
            "    class GlobalEight extends NonCollidingClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class OtherVendorBaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace Acme\\WidgetApp {\n" .
            "    use GlobalNamespaceClass;\n" .
            "    use OtherVendorBaseClass;\n" .
            "\n" .
            "    class ClassFour extends GlobalNamespaceClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassNine extends OtherVendorBaseClass\n" .
            "    {\n" .
            "    }\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilderClassAliaisCollision->setParent('NonCollidingClass');
        $this->assertEquals(array('NonCollidingClass'), $classBuilderClassAliaisCollision->getUsedClasses(), 'expected');
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
            "    use Acme\\WidgetApp\\Wingbat as WidgetAppWingbat;\n" .
            "\n" .
            "    use OtherVendor\\FooBarApp\\Wingbat as FooBarAppWingbat;\n" .
            "\n" .
            "    use BozoInc\\SomePackage\\NonCollidingClass as SomePackageNonCollidingClass;\n" .
            "\n" .
            "    class GlobalNamespaceClass extends \\Acme\\WidgetApp\\BaseClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class GlobalFive extends \\OtherVendor\\WidgetApp\\BaseClass\n" .
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
            "\n" .
            "    class GlobalEight extends SomePackageNonCollidingClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class OtherVendorBaseClass extends NonCollidingClass\n" .
            "    {\n" .
            "    }\n" .
            "}\n" .
            "\n" .
            "namespace Acme\\WidgetApp {\n" .
            "    use GlobalNamespaceClass;\n" .
            "    use OtherVendorBaseClass;\n" .
            "\n" .
            "    class ClassFour extends GlobalNamespaceClass\n" .
            "    {\n" .
            "    }\n" .
            "\n" .
            "    class ClassNine extends OtherVendorBaseClass\n" .
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

    public function testAddConstants()
    {
        $generator = new ClassGenerator();
        $classBuilder = $generator->getClassBuilder('HasConstants');
        $classBuilder->addConstants(array(
            'ZERO' => 0,
            'HELLO_WORLD' => 'hello world',
            'NOT_TRUE' => false,
            'TWENTY_TWO_OVER_SEVEN' => (22/7)
        ));
        $expectedSource =
            "class HasConstants\n" .
            "{\n" .
            "    const ZERO = 0;\n" .
            "    const HELLO_WORLD = 'hello world';\n" .
            "    const NOT_TRUE = false;\n" .
            "    const TWENTY_TWO_OVER_SEVEN = 3.1428571428571;\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        $classBuilder->addConstants(array('ONE_SMALL_STEP_FOR_MAN' => 'one giant leap for mankind'));
        $expectedSource =
            "class HasConstants\n" .
            "{\n" .
            "    const ZERO = 0;\n" .
            "    const HELLO_WORLD = 'hello world';\n" .
            "    const NOT_TRUE = false;\n" .
            "    const TWENTY_TWO_OVER_SEVEN = 3.1428571428571;\n" .
            "    const ONE_SMALL_STEP_FOR_MAN = 'one giant leap for mankind';\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
        try {
            $classBuilder->addConstants(array('invalid constant name' => 'should throw exception'));
            $this->fail('This should throw an exception');
        } catch (NativePhpException $e) {
            $this->assertEquals('Exception', get_class($e), 'Native PHP Exception');
            $this->assertEquals('"invalid constant name" is not a valid constant name', $e->getMessage(), 'expected error');
        }
        try {
            $classBuilder->addConstants(array('WAR_AND_PEACE' => array('war', 'peace')));
            $this->fail('This should throw an exception');
        } catch (NativePhpException $e) {
            $this->assertEquals('Exception', get_class($e), 'Native PHP Exception');
            $this->assertEquals('Constant WAR_AND_PEACE is not a valid constant type', $e->getMessage(), 'expected error');
        }
        try {
            $classBuilder->addConstants(array('HELLO_WORLD' => 'goodbye world'));
            $this->fail('This should throw an exception');
        } catch (NativePhpException $e) {
            $this->assertEquals('Exception', get_class($e), 'Native PHP Exception');
            $this->assertEquals('Constant HELLO_WORLD is already defined', $e->getMessage(), 'expected error');
        }
    }

    public function testAddProperties()
    {
        $generator = new ClassGenerator();
        $classBuilder = $generator->getClassBuilder('HasProperties');
        $classBuilder->addProperties(array(
            'year' => array(),
            'color' => array('visibility' => ReflectionProperty::IS_PRIVATE),
            'flavor' => array('visibility' => ReflectionProperty::IS_PROTECTED, 'static' => false),
            'name' => array('visibility' => ReflectionProperty::IS_PUBLIC),
            'foo' => array('visibility' => ReflectionProperty::IS_PUBLIC, 'static' => true),
            'fee' => array('visibility' => ReflectionProperty::IS_PUBLIC, 'initialValue' => 5),
            'bar' => array('visibility' => ReflectionProperty::IS_PROTECTED, 'initialValue' => null),
            'baz' => array('visibility' => ReflectionProperty::IS_PUBLIC, 'initialValue' => 'hello world'),
            'boo' => array('visibility' => ReflectionProperty::IS_PRIVATE, 'initialValue' => array(1, 3, 5, 7))
        ));
        $expectedSource =
            "class HasProperties\n" .
            "{\n" .
            "    \$year;\n" .
            "    private \$color;\n" .
            "    protected \$flavor;\n" .
            "    public \$name;\n" .
            "    public static \$foo;\n" .
            "    public \$fee = 5;\n" .
            "    protected \$bar = null;\n" .
            "    public \$baz = 'hello world';\n" .
            "    private \$boo = array (\n" .
            "      0 => 1,\n" .
            "      1 => 3,\n" .
            "      2 => 5,\n" .
            "      3 => 7,\n" .
            "    );\n" .
            "}";
        $this->assertEquals($expectedSource, $generator->getSource(), 'expected');
    }
}
