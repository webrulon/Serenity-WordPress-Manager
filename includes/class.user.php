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
			'user.addUsers'			=> array($this, 'add_users'),
			'user.editUsers'			=> array($this, 'edit_users'),
			'user.deleteUsers'		=> array($this, 'delete_users'),
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
		$username = $args[0];
		$query_args = (isset($args[1])) ? $args[1] : array();

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$query_args = array(
			'number'	=> (isset($query_args['number'])) ? $query_args['number'] : 20,
			'offset'	=> (isset($query_args['offset'])) ? $query_args['offset'] : 0,
			'role'		=> (isset($query_args['role'])) ? $query_args['role'] : '',
			'search'	=> (isset($query_args['search'])) ? '*' . $query_args['search'] . '*' : '',
			'fields'	=> (isset($query_args['fields'])) ? $query_args['fields'] : 'all_with_meta',
		);

		$users = new WP_User_Query($query_args);
		return $users->get_results();
	}

	public function add_users($args)
	{
		$username = $args[0];
		$userdata = $args[1];

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		// We need a multidimensional array so the foreach loop doesn't error
		$users = (!is_array($userdata[0])) ? array($userdata) : $userdata;

		$errors = array();
		foreach ($users as $user)
		{
			$result = wp_insert_user($user);

			if (is_wp_error($result))
			{
				$errors[] = $result->get_error_code();
			}
		}

		return (!empty($errors)) ? implode("\n", $errors) : 'User(s) inserted successfully';
	}

	public function edit_users($args)
	{
		$username = $args[0];
		$userdata = $args[1];

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		// We need a multidimensional array so the foreach loop doesn't error
		$users = (!is_array($userdata[0])) ? array($userdata) : $userdata;

		foreach ($users as $user)
		{
			$result = wp_update_user($user);
		}

		return 'User(s) updated successfully';
	}

	public function delete_users($args)
	{
		$username = $args[0];
		$users = (array) $args[1];
		$reassign = (isset($args[2])) ? $args[2] : 'novalue';

		if (in_array($username, $users))
		{
			return 'Cannot delete ' . $username . ' while in use';
		}

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		foreach ($users as $user)
		{
			if (is_string($user))
			{
				$user_id = get_user_by('login', $user);
				$user_id = $user_id->ID;
			}
			else
			{
				$user_id = $user;
			}

			wp_delete_user($user_id, $reassign);
		}

		return 'User deleted successfully';
	}
}

/* EOF */