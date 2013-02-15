<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 2.0
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Utils;

use Exception;

/**
 * Inflector
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
class Inflector
{
    const INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS = 'INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS';
    const INFLECTION_UPPERCASE_WITH_UNDERSCORE_SEPARATORS = 'INFLECTION_UPPERCASE_WITH_UNDERSCORE_SEPARATORS';
    const INFLECTION_CAMEL_CASE                           = 'INFLECTION_CAMEL_CASE';
    const INFLECTION_STUDLY_CASE                          = 'INFLECTION_STUDLY_CASE';

    public static function inflect($name, $from = self::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS, $to = self::INFLECTION_CAMEL_CASE)
    {
        if (($from == self::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS) || ($to == self::INFLECTION_CAMEL_CASE)) {
            return lcfirst(implode('', array_map('ucfirst', explode('_', $name))));
        } elseif (($from == self::INFLECTION_CAMEL_CASE) || ($to == self::INFLECTION_LOWERCASE_WITH_UNDERSCORE_SEPARATORS)) {
            return strtolower(implode('_', preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY)));
        } else {
            throw new Exception('Not implemented');
        }
    }
}
