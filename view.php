<?php
defined('OVRDRV') or die('Access denied');

class View {
    
    /*
     * The data to display in the view
     * @type array
     */
    static $data;
	
	/*
     * Output contents
     * @type string
     */
    static $contents;
    
    public static function display($tmpl, $data = null)
    {
        self::$data = $data;
        ob_start();
        require_once(dirname(__FILE__).DS.'tmpl'.DS.$tmpl.'.php');
        self::$contents = ob_get_clean();
		self::render();
    }
	
    private static function render()
    {
        ob_start();
        require_once(dirname(__FILE__).DS.'template.php');
        $doc = ob_get_clean();
		if(!empty(self::$data['message']))
		{
			$doc = str_replace('<**MESSAGE**>',self::$data['message'],$doc);
		}
        $doc = str_replace('<**CONTENT**>',self::$contents,$doc);
        echo $doc;
        exit();
    }
	
	public static function send($tmpl, $data = null)
	{
		self::$data = $data;
        ob_start();
        require_once(dirname(__FILE__).DS.'tmpl'.DS.$tmpl.'.php');
        self::$contents = ob_get_clean();
		
		$headers = "Content-Type: text/html; charset=iso-8859-1\n".$headers;
		$subject= "Clickfil Inquiry";
		
		@mail('***',$subject,self::$contents,$headers);
		return true;
	}
}
