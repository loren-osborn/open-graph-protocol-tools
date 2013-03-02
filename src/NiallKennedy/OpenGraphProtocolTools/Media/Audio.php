<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 2.0
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Media;

use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception;

/**
 * Audio file suitable for playback alongside the main linked content.
 * Structured properties representations of Open Graph protocol media.
 *
 * @link http://ogp.me/ns#audio Open Graph protocol audio structured properties
 */
class Audio extends Media
{
    /**
     * Map a file extension to a registered Internet media type
     * Include Flash as a video type due to its popularity as a wrapper
     *
     * @link http://www.iana.org/assignments/media-types/audio/index.html IANA video types
     * @param  string $extension file extension
     * @return string Internet media type in the format audio/* or Flash
     */
    public static function extensionToMediaType($extension)
    {
        if (empty($extension) || ! is_string($extension)) {
            throw new Exception("Invalid extension: " . var_export($extension, true));
        }
        if ($extension === 'swf') {
            return 'application/x-shockwave-flash';
        } elseif ($extension === 'mp3') {
            return 'audio/mpeg';
        } elseif ($extension === 'm4a') {
            return 'audio/mp4';
        } elseif ($extension === 'ogg' || $extension === 'oga') {
            return 'audio/ogg';
        }
        throw new Exception("Unrecognized audio extension: " . var_export($extension, true));
    }

    /**
     * Set the Internet media type. Allow only audio types + Flash wrapper.
     *
     * @param string $type Internet media type
     */
    public function setType( $type )
    {
        if ($type === 'application/x-shockwave-flash' || (is_string($type) && substr_compare( $type, 'audio/', 0, 6 ) === 0)) {
            $this->type = $type;
        } else {
            throw new Exception("Invalid  audio type: " . var_export($type, true));
        }

        return $this;
    }
}
