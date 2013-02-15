<?php
/*
 * Based on PSR-0 standard: Example [autoloader] implementation
 */
spl_autoload_register(function ($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    $pathPrefix = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'src';
    $absolutePath = $pathPrefix . DIRECTORY_SEPARATOR . $fileName;
    if (file_exists($absolutePath)) {
        require $absolutePath;
    }
});
