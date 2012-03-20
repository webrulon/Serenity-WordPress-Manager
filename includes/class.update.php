<?php

if (!defined('IN_SWPM'))
{
	die();
}

if (!class_exists('WP_Upgrader'))
{
	include(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
}

class swpm_xmlrpc_update extends swpm_xmlrpc_helper
{
	public function __construct($methods)
	{
		$this->methods = $methods + array(
			'update.updateCore'			=> array($this, 'update_core'),
			'update.getPlugins'			=> array($this, 'get_plugins'),
			'update.updatePlugins'		=> array($this, 'update_plugins'),
			'update.getThemes'			=> array($this, 'get_themes'),
			'update.updateThemes'		=> array($this, 'update_themes'),
		);
	}

	public function update_core($args)
	{
		$user = $this->login($args[0]);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$headers = get_headers('http://wordpress.org/latest', 1);
		$version = str_replace(array(' ', 'attachment;', 'filename=','wordpress-', '.tar.gz'), '', $headers['Content-Disposition']);

		$update = find_core_update($version, get_locale());

		ob_start();
		$upgrader = new swpm_core_upgrader();
		$result = $upgrader->upgrade($update);
		ob_end_clean();

		if (is_wp_error($result))
		{
			if ('up_to_date' == $result->get_error_code())
			{
				return 'Already newest version';
			}
			else
			{
				return 'Update failed: ' . $result->get_error_code();
			}
		}

		return 'Update succeeded';
	}

	public function get_plugins($args, $user = false)
	{
		$updates_only = (isset($args[1]) && 'true' == $args[1]) ? true : false;

		if (!$user)
		{
			$user = $this->login($args[0]);
			if (is_a($user, 'IXR_Error'))
			{
				return $user;
			}
		}

		require_once(ABSPATH . 'wp-admin/includes/plugin.php');

		// Get list of plugins
		$plugins = (array) get_plugins();
		$active_plugins = get_option('active_plugins', array());

		// Check for updates
		wp_update_plugins();

		$transient = get_site_transient('update_plugins');

		$updated = array();
		foreach ($plugins as $file => $plugin)
		{
			$new_version = (isset($transient->response[$file])) ? $transient->response[$file]->new_version : false;

			if ($updates_only && !$new_version)
			{
				continue;
			}

			$updated[$file]['active'] = (is_plugin_active($file)) ? true : false;

			if ($new_version)
			{
				$current = $transient->response[$file];

				$updated[$file] = array(
					'latest_version'		=> $new_version,
					'latest_package'		=> $current->package,
					'slug'					=> $current->slug,
				);
			}
			else
			{
				$updated[$file]['latest_version'] = $plugin['Version'];
			}
		}

		return $updated;
	}

	public function update_plugins($args)
	{
		$username = $args[0];
		$updates = (isset($args[1])) ? (array) $args[1] : array();

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$plugins = $this->get_plugins(array($username, 'true'), $user);

		$updates = array();
		foreach ($plugins as $file => $plugin)
		{
			if ((!empty($updates) && in_array($file, $updates)) || empty($updates))
			{
				$updates[] = $file;
			}
		}

		ob_start();
		$skin = new swpm_plugin_upgrader_skin();
		$upgrader = new Plugin_Upgrader($skin);
		$upgrader->bulk_upgrade($updates);
		$errors = ob_get_clean();

		return (!empty($errors)) ? $errors : 'Updated successfully';
	}

	public function get_themes($args, $user = false)
	{
		$updates_only = (isset($args[1]) && 'true' == $args[1]) ? true : false;

		if (!$user)
		{
			$user = $this->login($args[0]);
			if (is_a($user, 'IXR_Error'))
			{
				return $user;
			}
		}

		require_once(ABSPATH . 'wp-admin/includes/theme.php');

		// Get list of themes
		$themes = get_themes();
		$current = get_option('current_theme');

		// Check for updates
		wp_update_themes();

		$transient = get_site_transient('update_themes');

		$updated = array();
		foreach ($themes as $theme)
		{
			$new_version = (isset($transient->response[$theme['Template']])) ? $transient->response[$theme['Template']]['new_version'] : false;

			if ($updates_only && !$new_version)
			{
				continue;
			}

			$updated[$theme['Name']]['active'] = ($current == $theme['Name']) ? true : false;

			if ($new_version)
			{
				$active = $transient->response[$theme['Template']];

				$updated[$theme['Name']] = array(
					'latest_version'		=> $new_version,
					'latest_package'		=> $active['package'],
					'slug'					=> $theme['Template'],
				);
			}
			else
			{
				$updated[$theme['Name']]['latest_version'] = $theme['Version'];
			}
		}

		return $updated;
	}

	public function update_themes($args)
	{
		$username = $args[0];
		$updates = (isset($args[1])) ? (array) $args[1] : array();

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$themes = $this->get_themes(array($username, 'true'), $user);

		$updates = array();
		foreach ($themes as $name => $theme)
		{
			if ((!empty($updates) && in_array($theme['slug'], $updates)) || empty($updates))
			{
				$updates[] = $theme['slug'];
			}
		}

		ob_start();
		$skin = new swpm_theme_upgrader_skin();
		$upgrader = new Theme_Upgrader($skin);
		$upgrader->bulk_upgrade($updates);
		$errors = ob_get_clean();

		return (!empty($errors)) ? $errors : 'Updated successfully';
	}
}

class swpm_core_upgrader extends Core_Upgrader
{
	public function __construct()
	{
		parent::__construct();

		$this->skin = new swpm_core_upgrader_skin();
	}
}

class swpm_core_upgrader_skin extends WP_Upgrader_Skin
{
	public function __construct()
	{
		parent::__construct();
	}

	public function feedback()
	{
	}
}

class swpm_plugin_upgrader_skin extends Bulk_Plugin_Upgrader_Skin
{
	public $feedback;
	public $error;

	public function error($error)
	{
		$this->error = $error;
	}

	public function feedback($feedback)
	{
		$this->feedback = $feedback;
	}

	public function before()
	{
	}

	public function after()
	{
	}

	public function bulk_header()
	{
	}

	public function bulk_footer()
	{
	}
}

class swpm_theme_upgrader_skin extends Bulk_Theme_Upgrader_Skin
{
	public $feedback;
	public $error;

	public function error($error)
	{
		$this->error = $error;
	}

	public function feedback($feedback)
	{
		$this->feedback = $feedback;
	}

	public function before()
	{
	}

	public function after()
	{
	}

	public function bulk_header()
	{
	}

	public function bulk_footer()
	{
	}
}