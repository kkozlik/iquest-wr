<?php
// Set environment variables needed for serweb-frmwrk
// These variables are usualy set in apache config files. But to give them 
// effect in utilities executed from command line, they have to be set 
// in this php file.

putenv('SERWEB_DIR=/usr/share/serweb-frmwrk/');
putenv('SERWEB_SET_DIRS=/usr/share/iquest/functions/set_dirs.php');

if (file_exists(dirname(__FILE__)."/set_env.developer.php")){
	require_once (dirname(__FILE__)."/set_env.developer.php");
}

?>
