<?php

/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source;

use Addons\Levertine\Source\Model\Album;
use Addons\Levertine\Source\Model\Embed;
use BBC\Codes;
use BBC\ParserWrapper;
use BBC\PreparseCode;
use ElkArte\Helper\Util;
use ElkArte\Languages\Txt;
use ElkArte\User;

/**
 * Class LevGalBootstrap
 *
 * This class initializes the LevGal addon and provides various helper functions.
 */
class LevGalBootstrap
{
	/** @var string */
	public static $header = '';

	/**
	 * Initializes the application by defining constants, setting defaults, and defining the necessary hooks
	 * and autoloaders. Also handles URL parsing if applicable.
	 *
	 * Called from integrate_pre_load / File is loaded from integrate_pre_include
	 *
	 * @return void
	 */
	public static function initialize()
	{
		define('LEVGAL_VERSION', '2.0.0');

		self::setDefaults();
		self::defineHooks();

		// No querystring (e.g. index.php) or querystring isn't ?media it's nothing to do with us.
		if (ELK === 'SSI' || empty($_SERVER['QUERY_STRING']) || !str_starts_with($_SERVER['QUERY_STRING'], 'media'))
		{
			return;
		}

		self::parseURL();
	}

	/**
	 * Sets default configuration values for the gallery module. Any undefined settings in the global
	 * settings array are populated with default values specified in the method.
	 *
	 * @return void
	 */
	public static function setDefaults()
	{
		global $modSettings;

		$defaults = [
			'lgal_comments_per_page' => 20,
			'lgal_items_per_page' => 24,
			'lgal_dir' => '$boarddir/lgal_items',
			'lgal_max_space' => '100M',
			'lgal_chunk_size' => 524288,
			'lgal_selfmod_approve_item' => 0,
			'lgal_selfmod_approve_comment' => 0,
			'lgal_selfmod_edit_comment' => 0,
			'lgal_selfmod_delete_comment' => 0,
			'lgal_selfmod_lock_comment' => 0,
			'lgal_enable_image' => 1,
			'lgal_enable_audio' => 0,
			'lgal_enable_video' => 0,
			'lgal_enable_document' => 0,
			'lgal_enable_archive' => 0,
			'lgal_enable_generic' => 0,
			'lgal_enable_external' => 1,
			'lgal_image_formats' => 'jpg,gif,png,webp',
			'lgal_audio_formats' => 'mp3,m4a,oga,flac',
			'lgal_video_formats' => 'm4v',
			'lgal_document_formats' => 'doc,xls,ppt,pdf,txt',
			'lgal_archive_formats' => 'zip,rar',
			'lgal_generic_formats' => '',
			'lgal_external_formats' => 'youtube,vimeo,dailymotion,metacafe',
			'lgal_image_quotas' => 'a:2:{i:0;a:3:{i:0;a:1:{i:0;i:2;}i:1;s:9:"2048x2048";i:2;s:3:"10M";}i:1;a:3:{i:0;a:1:{i:0;i:0;}i:1;s:9:"1500x1500";i:2;s:2:"5M";}}',
			'lgal_audio_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_video_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_document_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_archive_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_generic_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_reports' => 'a:2:{s:5:"items";i:0;s:8:"comments";i:0;}',
			'lgal_count_author_views' => 1,
			'lgal_enable_mature' => 0,
			'lgal_open_link_new_tab' => 0,
			'lgal_import_rendering' => 0,
			'lgal_metadata' => 'a:3:{s:6:"images";a:21:{i:0;s:8:"datetime";i:1;s:4:"make";i:2;s:5:"flash";i:3;s:13:"exposure_time";i:4;s:7:"fnumber";i:5;s:13:"shutter_speed";i:6;s:12:"focal_length";i:7;s:11:"digitalzoom";i:8;s:10:"brightness";i:9;s:8:"contrast";i:10;s:9:"sharpness";i:11;s:8:"isospeed";i:12;s:11:"lightsource";i:13;s:13:"exposure_prog";i:14;s:13:"metering_mode";i:15;s:11:"sensitivity";i:16;s:5:"title";i:17;s:7:"subject";i:18;s:6:"author";i:19;s:8:"keywords";i:20;s:7:"comment";}s:5:"audio";a:8:{i:0;s:5:"title";i:1;s:6:"artist";i:2;s:12:"album_artist";i:3;s:5:"album";i:4;s:12:"track_number";i:5;s:5:"genre";i:6;s:8:"playtime";i:7;s:7:"bitrate";}s:5:"video";a:8:{i:0;s:5:"title";i:1;s:6:"artist";i:2;s:12:"album_artist";i:3;s:5:"album";i:4;s:12:"track_number";i:5;s:5:"genre";i:6;s:8:"playtime";i:7;s:7:"bitrate";}}',
			'lgal_social' => 'facebook,twitter,tumblr,reddit,pinterest',
			'lgal_feed_enable_item' => 1,
			'lgal_feed_enable_album' => 1,
			'lgal_feed_items_item' => 10,
			'lgal_feed_items_album' => 10,
			'lgal_unapproved_items' => 0,
			'lgal_unapproved_comments' => 0,
			'lgal_unapproved_albums' => 0,
		];
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * Add the necessary hooks to the system
	 */
	public static function defineHooks()
	{
		$hooks = [
			'integrate_redirect' => 'Addons\Levertine\Source\LevGalBootstrap::hookRedirect',
			'integrate_actions' => 'Addons\Levertine\Source\LevGalBootstrap::hookActions',
			'integrate_menu_buttons' => 'Addons\Levertine\Source\LevGalBootstrap::hookButtons',
			'integrate_additional_bbc' => 'Addons\Levertine\Source\LevGalBootstrap::hookBbcCodes',
			'pre_css_output' => 'Addons\Levertine\Source\LevGalBootstrap::hookCss',
			'integrate_delete_members' => 'Addons\Levertine\Source\Model\Member::deleteMembers',
			'integrate_delete_membergroups' => 'Addons\Levertine\Source\Model\Group::deleteGroup',
			'integrate_action_mentions_before' => 'Addons\Levertine\Source\LevGalBootstrap::hookLanguage',
			'integrate_mailist_pre_parsebbc' => 'Addons\Levertine\Source\LevGalBootstrap::hookMailPreParsebbc',
			'integrate_mailist_pre_markdown' => 'Addons\Levertine\Source\LevGalBootstrap::hookPreMarkdown',
			'integrate_mailist_pre_sig_parsebbc' => 'Addons\Levertine\Source\LevGalBootstrap::hookPreSig',
			'integrate_quickhelp' => 'Addons\Levertine\Source\LevGalBootstrap::hookLanguage',
			'integrate_pre_bbc_parser' => 'Addons\Levertine\Source\LevGalBootstrap::hookPreParsebbc',
		];

		foreach ($hooks as $hook => $callable)
		{
			add_integration_function($hook, $callable, '',false);
		}

		if (ELK !== 'SSI')
		{
			add_integration_function('integrate_load_theme', 'Addons\Levertine\Source\LevGalBootstrap::hookLoadTheme', '',false);
		}
		else
		{
			self::hookLoadTheme();
		}
	}

	public static function parseURL()
	{
		global $boardurl;

		$orig = $_SERVER['QUERY_STRING'];
		$_SERVER['QUERY_STRING'] = str_replace('%2F', '/', $_SERVER['QUERY_STRING']);

		// If it is, this is really just about us silently converting it internally to
		// suit ElkArte's other stuff
		$possible_routes = [
			'~^media/?$~i' => 'action=media',
			// The file item is special because we want to pre-empt a lot of ElkArte behaviour
			'~^media/file/(\d+)/?$~i' => 'action=dlattach;media;sa=file;item=%1$s',
			'~^media/file/([a-z0-9%-]+\.\d+)/?$~i' => 'action=dlattach;media;sa=file;item=%1$s',
			'~^media/file/(\d+)/([a-z]+)/?$~i' => 'action=dlattach;media;sa=file;item=%1$s;sub=%2$s',
			'~^media/file/([a-z0-9%-]+\.\d+)/([a-z_]+)/?$~i' => 'action=dlattach;media;sa=file;item=%1$s;sub=%2$s',
			// Normal handling
			'~^media/([a-z]+)/?$~i' => 'action=media;sa=%1$s',
			'~^media/([a-z]+)/(\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s',
			'~^media/([a-z]+)/([a-z_]+)/?$~i' => 'action=media;sa=%1$s;sub=%2$s',
			'~^media/([a-z]+)/([a-z_]+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;sub=%2$s;page=%3$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s',
			'~^media/([a-z]+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;page=%2$s',
			'~^media/([a-z]+)/(\d+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;page=%3$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;page=%3$s',
			'~^media/([a-z]+)/(\d+)/([a-z_]+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/([a-z_]+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s',
			'~^media/([a-z]+)/(\d+)/([a-z_]+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;page=%4$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/([a-z_]+)/page-(\d+)/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;page=%4$s',
			// And just for fun, let's deal with ElkArte session ids. Fortunately, there aren't many
			// cases we'd need this. And no pagination.
			'~^media/([a-z]+)/(\d+)/([a-z_]+)/([0-9a-z]{7,12}\=[0-9a-z]{32})/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;%4$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/([a-z_]+)/([0-9a-z]{7,12}\=[0-9a-z]{32})/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;%4$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/([a-z_]+=[a-z0-9]+)/([0-9a-z]{7,12}\=[0-9a-z]{32})/?$~i' => 'action=media;sa=%1$s;item=%2$s;%3$s;%4$s',
		];
		foreach ($possible_routes as $route => $dest)
		{
			if (preg_match($route, $_SERVER['QUERY_STRING'], $matches))
			{
				// Trailing / ?
				if (!str_ends_with($_SERVER['QUERY_STRING'], '/'))
				{
					header('Location: ' . $boardurl . '/index.php?' . $_SERVER['QUERY_STRING'] . '/', true, 301);
					exit;
				}

				if (count($matches) > 1)
				{
					array_shift($matches);
					$_SERVER['QUERY_STRING'] = vsprintf($dest, $matches);
				}
				else
				{
					$_SERVER['QUERY_STRING'] = $dest;
				}
			}
		}

		// If we've matched, we need to rewrite the original requested URI too.
		if ($orig !== $_SERVER['QUERY_STRING'])
		{
			// If we're serving files, we want to flag as dlattach to avoid certain queries.
			// This replacement needs to be done *before* HttpReq has been called
			$_SERVER['QUERY_STRING'] = str_replace('action=dlattach;media', 'action=media', $_SERVER['QUERY_STRING']) . ';comment';
			$_SERVER['REQUEST_URI'] = $boardurl . '/index.php?' . $_SERVER['QUERY_STRING'] . ';comment';
		}
	}

	/**
	 * Conceptually, ElkArte provides $context['html_headers'] as a dumping ground for mods to add to the
	 * <head> tag. Except a lot of mods seem to do this badly, so we have to manually bypass it.
	 */
	public static function addHtmlHeader($header)
	{
		self::$header .= $header;
	}

	public static function hookLanguage()
	{
		Txt::load('Levertine/LevGal');
	}

	/**
	 * This little function prevents ElkArte's redirection code from splicing up index.php?media/.../ URLs
	 */
	public static function hookRedirect(&$setLocation)
	{
		global $scripturl;

		$origSetLocation = $setLocation;
		if (str_contains($setLocation, 'action=media'))
		{
			$setLocation = $scripturl . '?media/';
			foreach (['sa', 'item', 'sub', 'page'] as $item)
			{
				if (preg_match('~;' . $item . '=([^;]+)~', $origSetLocation, $matches))
				{
					$setLocation .= $matches[1] . '/';
				}
			}
		}
	}

	/**
	 * This function has an easy job: add itself to the actions handler.
	 */
	public static function hookActions(&$actionArray)
	{
		$actionArray['media'] = ['\Addons\Levertine\Source\LevGal', 'Media'];

		if (empty($_REQUEST['action']))
		{
			return;
		}

		switch ($_REQUEST['action'])
		{
			case 'admin':
				add_integration_function('integrate_admin_areas', 'levgal_admin_bootstrap', 'ADDONSDIR/Levertine/Source/ManageLevGal.php', false);
				break;
			case 'profile':
				add_integration_function('integrate_profile_areas', '\Addons\Levertine\Source\LevGalProfile::LevGal_profile', '', false);
				break;
			case 'who':
				Txt::load('Levertine/LevGal-Who');
				break;
		}
	}

	/**
	 * This function adds the Media menu button to the menu.
	 */
	public static function hookButtons(&$buttons, &$menu_count)
	{
		global $txt, $scripturl, $context, $modSettings;

		if (!allowedTo('lgal_view'))
		{
			return;
		}

		// Just in case it wasn't yet loaded
		Txt::load('Levertine/LevGal');

		$before = 'admin';
		$temp_buttons = [];
		foreach ($buttons as $k => $v)
		{
			if ($k === $before)
			{
				if (!empty(User::$settings['lgal_new']))
				{
					$unseenModel = self::getModel('Unseen');
					$unseenModel->updateUnseenItems();
				}

				$temp_buttons['media'] = [
					'title' => $txt['levgal'],
					'href' => $scripturl . '?media/',
					'show' => true,
					'data-icon' => 'i-picture',
					'sub_buttons' => [
						'albumlist' => [
							'title' => $txt['lgal_see_albums'],
							'href' => $scripturl . '?media/albumlist/',
							'show' => true,
						],
						'newalbum' => [
							'title' => $txt['levgal_newalbum'],
							'href' => $scripturl . '?media/newalbum/',
							'show' => allowedTo(['lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum']),
						],
						'unseen' => [
							'title' => $txt['levgal_unseen'],
							'href' => $scripturl . '?media/unseen/',
							'amount' => !empty(User::$settings['lgal_unseen']) ? (int) User::$settings['lgal_unseen'] : 0,
							'show' => !empty(User::$settings['lgal_unseen']),
						],
						'searchmedia' => [
							'title' => $txt['levgal_search'],
							'href' => $scripturl . '?media/search/',
							'show' => true,
						],
						'stats' => [
							'title' => $txt['lgal_gallery_stats'],
							'href' => $scripturl . '?media/stats/',
							'show' => true,
						],
						'tag' => [
							'title' => $txt['levgal_tagcloud'],
							'href' => $scripturl . '?media/tag/cloud/',
							'show' => true,
						],
						'mymedia' => [
							'title' => $txt['levgal_mymedia'],
							'href' => $scripturl . '?action=profile;area=mediaitems;sa=items;u=' . $context['user']['id'],
							'show' => !$context['user']['is_guest'] && allowedTo(['lgal_manage', 'lgal_additem_own', 'lgal_additem_any']) && allowedTo(['profile_view_own', 'profile_view_any']),
							'sub_buttons' => [
								'myitems' => [
									'title' => $txt['levgal_myitems'],
									'href' => $scripturl . '?action=profile;area=mediaitems;sa=items;u=' . $context['user']['id'],
									'show' => true,
								],
								'myalbums' => [
									'title' => $txt['levgal_myalbums'],
									'href' => $scripturl . '?media/albumlist/' . $context['user']['id'] . '/member/',
									'show' => true,
								],
								'mybookmarks' => [
									'title' => $txt['levgal_mybookmarks'],
									'href' => $scripturl . '?action=profile;area=mediabookmarks;u=' . $context['user']['id'],
									'show' => true,
								],
							],
						],
						'moderation' => [
							'title' => $txt['levgal_moderate'],
							'href' => $scripturl . '?media/moderate/',
							'amount' => 0,
							'show' => false,
							'sub_buttons' => [
								'unapp_albums' => [
									'title' => $txt['levgal_unapproved_albums'],
									'href' => $scripturl . '?media/moderate/unapproved_albums/',
									'show' => false,
								],
								'unapp_items' => [
									'title' => $txt['levgal_unapproved_items'],
									'href' => $scripturl . '?media/moderate/unapproved_items/',
									'show' => false,
								],
								'unapp_comments' => [
									'title' => $txt['levgal_unapproved_comments'],
									'href' => $scripturl . '?media/moderate/unapproved_comments/',
									'show' => false,
								],
								'reported_items' => [
									'title' => $txt['levgal_reported_items'],
									'href' => $scripturl . '?media/moderate/reported_items/',
									'show' => false,
								],
								'reported_comments' => [
									'title' => $txt['levgal_reported_comments'],
									'href' => $scripturl . '?media/moderate/reported_comments/',
									'show' => false,
								],
							],
						],
					],
				];

				// If there are unapproved comments, we might want a menu item for this. But we need to only tell the user the right number.
				foreach (['albums', 'comments', 'items'] as $type)
				{
					$method = 'getUnapproved' . ucfirst($type) . 'Count';
					$unapproved = self::$method();
					if (!empty($unapproved))
					{
						$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons']['unapp_' . $type]['show'] = true;
						$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons']['unapp_' . $type]['amount'] = $unapproved;
						$temp_buttons['media']['sub_buttons']['moderation']['amount'] += $unapproved;
						$temp_buttons['media']['sub_buttons']['moderation']['show'] = true;
					}
				}

				// Reported items are somewhat simpler; there's only global counts - because there's only managers that can see it.
				if (allowedTo('lgal_manage'))
				{
					$reported = Util::unserialize($modSettings['lgal_reports'],  ['allowed_classes' => false]);
					foreach (['comments', 'items'] as $type)
					{
						if (!empty($reported[$type]))
						{
							$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons']['reported_' . $type]['show'] = true;
							$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons']['reported_' . $type]['amount'] = $reported[$type];
							$temp_buttons['media']['sub_buttons']['moderation']['amount'] += $reported[$type];
							$temp_buttons['media']['sub_buttons']['moderation']['show'] = true;
						}
					}
				}

				// Now, are there things we need to do?
				$amount = 0;
				foreach ($temp_buttons['media']['sub_buttons'] as $id => $button)
				{
					if (!empty($button['amount']))
					{
						$amount += $button['amount'];
						$temp_buttons['media']['sub_buttons'][$id]['alttitle'] = $temp_buttons['media']['sub_buttons'][$id]['title'] . ' [' . $button['amount'] . ']';
						$temp_buttons['media']['sub_buttons'][$id]['title'] .= ' [<strong>' . $button['amount'] . '</strong>]';
					}
				}

				foreach ($temp_buttons['media']['sub_buttons']['moderation']['sub_buttons'] as $id => $button)
				{
					if (!empty($button['amount']))
					{
						$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons'][$id]['alttitle'] = $temp_buttons['media']['sub_buttons']['moderation']['sub_buttons'][$id]['title'] . ' [' . $button['amount'] . ']';
						$temp_buttons['media']['sub_buttons']['moderation']['sub_buttons'][$id]['title'] .= ' [<strong>' . $button['amount'] . '</strong>]';
					}
				}

				if (!empty($amount))
				{
					$temp_buttons['media']['counter'] = 'media';
					$menu_count['media'] = $amount;
				}
			}

			$temp_buttons[$k] = $v;
		}

		$buttons = $temp_buttons;
	}

	/**
	 * Retrieves the count of unapproved comments based on the user's permissions and settings.
	 *
	 * @return int The number of unapproved comments.
	 */
	public static function getUnapprovedCommentsCount()
	{
		global $modSettings;
		static $count = null;

		if ($count === null)
		{
			$count = 0;
			if (!empty($modSettings['lgal_unapproved_comments']))
			{
				if (allowedTo('lgal_manage'))
				{
					$count = $modSettings['lgal_unapproved_comments'];
				}
				elseif (allowedTo('lgal_approve_comment') || (!empty($modSettings['lgal_selfmod_approve_comment']) && !User::$info['is_guest']))
				{
					$moderate = self::getModel('Moderate');
					$count = $moderate->getUnapprovedCommentsCount();
				}
			}
		}

		return $count;
	}

	/**
	 * Retrieves the count of unapproved items based on user permissions and settings.
	 *
	 * @return int The number of unapproved items.
	 */
	public static function getUnapprovedItemsCount()
	{
		global $modSettings;
		static $count = null;

		if ($count === null)
		{
			$count = 0;
			if (!empty($modSettings['lgal_unapproved_items']))
			{
				if (allowedTo('lgal_manage'))
				{
					$count = $modSettings['lgal_unapproved_items'];
				}
				elseif (allowedTo('lgal_approve_item') || (!empty($modSettings['lgal_selfmod_approve_item']) && !User::$info['is_guest']))
				{
					$moderate = self::getModel('Moderate');
					$count = $moderate->getUnapprovedItemsCount();
				}
			}
		}

		return $count;
	}

	/**
	 * Retrieves the count of unapproved albums if the user has the appropriate permissions.
	 *
	 * @return int The number of unapproved albums or 0 if none or if the user lacks permissions.
	 */
	public static function getUnapprovedAlbumsCount()
	{
		global $modSettings;

		return !empty($modSettings['lgal_unapproved_albums']) && allowedTo(['lgal_manage', 'lgal_approve_album']) ? $modSettings['lgal_unapproved_albums'] : 0;
	}

	/**
	 * This is solely so that we can run the prebuffer at the right time.
	 */
	public static function hookLoadTheme()
	{
		$buffers = ob_list_handlers();
		if (empty($buffers) || !in_array('Addons\Levertine\Source\LevGalBootstrap::hookBuffer', $buffers, true))
		{
			ob_start(['\Addons\Levertine\Source\LevGalBootstrap', 'hookBuffer']);
		}

	}

	/**
	 * Ensures the correct CSS file is loaded for rendering [media] tags.
	 *
	 * This method checks if the required CSS file has not already been loaded and the ParserWrapper class exists.
	 * If the conditions are met, the CSS file is loaded. This prevents issues with rendering media tags
	 * when the parser instance has already been created before the CSS is output by the template.
	 *
	 * @return void
	 */
	public static function hookCss()
	{
		global $context;

		// Check if this class has been initiated and if so, load our CSS file.
		// Although we load CSS in integrate_additional_bbc/hookBbcCodes, should the instance already
		// have been created, then getCodes is not run (again) until after the template has output CSS.
		// One could load this regardless, but it is only needed by the parser for rendering
		// [media] tags
		if (!isset($context['css_files']['main.css']) && class_exists(ParserWrapper::class, false))
		{
			loadCSSFile('main.css', ['stale' => LEVGAL_VERSION, 'subdir' => 'Levertine']);
		}
	}

	/**
	 * This declares the media bbcode items
	 *
	 * We define four bbcodes:
	 *  - [media]1[/media] for simple thumbnail+link
	 *  - [media type=album]1[/media] to display the thumbnails of a album
	 *  - [media optionalOptions id=1]description[/media] for more complex embedding with description and stuff
	 *         optionalOptions:
	 *            - align=left|right|center, left, and right are floated
	 *            - type=thumbnail|preview
	 *  - [clear] a self closed tag which can be used to "end" any float
	 *
	 * @param mixed $codes
	 */
	public static function hookBbcCodes(&$codes)
	{
		global $context;

		// This can be too late in the process and CSS files will have already been output
		if (!isset($context['css_files']['main.css']))
		{
			loadCSSFile('main.css', ['stale' => LEVGAL_VERSION, 'subdir' => 'Levertine']);
		}

		$codes[] = [
			// This handles simple [media]123[/media]
			Codes::ATTR_TAG => 'media',
			Codes::ATTR_LENGTH => 5,
			Codes::ATTR_TYPE => Codes::TYPE_UNPARSED_CONTENT,
			Codes::ATTR_BLOCK_LEVEL => false,
			Codes::ATTR_CONTENT => '!<lgalmediasimple: $1>',
			Codes::ATTR_VALIDATE => function(&$data, $disabledBBC, &$tag) {
				global $context, $settings, $txt;

				if (in_array('media', $disabledBBC, true))
				{
					return null;
				}

				$data = trim($data);
				if ($data === (string)(int) $data && allowedTo('lgal_view'))
				{
					if (empty($context['lgal_embeds']))
					{
						$context['lgal_embeds'] = self::getModel('Embed');
					}

					$count = $context['lgal_embeds']->setId($data);
					$context['lgal_embeds']->addSimple();
					$context['lgal_embeds']->setType('thumb');
					$tag[Codes::ATTR_CONTENT] =	'!<lgalmediasimple: ' . $count . '>';
				}
				else
				{
					Txt::load('Levertine/LevGal');
					$tag[Codes::ATTR_CONTENT] = '<img src="' . $settings['default_theme_url'] . '/Levertine/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
				}
			},
		];
		$codes[] = [
			// This handles [media type=album id=123][/media] tags or [media type="album"]123][/media]
			Codes::ATTR_TAG => 'media',
			Codes::ATTR_LENGTH => 5,
			Codes::ATTR_TYPE => Codes::TYPE_UNPARSED_CONTENT,
			Codes::ATTR_BEFORE => '{id},{type}',
			Codes::ATTR_BLOCK_LEVEL => false,
			Codes::ATTR_PARAM => [
				'type' => [
					Codes::PARAM_ATTR_MATCH => '(album)',
				],
				'id' => [
					Codes::PARAM_ATTR_MATCH => '([1-9][0-9]*)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				],
			],
			Codes::ATTR_CONTENT => '!<lgalmediasimple: {id}>',
			Codes::ATTR_VALIDATE => function(&$data, $disabledBBC, &$tag) {
				global $context, $txt, $settings;

				if (in_array('media', $disabledBBC, true))
				{
					return null;
				}

				[$id, $type] = explode(',', $tag[Codes::ATTR_BEFORE]);
				$id = (int) $id;
				unset($tag[Codes::ATTR_BEFORE]);

				if (empty($id))
				{
					// Maybe [media type=album]123[/media]
					$id = (int) trim($data);
				}
				if ($id > 0 && allowedTo('lgal_view'))
				{
					$album = new Album();
					$albumDetails = $album->getAlbumById($id);
					if ($album->isVisible() === false)
					{
						Txt::load('Levertine/LevGal');
						$tag[Codes::ATTR_CONTENT] = '<img class="item_image" src="' . $settings['default_theme_url'] . '/Levertine/icons/unknown.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
						return null;
					}
					[$sort, $direction] = explode('|', $albumDetails['sort']);

					// Fetching up to 40 items from the album
					$albumItems = $album->loadAlbumItems(40, 0, $sort, $direction);

					if (empty($context['lgal_embeds']))
					{
						$context['lgal_embeds'] = self::getModel('Embed');
					}

					$expanded = '<br />';
					foreach ($albumItems as $item)
					{
						$count = $context['lgal_embeds']->setId((int) $item['id_item']);
						$context['lgal_embeds']->addSimple();
						$context['lgal_embeds']->setType($type);
						$expanded .= '!<lgalmediasimple: ' . $count . '>';
					}

					$tag[Codes::ATTR_CONTENT] =	$expanded;
				}
				else
				{
					Txt::load('Levertine/LevGal');
					$tag[Codes::ATTR_CONTENT] = '<img src="' . $settings['default_theme_url'] . '/Levertine/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
				}
			},
		];
		// This handles [media id=123 align=left|center|right type=thumb|preview][/media] tags
		// or [media align=left|center|right type=thumb|preview]123[/media] tags
		$codes[] = [
			Codes::ATTR_TAG => 'media',
			Codes::ATTR_LENGTH => 5,
			Codes::ATTR_PARAM => [
				'id' => [
					Codes::PARAM_ATTR_MATCH => '([1-9][0-9]*)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				],
				'align' => [
					Codes::PARAM_ATTR_MATCH => '(left|center|right)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				],
				'type' => [
					Codes::PARAM_ATTR_MATCH => '(thumb|preview)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				],
			],
			Codes::ATTR_BLOCK_LEVEL => true,
			Codes::ATTR_TYPE => Codes::TYPE_UNPARSED_CONTENT,
			Codes::ATTR_CONTENT => '!<lgalmediacomplex: {id}>',
			Codes::ATTR_BEFORE => '{id},{align},{type}',
			Codes::ATTR_VALIDATE => function(&$data, $disabledBBC, &$tag) {
				global $context, $txt, $settings;

				if (in_array('media', $disabledBBC, true))
				{
					return null;
				}

				[$id, $align, $type] = explode(',', $tag[Codes::ATTR_BEFORE]);
				unset($tag[Codes::ATTR_BEFORE]); // Because demons.

				if (empty($id))
				{
					// allow for [media type=xxx]123[/media] with a simple embed figure caption
					$id = $data;
					$data = '_lgal_simple_';
				}
				$id = (int) $id;
				if ($id > 0 && allowedTo('lgal_view'))
				{
					if (empty($context['lgal_embeds']))
					{
						$context['lgal_embeds'] = self::getModel('Embed');
					}

					$count = $context['lgal_embeds']->setId($id);
					$context['lgal_embeds']->setAlign($align)->setType($type)->addComplex($data);
					$tag[Codes::ATTR_CONTENT] =	'!<lgalmediacomplex: ' . $count . '>';
				}
				else
				{
					Txt::load('Levertine/LevGal');
					$tag[Codes::ATTR_CONTENT] = '<img src="' . $settings['default_theme_url'] . '/Levertine/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
				}
			},
		];
		$codes[] = [
			Codes::ATTR_TAG => 'clear',
			Codes::ATTR_TYPE => Codes::TYPE_CLOSED,
			Codes::ATTR_DISALLOW_PARENTS => ["tt" => 1],
			Codes::ATTR_CONTENT => '<div class="separator"></div>',
			Codes::ATTR_BLOCK_LEVEL => true,
			Codes::ATTR_AUTOLINK => false,
			Codes::ATTR_LENGTH => 5,
		];
	}

	/**
	 * This nasty little function tries to grab the content after ob_sessrewrite has had its wicked way.
	 * And then proceed to fix index.php?PHPSESSID=blah&media before fixing things embedded into bbcode.
	 */
	public static function hookBuffer($buffer)
	{
		global $scripturl, $context;

		if (!empty(self::$header))
		{
			$buffer = str_replace('</head>', self::$header . "\n" . '</head>', $buffer);
		}

		// Now to fix any embeds.
		if (!empty($context['lgal_embeds']))
		{
			$context['lgal_embeds']->processBuffer($buffer);
		}

		if ($scripturl === '' || !defined('SID'))
		{
			return $buffer;
		}

		if (isset($_GET['debug']))
		{
			return str_replace($scripturl . '?debug;media', $scripturl . '?media', $buffer);
		}

		return str_replace($scripturl . '?' . SID . '&amp;media', $scripturl . '?media', $buffer);
	}

	/**
	 * Hook method executed before parsing the bbc.  Used to convert other
	 * gallery tags to Levgal.  Currently only supports SMG tag conversion.
	 *
	 * @param string $message The bbc message to be pre-parsed, passed by reference
	 * @param bool $previewing Whether the message is being previewed or not.
	 *
	 * @return void
	 */
	public static function hookPreParsebbc(&$message, $previewing)
	{
		global $modSettings;

		// Not converting, easy
		if (empty($modSettings['lgal_import_rendering']))
		{
			return;
		}

		// Don't render tags in code blocks
		PreparseCode::instance('')->preparsecode($message, $previewing);

		// Check for other gallery tags and convert them
		$lgalOtherEmbeds = new Embed();
		$lgalOtherEmbeds->convertSMG($message);

		// Back we go
		PreparseCode::instance('')->un_preparsecode($message);
	}

	/**
	 * This nifty little function limits a value inside the min/max specified but is easier to read :)
	 */
	public static function clamp($val, $min, $max)
	{
		return max(min($val, $max), $min);
	}

	/**
	 * Where possible, we should really be caching models. This means one model state (and any related data)
	 * can be preserved without having to explicitly otherwise juggle it around.
	 */
	public static function getModel($modelName)
	{
		static $cache = null;

		if (!isset($cache[$modelName]))
		{
			$model = '\Addons\Levertine\Source\Model\\' . $modelName;
			$cache[$modelName] = new $model();
		}

		return $cache[$modelName];
	}

	public static function getGalleryDir()
	{
		global $modSettings;

		return strtr($modSettings['lgal_dir'], ['$boarddir' => BOARDDIR]);
	}

	/**
	 * Used to interact with the message before its sent to parse_bbc as part of mail functions
	 */
	public static function hookMailPreParseBBC(&$message)
	{
		global $txt, $modSettings;

		Txt::load('Levertine/LevGal');

		// A scheduled task like daily digest, we can't render/geturl Media items as we don't know
		// (or want to lookup), those permissions.
		if (empty(User::$info))
		{
			// Replace the [media][/media] tag
			$message = preg_replace('~\[media.*?\].*?\[\/media\]~s', '[ ' . $txt['levgal_email_photo_gallery'] . ' ]', $message);

			if (!empty($modSettings['lgal_import_rendering']))
			{
				$message = preg_replace('~\[smg\s+([^]]*?(?:&quot;.+?&quot;.*?(?!&quot;))?)]( ?<br />)?[\r\n]?~is', '[ ' . $txt['levgal_email_photo_gallery'] . ' ]', $message);
			}
		}
	}

	/**
	 * Used to interact with the message after parse_bbc but before html2md.  We need to render
	 * the Media html tags !<lgalmediasimple> !<lgalmediacomplex> for our PBE MD response.  Here
	 * it will simply replace the tags with the urls to the image
	 */
	public static function hookPreMarkdown(&$message)
	{
		global $context;

		if (!empty($context['lgal_embeds']))
		{
			$context['lgal_embeds']->processPBE($message);
		}
	}

	/**
	 * Used to interact with the signature before it's sent to parse_bbc as part
	 * of PBE mail functions
	 */
	public static function hookPreSig(&$signature)
	{
		global $modSettings;

		// Remove Media tags in signatures
		$signature = preg_replace('~\[media.*?].*?\[/media]~s', '', $signature);

		if (!empty($modSettings['lgal_import_rendering']))
		{
			$signature = preg_replace('~\[smg id=\d{1,6}]~iU', '', $signature);
		}
	}
}
