<?php

class home
{
  # This is called every time controller loads
  public static function _construct()
  {

  }


  # Default method
  public static function index()
  {
    $data['items'] =& $_SESSION['items'];
    if (empty($data['items']))
    {
      $data['items'] = array();
    }


    $data['items_done'] =& $_SESSION['items_done'];
    if (empty($data['items_done']))
    {
      $data['items_done'] = array();
    }
    
    // Do something heavy and add timer mark
    \load::mark_time('Before views');

    // Load view
    // Pass array (key => value) as second parameter, to get variables available in your view
    load::view(array('header', 'home/index', 'footer'), $data);
  }


  public static function _json($do = '')
  {
    json_response($json_data);
    
    switch ($do)
    {
      case 'add':
        if (empty($_POST['title']))
        {
          return;
        }
        
        if (count($_SESSION['items']) + count($_SESSION['items_done']) >= 400)
        {
          return;
        }

        $_POST['title'] = strip_tags($_POST['title']);
        if (mb_strlen($_POST['title']) > 200)
        {
          mb_strpos($_POST['title'], 0, 200);
        }

        $_SESSION['items'][]['title'] = $_POST['title'];
        $json_data['title'] = end($_SESSION['items'])['title'];
        
        $keys = array_keys($_SESSION['items']);
        $json_data['id'] = end($keys);
      break;

      case 'save':
        if (!isset($_POST['id']) || empty($_POST['title']))
        {
          return;
        }

        $_POST['title'] = strip_tags($_POST['title']);
        if (mb_strlen($_POST['title']) > 200)
        {
          mb_strpos($_POST['title'], 0, 200);
        }

        $_SESSION['items'][$_POST['id']]['title'] = $_POST['title'];
        $json_data['title'] = $_SESSION['items'][$_POST['id']]['title'];
      break;


      case 'done':
        if (!isset($_POST['id']))
        {
          return;
        }

        array_unshift($_SESSION['items_done'], $_SESSION['items'][$_POST['id']]);
        unset($_SESSION['items'][$_POST['id']]);
        $json_data['status'] = 'ok';
      break;
    }
  }
}

?>