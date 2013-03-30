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

/**
 * Class builder for class generator
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class ClassBuilder
{
    private $className;
    private $parentClass;
    
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
        $this->className = $className;
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
        $usedClasses = array();
        if (!empty($this->parentClass)) {
            $usedClasses[] = $this->parentClass;
        }
        $result = array();
        foreach ($usedClasses as $className) {
            $parsed = self::parseClassName($className);
            if ($myNamespace != $parsed['classNamespace']) {
                $result[] = $className;
            }
        }
        return $result;
    }
    
    public function getSource($indent = '', $useClassMap = array())
    {
        $result = "{$indent}class " . $this->getBaseClassName();
        if (!empty($this->parentClass)) {
            $result .= ' extends ' . $useClassMap[$this->parentClass]['alias'];
        }
        $result .= "\n{$indent}" . '{' . "\n{$indent}" . '}';

        return $result;
    }
}