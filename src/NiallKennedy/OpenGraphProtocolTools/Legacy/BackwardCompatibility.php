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

    private static function getLegacyClasses()
    {
        return array(
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolMedia' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolMedia',
                'legacy_name' => 'OpenGraphProtocolMedia',
                'abstract'    => true
            ),
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolAudio' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolAudio',
                'legacy_name' => 'OpenGraphProtocolAudio',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolMedia',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVisualMedia' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVisualMedia',
                'legacy_name' => 'OpenGraphProtocolVisualMedia',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolMedia',
                'abstract'    => true
            ),
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolImage' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolImage',
                'legacy_name' => 'OpenGraphProtocolImage',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVisualMedia',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVideo' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVideo',
                'legacy_name' => 'OpenGraphProtocolVideo',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Media\\OpenGraphProtocolVisualMedia',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject',
                'legacy_name' => 'OpenGraphProtocolObject',
                'abstract'    => true
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolArticle' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolArticle',
                'legacy_name' => 'OpenGraphProtocolArticle',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolBook' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolBook',
                'legacy_name' => 'OpenGraphProtocolBook',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolProfile' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolProfile',
                'legacy_name' => 'OpenGraphProtocolProfile',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoObject' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoObject',
                'legacy_name' => 'OpenGraphProtocolVideoObject',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolObject',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoEpisode' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoEpisode',
                'legacy_name' => 'OpenGraphProtocolVideoEpisode',
                'parent'      => self::PACKAGE_NAMESPACE . '\\Objects\\OpenGraphProtocolVideoObject',
                'abstract'    => false
            ),
            self::PACKAGE_NAMESPACE . '\\OpenGraphProtocol' => array(
                'name'        => self::PACKAGE_NAMESPACE . '\\OpenGraphProtocol',
                'legacy_name' => 'OpenGraphProtocol',
                'abstract'    => false
            )
        );
    }

    protected function __construct($objectToProxy)
    {
        $legacyClasses = self::getLegacyClasses();
        if (!array_key_exists(get_class($objectToProxy), $legacyClasses)) {
            throw new Exception('Internal error: unknown class: ' . get_class($objectToProxy));
        }
        if ($legacyClasses[get_class($objectToProxy)]['abstract']) {
            throw new Exception('Internal error: should not be able to create abstract class object for class ' . get_class($objectToProxy));
        }
        $this->proxiedObject = $objectToProxy;
    }

    public function __call($name, $arguments)
    {
        $legacyClasses = self::getLegacyClasses();
        $classInfo     = $legacyClasses[get_class($this->proxiedObject)];
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
        $legacyClasses = self::getLegacyClasses();
        $classInfo     = $legacyClasses[$className];
        $inflectedName = Inflector::inflect($name, Inflector::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS, Inflector::INFLECTION_CAMEL_CASE);
        if (method_exists($className, $inflectedName)) {
            $result = forward_static_call_array(array($className, $inflectedName), $arguments);

            return $result;
        }
        throw new Exception('No such method: ' . $name);
    }

    public static function createProxyClasses()
    {
        $legacyClasses = self::getLegacyClasses();
        foreach ($legacyClasses as $classInfo) {
            if (!array_key_exists($classInfo['name'], self::$classCreationChecklist)) {
                self::createProxyClass($classInfo, $legacyClasses);
            }
        }
    }

    private static function createProxyClass($classInfo, $legacyClasses)
    {
        if (array_key_exists('parent', $classInfo) && !array_key_exists($classInfo['parent'], self::$classCreationChecklist)) {
            self::createProxyClass($legacyClasses[$classInfo['parent']], $legacyClasses);
        }
        $parent = array_key_exists('parent', $classInfo) ? $legacyClasses[$classInfo['parent']]['legacy_name'] : __CLASS__;
        $abstract = $classInfo['abstract'] ? 'abstract' : '';
        $source =
            "{$abstract} class {$classInfo['legacy_name']} extends {$parent}" .
            '{ ' .
                /* No legacy classes have constructors that take arguments. */
                'public function __construct() ' .
                '{' .
                    __CLASS__ . '::__construct(new ' . $classInfo['name'] . '());' .
                '} ' .
                'static public function __callStatic($name, $arguments) ' .
                '{' .
                    'return ' . __CLASS__ . '::callStaticInternal($name, $arguments, \'' . preg_replace('/\\\\/', '\\\\', $classInfo['name']) . '\');' .
                '}' .
            '}';
        eval($source);
        self::$classCreationChecklist[$classInfo['name']] = true;
    }
}
