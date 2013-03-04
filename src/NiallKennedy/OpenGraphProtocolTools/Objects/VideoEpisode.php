<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Objects;

use NiallKennedy\OpenGraphProtocolTools\Exceptions\Exception;

/**
 * Video TV show.
 *
 * @link http://ogp.me/#type_video.episode Video episode
 */
class VideoEpisode extends Video
{
    /**
     * URL of a video.tv_show which this episode belongs to
     * @var string
     */
    protected $series;

    /**
     * URL of a video.tv_show which this episode belongs to
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set the URL of a video.tv_show which this episode belongs to
     *
     * @param string $url URL of a video.tv_show
     */
    public function setSeries($url)
    {
        if (static::isValidUrl($url)) {
            $this->series = $url;
        } else {
            throw new Exception("Invalid series uri: " . var_export($url, true));
        }

        return $this;
    }
}
