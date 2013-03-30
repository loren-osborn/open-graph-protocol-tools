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

use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception as OgptException;

/**
 * Class to generate custom classes
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class ClassGenerator
{
    private $classMap = array();
    static private $ownerMap = array();
    
    public function __destruct()
    {
        foreach ($this->classMap as $obj) {
            unset(self::$ownerMap[spl_object_hash($obj)]);
        }
    }
    
    static public function getBuilderOwner($builder)
    {
        return self::$ownerMap[spl_object_hash($builder)];
    }
    
    public function getClassBuilder($className)
    {
        if (class_exists($className, true)) {
            throw new NativePhpException("Class $className already defined");
        }
        if (!array_key_exists($className, $this->classMap)) {
            $this->classMap[$className] = new ClassBuilder($className);
            self::$ownerMap[spl_object_hash($this->classMap[$className])] = $this;
        }
        return $this->classMap[$className];
    }
    
    public function isClassDefined($className)
    {
        if (class_exists($className, true)) {
            return true;
        }
        return array_key_exists($className, $this->classMap);
    }
    
    public function getSource()
    {
        $namespaceHunks = $this->getOrderedNamespaceHunks();
        $namespaceCodeBlocks = array();
        if (count($namespaceHunks) > 1) {
            $header = 'namespace %NAMESPACE%{';
            $footer = '}';
            $indent = '    ';
        } else {
            $onlyNamespaceHunk = $namespaceHunks[0];
            $orderedClassList = array_keys($onlyNamespaceHunk);
            $onlyNamespace = $onlyNamespaceHunk[$orderedClassList[0]]->getNamespace();
            $header = '';
            $footer = '';
            $indent = '';
            if (!empty($onlyNamespace)) {
                $header = "namespace {$onlyNamespace};\n\n";
            }
        }
        foreach ($namespaceHunks as $currentNamespaceHunk) {
            $orderedClassList = array_keys($currentNamespaceHunk);
            $currentNamespace = $currentNamespaceHunk[$orderedClassList[0]]->getNamespace();
            $formattedNamespace = empty($currentNamespace) ? '' : "{$currentNamespace} ";
            $usedClasses = array();
            $classSources = array();
            foreach ($currentNamespaceHunk as $className => $builder) {
                $usedClasses = array_merge($usedClasses, $builder->getUsedClasses());
            }
            $usedClasses = $this->getUseClassMap($usedClasses, $currentNamespace);
            foreach ($currentNamespaceHunk as $className => $builder) {
                $classSources[] = $builder->getSource($indent, $usedClasses);
            }
            $usedDeclarations = array();
            $lastClassInfo = null;
            foreach ($usedClasses as $className => $usedClassInfo) {
                $declaration = "{$indent}use {$className}";
                if (!empty($usedClassInfo['explicitAlias'])) {
                    $declaration .= " as {$usedClassInfo['explicitAlias']}";
                }
                $declaration .= ';';
                if ($lastClassInfo && ($lastClassInfo['vendorPrefix'] != $usedClassInfo['vendorPrefix'])) {
                    $declaration = "\n{$declaration}";
                }
                $usedDeclarations[] = $declaration;
                $lastClassInfo = $usedClassInfo;
            }
            $usedDeclarations = join("\n", $usedDeclarations);
            if (!empty($usedDeclarations)) {
                array_unshift($classSources, $usedDeclarations);
            }
            $classSources = array(join($classSources, "\n\n"));
            if (!empty($header)) {
                array_unshift($classSources, str_replace('%NAMESPACE%', $formattedNamespace, $header));
            }
            if (!empty($footer)) {
                $classSources[] = $footer;
            }
            $namespaceCodeBlocks[] = join($classSources, "\n");
        }
        return join($namespaceCodeBlocks, "\n\n");
    }
    
    private function getUseClassMap($usedClasses, $namespace)
    {
        $result = array();
        $parsedMap = array();
        $collisionMap = array();
        $vendorMap = array();
        $usedClasses = array_unique($usedClasses);
        foreach ($usedClasses as $className) {
            $parsedClassName = ClassBuilder::parseClassName($className);
            $vendorMap[$parsedClassName['vendorPrefix']][] = $className;
            $collisionMap[$parsedClassName['baseClassName']][] = $className;
            $parsedMap[$className] = $parsedClassName;
            $parsedMap[$className]['alias'] = null;
        }
        foreach ($collisionMap as $colidedClasses) {
            if (count($colidedClasses) > 1) {
                $depth = 1;
                $resolved = false;
                while (!$resolved) {
                    $trialCollisionMap = array();
                    $resolved = true;
                    $maxDeltaDepth = null;
                    foreach ($colidedClasses as $index => $className) {
                        $depthDelta = count($parsedMap[$className]['classNameParts']) - (1 + $depth);
                        if ($depthDelta >= 0) {
                            $depthSlice = array_slice($parsedMap[$className]['classNameParts'], 0 - ($depth + 1), 1);
                            $depthName = $depthSlice[0] . $parsedMap[$className]['baseClassName'];
                        } elseif ($depthDelta == -1) {
                            $depthName = 'Global' . $parsedMap[$className]['baseClassName'];
                        } elseif ($depthDelta == -2) {
                            $depthName = 'Global' . $index . $parsedMap[$className]['baseClassName'];
                        } elseif ($depthDelta <= -3) {
                            $depthName = join('', $parsedMap[$className]['classNameParts']);
                        }
                        if (is_null($maxDeltaDepth) || ($maxDeltaDepth < $depthDelta)) {
                            $maxDeltaDepth = $depthDelta;
                        }
                        if (array_key_exists($depthName, $collisionMap)) {
                            $resolved = false;
                        } elseif (array_key_exists($depthName, $trialCollisionMap)) {
                            $resolved = false;
                        } elseif ($resolved) {
                            $trialCollisionMap[$depthName] = $className;
                        }
                    } // classNameParts
                    if ($maxDeltaDepth < -3) {
                        throw new NativePhpException('Failed to resolve use class alias');
                    } elseif ($resolved) {
                        foreach ($trialCollisionMap as $alias => $className) {
                            $parsedMap[$className]['alias'] = $alias;
                        }
                    } else {
                        $depth--;
                    }
                }
            }
        }
        foreach ($vendorMap as $vendorClasses) {
            foreach ($vendorClasses as $className) {
                $usedClassInfo = array(
                    'className'     => $className,
                    'vendorPrefix'  => $parsedMap[$className]['vendorPrefix'],
                    'alias'         => $parsedMap[$className]['baseClassName'],
                    'explicitAlias' => null
                );
                if (!empty($parsedMap[$className]['alias'])) {
                    $usedClassInfo['explicitAlias'] = $parsedMap[$className]['alias'];
                    $usedClassInfo['alias']         = $parsedMap[$className]['alias'];
                }
                $result[$className] = $usedClassInfo;
            }
        }
        return $result;
    }
    
    private function getOrderedNamespaceHunks()
    {
        $namespaceHunks = array('map' => array(), 'orderedList' => array());
        $alreadyTraversed = array();
        foreach ($this->classMap as $name => $builder) {
            $this->assignClassToNamespaceHunk($name, $alreadyTraversed, $namespaceHunks);
        }
        return $namespaceHunks['orderedList'];
    }
    
    private function assignClassToNamespaceHunk($className, &$alreadyTraversed, &$namespaceHunks)
    {
        if (!array_key_exists($className, $alreadyTraversed) && array_key_exists($className, $this->classMap)) {
            $builder = $this->classMap[$className];
            $usedClasses = $builder->getUsedClasses();
            $minIndex = -1;
            foreach ($usedClasses as $referencedClass) {
                $this->assignClassToNamespaceHunk($referencedClass, $alreadyTraversed, $namespaceHunks);
                if (!class_exists($referencedClass, true) && ($minIndex < $namespaceHunks['classMap'][$referencedClass])) {
                    $minIndex = $namespaceHunks['classMap'][$referencedClass];
                }
            }
            $namespaceHunkIndex = null;
            if (array_key_exists($builder->getNamespace(), $namespaceHunks['map'])) {
                for ($i = 0; ($i < count($namespaceHunks['map'][$builder->getNamespace()])) && is_null($namespaceHunkIndex); $i++) {
                    if ($namespaceHunks['map'][$builder->getNamespace()][$i] >= $minIndex) {
                        $namespaceHunkIndex = $namespaceHunks['map'][$builder->getNamespace()][$i];
                    }
                }
            }
            if (is_null($namespaceHunkIndex)) {
                $namespaceHunkIndex = count($namespaceHunks['orderedList']);
                $namespaceHunks['map'][$builder->getNamespace()][] = $namespaceHunkIndex;
            }
            $namespaceHunks['orderedList'][$namespaceHunkIndex][$className] = $builder;
            $namespaceHunks['classMap'][$builder->getClassName()] = $namespaceHunkIndex;
            $alreadyTraversed[$className] = true;
        }
    }
}