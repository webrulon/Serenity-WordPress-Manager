<?php
/*
Plugin Name: Serenity WordPress Manager
Plugin URI: http://localhost/
Description: Easily manage multiple WordPress installations from one interface.
Version: 1.0.0
Author: Kevin Murek
Author URI: http://localhost/
License: GPLv2 or later
*/

define('SWPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWPM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IN_SWPM', true);

include_once(SWPM_PLUGIN_DIR . 'includes/class.core.php');

add_filter('wp_xmlrpc_server_class', 'swpm_xmlrpc', 99);
function swpm_xmlrpc($server)
{
	if (isset($_GET['swpm']))
	{
		return 'swpm_xmlrpc_server';
	}
	else
	{
		return $server;
	}
}

/* EOF */