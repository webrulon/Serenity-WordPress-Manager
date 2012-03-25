<?php

if (!defined('IN_SWPM'))
{
	die();
}

class swpm_worker_admin
{
	public function __construct()
	{
		add_action('init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}

	public function init()
	{
		if (!get_option('swpm_unique_key'))
		{
			add_option('swpm_unique_key', wp_generate_password(rand(20, 40)));
		}
	}

	public function admin_menu()
	{
		add_menu_page('Serenity WP Manager', 'Serenity WP Manager', 'activate_plugins', 'swpm-worker', array(&$this, 'swpm_worker_page'));
	}

	public function swpm_worker_page()
	{
		$key = get_option('swpm_unique_key');
?>
<div id="wrap">
	<h2>Settings</h2>

	<p>Your unique key: <input type="text" value="<?php echo ($key) ? $key : ''; ?>" size="50" disabled="disabled" /></p>
</div>
<?php
	}
}

$swpm_worker_admin = new swpm_worker_admin();

/* EOF */