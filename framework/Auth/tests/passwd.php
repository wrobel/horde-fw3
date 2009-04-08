<?php

require_once 'Horde/Util.php';
require_once 'Horde/String.php';
require_once dirname(__FILE__) . '/../Auth.php';

$auth = Auth::factory('passwd', array('filename' => dirname(__FILE__) . '/test.passwd'));

// List users
var_dump($auth->listUsers());

// Authenticate
var_dump($auth->_authenticate('user', array('password' => 'password')));

?>
