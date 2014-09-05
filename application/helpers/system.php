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
| Sessions
|--------------------------------------------------------------------------
*/

session_start(); // php is already configured with redis sessions



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