<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

use BBC\Codes;

/**
 * This file deals with some fundamental things for Levertine Gallery.
 */
class LevGal_Bootstrap
{
	/** @var string */
	public static $header = '';

	/**
	 * Initialize LevGal bootstrap functions, called from integrate_pre_load
	 */
	public static function initialize()
	{
		define('LEVGAL_VERSION', '1.1.1');

		self::setDefaults();
		self::defineAutoload();
		self::defineHooks();

		// No querystring (e.g. index.php) or querystring isn't ?media it's nothing to do with us.
		if (ELK === 'SSI' || empty($_SERVER['QUERY_STRING']) || strpos($_SERVER['QUERY_STRING'], 'media') !== 0)
		{
			return;
		}

		self::parseURL();
	}

	public static function setDefaults()
	{
		global $modSettings;

		$defaults = array(
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
			'lgal_image_formats' => 'jpg,gif,png',
			'lgal_audio_formats' => 'mp3,m4a,oga,flac',
			'lgal_video_formats' => 'm4v,ogv',
			'lgal_document_formats' => 'doc,xls,ppt,pdf,txt',
			'lgal_archive_formats' => 'zip,rar',
			'lgal_generic_formats' => '',
			'lgal_external_formats' => 'youtube,vimeo,dailymotion,metacafe',
			'lgal_image_quotas' => 'a:2:{i:0;a:3:{i:0;a:1:{i:0;i:2;}i:1;s:9:"1500x1500";i:2;s:3:"10M";}i:1;a:3:{i:0;a:1:{i:0;i:0;}i:1;s:9:"1000x1000";i:2;s:2:"5M";}}',
			'lgal_audio_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_video_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_document_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_archive_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_generic_quotas' => 'a:2:{i:0;a:2:{i:0;a:1:{i:0;i:2;}i:1;s:2:"5M";}i:1;a:2:{i:0;a:1:{i:0;i:0;}i:1;s:2:"2M";}}',
			'lgal_reports' => 'a:2:{s:5:"items";i:0;s:8:"comments";i:0;}',
			'lgal_count_author_views' => 1,
			'lgal_enable_mature' => 1,
			'lgal_metadata' => 'a:3:{s:6:"images";a:21:{i:0;s:8:"datetime";i:1;s:4:"make";i:2;s:5:"flash";i:3;s:13:"exposure_time";i:4;s:7:"fnumber";i:5;s:13:"shutter_speed";i:6;s:12:"focal_length";i:7;s:11:"digitalzoom";i:8;s:10:"brightness";i:9;s:8:"contrast";i:10;s:9:"sharpness";i:11;s:8:"isospeed";i:12;s:11:"lightsource";i:13;s:13:"exposure_prog";i:14;s:13:"metering_mode";i:15;s:11:"sensitivity";i:16;s:5:"title";i:17;s:7:"subject";i:18;s:6:"author";i:19;s:8:"keywords";i:20;s:7:"comment";}s:5:"audio";a:8:{i:0;s:5:"title";i:1;s:6:"artist";i:2;s:12:"album_artist";i:3;s:5:"album";i:4;s:12:"track_number";i:5;s:5:"genre";i:6;s:8:"playtime";i:7;s:7:"bitrate";}s:5:"video";a:8:{i:0;s:5:"title";i:1;s:6:"artist";i:2;s:12:"album_artist";i:3;s:5:"album";i:4;s:12:"track_number";i:5;s:5:"genre";i:6;s:8:"playtime";i:7;s:7:"bitrate";}}',
			'lgal_social' => 'facebook,twitter,tumblr,reddit,pinterest',
			'lgal_feed_enable_item' => 1,
			'lgal_feed_enable_album' => 1,
			'lgal_feed_items_item' => 10,
			'lgal_feed_items_album' => 10,
			'lgal_unapproved_items' => 0,
			'lgal_unapproved_comments' => 0,
			'lgal_unapproved_albums' => 0,
		);
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * Add necessary hooks to the system
	 */
	public static function defineHooks()
	{
		$hooks = array(
			'redirect' => 'LevGal_Bootstrap::hookRedirect',
			'actions' => 'LevGal_Bootstrap::hookActions',
			'menu_buttons' => 'LevGal_Bootstrap::hookButtons',
			'additional_bbc' => 'LevGal_Bootstrap::hookBbcCodes',
			'delete_members' => 'LevGal_Model_Member::deleteMembers',
			'delete_membergroups' => 'LevGal_Model_Group::deleteGroup',
			'action_mentions_before' => 'LevGal_Bootstrap::hookLanguage',
			'mailist_pre_parsebbc' => 'LevGal_Bootstrap::hookPreParsebbc',
			'mailist_pre_markdown' => 'LevGal_Bootstrap::hookPreMarkdown',
			'mailist_pre_sig_parsebbc' => 'LevGal_Bootstrap::hookPreSig',
		);

		foreach ($hooks as $point => $callable)
		{
			add_integration_function('integrate_' . $point, $callable, '',false);
		}

		if (ELK !== 'SSI')
		{
			add_integration_function('integrate_load_theme', 'LevGal_Bootstrap::hookLoadTheme', '',false);
		}
		else
		{
			self::hookLoadTheme();
		}
	}

	/**
	 * Spin up a custom autoloader
	 */
	public static function defineAutoload()
	{
		// To dispatch from SiteDispatcher
		$autoloader = Elk_Autoloader::instance();
		$autoloader->setupAutoloader(array(SOURCEDIR . '/levgal_src'));

		// Just in case any addons are crazy enough to do this.
		if (function_exists('__autoload'))
		{
			$autoloaders = spl_autoload_functions();
			if (!empty($autoloaders) && !in_array('__autoload', $autoloaders))
			{
				spl_autoload_register('__autoload');
			}
		}

		// Allow the gallery to be first in autoloader checking, else ElkArte will throw
		// an error before we get to look.
		spl_autoload_register(array('LevGal_Bootstrap', 'autoloader'), true, true);
	}

	public static function autoloader($class)
	{
		// We want LevGal_Controller to pass through our autoloader as part of site dispatcher
		if (strpos($class, '_Controller') === false && strpos($class, 'LevGal') === 0)
		{
			$fileparts = explode('_', $class);
			array_shift($fileparts);
			$file_path = SOURCEDIR . '/levgal_src/' . implode('/', $fileparts) . '.php';
			if (!file_exists($file_path))
			{
				throw new RuntimeException($file_path . ' not found');
			}
			require_once($file_path);
		}
	}

	public static function parseURL()
	{
		global $boardurl;

		$orig = $_SERVER['QUERY_STRING'];
		$_SERVER['QUERY_STRING'] = str_replace('%2F', '/', $_SERVER['QUERY_STRING']);

		// If it is, this is really just about us silently converting it internally to
		// suit ElkArte's other stuff
		//http://192.168.99.90/entegra/index.php?media/albumlist/0/group/
		$possible_routes = array(
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
			// And just for fun, let's deal with ElkArte session ids. Fortunately there aren't many
			// cases we'd need this. And no pagination.
			'~^media/([a-z]+)/(\d+)/([a-z_]+)/([0-9a-z]{7,12}\=[0-9a-z]{32})/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;%4$s',
			'~^media/([a-z]+)/([a-z0-9%-]+\.\d+)/([a-z_]+)/([0-9a-z]{7,12}\=[0-9a-z]{32})/?$~i' => 'action=media;sa=%1$s;item=%2$s;sub=%3$s;%4$s',
		);
		foreach ($possible_routes as $route => $dest)
		{
			if (preg_match($route, $_SERVER['QUERY_STRING'], $matches))
			{
				// Trailing / ?
				if (substr($_SERVER['QUERY_STRING'], -1) !== '/')
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
		if ($orig != $_SERVER['QUERY_STRING'])
		{
			// If we're serving files, we want to flag as dlattach to avoid certain queries.
			// This replacement needs to be done *before* HttpReq has been called
			$_SERVER['QUERY_STRING'] = str_replace('action=dlattach;media', 'action=media', $_SERVER['QUERY_STRING']);
			$_SERVER['REQUEST_URI'] = $boardurl . '/index.php?' . $_SERVER['QUERY_STRING'];
		}
	}

	/**
	 * Conceptually, ElkArte provides $context['html_headers'] as a dumping ground for mods to add to the
	 * <head> tag. Except a lot of mods seem to do this badly, so we have to manually bypass it.
	 */
	public static function addHtmlHeader($header)
	{
		LevGal_Bootstrap::$header .= $header;
	}

	public static function hookLanguage()
	{
		loadLanguage('levgal_lng/LevGal');
	}

	/**
	 * This little function prevents ElkArte's redirection code from splicing up index.php?media/.../ URLs
	 */
	public static function hookRedirect(&$setLocation, &$refresh)
	{
		global $scripturl;

		$origSetLocation = $setLocation;
		if (strpos($setLocation, 'action=media') !== false)
		{
			$setLocation = $scripturl . '?media/';
			foreach (array('sa', 'item', 'sub', 'page') as $item)
			{
				if (preg_match('~;' . $item . '=([^;]+)~', $origSetLocation, $matches))
				{
					$setLocation .= $matches[1] . '/';
				}
			}
			$refresh = false;
		}
	}

	/**
	 * This function has an easy job: add itself to the actions handler.
	 */
	public static function hookActions(&$actionArray)
	{
		$actionArray['media'] = array('LevGal_Controller', 'LevGal');

		if (empty($_REQUEST['action']))
		{
			return;
		}

		switch ($_REQUEST['action'])
		{
			case 'admin':
				add_integration_function('integrate_admin_areas', 'levgal_admin_bootstrap', 'SOURCEDIR/levgal_src/ManageLevGal.php',false);
				break;
			case 'profile':
				add_integration_function('integrate_profile_areas', 'LevGalProfile_Controller::LevGal_profile', '',false);
				break;
			case 'who':
				loadLanguage('levgal_lng/LevGal-Who');
				break;
		}
	}

	/**
	 * This function adds the Media menu button to the menu.
	 */
	public static function hookButtons(&$buttons, &$menu_count)
	{
		global $txt, $scripturl, $context, $user_settings, $modSettings;

		if (!allowedTo('lgal_view'))
		{
			return;
		}

		// Just in case it wasn't already loaded
		loadLanguage('levgal_lng/LevGal');

		$before = 'admin';
		$temp_buttons = array();
		foreach ($buttons as $k => $v)
		{
			if ($k === $before)
			{
				if (!empty($user_settings['lgal_new']))
				{
					$unseenModel = self::getModel('LevGal_Model_Unseen');
					$unseenModel->updateUnseenItems();
				}

				$temp_buttons['media'] = array(
					'title' => $txt['levgal'],
					'href' => $scripturl . '?media/',
					'show' => true,
					'data-icon' => 'i-picture',
					'sub_buttons' => array(
						'albumlist' => array(
							'title' => $txt['lgal_see_albums'],
							'href' => $scripturl . '?media/albumlist/',
							'show' => true,
						),
						'newalbum' => array(
							'title' => $txt['levgal_newalbum'],
							'href' => $scripturl . '?media/newalbum/',
							'show' => allowedTo(array('lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum')),
						),
						'unseen' => array(
							'title' => $txt['levgal_unseen'],
							'href' => $scripturl . '?media/unseen/',
							'amount' => !empty($user_settings['lgal_unseen']) ? (int) $user_settings['lgal_unseen'] : 0,
							'show' => !empty($user_settings['lgal_unseen']),
						),
						'searchmedia' => array(
							'title' => $txt['levgal_search'],
							'href' => $scripturl . '?media/search/',
							'show' => true,
						),
						'stats' => array(
							'title' => $txt['lgal_gallery_stats'],
							'href' => $scripturl . '?media/stats/',
							'show' => true,
						),
						'tag' => array(
							'title' => $txt['levgal_tagcloud'],
							'href' => $scripturl . '?media/tag/cloud/',
							'show' => true,
						),
						'mymedia' => array(
							'title' => $txt['levgal_mymedia'],
							'href' => $scripturl . '?action=profile;area=mediaitems;sa=items;u=' . $context['user']['id'],
							'show' => !$context['user']['is_guest'] && allowedTo(array('lgal_manage', 'lgal_additem_own', 'lgal_additem_any')) && allowedTo(array('profile_view_own', 'profile_view_any')),
							'sub_buttons' => array(
								'myitems' => array(
									'title' => $txt['levgal_myitems'],
									'href' => $scripturl . '?action=profile;area=mediaitems;sa=items;u=' . $context['user']['id'],
									'show' => true,
								),
								'myalbums' => array(
									'title' => $txt['levgal_myalbums'],
									'href' => $scripturl . '?media/albumlist/' . $context['user']['id'] . '/member/',
									'show' => true,
								),
								'mybookmarks' => array(
									'title' => $txt['levgal_mybookmarks'],
									'href' => $scripturl . '?action=profile;area=mediabookmarks;u=' . $context['user']['id'],
									'show' => true,
								),
							),
						),
						'moderation' => array(
							'title' => $txt['levgal_moderate'],
							'href' => $scripturl . '?media/moderate/',
							'amount' => 0,
							'show' => false,
							'sub_buttons' => array(
								'unapp_albums' => array(
									'title' => $txt['levgal_unapproved_albums'],
									'href' => $scripturl . '?media/moderate/unapproved_albums/',
									'show' => false,
								),
								'unapp_items' => array(
									'title' => $txt['levgal_unapproved_items'],
									'href' => $scripturl . '?media/moderate/unapproved_items/',
									'show' => false,
								),
								'unapp_comments' => array(
									'title' => $txt['levgal_unapproved_comments'],
									'href' => $scripturl . '?media/moderate/unapproved_comments/',
									'show' => false,
								),
								'reported_items' => array(
									'title' => $txt['levgal_reported_items'],
									'href' => $scripturl . '?media/moderate/reported_items/',
									'show' => false,
								),
								'reported_comments' => array(
									'title' => $txt['levgal_reported_comments'],
									'href' => $scripturl . '?media/moderate/reported_comments/',
									'show' => false,
								),
							),
						),
					),
				);

				// If there are unapproved comments, we might want a menu item for this. But we need to only tell the user the right number.
				foreach (array('albums', 'comments', 'items') as $type)
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
					$reported = @unserialize($modSettings['lgal_reports']);
					foreach (array('comments', 'items') as $type)
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

	public static function getUnapprovedCommentsCount()
	{
		global $modSettings, $user_info;
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
				elseif (allowedTo('lgal_approve_comment') || (!empty($modSettings['lgal_selfmod_approve_comment']) && !$user_info['is_guest']))
				{
					$moderate = self::getModel('LevGal_Model_Moderate');
					$count = $moderate->getUnapprovedCommentsCount();
				}
			}
		}

		return $count;
	}

	public static function getUnapprovedItemsCount()
	{
		global $modSettings, $user_info;
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
				elseif (allowedTo('lgal_approve_item') || (!empty($modSettings['lgal_selfmod_approve_item']) && !$user_info['is_guest']))
				{
					$moderate = self::getModel('LevGal_Model_Moderate');
					$count = $moderate->getUnapprovedItemsCount();
				}
			}
		}

		return $count;
	}

	public static function getUnapprovedAlbumsCount()
	{
		global $modSettings;

		return !empty($modSettings['lgal_unapproved_albums']) && allowedTo(array('lgal_manage', 'lgal_approve_album')) ? $modSettings['lgal_unapproved_albums'] : 0;
	}

	/**
	 * This is solely so that we can run the prebuffer at the right time.
	 */
	public static function hookLoadTheme()
	{
		$buffers = ob_list_handlers();
		if (empty($buffers) || !in_array('LevGal_Bootstrap::hookBuffer', $buffers))
		{
			ob_start(array('LevGal_Bootstrap', 'hookBuffer'));
		}
	}

	/**
	 * This declares the media bbcode items
	 *
	 * We define three bbcodes:
	 * [media]1[/media] for simple thumbnail+link
	 * [media optionalOptions id=1]description[/media] for more complex embedding with description and stuff
	 * optionalOptions:
	 *  - align=left|right|center, left and right are floated
	 *  - type=thumbnail|preview
	 * [clear] a self closed tag which can be used to "end" any float
	 *
	 * @param mixed $codes
	 */
	public static function hookBbcCodes(&$codes)
	{
		loadCSSFile('main.css', ['stale' => LEVGAL_VERSION, 'subdir' => 'levgal_res']);

		$codes[] = array(
			Codes::ATTR_TAG => 'media',
			Codes::ATTR_LENGTH => 5,
			Codes::ATTR_TYPE => Codes::TYPE_UNPARSED_CONTENT,
			Codes::ATTR_CONTENT => '!<lgalmediasimple: $1>',
			Codes::ATTR_VALIDATE => function(&$tag, &$data, $disabledBBC) {
				global $context, $settings, $txt;

				if (in_array('media', $disabledBBC))
				{
					return null;
				}

				$data = trim($data);
				if ($data === (string)(int) $data && allowedTo('lgal_view'))
				{
					if (empty($context['lgal_embeds']))
					{
						$context['lgal_embeds'] = new LevGal_Model_Embed();
					}

					$count = $context['lgal_embeds']->setId($data);
					$context['lgal_embeds']->addSimple();
					$tag[Codes::ATTR_CONTENT] =	'!<lgalmediasimple: ' . $count . '>';
				}
				else
				{
					loadLanguage('levgal_lng/LevGal');
					$tag[Codes::ATTR_CONTENT] = '<img src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
				}
			},
			Codes::ATTR_BLOCK_LEVEL => false,
		);
		$codes[] = array(
			Codes::ATTR_TAG => 'media',
			Codes::ATTR_LENGTH => 5,
			Codes::ATTR_PARAM => array(
				'id' => array(
					Codes::PARAM_ATTR_MATCH => '([1-9][0-9]*)',
				),
				'align' => array(
					Codes::PARAM_ATTR_MATCH => '(left|center|right)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				),
				'type' => array(
					Codes::PARAM_ATTR_MATCH => '(thumb|preview)',
					Codes::PARAM_ATTR_OPTIONAL => true,
				),
			),
			Codes::ATTR_TYPE => Codes::TYPE_UNPARSED_CONTENT,
			Codes::ATTR_CONTENT => '!<lgalmediacomplex: {id}>',
			Codes::ATTR_BEFORE => '{id},{align},{type}',
			Codes::ATTR_VALIDATE => function(&$tag, &$data, $disabledBBC) {
				global $context, $txt, $settings;

				if (in_array('media', $disabledBBC))
				{
					return null;
				}

				list($id, $align, $type) = explode(',', $tag[Codes::ATTR_BEFORE]);
				unset($tag[Codes::ATTR_BEFORE]); // Because demons.

				$id = (int) $id;
				if ($id > 0 && allowedTo('lgal_view'))
				{
					if (empty($context['lgal_embeds']))
					{
						$context['lgal_embeds'] = new LevGal_Model_Embed();
					}

					$count = $context['lgal_embeds']->setId($id);
					$context['lgal_embeds']->setAlign($align)->setType($type)->addComplex($data);
					$tag[Codes::ATTR_CONTENT] =	'!<lgalmediacomplex: ' . $count . '>';
				}
				else
				{
					loadLanguage('levgal_lng/LevGal');
					$tag[Codes::ATTR_CONTENT] = '<img src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
				}
			},
			Codes::ATTR_BLOCK_LEVEL => true,
		);
		$codes[] = array(
			Codes::ATTR_TAG => 'clear',
			Codes::ATTR_TYPE => Codes::TYPE_CLOSED,
			Codes::ATTR_CONTENT => '<div class="separator"></div>',
			Codes::ATTR_BLOCK_LEVEL => true,
			Codes::ATTR_AUTOLINK => false,
			Codes::ATTR_LENGTH => 5,
		);
	}

	/**
	 * This nasty little function tries to grab the content after ob_sessrewrite has had its wicked way.
	 * And then proceed to fix index.php?PHPSESSID=blah&media before fixing things embedded into bbcode.
	 */
	public static function hookBuffer($buffer)
	{
		global $scripturl, $context;

		if (!empty(LevGal_Bootstrap::$header))
		{
			$buffer = str_replace('</head>', LevGal_Bootstrap::$header . "\n" . '</head>', $buffer);
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
		else
		{
			return str_replace($scripturl . '?' . SID . '&amp;media', $scripturl . '?media', $buffer);
		}
	}

	/**
	 * This nifty little function limits a value inside the min/max specified but is easier to read :)
	 */
	public static function clamp($val, $min, $max)
	{
		return max(min($val, $max), $min);
	}

	/**
	 * Where possible we should really be caching models. This means one model state (and any related data)
	 * can be preserved without having to explicitly otherwise juggle it around.
	 */
	public static function getModel($modelName)
	{
		static $cache = null;

		if (!isset($cache[$modelName]))
		{
			$cache[$modelName] = new $modelName();
		}

		return $cache[$modelName];
	}

	public static function getGalleryDir()
	{
		global $modSettings;

		return strtr($modSettings['lgal_dir'], array('$boarddir' => BOARDDIR));
	}

	/**
	 * Used to interact with the message before its sent to parse_bbc as part of mail functions
	 */
	public static function hookPreParseBBC(&$message)
	{
		global $txt, $user_info;

		loadLanguage('levgal_lng/LevGal');

		// A scheduled task like daily digest, we can't render/geturl Media items as we don't know
		// (or want to lookup), those permissions.
		if (empty($user_info))
		{
			// Replace the [media][/media] tag
			$message = preg_replace('~\[media.*?\].*?\[\/media\]~s', '[ ' . $txt['levgal_email_photo_gallery'] . ' ]', $message);
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
	 * Used to interact with the signature before its sent to parse_bbc as part
	 * of PBE mail functions
	 */
	public static function hookPreSig(&$signature)
	{
		// Remove Media tags in signatures
		$signature = preg_replace('~\[media.*?\].*?\[/media]~s', '', $signature);
	}
}
