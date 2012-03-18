<?php

if (!defined('IN_SWPM'))
{
	die();
}

if (!class_exists('IXR_Server'))
{
	include(ABSPATH . WPINC . '/class-IXR.php');
}

if (!class_exists('swpm_xmlrpc_helper'))
{
	include(SWPM_PLUGIN_DIR . 'includes/class.helper.php');
}

class swpm_xmlrpc_server extends IXR_Server
{
	public function __construct()
	{
		$this->methods = array(
			// Test methods. Taken from class-wp-xmlrpc-server.php
			'test.helloWorld'					=> 'this:test_helloWorld',
			'test.addTwoNumbers'				=> 'this:test_addTwoNumbers',
		);

		if (file_exists(SWPM_PLUGIN_DIR . 'includes/class.' . $_GET['swpm'] . '.php'))
		{
			include_once(SWPM_PLUGIN_DIR . 'includes/class.' . $_GET['swpm'] . '.php');
			$class = 'swpm_xmlrpc_' . $_GET['swpm'];

			/*$this->$_GET['swpm'] = new $class($this->methods, $this);
			$this->methods = $this->$_GET['swpm']->methods;*/
			$$class = new $class($this->methods);
			$this->methods = $$class->methods;
		}

		$this->methods = apply_filters('swpm_xmlrpc_methods', $this->methods);
	}

	public function serve_request()
	{
		$this->IXR_Server($this->methods);
	}

	public function test_helloWorld($args)
	{
		return 'Hello World!';
	}

	public function test_addTwoNumbers($args)
	{
		return $args[0] + $args[1];
	}
}

/* EOF */