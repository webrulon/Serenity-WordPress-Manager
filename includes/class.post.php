<?php

if (!defined('IN_SWPM'))
{
	die();
}

class swpm_xmlrpc_post extends swpm_xmlrpc_helper
{
	public function __construct($methods)
	{
		$this->methods = $methods + array(
			'post.getPost'			=> array($this, 'get_post'),
			'post.addPosts'			=> array($this, 'add_posts'),
			'post.editPosts'		=> array($this, 'edit_posts'),
			'post.deletePosts'		=> array($this, 'delete_posts'),
		);
	}

	// Based on wp_getPage from class-wp-xmlrpc-server.php
	public function get_post($args)
	{
		$username = $args[0];
		$blog_id = (int) $args[1];
		$post_id = (int) $args[2];
		$type = $args[3];

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		switch ($type)
		{
			case 'post':
				if (!current_user_can('edit_posts', $post_id))
				{
					return new IXR_Error(401, __('You are not allowed to edit this post.'));
				}

				$post = get_post($post_id);
			break;

			case 'page':
				if (!current_user_can('edit_pages', $post_id))
				{
					return new IXR_Error(401, __('You are not allowed to edit this page.'));
				}

				$post = get_page($post_id);
			break;

			default:
				return new IXR_Error(404, __('The selected resource does not exist.'));
			break;
		}

		if ($post->ID)
		{
			// Get content
			$post_content = get_extended($post->post_content);
			$permalink = post_permalink($post->ID);

			// Get post parent
			$parent_title = '';
			if (!empty($post->post_parent))
			{
				$parent = get_page($post->post_parent);
				$parent_title = $parent->post_title;
			}

			// Get comment and ping settings
			$allow_comments = comments_open($post->ID) ? 1 : 0;
			$allow_pings = pings_open($post->ID) ? 1 : 0;

			// Get and format dates
			$post_date = mysql2date('Ymd\TH:i:s', $post->post_date, false);
			$post_date_gmt = mysql2date('Ymd\TH:i:s', $post->post_date_gmt, false);

			// Use GMT for drafts
			if ($post->post_status == 'draft')
			{
				$post_date_gmt = get_gmt_from_date(mysql2date('Y-m-d H:i:s', $post->post_date), 'Ymd\TH:i:s');
			}

			// Get categories
			$categories = array();
			foreach (wp_get_post_categories($post->ID) as $id)
			{
				$categories[] = get_cat_name($id);
			}

			// Get author
			$author = get_userdata($post->post_author);

			// Build return
			$dictionary = array(
				'dateCreated'				=> new IXR_Date($post_date),
				'user_id'					=> $post->post_author,
				'post_id'					=> $post->ID,
				'post_status'				=> $post->post_status,
				'description'				=> $post_content['main'],
				'title'						=> $post->post_title,
				'link'						=> $permalink,
				'permalink'					=> $permalink,
				'categories'				=> $categories,
				'excerpt'					=> $post->post_excerpt,
				'text_more'					=> $post_content['extended'],
				'allow_comments'			=> $allow_comments,
				'allow_pings'				=> $allow_pings,
				'slug'						=> $post->post_name,
				'password'					=> $post->post_password,
				'parent_id'					=> $post->post_parent,
				'parent_title'				=> $parent_title,
				'order'						=> $post->menu_order,
				'author_id'					=> (string) $author->ID,
				'author'					=> $author->display_name,
				'author_display_name'		=> $author->display_name,
				'date_created_gmt'			=> new IXR_Date($post_date_gmt),
				'custom_fields'				=> $this->get_custom_fields($post_id),
			);

			if ($type == 'page')
			{
				$dictionary['template'] = get_post_meta($post->ID, '_wp_page_template', true);
				if (empty($dictionary['template']))
				{
					$dictionary['template'] = 'default';
				}
			}

			return $dictionary;
		}
		else
		{
			return new IXR_Error(404, __('The selected resource does not exist.'));
		}
	}

	public function add_posts($args)
	{
		$username = $args[0];
		$posts = (isset($args[1][0])) ? $args[1] : array($args[1]);

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		$errors = array();
		foreach ($posts as $post)
		{
			$result = wp_insert_post($post);

			if (is_wp_error($result))
			{
				$errors[] = $result->get_error_code();
			}
		}

		return (!empty($errors)) ? $errors : 'Post(s) inserted successfully';
	}

	public function edit_posts($args)
	{
		$username = $args[0];
		$posts = (isset($args[1][0])) ? $args[1] : array($args[1]);

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		foreach ($posts as $post)
		{
			wp_update_post($post);
		}

		return 'Post(s) updated successfully';
	}

	public function delete_posts($args)
	{
		$username = $args[0];
		$posts = (array) $args[1];

		$user = $this->login($username);
		if (is_a($user, 'IXR_Error'))
		{
			return $user;
		}

		foreach ($posts as $post)
		{
			if (false === wp_delete_post($post))
			{
				return 'Cannot delete post: ' . $post;
			}
		}

		return 'Post(s) deleted successfully';
	}

	// Taken from class-wp-xmlrpc-server.php
	private function get_custom_fields($post_id)
	{
		$post_id = (int) $post_id;

		$custom_fields = array();

		foreach ((array) has_meta($post_id) as $meta)
		{
			// Don't expose protected fields.
			if (!current_user_can('edit_post_meta', $post_id, $meta['meta_key']))
			{
				continue;
			}

			$custom_fields[] = array(
				'id'		=> $meta['meta_id'],
				'key'		=> $meta['meta_key'],
				'value'		=> $meta['meta_value'],
			);
		}

		return $custom_fields;
	}
}

/* EOF */