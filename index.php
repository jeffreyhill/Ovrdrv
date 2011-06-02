<?php
define('OVRDRV', 1);
define('DS','/');
define('BASEURL', "http://".$_SERVER['HTTP_HOST'].DS.'ovrdrv');

try{
    require_once(dirname(__FILE__).DS.'config.php');
} 
catch (Exception $e)
{
    die('Error: Configuration not loaded');
}
try {
    require_once(dirname(__FILE__).DS.'controller.php');
}
catch (Exception $e)
{
    die('Error: Controller not loaded');
}
Controller::init();
Controller::load('Request');
$task = Request::getWord('task','index');
if(method_exists('Controller', $task))
{
    Controller::execute($task);
} else {
    Controller::error('Task not found');
}
?>