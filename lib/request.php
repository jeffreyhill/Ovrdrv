<?php
defined('OVRDRV') or die('Access denied');

// Portions from JRequest class (C) 2005-2008 Open Source Matters. All rights reserved.

/**
 * Create the request global object
 */
$GLOBALS['_Request'] = array();

/**
 * Set the available masks for cleaning variables
 */
define( 'Request_NOTRIM'   , 1 );
define( 'Request_ALLOWRAW' , 2 );
define( 'Request_ALLOWHTML', 4 );

class Request
{
    /**
     * Gets the request method
     * @return string
     */
    function getMethod()
    {
        $method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        return $method;
    }

    /**
     * Fetches and returns a given variable.
     * @static
     * @param   string  $name       Variable name
     * @param   string  $default    Default value if the variable does not exist
     * @param   string  $hash       Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
     * @param   string  $type       Return type for the variable, for valid values see {@link JFilterInput::clean()}
     * @param   int     $mask       Filter mask for the variable
     * @return  mixed   Requested variable
     */
    function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
    {
        // Ensure hash and type are uppercase
        $hash = strtoupper( $hash );
        if ($hash === 'METHOD') {
            $hash = strtoupper( $_SERVER['REQUEST_METHOD'] );
        }
        $type   = strtoupper( $type );
        $sig    = $hash.$type.$mask;

        // Get the input hash
        switch ($hash)
        {
            case 'GET' :
                $input = &$_GET;
                break;
            case 'POST' :
                $input = &$_POST;
                break;
            case 'FILES' :
                $input = &$_FILES;
                break;
            case 'COOKIE' :
                $input = &$_COOKIE;
                break;
            case 'ENV'    :
                $input = &$_ENV;
                break;
            case 'SERVER'    :
                $input = &$_SERVER;
                break;
            default:
                $input = &$_REQUEST;
                $hash = 'REQUEST';
                break;
        }

        if (isset($GLOBALS['_Request'][$name]['SET.'.$hash]) && ($GLOBALS['_Request'][$name]['SET.'.$hash] === true)) {
            // Get the variable from the input hash
            $var = (isset($input[$name]) && $input[$name] !== null) ? $input[$name] : $default;
            $var = self::_cleanVar($var, $mask, $type);
        }
        elseif (!isset($GLOBALS['_Request'][$name][$sig]))
        {
            if (isset($input[$name]) && $input[$name] !== null) {
                // Get the variable from the input hash and clean it
                $var = self::_cleanVar($input[$name], $mask, $type);

                // Handle magic quotes compatability
                if (get_magic_quotes_gpc() && ($var != $default) && ($hash != 'FILES')) {
                    $var = self::_stripSlashesRecursive( $var );
                }

                $GLOBALS['_Request'][$name][$sig] = $var;
            }
            elseif ($default !== null) {
                $var = self::_cleanVar($default, $mask, $type);
            }
            else {
                $var = $default;
            }
        } else {
            $var = $GLOBALS['_Request'][$name][$sig];
        }

        return $var;
    }

    function getInt($name, $default = 0, $hash = 'default')
    {
        return self::getVar($name, $default, $hash, 'int');
    }
    
    function getBool($name, $default = false, $hash = 'default')
    {
        return self::getVar($name, $default, $hash, 'bool');
    }
    
    function getWord($name, $default = '', $hash = 'default')
    {
        return self::getVar($name, $default, $hash, 'word');
    }

    function getString($name, $default = '', $hash = 'default', $mask = 0)
    {
        return (string) self::getVar($name, $default, $hash, 'string', $mask);
    }
    
    function get($hash = 'default', $mask = 0)
    {
        $hash = strtoupper($hash);

        if ($hash === 'METHOD') {
            $hash = strtoupper( $_SERVER['REQUEST_METHOD'] );
        }

        switch ($hash)
        {
            case 'GET' :
                $input = $_GET;
                break;

            case 'POST' :
                $input = $_POST;
                break;

            case 'FILES' :
                $input = $_FILES;
                break;

            case 'COOKIE' :
                $input = $_COOKIE;
                break;

            case 'ENV'    :
                $input = &$_ENV;
                break;

            case 'SERVER'    :
                $input = &$_SERVER;
                break;

            default:
                $input = $_REQUEST;
                break;
        }

        $result = self::_cleanVar($input, $mask);

        // Handle magic quotes compatability
        if (get_magic_quotes_gpc() && ($hash != 'FILES')) {
            $result = self::_stripSlashesRecursive( $result );
        }

        return $result;
    }

    /**
     * Sets a request variable
     *
     * @param   array   An associative array of key-value pairs
     * @param   string  The request variable to set (POST, GET, FILES, METHOD)
     * @param   boolean If true and an existing key is found, the value is overwritten, otherwise it is ingored
     */
    function set( $array, $hash = 'default', $overwrite = true )
    {
        foreach ($array as $key => $value) {
            self::setVar($key, $value, $hash, $overwrite);
        }
    }

    /**
     * Cleans the request from script injection.
     *
     * @static
     * @return  void
     */
    function clean()
    {
        self::_cleanArray( $_FILES );
        self::_cleanArray( $_ENV );
        self::_cleanArray( $_GET );
        self::_cleanArray( $_POST );
        self::_cleanArray( $_COOKIE );
        self::_cleanArray( $_SERVER );

        if (isset( $_SESSION )) {
            self::_cleanArray( $_SESSION );
        }

        $REQUEST    = $_REQUEST;
        $GET        = $_GET;
        $POST       = $_POST;
        $COOKIE     = $_COOKIE;
        $FILES      = $_FILES;
        $ENV        = $_ENV;
        $SERVER     = $_SERVER;

        if (isset ( $_SESSION )) {
            $SESSION = $_SESSION;
        }

        foreach ($GLOBALS as $key => $value)
        {
            if ( $key != 'GLOBALS' ) {
                unset ( $GLOBALS [ $key ] );
            }
        }
        $_REQUEST   = $REQUEST;
        $_GET       = $GET;
        $_POST      = $POST;
        $_COOKIE    = $COOKIE;
        $_FILES     = $FILES;
        $_ENV       = $ENV;
        $_SERVER    = $SERVER;

        if (isset ( $SESSION )) {
            $_SESSION = $SESSION;
        }

        // Make sure the request hash is clean on file inclusion
        $GLOBALS['_Request'] = array();
    }

    /**
     * Adds an array to the GLOBALS array and checks that the GLOBALS variable is not being attacked
     *
     * @access  protected
     * @param   array   $array  Array to clean
     * @param   boolean True if the array is to be added to the GLOBALS
     */
    function _cleanArray( &$array, $globalise=false )
    {
        static $banned = array( '_files', '_env', '_get', '_post', '_cookie', '_server', '_session', 'globals' );

        foreach ($array as $key => $value)
        {
            // PHP GLOBALS injection bug
            $failed = in_array( strtolower( $key ), $banned );

            // PHP Zend_Hash_Del_Key_Or_Index bug
            $failed |= is_numeric( $key );
            if ($failed) {
                die( 'Illegal variable <b>' . implode( '</b> or <b>', $banned ) . '</b> passed to script.' );
            }
            if ($globalise) {
                $GLOBALS[$key] = $value;
            }
        }
    }
    
    function _cleanFilter($source, $type = 'string')
    {
        // Handle the type constraint
        switch (strtoupper($type))
        {
            case 'INT' :
            case 'INTEGER' :
                // Only use the first integer value
                preg_match('/-?[0-9]+/', (string) $source, $matches);
                $result = @ (int) $matches[0];
                break;

            case 'BOOL' :
            case 'BOOLEAN' :
                $result = (bool) $source;
                break;

            case 'WORD' :
                $result = (string) preg_replace( '/[^A-Z_]/i', '', $source );
                break;

            case 'ALNUM' :
                $result = (string) preg_replace( '/[^A-Z0-9]/i', '', $source );
                break;

            case 'CMD' :
                $result = (string) preg_replace( '/[^A-Z0-9_\.-]/i', '', $source );
                $result = ltrim($result, '.');
                break;

            case 'STRING' :
                $result = (string) self::_remove(self::_decode((string) $source));
                break;

            case 'ARRAY' :
                $result = (array) $source;
                break;

            case 'PATH' :
                $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
                preg_match($pattern, (string) $source, $matches);
                $result = @ (string) $matches[0];
                break;

            default :
                // Check for static usage and assign $filter the proper variable
                if(is_object($this) && get_class($this) == 'JFilterInput') {
                    $filter =& $this;
                } else {
                    $filter =& JFilterInput::getInstance();
                }
                // Are we dealing with an array?
                if (is_array($source)) {
                    foreach ($source as $key => $value)
                    {
                        // filter element for XSS and other 'bad' code etc.
                        if (is_string($value)) {
                            $source[$key] = $filter->_remove($filter->_decode($value));
                        }
                    }
                    $result = $source;
                } else {
                    // Or a string?
                    if (is_string($source) && !empty ($source)) {
                        // filter source for XSS and other 'bad' code etc.
                        $result = $filter->_remove($filter->_decode($source));
                    } else {
                        // Not an array or string.. return the passed parameter
                        $result = $source;
                    }
                }
                break;
        }
        return $result;
    }
    
    /**
     * Clean up an input variable.
     *
     * @param mixed The input variable.
     * @param int Filter bit mask. 1=no trim: If this flag is cleared and the
     * input is a string, the string will have leading and trailing whitespace
     * trimmed. 2=allow_raw: If set, no more filtering is performed, higher bits
     * are ignored. 4=allow_html: HTML is allowed, but passed through a safe
     * HTML filter first. If set, no more filtering is performed. If no bits
     * other than the 1 bit is set, a strict filter is applied.
     * @param string The variable type {@see JFilterInput::clean()}.
     */
    function _cleanVar($var, $mask = 0, $type=null)
    {
        // If the no trim flag is not set, trim the variable
        if (!($mask & 1) && is_string($var)) {
            $var = trim($var);
        }
        return $var;
    }
    
    /**
     * Strips slashes recursively on an array
     *
     * @access  protected
     * @param   array   $array      Array of (nested arrays of) strings
     * @return  array   The input array with stripshlashes applied to it
     */
    function _stripSlashesRecursive( $value )
    {
        $value = is_array( $value ) ? array_map( array( 'Request', '_stripSlashesRecursive' ), $value ) : stripslashes( $value );
        return $value;
    }
	
	
	/**
	 * Function to determine if contents of an attribute is safe
	 *
	 * @static
	 * @param	array	$attrSubSet	A 2 element array for attributes name,value
	 * @return	boolean True if bad code is detected
	 * @since	1.5
	 */
	function checkAttribute($attrSubSet)
	{
		$attrSubSet[0] = strtolower($attrSubSet[0]);
		$attrSubSet[1] = strtolower($attrSubSet[1]);
		return (((strpos($attrSubSet[1], 'expression') !== false) && ($attrSubSet[0]) == 'style') || (strpos($attrSubSet[1], 'javascript:') !== false) || (strpos($attrSubSet[1], 'behaviour:') !== false) || (strpos($attrSubSet[1], 'vbscript:') !== false) || (strpos($attrSubSet[1], 'mocha:') !== false) || (strpos($attrSubSet[1], 'livescript:') !== false));
	}

	/**
	 * Internal method to iteratively remove all unwanted tags and attributes
	 *
	 * @access	protected
	 * @param	string	$source	Input string to be 'cleaned'
	 * @return	string	'Cleaned' version of input parameter
	 */
	function _remove($source)
	{
		$loopCounter = 0;

		// Iteration provides nested tag protection
		while ($source != $this->_cleanTags($source))
		{
			$source = $this->_cleanTags($source);
			$loopCounter ++;
		}
		return $source;
	}

	/**
	 * Internal method to strip a string of certain tags
	 *
	 * @access	protected
	 * @param	string	$source	Input string to be 'cleaned'
	 * @return	string	'Cleaned' version of input parameter
	 */
	function _cleanTags($source)
	{
		/*
		 * In the beginning we don't really have a tag, so everything is
		 * postTag
		 */
		$preTag		= null;
		$postTag	= $source;
		$currentSpace = false;
		$attr = '';	 // moffats: setting to null due to issues in migration system - undefined variable errors

		// Is there a tag? If so it will certainly start with a '<'
		$tagOpen_start	= strpos($source, '<');

		while ($tagOpen_start !== false)
		{
			// Get some information about the tag we are processing
			$preTag			.= substr($postTag, 0, $tagOpen_start);
			$postTag		= substr($postTag, $tagOpen_start);
			$fromTagOpen	= substr($postTag, 1);
			$tagOpen_end	= strpos($fromTagOpen, '>');

			// Let's catch any non-terminated tags and skip over them
			if ($tagOpen_end === false) {
				$postTag		= substr($postTag, $tagOpen_start +1);
				$tagOpen_start	= strpos($postTag, '<');
				continue;
			}

			// Do we have a nested tag?
			$tagOpen_nested = strpos($fromTagOpen, '<');
			$tagOpen_nested_end	= strpos(substr($postTag, $tagOpen_end), '>');
			if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
				$preTag			.= substr($postTag, 0, ($tagOpen_nested +1));
				$postTag		= substr($postTag, ($tagOpen_nested +1));
				$tagOpen_start	= strpos($postTag, '<');
				continue;
			}

			// Lets get some information about our tag and setup attribute pairs
			$tagOpen_nested	= (strpos($fromTagOpen, '<') + $tagOpen_start +1);
			$currentTag		= substr($fromTagOpen, 0, $tagOpen_end);
			$tagLength		= strlen($currentTag);
			$tagLeft		= $currentTag;
			$attrSet		= array ();
			$currentSpace	= strpos($tagLeft, ' ');

			// Are we an open tag or a close tag?
			if (substr($currentTag, 0, 1) == '/') {
				// Close Tag
				$isCloseTag		= true;
				list ($tagName)	= explode(' ', $currentTag);
				$tagName		= substr($tagName, 1);
			} else {
				// Open Tag
				$isCloseTag		= false;
				list ($tagName)	= explode(' ', $currentTag);
			}

			/*
			 * Exclude all "non-regular" tagnames
			 * OR no tagname
			 * OR remove if xssauto is on and tag is blacklisted
			 */
			if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) {
				$postTag		= substr($postTag, ($tagLength +2));
				$tagOpen_start	= strpos($postTag, '<');
				// Strip tag
				continue;
			}

			/*
			 * Time to grab any attributes from the tag... need this section in
			 * case attributes have spaces in the values.
			 */
			while ($currentSpace !== false)
			{
				$attr			= '';
				$fromSpace		= substr($tagLeft, ($currentSpace +1));
				$nextSpace		= strpos($fromSpace, ' ');
				$openQuotes		= strpos($fromSpace, '"');
				$closeQuotes	= strpos(substr($fromSpace, ($openQuotes +1)), '"') + $openQuotes +1;

				// Do we have an attribute to process? [check for equal sign]
				if (strpos($fromSpace, '=') !== false) {
					/*
					 * If the attribute value is wrapped in quotes we need to
					 * grab the substring from the closing quote, otherwise grab
					 * till the next space
					 */
					if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes +1)), '"') !== false)) {
						$attr = substr($fromSpace, 0, ($closeQuotes +1));
					} else {
						$attr = substr($fromSpace, 0, $nextSpace);
					}
				} else {
					/*
					 * No more equal signs so add any extra text in the tag into
					 * the attribute array [eg. checked]
					 */
					if ($fromSpace != '/') {
						$attr = substr($fromSpace, 0, $nextSpace);
					}
				}

				// Last Attribute Pair
				if (!$attr && $fromSpace != '/') {
					$attr = $fromSpace;
				}

				// Add attribute pair to the attribute array
				$attrSet[] = $attr;

				// Move search point and continue iteration
				$tagLeft		= substr($fromSpace, strlen($attr));
				$currentSpace	= strpos($tagLeft, ' ');
			}

			// Is our tag in the user input array?
			$tagFound = in_array(strtolower($tagName), $this->tagsArray);

			// If the tag is allowed lets append it to the output string
			if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {

				// Reconstruct tag with allowed attributes
				if (!$isCloseTag) {
					// Open or Single tag
					$attrSet = $this->_cleanAttributes($attrSet);
					$preTag .= '<'.$tagName;
					for ($i = 0; $i < count($attrSet); $i ++)
					{
						$preTag .= ' '.$attrSet[$i];
					}

					// Reformat single tags to XHTML
					if (strpos($fromTagOpen, '</'.$tagName)) {
						$preTag .= '>';
					} else {
						$preTag .= ' />';
					}
				} else {
					// Closing Tag
					$preTag .= '</'.$tagName.'>';
				}
			}

			// Find next tag's start and continue iteration
			$postTag		= substr($postTag, ($tagLength +2));
			$tagOpen_start	= strpos($postTag, '<');
		}

		// Append any code after the end of tags and return
		if ($postTag != '<') {
			$preTag .= $postTag;
		}
		return $preTag;
	}

	/**
	 * Internal method to strip a tag of certain attributes
	 *
	 * @access	protected
	 * @param	array	$attrSet	Array of attribute pairs to filter
	 * @return	array	Filtered array of attribute pairs
	 */
	function _cleanAttributes($attrSet)
	{
		// Initialize variables
		$newSet = array();

		// Iterate through attribute pairs
		for ($i = 0; $i < count($attrSet); $i ++)
		{
			// Skip blank spaces
			if (!$attrSet[$i]) {
				continue;
			}

			// Split into name/value pairs
			$attrSubSet = explode('=', trim($attrSet[$i]), 2);
			list ($attrSubSet[0]) = explode(' ', $attrSubSet[0]);

			/*
			 * Remove all "non-regular" attribute names
			 * AND blacklisted attributes
			 */
			if ((!preg_match('/[a-z]*$/i', $attrSubSet[0])) || (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist)) || (substr($attrSubSet[0], 0, 2) == 'on')))) {
				continue;
			}

			// XSS attribute value filtering
			if ($attrSubSet[1]) {
				// strips unicode, hex, etc
				$attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
				// strip normal newline within attr value
				$attrSubSet[1] = preg_replace('/[\n\r]/', '', $attrSubSet[1]);
				// strip double quotes
				$attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
				// convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
				if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'")) {
					$attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
				}
				// strip slashes
				$attrSubSet[1] = stripslashes($attrSubSet[1]);
			}

			// Autostrip script tags
			if (JFilterInput::checkAttribute($attrSubSet)) {
				continue;
			}

			// Is our attribute in the user input array?
			$attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);

			// If the tag is allowed lets keep it
			if ((!$attrFound && $this->attrMethod) || ($attrFound && !$this->attrMethod)) {

				// Does the attribute have a value?
				if ($attrSubSet[1]) {
					$newSet[] = $attrSubSet[0].'="'.$attrSubSet[1].'"';
				} elseif ($attrSubSet[1] == "0") {
					/*
					 * Special Case
					 * Is the value 0?
					 */
					$newSet[] = $attrSubSet[0].'="0"';
				} else {
					$newSet[] = $attrSubSet[0].'="'.$attrSubSet[0].'"';
				}
			}
		}
		return $newSet;
	}

	/**
	 * Try to convert to plaintext
	 *
	 * @access	protected
	 * @param	string	$source
	 * @return	string	Plaintext string
	 */
	function _decode($source)
	{
		// entity decode
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		foreach($trans_tbl as $k => $v) {
			$ttr[$v] = utf8_encode($k);
		}
		$source = strtr($source, $ttr);
		// convert decimal
		$source = preg_replace('/&#(\d+);/me', "utf8_encode(chr(\\1))", $source); // decimal notation
		// convert hex
		$source = preg_replace('/&#x([a-f0-9]+);/mei', "utf8_encode(chr(0x\\1))", $source); // hex notation
		return $source;
	}
}