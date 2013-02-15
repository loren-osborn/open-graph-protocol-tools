<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 2.0
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Legacy;

use NiallKennedy\OpenGraphProtocolTools\Utils\Inflector;
use Exception;
use ReflectionClass;

/**
 * Ploxy object to provide backward compatibility
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class BackwardCompatibility
{
    const PACKAGE_NAMESPACE                               = 'NiallKennedy\\OpenGraphProtocolTools';

    private $proxiedObject;
    private static $classCreationChecklist = array();

    private static function getLegacyClassNameMap()
    {
        return array(
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolMedia'          => 'OpenGraphProtocolMedia',
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolAudio'          => 'OpenGraphProtocolAudio',
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVisualMedia'    => 'OpenGraphProtocolVisualMedia',
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolImage'          => 'OpenGraphProtocolImage',
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVideo'          => 'OpenGraphProtocolVideo',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject'       => 'OpenGraphProtocolObject',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolArticle'      => 'OpenGraphProtocolArticle',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolBook'         => 'OpenGraphProtocolBook',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolProfile'      => 'OpenGraphProtocolProfile',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoObject'  => 'OpenGraphProtocolVideoObject',
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoEpisode' => 'OpenGraphProtocolVideoEpisode',
            self::PACKAGE_NAMESPACE . '\\OpenGraphProtocol'                      => 'OpenGraphProtocol'
        );
    }

    protected function __construct($objectToProxy)
    {
        $legacyClassNameMap = self::getLegacyClassNameMap();
        if (!array_key_exists(get_class($objectToProxy), $legacyClassNameMap)) {
            throw new Exception('Internal error: unknown class: ' . get_class($objectToProxy));
        }
        $this->proxiedObject = $objectToProxy;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->proxiedObject, $name)) {
            $cleanArgs = self::cleanProxyCallArgs(get_class($this->proxiedObject), $name, $arguments);
            $rawResults = call_user_func_array(array($this->proxiedObject, $name), $cleanArgs);

            return self::cleanProxyCallResult(get_class($this->proxiedObject), $name, $rawResults);
        }
        throw new Exception('No such method: ' . $name);
    }

    private static function cleanProxyCallArgs($class, $method, $arguments)
    {
        $result = array();
        foreach ($arguments as $arg) {
            if ($arg instanceof self) {
                $result[] = $arg->proxiedObject;
            } else {
                $result[] = $arg;
            }
        }

        return $result;
    }

    private static function cleanProxyCallResult($class, $method, $rawResult)
    {
        $result = $rawResult;
        if ($method == 'toArray') {
            $result = array();
            foreach ($rawResult as $key => $value) {
                $inflectedKey = Inflector::inflect($key, Inflector::INFLECTION_CAMEL_CASE, Inflector::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS);
                $result[$inflectedKey] = $value;
            }
        }

        return $result;
    }

    protected static function callStaticInternal($name, $arguments, $className)
    {
        $inflectedName = Inflector::inflect($name, Inflector::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS, Inflector::INFLECTION_CAMEL_CASE);
        if (method_exists($className, $inflectedName)) {
            $result = forward_static_call_array(array($className, $inflectedName), $arguments);

            return $result;
        }
        throw new Exception('No such method: ' . $name);
    }

    public static function createProxyClasses()
    {
        $legacyClassNameMap = self::getLegacyClassNameMap();
        foreach ($legacyClassNameMap as $className => $legacyClassName) {
            if (!array_key_exists($className, self::$classCreationChecklist)) {
                self::createProxyClass($className, $legacyClassName, $legacyClassNameMap);
            }
        }
    }

    private static function createProxyClass($className, $legacyClassName, $legacyClassNameMap)
    {
        $reflection = new ReflectionClass($className);
        $parent     = __CLASS__;
        if ($reflection->getParentClass()) {
            $parentClassName = $reflection->getParentClass()->getName();
            if (!array_key_exists($parentClassName, self::$classCreationChecklist)) {
                self::createProxyClass($legacyClassNameMap[$parentClassName], $parentClassName, $legacyClassNameMap);
            }
            $parent = $legacyClassNameMap[$parentClassName];
        }
        $abstract  = $reflection->isAbstract() ? 'abstract' : '';
        $constants = '';
        foreach ($reflection->getConstants() as $constName => $constValue) {
            $constants .= "const {$constName} = " . var_export($constValue, true) . '; ';
        }
        $compatibilityClassSource =
            "{$abstract} class {$legacyClassName} extends {$parent}" .
            '{ ' .
                $constants .
                /* No legacy classes have constructors that take arguments. */
                'public function __construct() ' .
                '{' .
                    __CLASS__ . '::__construct(new ' . $className . '());' .
                '} ' .
                'static public function __callStatic($name, $arguments) ' .
                '{' .
                    'return ' . __CLASS__ . '::callStaticInternal($name, $arguments, \'' . preg_replace('/\\\\/', '\\\\', $className) . '\');' .
                '}' .
            '}';
        eval($compatibilityClassSource);
        self::$classCreationChecklist[$className] = true;
    }
}
