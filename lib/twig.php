<?php

class PrincipiaForumExtension extends \Twig\Extension\AbstractExtension {
	public function getFunctions() {
		return [
			// datetime.php
			new \Twig\TwigFunction('timeunits', 'timeunits', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('timeunits2', 'timeunits2', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('timelink', 'timelink', ['is_safe' => ['html']]),

			// layout.php
			new \Twig\TwigFunction('rendernewstatus', 'rendernewstatus', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('render_page_bar', 'RenderPageBar', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('if_empty_query2', 'if_empty_query2', ['is_safe' => ['html']]),

			// perm.php
			new \Twig\TwigFunction('load_user_permset', 'load_user_permset'),
			new \Twig\TwigFunction('permset_for_user', 'permset_for_user'),
			new \Twig\TwigFunction('is_root_gid', 'is_root_gid'),
			new \Twig\TwigFunction('gid_for_user', 'gid_for_user'),
			new \Twig\TwigFunction('load_guest_permset', 'load_guest_permset'),
			new \Twig\TwigFunction('load_bot_permset', 'load_bot_permset'),
			new \Twig\TwigFunction('title_for_perm', 'title_for_perm'),
			new \Twig\TwigFunction('apply_group_permissions', 'apply_group_permissions'),
			new \Twig\TwigFunction('in_permset', 'in_permset'),
			new \Twig\TwigFunction('can_edit_post', 'can_edit_post'),
			new \Twig\TwigFunction('can_edit_group_assets', 'can_edit_group_assets'),
			new \Twig\TwigFunction('can_edit_user_assets', 'can_edit_user_assets'),
			new \Twig\TwigFunction('can_edit_user', 'can_edit_user'),
			new \Twig\TwigFunction('forums_with_view_perm', 'forums_with_view_perm'),
			new \Twig\TwigFunction('can_view_forum', 'can_view_forum'),
			new \Twig\TwigFunction('needs_login', 'needs_login'),
			new \Twig\TwigFunction('can_create_forum_thread', 'can_create_forum_thread'),
			new \Twig\TwigFunction('can_create_forum_post', 'can_create_forum_post'),
			new \Twig\TwigFunction('can_edit_forum_posts', 'can_edit_forum_posts'),
			new \Twig\TwigFunction('can_delete_forum_posts', 'can_delete_forum_posts'),
			new \Twig\TwigFunction('can_edit_forum_threads', 'can_edit_forum_threads'),
			new \Twig\TwigFunction('has_perm', 'has_perm'),
			new \Twig\TwigFunction('has_perm_with_bindvalue', 'has_perm_with_bindvalue'),
			new \Twig\TwigFunction('parent_group_for_group', 'parent_group_for_group'),
			new \Twig\TwigFunction('perms_for_x', 'perms_for_x'),

			// post.php
			new \Twig\TwigFunction('threadpost', 'threadpost', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('posttoolbar', 'posttoolbar', ['is_safe' => ['html']]),
		];
	}
	public function getFilters() {
		return [

		];
	}
}
