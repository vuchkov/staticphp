<?php

class router
{

    public static $url = null;
    public static $prefixes = null;
    public static $segments = null;
    

    private static $file = null;    
    private static $class = null;
    private static $method = null;


    public static function init()
    {
        self::parse_url();
        self::split_segments();
        self::load_controller();
    }


    public static function redirect($url = '', $site_url = true, $e301 = false)
    {
        if ($e301 === true)
        {
            header("HTTP/1.1 301 Moved Permanently");
        }
        
        header("Location: ".($site_url === false ? $url : site_url($url)));
        header("Connection: close");

        exit;
    }
    
    
    public static function have_prefix($p)
    {
        return (isset(self::$prefixes[$p]));
    }
    
    
    public static function trim_slashes($s, $booth = false)
    {
        $s = str_replace('\\', '/', $s);
        return ($booth == true ? trim($s, '/') : ltrim($s, '/'));
    }
    
    
    public static function segment($index)
    {
    	return (!empty(self::$segments[$index]) ? self::$segments[$index] : false);
    }
    
    
    public static function e404()
    {
    	header("HTTP/1.0 404 Not Found");
    	load('views/E404');
    }






// ------------ PRIVATE METHODS ----------------------

    
    private static function parse_url()
    {
        // Get urls
        $domain_url = 'http'.(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].'/';
        $script_path = self::trim_slashes(dirname($_SERVER['SCRIPT_NAME']), true);
        
        // Get request string        
        self::$url = urldecode(g('config')->request_uri);
        
        // Replace directory in url
        self::$url = self::trim_slashes(preg_replace('/^\/?'.preg_quote($script_path, '/').'|\?.*/', '', self::$url), true);

        // Set config
        g('config')->domain_url = $domain_url;
        if (g('config')->base_url == 'AUTO')
        {
            g('config')->base_url = $domain_url.(!empty($script_path) ? $script_path.'/' : '');
        }
    }
    
    
    private static function split_segments()
    {
        $prefixes = array();
        $lang_prefixes = array_keys(g('config')->languages);
        $segments = explode('/', self::$url);
        
        
        // Get URL prefixes
        foreach(g('config')->url_prefixes as $item)
        {
            if (isset($segments[0]) && $segments[0] == $item)
            {
                array_shift($segments);

                $prefixes[$item] = '';
            }
        }


        // Get language
        if (empty($segments[0]) || !in_array($segments[0], $lang_prefixes))
        {
            if (g('config')->lang_redirect === true)
            {
                self::redirect(site_url(g('config')->lang_default_prefix . '/' . implode('/', $segments), implode('/', $prefixes), false), false, true);
            }
            else
            {
                $lang = g('config')->lang_default_prefix;
            }
        }
        else
        {
            $lang = $segments[0];
            array_shift($segments);
        }


        // Set language
        g('config')->language = $lang;

        // Set segments and prefixes
        self::$segments = $segments;
        self::$prefixes = $prefixes;


        // Unset local ones
        unset($segments, $prefixes, $lang);
    }


    private static function load_controller()
    {
        // Get routing settings
        $routing = g('config')->routing;
        
        // Get controllers path
        $cpath = (defined('ADMIN_PATH') ? ADMIN_PATH : APP_PATH);
        
		// Explode default method
		$tmp = explode('/', $routing['']);
		$count = count($tmp);
		
		// Set default class and method
		self::$class = $tmp[$count - 2];
        self::$method = $tmp[$count - 1];



        // If empty segments set file as class name
        if (empty(self::$segments[0]))
        {
        	self::$file = implode('/', array_slice($tmp, 0, -1));
        }
        else
        {
	        // Check config routing array
	        foreach((array)$routing as $key=>$item)
	        {
	            if (!empty($key) && !empty($item))
	            {
	                $key = str_replace('/', '\\/', $key);
	                if (preg_match('/'.$key.'/', self::$url))
                    {
                        // Explode found segments
	                    $tmp = explode('/', $item);
	                    $count = count($tmp);

                        // Set file, class and method
                        self::$file = implode('/', array_slice($tmp, 0, -1));
                        self::$class = $tmp[$count - 2];
                        self::$method = $tmp[$count - 1];

	                    unset($tmp);
	                }
	            }
	        }
	
	
	        // If there was no corresponding records from routing array, try segments
	        if (empty(self::$file))
	        {
            	self::$file = self::$segments[0];
    			$mi = 1;

                // Check for subdirectory
                if (is_dir($cpath.'controllers'.DS.self::$file))
                {
                    // Add set class name as segment[1]
                    if (!empty(self::$segments[1]))
                    {
                        self::$class = self::$segments[1];
                    }

                    // Add class name to self::$file
                    self::$file .= '/'.self::$class;

                    // Increase method index
                    ++$mi;
                }
                
                // Add default class name to self::$file
                else
                {
                    self::$class = self::$file;
                }

                self::$method = (!empty(self::$segments[$mi]) ? self::$segments[$mi] : self::$method);
	        }
		}


		self::_load_controller($cpath.'controllers'.DS.self::$file.'.php', self::$class, self::$method);
        unset($mi, $routing);
    }
    
    
    
    public static function _load_controller($File, $Class, $Method)
    {
        // Check for controller file and class name
        if (is_file($File))
        {
            include_once($File);
            if (class_exists($Class))
            {
                $methods = get_class_methods($Class);
                if (in_array($Method, $methods) || in_array('__callStatic', $methods))
                {
                    // Call our contructor
                    if (in_array('construct', $methods))
                    {
                        call_user_func(array($Class, 'construct'));
                    }
                    call_user_func(array($Class, $Method));
                }
                else
                {
                	self::e404();
                }
            }
            else
            {
                throw new Exception('Can\'t load controller');
            }
        }
        else
        {
            self::e404();
        }


        unset($methods);
    }

}

?>