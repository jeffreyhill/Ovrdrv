<?php
defined('OVRDRV') or die('Access denied');

class Controller {
    
    static $data;
    
    static function load($lib)
    {
        try {
            require_once(dirname(__FILE__).DS.'lib'.DS.strtolower($lib).'.php');
        } catch (Exception $e)
        {
            die('Library '.$lib.' not found');
        }
    }
    
    public static function execute($task)
    {
        self::$task();
    }
    
    public static function init()
    {
        try {
            require_once(dirname(__FILE__).DS.'view.php');
        } catch (Exception $e)
        {
            die($e->getMessage());
        }
    }
    
    private static function index()
    {
        View::display('index');
    }
    
    private static function submit()
    {
        self::load('Request');
        self::$data = Request::getVar('frm');
		if(empty(self::$data['email']))
		{
			self::message('Please include an email address');
		}
		if(!preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', self::$data['email']))
		{
			self::message('Email address not valid');
		}
		View::send('confirm', self::$data);
        self::message('Thank you for your interest!');
    }
    
    public static function message($message)
    {
        View::display('index', array('message'=>$message));
    }
}
