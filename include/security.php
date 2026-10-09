<?php
defined('_VALID') or die('Restricted Access!');

/*
 * Magic quotes were removed back in PHP 5.4, so the old
 * get_magic_quotes_gpc() / each() block was dead code - and on PHP 8 it is a
 * fatal error (both functions were removed). Removed for PHP 8 compatibility.
 */

function disableRegisterGlobals()
{
    if( (bool)@ini_get('register_globals') ) {
        $noUnset    = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');                                                                   
        $input      = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
    
        foreach ($input as $k => $v) {
            if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                unset($GLOBALS[$k]);
            }
        }
    }
}
?>
