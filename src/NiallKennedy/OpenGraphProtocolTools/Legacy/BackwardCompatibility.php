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
use ReflectionProperty;

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
    private $proxiedClass;
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

    protected function __construct($objectToProxy, $proxyClassName)
    {
        $legacyClassNameMap = self::getLegacyClassNameMap();
        if (!array_key_exists($proxyClassName, $legacyClassNameMap)) {
            throw new Exception("Internal error: unknown class: $proxyClassName");
        }
        if (!($objectToProxy instanceof $proxyClassName)) {
            throw new Exception("Internal error: object not member of $proxyClassName");
        }
        $this->proxiedObject = $objectToProxy;
        $this->proxiedClass  = $proxyClassName;
        self::$objectIdToProxyObjMap[spl_object_hash($objectToProxy)] =  $this;
    }

   public function __destruct()
   {
       unset(self::$objectIdToProxyObjMap[spl_object_hash($this->proxiedObject)]);
   }

    public function __call($name, $arguments)
    {
        if (method_exists($this->proxiedObject, $name)) {
            $cleanArgs = self::cleanProxyCallArgs($this->proxiedClass, $name, false, $arguments);
            $myProperties = get_object_vars($this);
            foreach ($myProperties as $propName => $propVal) {
                if (!preg_match('/^proxied(Object|Class)$/', $propName)) {
                    if (method_exists($this->proxiedObject, 'set' . ucfirst($propName))) {
                        call_user_func_array(array($this->proxiedObject, 'set' . ucfirst($propName)), array($propVal));
                    } else {
                        $this->proxiedObject->$propName = $propVal;
                    }
                }
            }
            try {
                $result = call_user_func_array(array($this->proxiedObject, $name), $cleanArgs);
            } catch (OgptException $e) {
                /* ignore OGPT exceptions */
            }
            foreach (array_keys($myProperties) as $propName) {
                if (!preg_match('/^proxied(Object|Class)$/', $propName)) {
                    if (method_exists($this->proxiedObject, 'get' . ucfirst($propName))) {
                        $this->$propName = call_user_func_array(array($this->proxiedObject, 'get' . ucfirst($propName)), array());
                    } else {
                        $this->$propName = $this->proxiedObject->$propName;
                    }
                }
            }

            return self::cleanProxyReturnValue($this->proxiedClass, $name, false, $result);
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
        } elseif (is_array($value)) {
            foreach (array_keys($value) as $key) {
                $value[$key] = self::cleanProxyReturnValue($class, $method, $static, $value[$key]);
            }
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
        $abstract          = '';
        $concreteClassName = $className;
        $concreteClassDef  = '';
        if ($reflection->isAbstract()) {
            $abstract  = 'abstract ';
        }
        $protectedPropertyList = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);
        $accessors = array();
        if ($reflection->isAbstract() || (count($protectedPropertyList) > 0)) {
            foreach ($protectedPropertyList as $propRef) {
                if (!($reflection->hasMethod('get' . ucfirst($propRef->getName())))) {
                    $accessors[] =
                        "\t\t\t" . 'public function get' . ucfirst($propRef->getName()) . '() {' . "\n" .
                            "\t\t\t\t" . 'return $this->' . $propRef->getName() . ';' . "\n" .
                        "\t\t\t" . '}' . "\n";
                }
                if (!($reflection->hasMethod('set' . ucfirst($propRef->getName())))) {
                    $accessors[] =
                        "\t\t\t" . 'public function set' . ucfirst($propRef->getName()) . '($val) {' . "\n" .
                            "\t\t\t\t" . '$this->' . $propRef->getName() . ' = $val;' . "\n" .
                        "\t\t\t" . '}' . "\n";
                }
            }
        }
        if ($reflection->isAbstract() || (count($accessors) > 0)) {
            $classNameParts = explode('\\', $className);
            if (count($classNameParts) > 1) {
                $concreteClassNamespace = implode('\\', array_merge(
                    array_slice($classNameParts, 0, 2),
                    array('Legacy'),
                    array_slice($classNameParts, 2, -1)
                ));
                $concreteClassShortName = 'Proxyable' . $classNameParts[count($classNameParts) - 1];
                $concreteClassName      = $concreteClassNamespace . '\\' . $concreteClassShortName;
                $concreteClassDef       =
                    "namespace {$concreteClassNamespace} " . '{' . "\n" .
                        "\tclass {$concreteClassShortName} extends \\{$className} " . '{' . "\n" .
                            implode("\n", $accessors) . ' ' .
                        "\t" . '}' . "\n" .
                    '}' . "\n\n";
                $codePrefix  = 'namespace {' . "\n";
                $codeSuffix  = "\n" . '}';
            } else {
                throw new Exception("Internal error: We shouldn't be proxying a class outside a namespace");
            }
        }
        $constants = '';
        foreach ($reflection->getConstants() as $constName => $constValue) {
            $constants .= "\t\tconst {$constName} = " . var_export($constValue, true) . ';' . "\n";
        }
        if ($constants != '') {
            $constants .= "\n";
        }
        $compatibilityClassSource =
            $concreteClassDef .
            'namespace {' . "\n" .
                "\t{$abstract}class {$legacyClassName} extends {$parent}\n" .
                "\t" .'{' . "\n" .
                    $constants .
                    /* No legacy classes have constructors that take arguments. */
                    "\t\t" . 'public function __construct()' . "\n" .
                    "\t\t" . '{' . "\n" .
                        "\t\t\t" . __CLASS__ . '::__construct(new ' . $concreteClassName . "(), '" . str_replace('\\', '\\\\', $className) . "');\n" .
                    "\t\t" . '}' . "\n\n" .
                    "\t\t" . 'static public function __callStatic($name, $arguments)' . "\n" .
                    "\t\t" . '{' . "\n" .
                        "\t\t\t" . 'return ' . __CLASS__ . '::callStaticInternal($name, $arguments, \'' . preg_replace('/\\\\/', '\\\\', $className) . '\');' . "\n" .
                    "\t\t" . '}' . "\n" .
                "\t" . '}' . "\n" .
            '}';
        $compatibilityClassSource = str_replace("\t", '    ', $compatibilityClassSource);
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
