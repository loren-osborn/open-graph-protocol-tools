<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.99.0 (working toward 2.0 release)
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Common base for exahustive test of new and legacy OpenGraphProtocol classes
 *
 * @author Loren Osborn <loren.osborn@hautelook.com>
 */
abstract class OpenGraphProtocolTestBase extends PHPUnit_Framework_TestCase
{
    /* self-shunting entry point for testBuildHTML() */
    public function toArray()
    {
        return array(
            'test' => array(
                'condition' => 'is object',
                'type'      => 'self shunting',
                'nesting'   => array('deep', 'enough')
            )
        );
    }

    public function testClassConstants()
    {
        $this->assertEquals('1.99.0',            $this->getOpenGraphProtocolConstant('VERSION'),     'expected value');
        $this->assertEquals(false,               $this->getOpenGraphProtocolConstant('VERIFY_URLS'), 'expected value');
        $this->assertEquals('property',          $this->getOpenGraphProtocolConstant('META_ATTR'),   'expected value');
        $this->assertEquals('og',                $this->getOpenGraphProtocolConstant('PREFIX'),      'expected value');
        $this->assertEquals('http://ogp.me/ns#', $this->getOpenGraphProtocolConstant('NS'),          'expected value');
    }

    abstract protected function callStaticBuildHTML();

    abstract protected function callStaticSupportedTypes();

    abstract protected function callStaticSupportedLocales();

    abstract protected function callStaticIsValidUrl();

    abstract protected function createOpenGraphProtocol();

    abstract protected function getOpenGraphProtocolConstant($name);

    public function testBuildHTML()
    {
        $this->assertNull($this->callStaticBuildHTML(array()), 'Empty array gives null');
        $this->assertEquals(('<meta property="og:test" content="default prefix">' . PHP_EOL), $this->callStaticBuildHTML(array('test' => 'default prefix')), 'default prefix is correct');
        $input = array(
            'car'        => 'red',
            'truck'      => array(
                'black',
                array(
                    'year'       => array(1987),
                    'wheels'     => '4',
                    'tires'      => 4,
                )
            ),
            'object'     => $this,
            '6'          => 'prefix',
            '7.0'        => 'lucky',
            8            => 'my prefix',
            9.0          => 'also prefix',
            'ignore'     => '',
            'silent'     => array(),
            'html'       => '<span class="custom">entity &amp; testing</span>',
            'multi_line' => 'line 1' . PHP_EOL . 'line 2' . PHP_EOL . 'line 3'
        );
        $expected =
            '<meta property="customPrefix:car" '                   . 'content="red">' . PHP_EOL .
            '<meta property="customPrefix:truck" '                 . 'content="black">' . PHP_EOL .
            '<meta property="customPrefix:truck:year" '            . 'content="1987">' . PHP_EOL .
            '<meta property="customPrefix:truck:wheels" '          . 'content="4">' . PHP_EOL .
            '<meta property="customPrefix:truck:tires" '           . 'content="4">' . PHP_EOL .
            '<meta property="customPrefix:object:test:condition" ' . 'content="is object">' . PHP_EOL .
            '<meta property="customPrefix:object:test:type" '      . 'content="self shunting">' . PHP_EOL .
            '<meta property="customPrefix:object:test:nesting" '   . 'content="deep">' . PHP_EOL .
            '<meta property="customPrefix:object:test:nesting" '   . 'content="enough">' . PHP_EOL .
            '<meta property="customPrefix" '                       . 'content="prefix">' . PHP_EOL .
            '<meta property="customPrefix:7.0" '                   . 'content="lucky">' . PHP_EOL .
            '<meta property="customPrefix" '                       . 'content="my prefix">' . PHP_EOL .
            '<meta property="customPrefix" '                       . 'content="also prefix">' . PHP_EOL .
            '<meta property="customPrefix:html" '                  . 'content="&lt;span class=&quot;custom&quot;&gt;entity &amp;amp; testing&lt;/span&gt;">' . PHP_EOL .
            '<meta property="customPrefix:multi_line" '            . 'content="line 1&#10;line 2&#10;line 3">' . PHP_EOL;
        $this->assertEquals($expected, $this->callStaticBuildHTML($input, 'customPrefix'), 'multi-behaviour test');
    }

    public function testSupportedTypes()
    {
        if (function_exists('gettext')) {
            // Set language to "en_US"
            putenv('LC_MESSAGES=en_US');
            setlocale(LC_MESSAGES, 'en_US');

            // Specify location of translation tables
            bindtextdomain('OpenGraphProtocolTools', (dirname(__FILE__) . '/locale'));

            // Choose domain
            textdomain("OpenGraphProtocolTools");
        }
        $flatOutput            = $this->callStaticSupportedTypes(true);
        $nestedOutput          = $this->callStaticSupportedTypes(false);
        $defaultOutput         = $this->callStaticSupportedTypes();
        $expectedFlatOutput    = array(
            'activity',            'sport',               'company',             'bar',                 'cafe',
            'hotel',               'restaurant',          'cause',               'sports_league',       'sports_team',
            'band',                'government',          'non_profit',          'school',              'university',
            'actor',               'athlete',             'author',              'director',            'musician',
            'politician',          'profile',             'public_figure',       'city',                'country',
            'landmark',            'state_province',      'music.album',         'book',                'drink',
            'video.episode',       'food',                'game',                'video.movie',         'music.playlist',
            'product',             'music.radio_station', 'music.song',          'video.tv_show',       'video.other',
            'article',             'blog',                'website'
        );
        $expectedNestedOutput  = array(
            'Activities' => array(
                'activity'       => 'Activity',
                'sport'          => 'Sport'
            ),
            'Businesses' => array(
                'company'        => 'Company',
                'bar'            => 'Bar',
                'cafe'           => 'Cafe',
                'hotel'          => 'Hotel',
                'restaurant'     => 'Restaurant'
            ),
            'Groups' => array(
                'cause'          => 'Cause',
                'sports_league'  => 'Sports league',
                'sports_team'    => 'Sports team'
            ),
            'Organizations' => array(
                'band'           => 'Band',
                'government'     => 'Government',
                'non_profit'     => 'Non-profit',
                'school'         => 'School',
                'university'     => 'University'
            ),
            'People' => array(
                'actor'          => 'Actor or actress',
                'athlete'        => 'Athlete',
                'author'         => 'Author',
                'director'       => 'Director',
                'musician'       => 'Musician',
                'politician'     => 'Politician',
                'profile'        => 'Profile',
                'public_figure'  => 'Public Figure'
            ),
            'Places' => array(
                'city'           => 'City or locality',
                'country'        => 'Country',
                'landmark'       => 'Landmark',
                'state_province' => 'State or province'
            ),
            'Products and Entertainment' => array(
                'music.album'    => 'Music Album',
                'book'           => 'Book',
                'drink'          => 'Drink',
                'video.episode'  => 'Video episode',
                'food'           => 'Food',
                'game'           => 'Game',
                'video.movie'    => 'Movie',
                'music.playlist' => 'Music playlist',
                'product'        => 'Product',
                'music.radio_station'
                                 => 'Radio station',
                'music.song'     => 'Song',
                'video.tv_show'  => 'Television show',
                'video.other'    => 'Video'
            ),
            'Websites' => array(
                'article'        => 'Article',
                'blog'           => 'Blog',
                'website'        => 'Website'
            )
        );
        $expectedDefaultOutput = $expectedNestedOutput;
        $this->assertEquals($expectedDefaultOutput, $defaultOutput, 'default output');
        $this->assertEquals($expectedFlatOutput,    $flatOutput,    'flat output');
        $this->assertEquals($expectedNestedOutput,  $nestedOutput,  'nested output');
    }

    public function testSupportedTypesLocalized()
    {
        if (!function_exists('gettext')) {
            $this->markTestSkipped('gettext() support missing');
        } else {
            // Set language to "backwards_en_US"
            putenv('LC_MESSAGES=backwards_en_US');
            setlocale(LC_MESSAGES, 'backwards_en_US');

            // Specify location of translation tables
            bindtextdomain('OpenGraphProtocolTools', (dirname(__FILE__) . '/locale'));

            // Choose domain
            textdomain("OpenGraphProtocolTools");

            // Translation is looking for in ./locale/backwards_en_US/LC_MESSAGES/OpenGraphProtocolTools.mo now

            // Print a test message
            $backwardsNestedOutput          = $this->callStaticSupportedTypes(false);
            $expectedBackwardsNestedOutput  = array(
                'seitivitcA' => array(
                    'activity'       => 'ytivitcA',
                    'sport'          => 'tropS'
                ),
                'sessenisuB' => array(
                    'company'        => 'ynapmoC',
                    'bar'            => 'raB',
                    'cafe'           => 'efaC',
                    'hotel'          => 'letoH',
                    'restaurant'     => 'tnaruatseR'
                ),
                'spuorG' => array(
                    'cause'          => 'esuaC',
                    'sports_league'  => 'eugael stropS',
                    'sports_team'    => 'maet stropS'
                ),
                'snoitazinagrO' => array(
                    'band'           => 'dnaB',
                    'government'     => 'tnemnrevoG',
                    'non_profit'     => 'tiforp-noN',
                    'school'         => 'loohcS',
                    'university'     => 'ytisrevinU'
                ),
                'elpoeP' => array(
                    'actor'          => 'ssertca ro rotcA',
                    'athlete'        => 'etelhtA',
                    'author'         => 'rohtuA',
                    'director'       => 'rotceriD',
                    'musician'       => 'naicisuM',
                    'politician'     => 'naicitiloP',
                    'profile'        => 'eliforP',
                    'public_figure'  => 'erugiF cilbuP'
                ),
                'secalP' => array(
                    'city'           => 'ytilacol ro ytiC',
                    'country'        => 'yrtnuoC',
                    'landmark'       => 'kramdnaL',
                    'state_province' => 'ecnivorp ro etatS'
                ),
                'tnemniatretnE dna stcudorP' => array(
                    'music.album'    => 'mublA cisuM',
                    'book'           => 'kooB',
                    'drink'          => 'knirD',
                    'video.episode'  => 'edosipe oediV',
                    'food'           => 'dooF',
                    'game'           => 'emaG',
                    'video.movie'    => 'eivoM',
                    'music.playlist' => 'tsilyalp cisuM',
                    'product'        => 'tcudorP',
                    'music.radio_station'
                                     => 'noitats oidaR',
                    'music.song'     => 'gnoS',
                    'video.tv_show'  => 'wohs noisiveleT',
                    'video.other'    => 'oediV'
                ),
                'setisbeW' => array(
                    'article'        => 'elcitrA',
                    'blog'           => 'golB',
                    'website'        => 'etisbeW'
                )
            );
            $this->assertEquals($expectedBackwardsNestedOutput, $backwardsNestedOutput, 'nested output');

            // restore default language
            putenv('LC_MESSAGES=en_US');
            setlocale(LC_MESSAGES, 'en_US');

            // restore domain
            textdomain("OpenGraphProtocolTools");
        }
    }

    public function testSupportedLocales()
    {
        if (function_exists('gettext')) {
            // Set language to "en_US"
            putenv('LC_MESSAGES=en_US');
            setlocale(LC_MESSAGES, 'en_US');

            // Specify location of translation tables
            bindtextdomain('OpenGraphProtocolTools', (dirname(__FILE__) . '/locale'));

            // Choose domain
            textdomain("OpenGraphProtocolTools");
        }
        $outputKeys         = $this->callStaticSupportedLocales(true);
        $outputAll          = $this->callStaticSupportedLocales(false);
        $outputDefault      = $this->callStaticSupportedLocales();
        $expectedOutputKeys = array(
            'af_ZA',               'ar_AR',               'az_AZ',               'be_BY',               'bg_BG',
            'bn_IN',               'bs_BA',               'ca_ES',               'cs_CZ',               'cy_GB',
            'da_DK',               'de_DE',               'el_GR',               'en_GB',               'en_US',
            'eo_EO',               'es_ES',               'es_LA',               'et_EE',               'eu_ES',
            'fa_IR',               'fi_FI',               'fo_FO',               'fr_CA',               'fr_FR',
            'fy_NL',               'ga_IE',               'gl_ES',               'he_IL',               'hi_IN',
            'hr_HR',               'hu_HU',               'hy_AM',               'id_ID',               'is_IS',
            'it_IT',               'ja_JP',               'ka_GE',               'ko_KR',               'ku_TR',
            'la_VA',               'lt_LT',               'lv_LV',               'mk_MK',               'ml_IN',
            'ms_MY',               'nb_NO',               'ne_NP',               'nl_NL' ,              'nn_NO',
            'pa_IN',               'pl_PL',               'ps_AF',               'pt_PT',               'ro_RO',
            'ru_RU',               'sk_SK',               'sl_SI',               'sq_AL',               'sr_RS',
            'sv_SE',               'sw_KE',               'ta_IN',               'te_IN',               'th_TH',
            'tl_PH',               'tr_TR',               'uk_UA',               'vi_VN',               'zh_CN',
            'zh_HK',               'zh_TW'
        );
        $expectedOutputAll = array(
            'af_ZA' => 'Afrikaans',
            'ar_AR' => 'Arabic',
            'az_AZ' => 'Azeri',
            'be_BY' => 'Belarusian',
            'bg_BG' => 'Bulgarian',
            'bn_IN' => 'Bengali',
            'bs_BA' => 'Bosnian',
            'ca_ES' => 'Catalan',
            'cs_CZ' => 'Czech',
            'cy_GB' => 'Welsh',
            'da_DK' => 'Danish',
            'de_DE' => 'German',
            'el_GR' => 'Greek',
            'en_GB' => 'English (UK)',
            'en_US' => 'English (US)',
            'eo_EO' => 'Esperanto',
            'es_ES' => 'Spanish (Spain)',
            'es_LA' => 'Spanish (Latin America)',
            'et_EE' => 'Estonian',
            'eu_ES' => 'Basque',
            'fa_IR' => 'Persian',
            'fi_FI' => 'Finnish',
            'fo_FO' => 'Faroese',
            'fr_CA' => 'French (Canada)',
            'fr_FR' => 'French (France)',
            'fy_NL' => 'Frisian',
            'ga_IE' => 'Irish',
            'gl_ES' => 'Galician',
            'he_IL' => 'Hebrew',
            'hi_IN' => 'Hindi',
            'hr_HR' => 'Croatian',
            'hu_HU' => 'Hungarian',
            'hy_AM' => 'Armenian',
            'id_ID' => 'Indonesian',
            'is_IS' => 'Icelandic',
            'it_IT' => 'Italian',
            'ja_JP' => 'Japanese',
            'ka_GE' => 'Georgian',
            'ko_KR' => 'Korean',
            'ku_TR' => 'Kurdish',
            'la_VA' => 'Latin',
            'lt_LT' => 'Lithuanian',
            'lv_LV' => 'Latvian',
            'mk_MK' => 'Macedonian',
            'ml_IN' => 'Malayalam',
            'ms_MY' => 'Malay',
            'nb_NO' => 'Norwegian (bokmal)',
            'ne_NP' => 'Nepali',
            'nl_NL' => 'Dutch',
            'nn_NO' => 'Norwegian (nynorsk)',
            'pa_IN' => 'Punjabi',
            'pl_PL' => 'Polish',
            'ps_AF' => 'Pashto',
            'pt_PT' => 'Portuguese (Brazil)',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'sk_SK' => 'Slovak',
            'sl_SI' => 'Slovenian',
            'sq_AL' => 'Albanian',
            'sr_RS' => 'Serbian',
            'sv_SE' => 'Swedish',
            'sw_KE' => 'Swahili',
            'ta_IN' => 'Tamil',
            'te_IN' => 'Telugu',
            'th_TH' => 'Thai',
            'tl_PH' => 'Filipino',
            'tr_TR' => 'Turkish',
            'uk_UA' => 'Ukrainian',
            'vi_VN' => 'Vietnamese',
            'zh_CN' => 'Simplified Chinese (China)',
            'zh_HK' => 'Traditional Chinese (Hong Kong)',
            'zh_TW' => 'Traditional Chinese (Taiwan)'
        );
        $expectedOutputDefault = $expectedOutputAll;
        $this->assertEquals($expectedOutputDefault, $outputDefault, 'default output');
        $this->assertEquals($expectedOutputKeys,    $outputKeys,    'keys output only');
        $this->assertEquals($expectedOutputAll,     $outputAll,     'all output');
    }

    public function testSupportedLocalesLocalized()
    {
        if (!function_exists('gettext')) {
            $this->markTestSkipped('gettext() support missing');
        } else {
            // Set language to "backwards_en_US"
            putenv('LC_MESSAGES=backwards_en_US');
            setlocale(LC_MESSAGES, 'backwards_en_US');

            // Specify location of translation tables
            bindtextdomain('OpenGraphProtocolTools', (dirname(__FILE__) . '/locale'));

            // Choose domain
            textdomain("OpenGraphProtocolTools");

            // Translation is looking for in ./locale/backwards_en_US/LC_MESSAGES/OpenGraphProtocolTools.mo now

            // Print a test message
            $backwardsOutputAll         = $this->callStaticSupportedLocales(false);
            $expectedBackwardsOutputAll = array(
                'af_ZA' => 'snaakirfA',
                'ar_AR' => 'cibarA',
                'az_AZ' => 'irezA',
                'be_BY' => 'naisuraleB',
                'bg_BG' => 'nairagluB',
                'bn_IN' => 'ilagneB',
                'bs_BA' => 'nainsoB',
                'ca_ES' => 'nalataC',
                'cs_CZ' => 'hcezC',
                'cy_GB' => 'hsleW',
                'da_DK' => 'hsinaD',
                'de_DE' => 'namreG',
                'el_GR' => 'keerG',
                'en_GB' => ')KU( hsilgnE',
                'en_US' => ')SU( hsilgnE',
                'eo_EO' => 'otnarepsE',
                'es_ES' => ')niapS( hsinapS',
                'es_LA' => ')aciremA nitaL( hsinapS',
                'et_EE' => 'nainotsE',
                'eu_ES' => 'euqsaB',
                'fa_IR' => 'naisreP',
                'fi_FI' => 'hsinniF',
                'fo_FO' => 'eseoraF',
                'fr_CA' => ')adanaC( hcnerF',
                'fr_FR' => ')ecnarF( hcnerF',
                'fy_NL' => 'naisirF',
                'ga_IE' => 'hsirI',
                'gl_ES' => 'naicilaG',
                'he_IL' => 'werbeH',
                'hi_IN' => 'idniH',
                'hr_HR' => 'naitaorC',
                'hu_HU' => 'nairagnuH',
                'hy_AM' => 'nainemrA',
                'id_ID' => 'naisenodnI',
                'is_IS' => 'cidnalecI',
                'it_IT' => 'nailatI',
                'ja_JP' => 'esenapaJ',
                'ka_GE' => 'naigroeG',
                'ko_KR' => 'naeroK',
                'ku_TR' => 'hsidruK',
                'la_VA' => 'nitaL',
                'lt_LT' => 'nainauhtiL',
                'lv_LV' => 'naivtaL',
                'mk_MK' => 'nainodecaM',
                'ml_IN' => 'malayalaM',
                'ms_MY' => 'yalaM',
                'nb_NO' => ')lamkob( naigewroN',
                'ne_NP' => 'ilapeN',
                'nl_NL' => 'hctuD',
                'nn_NO' => ')ksronyn( naigewroN',
                'pa_IN' => 'ibajnuP',
                'pl_PL' => 'hsiloP',
                'ps_AF' => 'othsaP',
                'pt_PT' => ')lizarB( eseugutroP',
                'ro_RO' => 'nainamoR',
                'ru_RU' => 'naissuR',
                'sk_SK' => 'kavolS',
                'sl_SI' => 'nainevolS',
                'sq_AL' => 'nainablA',
                'sr_RS' => 'naibreS',
                'sv_SE' => 'hsidewS',
                'sw_KE' => 'ilihawS',
                'ta_IN' => 'limaT',
                'te_IN' => 'uguleT',
                'th_TH' => 'iahT',
                'tl_PH' => 'onipiliF',
                'tr_TR' => 'hsikruT',
                'uk_UA' => 'nainiarkU',
                'vi_VN' => 'esemanteiV',
                'zh_CN' => ')anihC( esenihC deifilpmiS',
                'zh_HK' => ')gnoK gnoH( esenihC lanoitidarT',
                'zh_TW' => ')nawiaT( esenihC lanoitidarT'
            );
            $this->assertEquals($expectedBackwardsOutputAll, $backwardsOutputAll, 'nested output');

            // restore default language
            putenv('LC_MESSAGES=en_US');
            setlocale(LC_MESSAGES, 'en_US');

            // restore domain
            textdomain("OpenGraphProtocolTools");
        }
    }

    public function testIsValidUrl()
    {
        $this->assertEquals('', $this->callStaticIsValidUrl(''), 'Empty string gives empty string');
        $this->assertEquals('', $this->callStaticIsValidUrl($this), 'non-string gives empty string');
        $this->assertEquals('', $this->callStaticIsValidUrl('not a url'), 'non-URL gives empty string');
        $this->assertEquals(
            'http://php.net/downloads.php/',
            $this->callStaticIsValidUrl('http://php.net/downloads.php/'),
            'valid url gives itself'
        );
        $this->assertEquals(
            'http://php.net/downloads.php?param=1234',
            $this->callStaticIsValidUrl('http://php.net/downloads.php?param=1234'),
            'valid url gives itself'
        );
        $this->assertEquals(
            'http://php.net/downloads.php#bookmark',
            $this->callStaticIsValidUrl('http://php.net/downloads.php#bookmark'),
            'valid url gives itself'
        );
        $this->assertEquals(
            'http://php.net/downloads.php?param=1234#bookmark',
            $this->callStaticIsValidUrl('http://php.net/downloads.php?param=1234#bookmark'),
            'valid url gives itself'
        );
        $this->assertEquals(
            'https://www.google.com/',
            $this->callStaticIsValidUrl('https://www.google.com/'),
            'valid url gives itself'
        );
    }

    public function testToHTML()
    {
        $empty = $this->createOpenGraphProtocol();
        $this->assertEquals('', $empty->toHTML(), 'Empty object gives no output');
    }

    public function getLengthLimitedProperties()
    {
        return array(
            array('setTitle', 'getTitle', 'title', 'og:title', 128),
            array('setSiteName', 'getSiteName', 'site name', 'og:site_name', 128),
            array('setDescription', 'getDescription', 'description', 'og:description', 255)
        );
    }

    abstract protected function expectFailure($operation, $failure);

    /**
     * @dataProvider getLengthLimitedProperties
     */
    abstract public function testDefaultLengthLimitedPropertyTruncation($setter, $getter, $humanReadable, $property, $maxLength);

    /**
     * @dataProvider getLengthLimitedProperties
     */
    public function testLengthLimitedProperties($setter, $getter, $humanReadable, $property, $maxLength)
    {
        $ogpt = $this->createOpenGraphProtocol();
        $ogpt->$setter('not null');
        $this->assertEquals('not null', $ogpt->$getter(), 'expected value');
        $this->expectFailure(
            function () use ($ogpt, $setter) {
                $ogpt->$setter(null);
            },
            'Invalid ' . $humanReadable . ': NULL'
        );
        $this->assertEquals('not null', $ogpt->$getter(), 'did not change');
        $this->expectFailure(
            function () use ($ogpt, $setter) {
                $ogpt->$setter(array());
            },
            'Invalid ' . $humanReadable . ": array (\n)"
        );
        $this->assertEquals('not null', $ogpt->$getter(), 'did not change');
        $ogpt->$setter(str_repeat('a', $maxLength));
        $this->assertEquals(str_repeat('a', $maxLength), $ogpt->$getter(), 'correct value');
        $this->expectFailure(
            function () use ($ogpt, $setter, $maxLength) {
                $ogpt->$setter(str_repeat('b', ($maxLength + 1)), false);
            },
            ucfirst($humanReadable) . ' too long: \'' . str_repeat('b', ($maxLength + 1)) . '\''
        );
        $this->assertEquals(str_repeat('a', $maxLength), $ogpt->$getter(), 'did not change');
        $this->assertEquals($ogpt->$setter(str_repeat('c', ($maxLength + 1)), true), $ogpt, 'should return self');
        $this->assertEquals(str_repeat('c', $maxLength), $ogpt->$getter(), 'correct value');
        $this->assertEquals($ogpt->$setter('War & Peace'), $ogpt, 'should return self');
        $this->assertEquals('War & Peace', $ogpt->$getter(), 'correct value');
        $this->assertEquals('<meta property="' . $property . '" content="War &amp; Peace">', $ogpt->toHTML(), 'correct value');
    }

    public function getSetTestedStringProperties()
    {
        $this->assertFalse($this->getOpenGraphProtocolConstant('VERIFY_URLS'), 'ensure that we\'re not validating urls');
        $invalidUrls = array();
        if ($this->getOpenGraphProtocolConstant('VERIFY_URLS')) { /* This is disabled */
            $invalidUrls[] = 'This is not a url';
        }

        return array(
            array('Type',       array('not_a_valid_type'),                             array('university', 'cafe', 'video.tv_show')                                   ),
            array('URL',        $invalidUrls,                                          array('http://www.google.com/search?q=widget', 'https://www.bankofamerica.com')),
            array('Determiner', array('der','die','das', 'its', 'their'),              array('a','an','auto','the')                                                   ),
            array('Locale',     array('English', 'Pig Latin', 'Welsch', 'ISO-8859-1'), array('fy_NL', 'ga_IE', 'gl_ES', 'he_IL', 'hi_IN')                             )
        );
    }

    /**
     * @dataProvider getSetTestedStringProperties
     */
    public function testSetTestedStringProperties($propertyName, $invalidValues, $validValues)
    {
        $setter        = "set{$propertyName}";
        $getter        = "get{$propertyName}";
        $humanReadable = strtolower($propertyName);
        $property      = 'og:' . strtolower($propertyName);
        $ogpt          = $this->createOpenGraphProtocol();
        $invalidValueMap = array(
            'NULL'       => null,
            "array (\n)" => array(),
            "''"         => ''
        );
        foreach ($invalidValues as $invalidString) {
            $invalidValueMap["'{$invalidString}'"] = $invalidString;
        }
        foreach ($invalidValueMap as $stringRepresentation => $invalidInputValue) {
            $this->expectFailure(
                function () use ($ogpt, $setter, $invalidInputValue) {
                    $ogpt->$setter($invalidInputValue);
                },
                "Invalid {$humanReadable}: {$stringRepresentation}"
            );
        }
        foreach ($validValues as $validString) {
            $this->assertEquals($ogpt->$setter($validString), $ogpt, 'should return self');
            $this->assertEquals($validString, $ogpt->$getter(), 'correct value');
            $this->assertEquals('<meta property="' . $property . '" content="' . $validString . '">', $ogpt->toHTML(), 'correct value');
        }
    }

    abstract protected function getMediaToAdd();

    /**
     * @dataProvider getMediaToAdd
     */
    public function testAddMedia($class, $extension, $kind)
    {
        $getter = 'get' . ucfirst($kind);
        $setter = 'add' . ucfirst($kind);
        $ogpt = $this->createOpenGraphProtocol();
        $this->assertNull($ogpt->$getter(), 'initial value');
        $mediaPaths = array('/my' . ucfirst($kind) . ".{$extension}", '/other' . ucfirst($kind) . ".{$extension}");
        foreach ($mediaPaths as $index => $contentPath) {
            $image = new $class();
            $image->setURL("http://localhost{$contentPath}");
            $image->setSecureURL("https://localhost{$contentPath}");
            $ogpt->$setter($image);
            $mediaList = $ogpt->$getter();
            $expectedHtmlParts = array();
            $this->assertInternalType('array', $mediaList, 'correct type');
            $this->assertCount(1 + $index, $mediaList, 'correct image count');
            for ($i = 0; $i <= $index; $i++) {
                $this->assertInternalType('array', $mediaList[$i], 'correct type');
                $this->assertCount(2, $mediaList[$i], 'correct item count');
                $this->assertEquals("http://localhost{$mediaPaths[$i]}", $mediaList[$i][0], 'correct item count');
                $this->assertInternalType('array', $mediaList[$i][1], 'correct type');
                $this->assertCount(1, $mediaList[$i][1], 'correct count');
                $this->assertInstanceOf($class, $mediaList[$i][1][0], 'correct class');
                $this->assertNull($mediaList[$i][1][0]->getURL(), 'Url unset');
                $this->assertEquals("https://localhost{$mediaPaths[$i]}", $mediaList[$i][1][0]->getSecureURL(), 'correct value');
                $expectedHtmlParts[] =
                      "<meta property=\"og:{$kind}\" content=\"http://localhost{$mediaPaths[$i]}\">\n" .
                    "<meta property=\"og:{$kind}:secure_url\" content=\"https://localhost{$mediaPaths[$i]}\">";
            }
            $this->assertEquals(join("\n", $expectedHtmlParts), $ogpt->toHTML(), 'correct value');
        }
    }
}
