<?php

if (!defined('IN_SWPM'))
{
	die();
}

class swpm_xmlrpc_user extends swpm_xmlrpc_helper
{
	public function __construct($methods)
	{
		$this->methods = $methods + array(
			'user.getUser'			=> array($this, 'get_user'),
			'user.getUsers'			=> array($this, 'get_users'),
			'user.addUser'			=> array($this, 'add_user'),
			'user.editUser'			=> array($this, 'edit_user'),
			'user.deleteUser'		=> array($this, 'delete_user'),
		);
	}

	public function get_user($args)
	{
		$username = $args[0];
		$field = $args[1];
		$value = $args[2];

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$user = get_user_by($field, $value);
		$user_id = $user->ID;

		return (array) get_userdata($user_id);
	}

	public function get_users($args)
	{
	}

	public function add_user($args)
	{
	}

	public function edit_user($args)
	{
	}

	public function delete_user($args)
	{
		$username = $args[0];
		$user = $args[1];
		$reassign = (isset($args[2])) ? $args[2] : 'novalue';

		if (is_string($user))
		{
			$user_id = get_user_by('login', $user);
			$user_id = $user_id->ID;
		}
		else
		{
			$user_id = $user;
		}

		if (wp_delete_user($user_id, $reassign))
		{
			return 'User deleted successfully';
		}
		else
		{
			return 'Unable to delete specified user';
		}
	}
}

/* EOF */