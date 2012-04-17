<?php

// This is the right place to set various system startup options, for example to return default headers or start a session.

/*
|--------------------------------------------------------------------------
| Send out some headers
|--------------------------------------------------------------------------
*/

header('Content-type: text/html; charset=utf-8');



/*
|--------------------------------------------------------------------------
| Memcached
|--------------------------------------------------------------------------
*/

load::$config['memcached'] = new Memcached('main_site');  
$check = load::$config['memcached']->getServerList();
if (empty($check))
{
  load::$config['memcached']->addServer('localhost', 11211);
}



/*
|--------------------------------------------------------------------------
| Sessions
|--------------------------------------------------------------------------
*/

new \models\sessions_memcached(load::$config['memcached']);



/*
|--------------------------------------------------------------------------
| CLI Access
|--------------------------------------------------------------------------
*/

if (!empty($GLOBALS['argv'][1]))
{
  load::$config['request_uri'] =& $GLOBALS['argv'][1];
}



/*
|--------------------------------------------------------------------------
| Some global functions
|--------------------------------------------------------------------------
*/

// Init ajax request response
function json_response(&$json_data)
{
  static $json_request = FALSE;
  if (empty($json_request))
  {
    header('Content-Type:application/json; charset=utf-8');
    register_shutdown_function(function(&$data){
      $data = reset($data);
      if (!empty($data))
      {
        echo json_encode($data);
      }
    }, array(&$json_data));

    $json_request = TRUE;
  }
}

?>