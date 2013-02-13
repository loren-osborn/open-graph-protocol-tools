<?php
/**
 * Open Graph Protocol Tools
 *
 * THIS FILE IS DEPRECATED: Please configure your autoloader to load NiallKennedy\OpenGraphProtocolTools
 * and add 'use' statements as required.
 *
 * NOTE: This file isn't PSR-0 compliant (as it is not scoped within a vendor namespace) but is
 * provided for backward compatibility only.
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 2.0
 * @copyright Public Domain
 */

/* The following line is an intentional PSR-1 violation */
trigger_error('Please configure NiallKennedy\\OpenGraphProtocolTools with your autoloader', E_USER_DEPRECATED);

class OpenGraphProtocolImage extends NiallKennedy\OpenGraphProtocolTools\Media\OpenGraphProtocolImage
{
    public static function extension_to_media_type( $extension )
    {
        return self::extensionToMediaType($extension);
    }

    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolVideo extends NiallKennedy\OpenGraphProtocolTools\Media\OpenGraphProtocolVideo
{
    public static function extension_to_media_type( $extension )
    {
        return self::extensionToMediaType($extension);
    }

    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolAudio extends NiallKennedy\OpenGraphProtocolTools\Media\OpenGraphProtocolAudio
{
    public static function extension_to_media_type( $extension )
    {
        return self::extensionToMediaType($extension);
    }
}

class OpenGraphProtocolArticle extends NiallKennedy\OpenGraphProtocolTools\Objects\OpenGraphProtocolArticle
{
    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolProfile extends NiallKennedy\OpenGraphProtocolTools\Objects\OpenGraphProtocolProfile
{
    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolBook extends NiallKennedy\OpenGraphProtocolTools\Objects\OpenGraphProtocolBook
{
    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolVideoObject extends NiallKennedy\OpenGraphProtocolTools\Objects\OpenGraphProtocolVideoObject
{
    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocolVideoEpisode extends NiallKennedy\OpenGraphProtocolTools\Objects\OpenGraphProtocolVideoEpisode
{
    public static function datetime_to_iso_8601(DateTime $date)
    {
        return self::datetimeToIso8601($date);
    }
}

class OpenGraphProtocol extends NiallKennedy\OpenGraphProtocolTools\OpenGraphProtocol
{
    public static function is_valid_url($url, array $accepted_mimes = array())
    {
        return self::isValidUrl($url, $accepted_mimes);
    }

    public static function supportedLocales($keys_only = false)
    {
        return self::supported_locales($keys_only);
    }

    public static function supportedTypes($flatten = false)
    {
        return self::supported_types($flatten);
    }
}
