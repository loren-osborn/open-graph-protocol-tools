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

use Exception as NativePhpException;
use ReflectionProperty;

/**
 * Class builder for class generator
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class ClassBuilder
{
    const VALID_SYMBOL_NAME_PATTERN = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

    private $className;
    private $parentClass;
    private $constants;
    private $properties;
    private $methods;
    private $visibilityMap = array(
        ReflectionProperty::IS_PRIVATE => 'private',
        ReflectionProperty::IS_PROTECTED => 'protected',
        ReflectionProperty::IS_PUBLIC => 'public'
    );

    public static function parseClassName($className)
    {
        $classNameParts = explode('\\', $className);
        $result = array('className' => $className, 'classNameParts' => $classNameParts);
        if (count($classNameParts) > 1) {
            $result['classNamespace'] = implode('\\', array_slice($classNameParts, 0, -1));
            $result['baseClassName']  = $classNameParts[count($classNameParts) - 1];
            $result['vendorPrefix']   = $classNameParts[0];
        } else {
            $result['classNamespace'] = '';
            $result['baseClassName']  = $className;
            $result['vendorPrefix']   = 'Global';
        }

        return $result;
    }

    public function __construct($className)
    {
        $this->className  = $className;
        $this->constants  = array();
        $this->properties = array();
        $this->methods    = array();
    }

    public function setParent($className)
    {
        $generator = ClassGenerator::getBuilderOwner($this);
        $ancestor = $className;
        while ((!empty($ancestor)) && ($generator->isClassDefined($ancestor)) && ($ancestor != $this->className)) {
            $nextBuilder = $generator->getClassBuilder($ancestor);
            $ancestor = $nextBuilder->getParent();
        }
        if ($ancestor == $this->className) {
            throw new NativePhpException('Inheritence cycles are illegal');
        }
        $this->parentClass = $className;
    }

    public function getParent()
    {
        return $this->parentClass;
    }

    public function getBaseClassName()
    {
        $parsed = self::parseClassName($this->className);

        return $parsed['baseClassName'];
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getNamespace()
    {
        $parsed = self::parseClassName($this->className);

        return $parsed['classNamespace'];
    }

    public function getUsedClasses()
    {
        $myNamespace = $this->getNamespace();
        $result = array();
        if (!empty($this->parentClass)) {
            $result[] = $this->parentClass;
        }
        foreach ($this->methods as $name => $details) {
            if (array_key_exists('arguments', $details) && $details['arguments']) {
                foreach ($details['arguments'] as $arg) {
                    $parsed = $this->getSnippitUseClassInfo($arg);
                    $result = array_merge($result, $parsed['used_classes']);
                }
            }
            if (array_key_exists('body', $details) && $details['body']) {
                foreach ($details['body'] as $lines) {
                    $parsed = $this->getSnippitUseClassInfo($lines[1]);
                    $result = array_merge($result, $parsed['used_classes']);
                }
            }
        }

        return $result;
    }

    public function getSource($indent = '', $useClassMap = array())
    {
        $codeBlocks = array();
        $constants = '';
        foreach ($this->constants as $name => $value) {
            $constants .= "{$indent}    const {$name} = " . var_export($value, true) . ";\n";
        }
        if (!empty($constants)) {
            $codeBlocks[] = $constants;
        }
        $properties = '';
        foreach ($this->properties as $name => $details) {
            $propPrefix = '';
            $propSuffix = '';
            if (array_key_exists('visibility', $details) && array_key_exists($details['visibility'], $this->visibilityMap)) {
                $propPrefix .= $this->visibilityMap[$details['visibility']] . ' ';
            }
            if (array_key_exists('static', $details) && $details['static']) {
                $propPrefix .= 'static ';
            }
            if (array_key_exists('initialValue', $details)) {
                $value = var_export($details['initialValue'], true);
                if (preg_match('/^(NULL|TRUE|FALSE)$/', $value)) {
                    $value = strtolower($value);
                }
                $value = str_replace("\n", "\n{$indent}    ", $value);
                $propSuffix .= " = $value";
            }
            $properties .= "{$indent}    {$propPrefix}\${$name}{$propSuffix};\n";
        }
        if (!empty($properties)) {
            $codeBlocks[] = $properties;
        }
        foreach ($this->methods as $name => $details) {
            $funcPrefix = '';
            if (array_key_exists('visibility', $details) && array_key_exists($details['visibility'], $this->visibilityMap)) {
                $funcPrefix .= $this->visibilityMap[$details['visibility']] . ' ';
            }
            if (array_key_exists('static', $details) && $details['static']) {
                $funcPrefix .= 'static ';
            }
            $arguments = '';
            if (array_key_exists('arguments', $details) && $details['arguments']) {
                $cleanArgs = array();
                foreach ($details['arguments'] as $arg) {
                    $parsed = $this->getSnippitUseClassInfo($arg, $useClassMap);
                    $cleanArgs[] = $parsed['snippit'];
                }
                $arguments = implode(', ', $cleanArgs);
            }
            $body = '';
            if (array_key_exists('body', $details) && $details['body']) {
                foreach ($details['body'] as $lines) {
                    $parsed = $this->getSnippitUseClassInfo($lines[1], $useClassMap);
                    $body .= "{$indent}" . str_repeat(' ', 4 * (2 + $lines[0])) . "{$parsed['snippit']}\n";
                }
            }
            $codeBlocks[] =
                "{$indent}    {$funcPrefix}function {$name}({$arguments})\n" .
                "{$indent}    {\n" .
                $body .
                "{$indent}    }\n";
        }
        $result = "{$indent}class " . $this->getBaseClassName();
        if (!empty($this->parentClass)) {
            $result .= ' extends ' . $useClassMap[$this->parentClass]['alias'];
        }
        $result .= "\n{$indent}" . '{' . "\n" . join ("\n", $codeBlocks) . "{$indent}" . '}';

        return $result;
    }

    public function addConstants($constants)
    {
        foreach ($constants as $name => $value) {
            if (!preg_match('/^' . self::VALID_SYMBOL_NAME_PATTERN . '$/', $name)) {
                throw new NativePhpException("\"{$name}\" is not a valid constant name");
            }
            if (!(
                is_string($value) ||
                is_numeric($value) ||
                is_bool($value) ||
                is_null($value)
            )) {
                throw new NativePhpException("Constant $name is not a valid constant type");
            }
            if (array_key_exists($name, $this->constants)) {
                throw new NativePhpException("Constant $name is already defined");
            }
        }
        $this->constants = array_merge($this->constants, $constants);
    }

    public function addProperties($properties)
    {
        foreach ($properties as $name => $value) {
            if (!preg_match('/^' . self::VALID_SYMBOL_NAME_PATTERN . '$/', $name)) {
                throw new NativePhpException("\"{$name}\" is not a valid constant name");
            }
            if (array_key_exists($name, $this->constants)) {
                throw new NativePhpException("Constant $name is already defined");
            }
        }
        $this->properties = array_merge($this->properties, $properties);
    }

    public function addMethod($name, $details = array())
    {
        if (!preg_match('/^' . self::VALID_SYMBOL_NAME_PATTERN . '$/', $name)) {
            throw new NativePhpException("\"{$name}\" is not a valid method name");
        }
        if (array_key_exists($name, $this->methods)) {
            throw new NativePhpException("Method $name is already defined");
        }
        $this->methods[$name] = $details;
    }

    private function getSnippitUseClassInfo($snippit, $classMap = null)
    {
        $result = array('used_classes' => array());
        $strReplaceMap = array('%%' => '%');
        $funcClassPattern =
            '/%(class|class_as_string):((' . self::VALID_SYMBOL_NAME_PATTERN . '\\\\)*' .
            self::VALID_SYMBOL_NAME_PATTERN . ')%/';
        $matches = array();
        if (preg_match_all($funcClassPattern, $snippit, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $eachMatch) {
                if ($eachMatch[1] == 'class') {
                    $result['used_classes'][] = $eachMatch[2];
                }
                if (!empty($classMap)) {
                    if ($eachMatch[1] == 'class') {
                        $strReplaceMap[$eachMatch[0]] = $classMap[$eachMatch[2]]['alias'];
                    } else {
                        $strReplaceMap[$eachMatch[0]] = "'" . str_replace('\\', '\\\\', $eachMatch[2]) . "'";
                    }
                }
            }
        }
        if (!is_null($classMap)) {
            $result['snippit'] = str_replace(array_keys($strReplaceMap), array_values($strReplaceMap), $snippit);
        }

        return $result;
    }
}
