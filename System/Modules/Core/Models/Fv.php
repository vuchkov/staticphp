<?php

/*
    Form Validation class
    Simple usage:

    Fv::init($_POST);
    Fv::addRules([
        'email' => [
            'valid' => ['required', 'email'],
            'filter' => ['trim'],
        ],
    ]);

    // This will print out all errors
    if (Fv::validate() == false)
    {
        print_r(Fv::$errors_all);
    }

    // And html code, this will output first error for "email" field
    <?php if (($test = Fv::getError('email')) != false): ?>
    <div class="error"><?php echo $test[0]; ?></div>
    <?php endif; ?>

    // Another usage
    <div><input type="text" name="email"<?php echo Fv::setInputValue('email'); ?> /></div>

    // And even this one
    <div><input type="text" name="test[]"<?php echo Fv::setInputValue(['test', 0]); ?> /></div>
*/

namespace Core\Models;

use Core\Models\Config;

/**
 * Form validation class.
 */
class Fv
{
    public $errors = null;
    public $errors_all = null;

    public $post = [];
    private $rules = [];

    private $default_errors = [
        'missing' => 'Field "!name" is missing',
        'required' => 'Field "!name" is required',
        'email' => '"!value" is not a correct e-mail address',
        'date' => '"!value" is not a correct date format',
        'ipv4' => '"!value" is not a correct ipv4 address',
        'ipv6' => '"!value" is not a correct ipv6 address',
        'creditCard' => '"!value" is not a correct credit card number',

        'length' => 'Field "!name" has not correct length',
        'equal' => 'Field "!name" has wrong value',
        'format' => 'Field "!name" has not a correct format',

        'integer' => 'Field "!name" must be integer',
        'float' => 'Field "!name" must be float number',
        'string' => 'Field "!name" can contain only letters, []$/!.?()-\'" and space chars',

        'uploadRequired' => 'Field "!name" is required',
        'uploadSize' => 'Uploaded file is to large',
        'uploadExt' => 'File type is not allowed',
    ];


    public function __construct()
    {
        foreach (func_get_args() as $item) {
            if (is_array($item)) {
                $this->post = array_merge($this->post, $item);
            }
        }
    }

    public function errors($errors)
    {
        $this->default_errors = array_merge($this->default_errors, $errors);
    }


    public function addRules($rules)
    {
        $this->rules = array_merge($this->rules, $rules);
    }


    public function validate()
    {
        foreach ($this->rules as $name => $value) {
            if (!isset($this->post[$name])) {
                $this->setError('missing', $name);
            } else {
                $this->filterField($name);
                $this->validateField($name);
            }
        }

        return empty($this->errors);
    }


    public function filterField($name)
    {
        if (!empty($this->rules[$name]['filter'])) {
            foreach ($this->rules[$name]['filter'] as $item) {
                if (empty($item)) {
                    return;
                }

                $matches = $args = [];
                $call = null;

                if (is_callable($item) == false) {
                    // Get args from []
                    if (preg_match('/(\w+)\[(.*)\]/', $item, $matches)) {
                        $item = $matches[1];
                        $args = explode(',', $matches[2]);
                        $args = str_replace('&#44;', ',', $args);
                    }
                }

                // Add value as first argument
                array_unshift($args, $this->post[$name]);
                array_push($args, $name);
                array_push($args, $this->post);

                // Call function
                $this->post[$name] = $this->callFunc($item, $args);
            }
        }
    }


    public function validateField($name)
    {
        if (!empty($this->rules[$name]['valid'])) {
            foreach ($this->rules[$name]['valid'] as $item) {
                if (empty($item)) {
                    return;
                }

                $matches = $args = [];
                $call = null;

                if (is_callable($item) == false) {
                    // Get args from []
                    if (preg_match('/(\w+)\[(.*)\]/', $item, $matches)) {
                        $item = $matches[1];
                        $args = explode(',', $matches[2]);
                        $args = str_replace('&#44;', ',', $args);
                    }
                }

                // Add other values
                array_unshift($args, $this->post[$name]);
                array_push($args, $name);
                array_push($args, $this->post);

                // Call function
                if ($this->callFunc($item, $args) === false) {
                    $this->setError($item, $name, $this->post[$name]);
                }
            }
        }
    }


    public function setError($type, $name, $value = '')
    {
        $this->errors_all[] = &$tmp;
        $this->errors[$name][] = &$tmp;

        if (is_string($type) === false) {
            $type = 'default';
        }

        $tmp = strtr(
            (
                !empty($this->rules[$name]['errors'][$type])
                    ? $this->rules[$name]['errors'][$type]
                    : (
                        empty($this->default_errors[$type]) ? '' : $this->default_errors[$type]
                    )
            ),
            ['!name' => $this->rules[$name]['title'] ?? $name, '!value' => $value]
        );
    }


    public function hasError($name)
    {
        return !empty($this->errors[$name]);
    }


    public function getError($name)
    {
        return (empty($this->errors[$name]) ? false : $this->errors[$name]);
    }


    protected function callFunc($func, $args = null)
    {
        // Check for callable function
        if (is_callable($func)) {
            $call =& $func;
        } elseif (method_exists(__CLASS__, $func)) {
            $call = [__CLASS__, $func];
        } elseif (function_exists($func)) {
            $call = $func;
        }

        // Call method / function
        if (!empty($call)) {
            return call_user_func_array($call, $args);
        }
    }




    /**
    *
    *   FILTER METHODS
    *
    **/

    public static function setPlain($string, $valid = '')
    {
        return preg_replace('/[^a-z_\-0-9\ \p{L}'.$valid.']+/iu', '', $string);
    }

    public static function setClean($string)
    {
        $string = strip_tags($string);
        $string = stripslashes($string);
        $string = str_replace(['<', '>'], ['&lt;', '&gt;'], $string);
        $string = trim($string, " \r\n\t");

        return $string;
    }

    public static function translit($string)
    {
        // Cache current locale, set new one as UTF8
        $current_locale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.UTF8');

        // Do some magick
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        // Revert locale
        setlocale(LC_ALL, $current_locale);

        // Return
        return $string;
    }

    // Requires iconv
    public static function setFriendly($string)
    {
        $string = self::translit($string);
        $string = strip_tags($string);
        $string = strtolower($string);
        $string = str_replace([' ', "'", '--', '--'], '-', $string);
        $string = preg_replace('/[^a-z_\-0-9]*/', '', $string);
        $string = trim($string, '-');

        return $string;
    }

    public static function xss($string)
    {
        // Decode urls
        $string = rawurldecode($string);

        // Escape non ending tags
        $string = preg_replace('#(<)([a-z]+[^>]*(</[a-z]*>|</|$))#iu', '&lt;$2', $string);

        // Avoid php tags
        $string = str_ireplace(["\t", '<?php', '<?', '?>'],  [' ', '&lt;?php', '&lt;?', '?&gt;'], $string);

        // Clean empty tags
        $string = preg_replace('#<(?!input¦br¦img¦hr¦\/)[^>]*>\s*<\/[^>]*>#iu', '', $string);

        $string = str_ireplace(["&amp;", "&lt;", "&gt;"], ["&amp;amp;", "&amp;lt;", "&amp;gt;"], $string);

        // fix &entitiy\n;
        $string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $string);
        $string = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $string);

        $string = html_entity_decode($string, ENT_COMPAT, "UTF-8");

        // remove any attribute starting with "on" or xmlns
        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])\ ?(on|xmlns)[^>]*?>#iUu', "$1>", $string);

        // remove javascript: and vbscript: protocol
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu', '$1=$2nomozbinding...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $string);

        //remove any style attributes, IE allows too much stupid things in them, eg.
        //<span style="width: expression(alert('Ping!'));"></span>
        // and in general you really don't want style declarations in your UGC

        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(class|lang|style|size|face)[^>]*>#iUu', "$1>", $string);

        //remove namespaced elements (we do not need them...)
        $string = preg_replace('#</*\w+:\w[^>]*>#i', "", $string);

        //remove really unwanted tags
        //do {
        //    $oldstring = $string;
        $string = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*(>|<|$)#i', "", $string);
        //} while ($oldstring != $string);

        return $string;
    }




    /**
    *
    *   VALIDATION METHODS
    *
    **/

    public static function required($value)
    {
        $value = trim($value);
        return !empty($value);
    }

    public static function email($email)
    {
        return (bool) preg_match("/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/ix", $email);
    }

    public static function date($value, $format = '^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$')
    {
        return self::format($value, $format);
    }

    public static function ipv4($value)
    {
        return (bool) preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $value);
    }

    public static function ipv6($value)
    {
        return (bool) preg_match('/^(^(([0-9A-F]{1,4}(((:[0-9A-F]{1,4}){5}::[0-9A-F]{1,4})|((:[0-9A-F]{1,4}){4}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,1})|((:[0-9A-F]{1,4}){3}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,2})|((:[0-9A-F]{1,4}){2}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,3})|(:[0-9A-F]{1,4}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,4})|(::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,5})|(:[0-9A-F]{1,4}){7}))$|^(::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,6})$)|^::$)|^((([0-9A-F]{1,4}(((:[0-9A-F]{1,4}){3}::([0-9A-F]{1,4}){1})|((:[0-9A-F]{1,4}){2}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,1})|((:[0-9A-F]{1,4}){1}::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,2})|(::[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,3})|((:[0-9A-F]{1,4}){0,5})))|([:]{2}[0-9A-F]{1,4}(:[0-9A-F]{1,4}){0,4})):|::)((25[0-5]|2[0-4][0-9]|[0-1]?[0-9]{0,2})\.){3}(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]{0,2})$$/', $value);
    }

    public static function creditCard($value)
    {
        $value = preg_replace('/[^0-9]+/', '', $value);

        return (bool) preg_match('/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/', $value);
    }

    public static function length($value, $from, $to = null)
    {
        $len = strlen($value);
        switch (true) {
            case ($to == '>'):
                return ($len >= $from);
                break;

            case ($to == '>'):
                return ($len <= $from);
                break;

            case (ctype_digit($to)):
                return ($len >= $from && $len <= $to);
                break;

            case ($to == '='):
            default:
                return ($len == $from);
                break;
        }
    }

    public static function equal($value, $equal, $cast = false)
    {
        return ($cast == false ? $value === $equal : $value == $equal);
    }

    public static function format($value, $format = '')
    {
        $format = str_replace('/', '\\/', $format);
        return (bool) preg_match("/$format/", $value);
    }

    public static function integer($value)
    {
        return (bool) preg_match('/^\d+$/x', $value);
    }

    public static function float($value, $delimiter = '.')
    {
        return (bool) preg_match('/^\d+'.preg_quote($delimiter, '/').'?\d+$/', $value);
    }

    public static function string($value)
    {
        return (bool) preg_match('/^[a-z\p{L}]+$/iu', $value);
    }




    public static function uploadRequired($upload)
    {
        return (is_array($upload) && !empty($upload['name']) && !empty($upload['tmp_name']) && !empty($upload['size']));
    }

    public static function uploadSize($upload, $size)
    {
        if (self::uploadRequired($upload)) {
            return ($upload['size'] <= $size);
        }
    }

    public static function uploadExt($upload, $extensions)
    {
        if (self::uploadRequired($upload)) {
            $ext = explode(' ', $extensions);
            $tmp = explode('.', $upload['name']);

            return in_array(end($tmp), $ext);
        }
    }




    /**
    *
    *   FORM HELPERS
    *
    **/

    public static function isGet()
    {
        return (strtolower($_SERVER['REQUEST_METHOD']) === 'get');
    }

    // $isset checks against $_POST not local self::$post
    public static function isPost($isset = null)
    {
        // Check if post
        if (strtolower($_SERVER['REQUEST_METHOD']) !== 'post') {
            return false;
        }

        // Check if isset keys in POST data
        if ($isset !== null) {
            foreach ((array)$isset as $key) {
                if (!isset($_POST[$key])) {
                    return false;
                }
            }
        }

        return true;
    }



    public function setInputValue($name)
    {
        if (($field = $this->getField($name)) == false) {
            return false;
        }

        return ' value="'.(!empty($field) ? htmlspecialchars($field) : '').'"';
    }

    public function setSelected($name, $test = '')
    {
        if (($field = $this->getField($name)) == false) {
            return false;
        }

        return ((is_array($field) && in_array($test, $field)) || $field == $test ? ' selected="selected"' : '');
    }


    public function setChecked($name)
    {
        if (($field = $this->getField($name)) == false) {
            return false;
        }

        return (!empty($field) ? ' checked="checked"' : '');
    }


    public function setValue($name)
    {
        if (($field = $this->getField($name)) == false) {
            return false;
        }

        return $field;
    }


    private function getField($name)
    {
        $field = $this->post;

        foreach ((array)$name as $item) {
            if (isset($field[$item])) {
                $field =& $field[$item];
            } else {
                return false;
            }
        }

        return $field;
    }


    /*
    |--------------------------------------------------------------------------
    | Register Twig filters
    |--------------------------------------------------------------------------
    */

    public static function registerTwig()
    {
        // Register filters
        $filter = new \Twig\TwigFilter('fvPlain', function ($value, $valid = '') {
            return \Core\Models\Fv::setPlain($value);
        });
        Config::$items['view_engine']->addFilter($filter);

        $filter = new \Twig\TwigFilter('fvFriendly', function ($value) {
            return \Core\Models\Fv::setFriendly($value);
        });
        Config::$items['view_engine']->addFilter($filter);

        $filter = new \Twig\TwigFilter('fvXSS', function ($value, $valid = '') {
            return \Core\Models\Fv::xss($value);
        });
        Config::$items['view_engine']->addFilter($filter);
    }
}
