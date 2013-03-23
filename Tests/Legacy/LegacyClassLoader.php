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

/* all in root namespace */
use PHPUnit_Framework_TestCase;
use OpenGraphProtocolImage;
use OpenGraphProtocolVideo;
use OpenGraphProtocolAudio;
use OpenGraphProtocolArticle;
use OpenGraphProtocol;

/**
 * Test class for OGPT legacy behavior
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class LegacyClassLoader
{
    public function testLoad(PHPUnit_Framework_TestCase $testObject)
    {
        /* if not yet included, include backward compatibility code and veryify deprication warning. */
        if (!class_exists('OpenGraphProtocol', false)) {
            $errorHistory = array();
            $packageRoot  = __FILE__;
            for ($i = 0; $i < 3; $i++) {
                $packageRoot = dirname($packageRoot);
            }
            set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$errorHistory) {
                $errorHistory[] = array($errno, $errstr, $errfile, $errline);
            });
            $includeFile = $packageRoot . DIRECTORY_SEPARATOR . 'media.php';
            $testObject->assertTrue(file_exists($includeFile), "file exists {$includeFile}");
            require $includeFile;
            restore_error_handler();
            $errorString = '';
            foreach ($errorHistory as $event) {
                $errorString .= "\n" . $this->errorLevelToString($event[0]) . ": {$event[1]} in {$event[2]}:{$event[3]}";
            }
            $expectedErrorMessage = 'Please configure NiallKennedy\\OpenGraphProtocolTools with your autoloader';
            $testObject->assertCount( 1,                     $errorHistory,       "Expected error count{$errorString}"  );
            $testObject->assertEquals(E_USER_DEPRECATED,     $errorHistory[0][0], "Expected error level{$errorString}"  );
            $testObject->assertEquals($expectedErrorMessage, $errorHistory[0][1], "Expected error message{$errorString}");
            $testObject->assertTrue(class_exists('OpenGraphProtocolMedia',        false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolAudio',        false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolVisualMedia',  false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolImage',        false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolVideo',        false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolObject',       false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolArticle',      false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolBook',         false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolProfile',      false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolVideoObject',  false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocolVideoEpisode', false), 'class loaded');
            $testObject->assertTrue(class_exists('OpenGraphProtocol',             false), 'class loaded');
        }
    }

    private function errorLevelToString($level)
    {
        $allConstants          = get_defined_constants(true);
        $result                = array();
        $errorLevels           = array();
        $errorLevelsByBitCount = array();
        foreach ($allConstants['Core'] as $name => $value) {
            if (substr($name, 0, 2) == 'E_') {
                $errorLevelsByBitCount[$this->countBits($value)][$value] = $name;
            }
        }
        $bitCounts = array_keys($errorLevelsByBitCount);
        rsort($bitCounts, SORT_NUMERIC);
        foreach ($bitCounts as $eachBitCount) {
            $map = $errorLevelsByBitCount[$eachBitCount];
            foreach ($map as $value => $name) {
                $errorLevels[$value] = $name;
            }
        }
        $result = '';
        foreach ($errorLevels as $value => $name) {
            if (($level & $value) == $value) {
                $result[] = $name;
                $level -= $value;
            }
        }
        if (($level != 0) || (count($result) == 0)) {
            $result[] = $level;
        }

        return implode('|', $result);
    }

    private function countBits($x)
    {
        $x -= (($x >> 1) & 0x55555555);
        $x = ((($x >> 2) & 0x33333333) + ($x & 0x33333333));
        $x = ((($x >> 4) + $x) & 0x0f0f0f0f);
        $x += ($x >> 8);
        $x += ($x >> 16);

        return($x & 0x0000003f);
    }
}
