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
 * Class to generate custom classes
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class ClassGenerator
{
    private $classMap = array();
    private static $ownerMap = array();

    public function __destruct()
    {
        foreach ($this->classMap as $obj) {
            unset(self::$ownerMap[spl_object_hash($obj)]);
        }
    }

    public static function getBuilderOwner($builder)
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
                $eachUsedClasses = $builder->getUsedClasses();
                $usedClasses = array_merge($usedClasses, $eachUsedClasses);
            }
            $usedClasses = $this->getUseClassMap($usedClasses, $currentNamespaceHunk);
            foreach ($currentNamespaceHunk as $className => $builder) {
                $classSources[] = $builder->getSource($indent, $usedClasses);
            }
            $usedDeclarations = array();
            $lastClassInfo = null;
            foreach ($usedClasses as $className => $usedClassInfo) {
                if (!$usedClassInfo['skipDeclaration']) {
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

    private function getUseClassMap($usedClasses, $hunkClasses)
    {
        $orderedHunkClasses = array_values($hunkClasses);
        $namespace = $orderedHunkClasses[0]->getNamespace();
        $result = array();
        $parsedMap = array();
        $collisionMap = array();
        $hunkCollisionMap = array();
        $vendorMap = array();
        $usedClasses = array_unique($usedClasses);
        foreach ($hunkClasses as $className => $builder) {
            $hunkCollisionMap[$builder->getBaseClassName()][] = $builder;
        }
        foreach ($usedClasses as $className) {
            $parsedClassName = ClassBuilder::parseClassName($className);
            $parsedMap[$className] = $parsedClassName;
            $parsedMap[$className]['alias'] = null;
            $parsedMap[$className]['forceAbsolute'] = false;
            $vendorMap[$parsedMap[$className]['vendorPrefix']][] = $className;
            if ($parsedMap[$className]['classNamespace'] == $namespace) {
                if (!array_key_exists($parsedMap[$className]['baseClassName'], $hunkCollisionMap)) {
                    $hunkCollisionMap[$parsedMap[$className]['baseClassName']] = true;
                }
            } else {
                $collisionMap[$parsedMap[$className]['baseClassName']][] = $className;
            }
        }
        foreach ($collisionMap as $colidedClasses) {
            if (
                (count($colidedClasses) > 1) ||
                array_key_exists($parsedMap[$colidedClasses[0]]['baseClassName'], $hunkCollisionMap)
            ) {
                $depth = 1;
                $resolved = false;
                while (!$resolved) {
                    $trialCollisionMap = array();
                    $resolved = true;
                    $maxDeltaDepth = null;
                    foreach ($colidedClasses as $className) {
                        $depthDelta = count($parsedMap[$className]['classNameParts']) - ($depth + 1);
                        if (is_null($maxDeltaDepth) || ($maxDeltaDepth < $depthDelta)) {
                            $maxDeltaDepth = $depthDelta;
                        }
                        if ($depthDelta >= 0) {
                            $depthSlice = array_slice($parsedMap[$className]['classNameParts'], $depthDelta, 1);
                            $depthName = $depthSlice[0] . $parsedMap[$className]['baseClassName'];
                        } else {
                            $depthName = '\\' . $className;
                        }
                        if (array_key_exists($depthName, $hunkCollisionMap)) {
                            $resolved = false;
                        } elseif (array_key_exists($depthName, $collisionMap)) {
                            $resolved = false;
                        } elseif (array_key_exists($depthName, $trialCollisionMap)) {
                            $resolved = false;
                        } elseif ($resolved) {
                            $trialCollisionMap[$depthName] = $className;
                        }
                    }
                    if ($maxDeltaDepth < 0) {
                        foreach ($colidedClasses as $className) {
                            $parsedMap[$className]['forceAbsolute'] = true;
                        }
                        $resolved = true;
                    } elseif ($resolved) {
                        foreach ($trialCollisionMap as $alias => $className) {
                            if (preg_match('/^\\\\/', $alias)) {
                                $parsedMap[$className]['forceAbsolute'] = true;
                            } else {
                                $parsedMap[$className]['alias'] = $alias;
                            }
                        }
                    } else {
                        $depth++;
                    }
                }
            }
        }
        foreach ($vendorMap as $vendorClasses) {
            foreach ($vendorClasses as $className) {
                $usedClassInfo = array(
                    'className'       => $className,
                    'vendorPrefix'    => $parsedMap[$className]['vendorPrefix'],
                    'alias'           => $parsedMap[$className]['baseClassName'],
                    'explicitAlias'   => null,
                    'skipDeclaration' => false
                );
                if ($parsedMap[$className]['classNamespace'] == $namespace) {
                    $usedClassInfo['skipDeclaration'] = true;
                } elseif ($parsedMap[$className]['forceAbsolute']) {
                    $usedClassInfo['skipDeclaration'] = true;
                    $usedClassInfo['alias']           = '\\' . $className;
                } elseif (!empty($parsedMap[$className]['alias'])) {
                    $usedClassInfo['explicitAlias']   = $parsedMap[$className]['alias'];
                    $usedClassInfo['alias']           = $parsedMap[$className]['alias'];
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
