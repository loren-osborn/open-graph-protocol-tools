<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Legacy;

use Exception;
use ReflectionClass;

use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception as OgptException;

/**
 * Ploxy object to provide backward compatibility
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class BackwardCompatibility
{
    const PACKAGE_NAMESPACE = 'NiallKennedy\\OpenGraphProtocolTools';

    private $proxiedObject;
    private static $classCreationChecklist = array();
    private static $objectIdToProxyObjMap = array();

    private static function getLegacyClassNameMap()
    {
        return array(
            self::PACKAGE_NAMESPACE . '\\Media\\Media'          => 'OpenGraphProtocolMedia',
            self::PACKAGE_NAMESPACE . '\\Media\\Audio'          => 'OpenGraphProtocolAudio',
            self::PACKAGE_NAMESPACE . '\\Media\\VisualMedia'    => 'OpenGraphProtocolVisualMedia',
            self::PACKAGE_NAMESPACE . '\\Media\\Image'          => 'OpenGraphProtocolImage',
            self::PACKAGE_NAMESPACE . '\\Media\\Video'          => 'OpenGraphProtocolVideo',
            self::PACKAGE_NAMESPACE . '\\Objects\\Object'       => 'OpenGraphProtocolObject',
            self::PACKAGE_NAMESPACE . '\\Objects\\Article'      => 'OpenGraphProtocolArticle',
            self::PACKAGE_NAMESPACE . '\\Objects\\Book'         => 'OpenGraphProtocolBook',
            self::PACKAGE_NAMESPACE . '\\Objects\\Profile'      => 'OpenGraphProtocolProfile',
            self::PACKAGE_NAMESPACE . '\\Objects\\Video'        => 'OpenGraphProtocolVideoObject',
            self::PACKAGE_NAMESPACE . '\\Objects\\VideoEpisode' => 'OpenGraphProtocolVideoEpisode',
            self::PACKAGE_NAMESPACE . '\\OpenGraphProtocol'     => 'OpenGraphProtocol'
        );
    }

    protected function __construct($objectToProxy)
    {
        $legacyClassNameMap = self::getLegacyClassNameMap();
        if (!array_key_exists(get_class($objectToProxy), $legacyClassNameMap)) {
            throw new Exception('Internal error: unknown class: ' . get_class($objectToProxy));
        }
        $this->proxiedObject = $objectToProxy;
        self::$objectIdToProxyObjMap[spl_object_hash($objectToProxy)] =  $this;
    }

   public function __destruct()
   {
       unset(self::$objectIdToProxyObjMap[spl_object_hash($this->proxiedObject)]);
   }

    public function __call($name, $arguments)
    {
        if (method_exists($this->proxiedObject, $name)) {
            $cleanArgs = self::cleanProxyCallArgs(get_class($this->proxiedObject), $name, false, $arguments);
            try {
                $result = call_user_func_array(array($this->proxiedObject, $name), $cleanArgs);
            } catch (OgptException $e) {
                /* ignore OGPT exceptions */
            }

            return self::cleanProxyReturnValue(get_class($this->proxiedObject), $name, false, $result);
        }
        throw new Exception('No such method: ' . $name);
    }

    protected static function callStaticInternal($name, $arguments, $className)
    {
        $inflectedName = self::inflectStaticMethodName($name);
        if (method_exists($className, $inflectedName)) {
            $cleanArgs = self::cleanProxyCallArgs($className, $name, true, $arguments);
            try {
                $result = forward_static_call_array(array($className, $inflectedName), $cleanArgs);
            } catch (OgptException $e) {
                /* ignore OGPT exceptions */
            }

            return self::cleanProxyReturnValue($className, $name, true, $result);
        }
        throw new Exception('No such method: ' . $name);
    }

    private static function cleanProxyCallArgs($class, $method, $static, $arguments)
    {
        $result = array();
        foreach ($arguments as $arg) {
            if ($arg instanceof self) {
                $result[] = $arg->proxiedObject;
            } else {
                $result[] = $arg;
            }
        }
        if (!$static && preg_match('/^set[A-Z]/', $method)) {
            $classReflection = new ReflectionClass($class);
            $methodReflection = $classReflection->getMethod($method);
            if (($methodReflection->getNumberOfParameters() == 2) && (count($result) == 1)) {
                // $result[1]: $autoTruncate
                $result[1] = true;
            }
        }

        return $result;
    }

    private static function cleanProxyReturnValue($class, $method, $static, $value)
    {
        if (
            is_object($value) &&
            array_key_exists(spl_object_hash($value), self::$objectIdToProxyObjMap)
        ) {
            $value = self::$objectIdToProxyObjMap[spl_object_hash($value)];
        }

        return $value;
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

    public static function inflectStaticMethodName($name)
    {
        if (($name != 'buildHTML') && (preg_match('/[A-Z]/', $name))) {
            // legacy names are all lower case
            return null;
        }

        return lcfirst(implode('', array_map('ucfirst', explode('_', $name))));
    }
}
