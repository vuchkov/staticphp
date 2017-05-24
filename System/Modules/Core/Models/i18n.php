<?php

namespace Core\Models;

use \Core\Models\Load;
use \Core\Models\Router;
use \Core\Models\Db;
use \Core\Models\Cache;

/**
 *  Internationalization (i18n).
 */
class i18n
{
    /**
     *  Array holding all i18n config.
     *
     * (default value: null)
     *
     * @var array
     * @access public
     * @static
     */
    public static $config = null;

    /**
     *  Array holding info of all available countries.
     *
     * (default value: null)
     *
     * @var array
     * @access public
     * @static
     */
    public static $countries = null;

    /**
     *  Currently active country.
     *
     * (default value: null)
     *
     * @var array
     * @access public
     * @static
     */
    public static $current_country = null;

    /**
     *  Current country's abbreviation code.
     *
     * (default value: null)
     *
     * @var array
     * @access public
     * @static
     */
    public static $country_code = null;

    /**
     *  Current language's abbreviation code.
     *
     * (default value: null)
     *
     * @var string
     * @access public
     * @static
     */
    public static $language_code = null;

    /**
     *  Current url prefix
     *
     * (default value: null)
     *
     * @var string
     * @access public
     * @static
     */
    public static $url_prefix = null;

    /**
     *  Language key to look for in database
     *
     * (default value: null)
     *
     * @var string
     * @access public
     * @static
     */
    public static $language_key = null;

    /**
     *  Cache key for setting and getting cached values
     *
     * (default value: '')
     *
     * @var string
     * @access public
     * @static
     */
    private static $cache_key = '';

    /**
     *  Current country's and language's cached strings.
     *
     * (default value: [])
     *
     * @var array
     * @access public
     * @static
     */
    private static $cache = [];


    /*
     * =============================================== Main Methods ====================================================
     */

    /**
     *  Country & language hash value.
     *
     * @access public
     * @static
     * @return string
     */
    public static function hash()
    {
        return sha1(self::$country_code.self::$language_code);
    }

    /**
     *  Make country and language prefix.
     *
     * @access public
     * @static
     * @param  array $country
     * @param  string $language
     * @return void
     */
    public static function urlPrefix($country, $language)
    {
        return str_replace(['{{country}}', '{{language}}'], [$country['code'], $language], self::$config['url_format']);
    }

    /**
     * Prints debug information.
     *
     * @access public
     * @static
     * @return void
     */
    public static function debug()
    {
        echo "i18n::\$url_prefix";
        print_r(self::$url_prefix);
        echo "\n";

        echo "i18n::\$country_code: ";
        print_r(i18n::$country_code);
        echo "\n";

        echo "i18n::\$language_code: ";
        print_r(i18n::$language_code);
        echo "\n";

        echo "i18n::\$current_country: ";
        print_r(i18n::$current_country);
        echo "\n";

        echo "i18n::\$countries: ";
        print_r(i18n::$countries);
        echo "\n";

        echo "i18n::\$config: ";
        print_r(i18n::$config);
        echo "\n";

        echo "i18n::\$cache: ";
        print_r(i18n::$cache);
        echo "\n";
    }

    /**
     *  Init stuff.
     *
     * @access public
     * @static
     * @return void
     */
    public static function init()
    {
        // If i18n config is not already loaded, do it now
        if (empty(Load::$config['i18n'])) {
            Load::Config('i18n');
        }

        // Default country
        self::$config = &Load::$config['i18n'];
        self::$countries = &self::$config['available'];
        self::$current_country = reset(self::$countries);
        self::$country_code = &self::$current_country['code'];
        self::$language_code = reset(self::$current_country['languages']);

        // Search for current country in URI
        $found_country_language = false;
        foreach (self::$countries as &$country) {
            foreach ($country['languages'] as &$language) {
                $test = self::urlPrefix($country, $language);
                if (in_array($test, Router::$prefixes)) {
                    self::$current_country = &$country;
                    self::$country_code = &self::$current_country['code'];
                    self::$language_code = &$language;
                    self::$url_prefix = $test;

                    $found_country_language = true;
                    break;
                }
            }
        }

        // Redirect to default language
        if ($found_country_language === false) {
            if (!empty(self::$config['redirect'])) {
                $url  = self::urlPrefix(self::$current_country, self::$language_code);
                $url .= Router::$requested_url;
                Router::redirect($url);
            } else {
                self::$url_prefix = self::urlPrefix(self::$current_country, self::$language_code);
            }
        }

        // Key
        self::$language_key = self::$country_code.'_'.self::$language_code;
        self::$cache_key = self::$config['cache_prefix'].self::$language_key;

        // Load cache, if external
        if (self::$config['cache'] === 'external') {
            Cache::init();
        }

        // Load languages
        self::load();
    }

    /**
     *  Load strings.
     *
     * @access public
     * @static
     * @return void
     */
    public static function load()
    {
        $cached = Db::fetch('SELECT created FROM i18n_cached LIMIT 1');
        if (empty($cached)) {
            $res = Db::fetchAll(
                '
                    SELECT keys.key, tr.value FROM i18n_keys AS keys
                    LEFT JOIN i18n_translations AS tr ON tr.key_id = keys.id AND tr.language = ?
                    ORDER BY keys.id
                ',
                [self::$language_key]
            );

            foreach ($res as $item) {
                self::$cache[$item['key']] = $item['value'];
            }

            self::cacheWrite($res);
            self::cacheApprove();
        } else {
            $items = self::cacheRead();
            if (is_array($items) === false) {
                self::cacheInvalidate();
                self::load();
                return;
            }

            self::$cache = &$items;
        }
    }

    /**
     *  Not sure.
     *
     * @access public
     * @static
     * @param  string $ident
     * @param  array $replace
     * @param  null $escape
     * @return string
     */
    public static function item($ident, $replace = array(), $escape = NULL)
    {
        return empty($replace) ? constant($ident) : str_replace(array_keys($replace), $replace, constant($ident));
    }

    /**
     * Gets translated text.
     *
     * @access public
     * @static
     * @param  string $text Text to translate
     * @param  array $replace Replace stuff
     * @param  null $escape Escape some types of chars, for example for javascript or html input parameters
     * @return string
     */
    public static function translate($text, $replace = [], $escape = null)
    {
        if (empty(self::$cache[$text])) { // A note: using isset returns false when value is NULL returned from postgresql
            if (array_key_exists($text, self::$cache) === false) {
                $record = Db::fetch('INSERT INTO i18n_keys (key) VALUES (?) RETURNING id', $text);
            } else {
                $record = Db::fetch('SELECT id FROM i18n_keys WHERE key = ?', $text);
            }

            self::$cache[$text] = $text.'*';
            Db::query(
                'INSERT INTO i18n_translations (key_id, language, value) VALUES (?, ?, ?)',
                [$record['id'], self::$language_key, $text.'*']
            );

            // Clear cache
            self::cacheInvalidate();
        }

        // Set text to translation if its not empty
        if (!empty(self::$cache[$text])) {
            $text = self::$cache[$text];
        }

        // Do some output escaping, if pointed
        switch ($escape) {
            case 'js':
                $text = str_replace(array("'", "\r", "\n"), array("\\'", '', ''), $text);
            break;

            case 'input':
                $text = str_replace('"', '&quot;', $text);
            break;
        }

        // Return text, replace if necessary
        return empty($replace) ? $text : str_replace(array_keys($replace), $replace, $text);
    }


    /*
     * =============================================== Twig ============================================================
     */

    /**
     * Register twig methods
     *
     * @access public
     * @static
     * @param  object $engine Reference to twig engine
     * @return void
     */
    public static function twigRegister($engine)
    {
        // Variables
        Load::$config['view_data']['i18n']['country_code'] = &self::$country_code;
        Load::$config['view_data']['i18n']['language_code'] = &self::$language_code;
        Load::$config['view_data']['i18n']['url_prefix'] = &self::$url_prefix;
        Load::$config['view_data']['i18n']['countries'] = &self::$countries;

        // Register filters
        $filter = new \Twig_SimpleFilter('translate', function ($text, $replace = [], $escape = null) {
            return \Core\Models\i18n::translate($text, $replace, $escape);
        });
        $engine->addFilter($filter);


        // Register functions
        $filter = new \Twig_SimpleFunction('_', function ($text, $replace = [], $escape = null) {
            return \Core\Models\i18n::translate($text, $replace, $escape);
        });
        $engine->addFunction($filter);
    }


    /*
     * =============================================== Cache ===========================================================
     */

    /**
     * Returns path to a cache file
     *
     * @access public
     * @static
     * @return string
     */
    public static function cacheFile()
    {
        $cache_dir = APP_PATH.'Cache/'.self::$config['cache_subdir'].'/';
        $cache_file = $cache_dir.self::$cache_key.'.php';

        // Create directories
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0777, TRUE);
        }

        return $cache_file;
    }

    /**
     * Invalidates the cache
     *
     * @access public
     * @static
     * @return void
     */
    public static function cacheInvalidate()
    {
        Db::query('DELETE FROM i18n_cached');
    }

    /**
     * Approves the cache
     *
     * @access public
     * @static
     * @return void
     */
    public static function cacheApprove()
    {
        Db::query('INSERT INTO i18n_cached DEFAULT VALUES;');
    }

    /**
     * Write to cache
     *
     * @access public
     * @static
     * @param  object $items Items to set
     * @return void
     */
    public static function cacheWrite($res = null)
    {
        if (self::$config['cache'] === 'internal') {
            // Write to internal (file) cache

            $cache_file = self::cacheFile();
            $contents = "<?php\n\n# Country: ". self::$country_code ."\n# Language: ". self::$language_code ."\n\n";

            // Walk through the result
            foreach ($res as $item) {
                $item['key'] = str_replace("'", "\\'", stripslashes($item['key']));
                $item['value'] = str_replace("'", "\\'", stripslashes($item['value']));
                $contents .= "\$l['{$item['key']}'] = '{$item['value']}';\n";
            }

            // Put contents to the file
            file_put_contents($cache_file, $contents);
        } else {
            // Write to external cache (defined by Cache model)
            $cache = [];
            foreach ($res as $item) {
                $cache[$item['key']] = $item['value'];
            }

            Cache::set(self::$cache_key, $cache);
        }
    }

    /**
     * Load from cache
     *
     * @access public
     * @static
     * @return array Returns array of translations
     */
    public static function &cacheRead()
    {
        $dummy = false;

        // Load from internal (file) cache
        if (self::$config['cache'] === 'internal') {
            $cache_file = self::cacheFile();

            if (is_file($cache_file) === false) {
                return $dummy;
            }

            require $cache_file;

            if (!isset($l)) {
                return $dummy;
            }

            return $l;
        }

        // Load from external cache (defined by Cache model)
        $res = Cache::get(self::$cache_key);
        if (empty($res) || is_array($res) === false) {
            return $dummy;
        }

        return $res;
    }
}