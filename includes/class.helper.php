<?php

if (!defined('IN_SWPM'))
{
	die();
}

class swpm_xmlrpc_helper
{
	public function __construct()
	{
	}

	/*public function login($username, $password)
	{
		$user = wp_authenticate($username, $password);

		if (is_wp_error($user))
		{
			return new IXR_Error(403, __('Bad username/password combination.'));
		}

		wp_set_current_user($user->ID);
		return $user;
	}*/
	public function login($username)
	{
		$user = wp_set_current_user(null, $username);

		if (is_wp_error($user))
		{
			return new IXR_Error(403, __('Bad username.'));
		}

		return $user;
	}
}

/* EOF */