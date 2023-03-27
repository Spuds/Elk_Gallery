<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides the handling for albums, site/?media/album/*.
 */
class LevGal_Action_Album extends LevGal_Action_Abstract
{
	/** @var bool|int */
	private $album_id;
	/** @var bool|string */
	private $album_slug;
	/** @var \LevGal_Model_Album */
	private $album_obj;
	/** @var string */
	private $order_by;

	public function __construct()
	{
		global $context;

		// This means we load useful resources.
		parent::__construct();

		// Attempt to get something useful.
		list ($this->album_slug, $this->album_id) = $this->getSlugAndId();

		// Fetch some details.
		$this->album_obj = new LevGal_Model_Album();
		$context['album_details'] = $this->album_obj->getAlbumById($this->album_id);

		// Does the album even exist? Can they see it if it does?
		if (!$context['album_details'] || !$this->album_obj->isVisible())
		{
			LevGal_Helper_Http::fatalError('error_no_album');
		}

		// Does the album slug provided match the provided slug?
		if ($context['album_details']['album_slug'] !== $this->album_slug)
		{
			LevGal_Helper_Http::hardRedirect($context['album_details']['album_url'] . (!empty($_GET['sub']) ? $_GET['sub'] . '/' : ''));
		}

		// Set the default album sorting
		$this->order_by = $context['album_details']['sort'];
	}

	public function actionIndex()
	{
		[$order_by, $order] = explode('|', $this->order_by, 2);
		$this->processAlbums($order_by, $order);
	}

	public function actionView_date_desc()
	{
		$this->processAlbums('date', 'desc');
	}

	public function actionView_date_asc()
	{
		$this->processAlbums('date', 'asc');
	}

	public function actionView_name_desc()
	{
		$this->processAlbums('name', 'desc');
	}

	public function actionView_name_asc()
	{
		$this->processAlbums('name', 'asc');
	}

	public function actionView_views_desc()
	{
		$this->processAlbums('views', 'desc');
	}

	public function actionView_views_asc()
	{
		$this->processAlbums('views', 'asc');
	}

	public function actionView_comments_desc()
	{
		$this->processAlbums('comments', 'desc');
	}

	public function actionView_comments_asc()
	{
		$this->processAlbums('comments', 'asc');
	}

	protected function processAlbums($order_by, $order)
	{
		global $context, $txt, $modSettings, $scripturl;

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_viewing_album'], $context['album_details']['album_name']);

		$album = $this->album_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album['name'], $album['url']);

		$sort_options = $this->album_obj->getSortingOptions();
		$context['sort_options'] = array_keys($sort_options);
		$context['sort_criteria'] = array(
			'order_by' => $order_by,
			'order' => $order,
		);

		$sort_method = ($order_by === 'date' && $order === 'desc') ? '' : 'view_' . $order_by . '_' . $order . '/';
		$context['canonical_url'] = $context['album_details']['album_url'] . $sort_method;

		$this->setTemplate('LevGal-Album', 'main_album_view');

		loadCSSFile('glightbox.min.css', ['subdir' => 'levgal_res/lightbox']);
		loadJavascriptFile('glightbox.min.js', ['subdir' => 'levgal_res/lightbox', 'defer' => true]);

		$context['album_owner'] = $this->album_obj->loadOwnerData();

		$context['can_see_unapproved'] = array(
			'items' => !empty($context['album_details']['num_unapproved_items']) && (allowedTo(array('lgal_manage', 'lgal_approve_item')) || ($this->album_obj->isOwnedByUser() && !empty($modSettings['lgal_selfmod_approve_item']))) ? $context['album_details']['num_unapproved_items'] : 0,
			'comments' => !empty($context['album_details']['num_unapproved_comments']) ? $context['album_details']['num_unapproved_comments'] : 0,
		);
		// Comments are a lot complicated: we can only show them comments if they could approve them
		// - or failing that, the number of comments on their items that are unapproved.
		if (!empty($context['can_see_unapproved']['comments'])
			&& !allowedTo(array('lgal_manage', 'lgal_approve_comment')))
		{
			// So they don't have actual permission (and thus could see it normally, but they
			// might have self-mod permission, in which case we need comments on their items only.
			$context['can_see_unapproved']['comments'] = empty($modSettings['lgal_selfmod_approve_comment']) ? 0 : $this->album_obj->getUnapprovedCommentsOnUserItems($context['user']['id']);
		}

		$context['num_items'] = $this->album_obj->countAlbumItems();
		$num_pages = ceil($context['num_items'] / $modSettings['lgal_items_per_page']);
		$this_page = isset($_GET['page']) ? LevGal_Bootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['album_items'] = $this->album_obj->loadAlbumItems($modSettings['lgal_items_per_page'], ($this_page - 1) * $modSettings['lgal_items_per_page'], $order_by, $order);

		if ($num_pages > 1)
		{
			$context['album_pageindex'] = levgal_pageindex($context['album_details']['album_url'] . $sort_method, $this_page, $num_pages, '#gallery_contain');
		}

		if (!empty($modSettings['lgal_feed_enable_album']))
		{
			LevGal_Bootstrap::addHtmlHeader('
	<link rel="alternate" type="application/atom+xml" title="' . sprintf($txt['lgal_items_in'], $context['album_details']['album_name']) . '" href="' . $context['album_details']['album_url'] . 'feed/" />');
		}

		list($context['album_family'], $context['album_counts']) = $this->album_obj->getAlbumFamily();

		$context['album_actions'] = array();
		if ($this->album_obj->canUploadItems())
		{
			$context['album_actions']['actions']['additem'] = array($txt['lgal_add_item'], $album['url'] . 'add/', 'tab' => true);
			if (allowedTo(array('lgal_manage', 'lgal_addbulk')))
			{
				$context['album_actions']['actions']['addbulk'] = array($txt['lgal_add_bulk'], $album['url'] . 'addbulk/', 'tab' => true);
			}
		}
		if (!$context['user']['is_guest'])
		{
			// Notifications
			$notify = new LevGal_Model_Notify();
			if (!empty($notify->getSiteEnableNotifications()['lgnew']))
			{
				$action = $notify->getNotifyAlbumStatus($this->album_id, $context['user']['id']) ? 'unnotify' : 'notify';
				$context['album_actions']['actions'][$action] = array($txt['lgal_' . $action], $album['url'] . $action . '/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_' . $action . '_album_desc']);
			}
			// Marking seen
			$context['album_actions']['actions']['markseen'] = array($txt['lgal_mark_album_seen'], $album['url'] . 'markseen/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'tab' => true);
		}

		if (allowedTo('lgal_manage'))
		{
			$action = $this->album_obj->isFeatured() ? 'unfeature' : 'feature';
			$context['album_actions']['moderation'][$action . '_album'] = array($txt['lgal_' . $action . '_album'], $album['url'] . $action . '/' . $context['session_var'] . '=' . $context['session_id'] . '/');
		}

		if (!$this->album_obj->isApproved() && allowedTo(array('lgal_manage', 'lgal_approve_album')))
		{
			$context['album_actions']['moderation']['approvealbum'] = array($txt['lgal_approve_album_title'], $album['url'] . 'approve/' . $context['session_var'] . '=' . $context['session_id'] . '/');
		}

		// The templates make use of hierarchy for navigation links
		$ownership = $this->album_obj->getAlbumOwnership();
		/** @var \LevGal_Model_AlbumList $album_list */
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$owner = reset($ownership['owners']);
		$context['hierarchy'] = $album_list->getAlbumHierarchy($ownership['type'], $owner);

		if ($this->album_obj->isEditable())
		{
			$context['album_actions']['moderation']['editalbum'] = array($txt['lgal_edit_album_title'], $album['url'] . 'edit/', 'tab' => true);
			$context['album_actions']['actions']['editalbum'] = array($txt['lgal_edit_album_title'], $album['url'] . 'edit/', 'tab' => true, 'sidebar' => false);

			// Can they move this album?
			if ($ownership['type'] === 'site' && isAllowedTo('lgal_manage'))
			{
				$context['album_actions']['moderation']['movealbum'] = array($txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/site/');
			}
			elseif ($ownership['type'] === 'member')
			{
				if (!empty($context['hierarchy']) && count($context['hierarchy']) > 1)
				{
					$context['album_actions']['moderation']['movealbum'] = array($txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/' . $owner . '/member/');
				}
			}
		}

		if (allowedTo(array('lgal_manage', 'lgal_delete_album_any')) || (allowedTo('lgal_delete_album_own') && $this->album_obj->isOwnedByUser()))
		{
			$context['album_actions']['moderation']['deletealbum'] = array($txt['lgal_delete_album_title'], $album['url'] . 'delete/');
		}

		// Attempt to provide navigation back breadcrumbs when surfing albums
		if (empty($_SERVER['HTTP_REFERER']) || !isset($_SESSION['levgal_breadcrumbs']))
		{
			$_SESSION['levgal_breadcrumbs'] = [];
		}
		$key = array_search($this->album_id, array_keys($_SESSION['levgal_breadcrumbs']));
		if ($key !== false)
		{
			$key = $key === 0 ? 1 : $key;
			$_SESSION['levgal_breadcrumbs'] = array_slice($_SESSION['levgal_breadcrumbs'], 0, $key, true);
		}
		else
		{
			$_SESSION['levgal_breadcrumbs'][$this->album_id] = [$this->album_slug, $context['album_details']['album_name']];
		}
	}

	public function actionChunked()
	{
		$uploadModel = new LevGal_Model_Upload();

		// Before we go any further...
		$uploadModel->assertGalleryWritable();

		if (checkSession('post', '', false) !== '')
		{
			LevGal_Helper_Http::jsonResponse(array(
				'error' => 'session_timeout',
				'fatal' => true)
			);
		}

		$filename = empty($_POST['async_filename']) ? '' : rawurldecode($_POST['async_filename']);
		$fileID = $_POST['async'] ?? 0;
		$chunks = isset($_POST['async_chunks']) ? (int) $_POST['async_chunks'] : 0;

		$result = $uploadModel->combineChunks($fileID, $chunks, $filename);

		// Some error?
		if ($result['code'] !== '')
		{
			LevGal_Helper_Http::jsonResponse(array(
				'error' => $result['error'],
				'code' => $result['code'],
				'fatal' => true,
				'async' => $result['id'],
				'filename' => $filename,
			));
		}
		else
		{
			LevGal_Helper_Http::jsonResponse(array(
				'Combined' => 1,
				'async' => $result['id']
			), 200);
		}
	}

	public function actionAsync()
	{
		global $txt;

		loadLanguage('levgal_lng/LevGal');
		loadLanguage('levgal_lng/LevGal-Errors');

		if (!$this->album_obj->canUploadItems())
		{
			LevGal_Helper_Http::jsonResponse(array('error' => $txt['cannot_lgal_additem'], 'fatal' => true));
		}

		// We did actually get something, yes? We possibly got multiple files?
		if (empty($_FILES) || !empty($_FILES['file']['error']) || is_array($_FILES['file']['name']))
		{
			LevGal_Helper_Http::jsonResponse(array('error' => $txt['lgal_upload_failed'], 'fatal' => true));
		}

		if (checkSession('post', '', false) !== '')
		{
			LevGal_Helper_Http::jsonResponse(array('error' => 'session_timeout', 'fatal' => true));
		}

		$filename = $_POST['async_filename'] ?? $_FILES['file']['name'];
		$uploadModel = new LevGal_Model_Upload();
		$result = $uploadModel->saveAsyncFile($filename);

		// Some error?
		if ($result['code'] !== '')
		{
			LevGal_Helper_Http::jsonResponse(array(
				'error' => $result['error'],
				'code' => $result['code'],
				'fatal' => true,
				'async' => $result['id']
			));
		}
		else
		{
			LevGal_Helper_Http::jsonResponse(array(
				'OK' => 1,
				'async' => $result['id']
			), 200);
		}
	}

	public function actionAdd()
	{
		global $context, $txt, $modSettings, $user_info;

		$uploadModel = new LevGal_Model_Upload();

		// Before we go any further...
		$uploadModel->assertGalleryWritable();

		// First, permission check.
		if (!$this->album_obj->canUploadItems())
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_additem');
		}

		// Second up, going over the gallery limit. Once it goes over the quota, boom goes the dynamite.
		if (!allowedTo('lgal_manage') && !$uploadModel->isGalleryUnderQuota())
		{
			loadLanguage('levgal_lng/LevGal-Errors');
			$txt['levgal_gallery_over_quota'] = sprintf($txt['levgal_gallery_over_quota'], LevGal_Helper_Format::filesize($uploadModel->getGalleryQuota()));
			LevGal_Helper_Http::fatalError('levgal_gallery_over_quota');
		}

		loadLanguage('levgal_lng/LevGal-Upload');

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_adding_to_album'], $context['album_details']['album_name']);

		$album = $this->album_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album['name'], $album['url']);
		$this->addLinkTree($txt['lgal_add_item'], $album['url'] . 'add/');
		$context['canonical_url'] = $album['url'] . 'add/';

		loadJavascriptFile(['/dropzone/dropzone.js', 'url_slug.js', 'upload.js', 'jquery.flexdatalist.min.js'], ['subdir' => 'levgal_res', 'defer' => false]);
		addInlineJavascript('
		Dropzone.autoDiscover = false;
			$(".flexdatalist").flexdatalist({
				minLength: 0,
				limitOfValues: 5,' . (!empty($modSettings['lgal_tag_items_list_more']) ? '
				noResultsText: "' . $txt['lgal_item_tag_notfound'] . '"' : '
				selectionRequired: true') . '
			});', true);
		loadCSSFile(['/dropzone/dropzone.css', 'jquery.flexdatalist.css'], ['subdir' => 'levgal_res']);

		$this->setTemplate('LevGal-Album', 'add_single_item');

		$context['description_box'] = new LevGal_Helper_Richtext('message');

		$context['item_name'] = '';
		$context['item_slug'] = '';
		$context['item_posted_by'] = $_SESSION['guest_name'] ?? '';
		$context['description'] = '';

		// Tags
		/** @var $tagModel \LevGal_Model_Tag */
		$tagModel = LevGal_Bootstrap::getModel('LevGal_Model_Tag');
		$context['tags'] = $tagModel->getSiteTags();
		$context['can_add_tags'] = !empty($modSettings['lgal_tag_items_list_more']);

		$context['requires_approval'] = !allowedTo(array('lgal_manage', 'lgal_additem_approve', 'lgal_approve_item'));

		// Get the list of file types. If there aren't any, bail.
		$context['allowed_formats'] = $uploadModel->getDisplayFileFormats();
		$context['external_formats'] = array();
		if (!empty($context['allowed_formats']['external']))
		{
			$context['external_formats'] = $context['allowed_formats']['external'];
			unset ($context['allowed_formats']['external']);
		}
		if (empty($context['allowed_formats']) && empty($context['external_formats']))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_additem');
		}
		if (!empty($context['allowed_formats']))
		{
			$context['lgal_enable_resize'] = !empty($modSettings['lgal_enable_resize']);
			$context['quota_data'] = array(
				'formats' => $uploadModel->getFormatMap(),
				'quotas' => $uploadModel->getAllQuotas(),
			);
		}

		$context['upload_url'] = '';
		$context['upload_type'] = !empty($context['allowed_formats']) ? 'file' : 'link';

		$context['new_options'] = array();
		if (!empty($modSettings['lgal_enable_mature']))
		{
			$context['new_options']['mature'] = array('label' => $txt['lgal_mark_mature'], 'value' => false);
		}
		if (!$this->album_obj->isLockedForComments())
		{
			$context['new_options']['enable_comments'] = array('label' => $txt['lgal_enable_comments'], 'value' => true);
			if (!$user_info['is_guest'])
			{
				$context['new_options']['enable_notify'] = array('label' => $txt['lgal_enable_notify'], 'value' => false);
			}
		}

		// And lastly, custom fields.
		$context['custom_field_model'] = new LevGal_Model_Custom();
		$context['custom_fields'] = $context['custom_field_model']->prepareFieldInputs($this->album_id);

		// Save!
		if (isset($_POST['save']))
		{
			checkSession();
			$context['errors'] = array();

			// First, the item name.
			$context['item_name'] = LevGal_Helper_Sanitiser::sanitiseThingNameFromPost('item_name');
			if (empty($context['item_name']))
			{
				$context['errors']['upload_no_title'] = $txt['lgal_upload_no_title'];
			}

			// Next, dust off the slug. Items don't *have* to have slugs.
			$context['item_slug'] = LevGal_Helper_Sanitiser::sanitiseSlugFromPost('item_slug');

			// If a guest, we need their username.
			if ($context['user']['is_guest'])
			{
				list($valid, $context['item_posted_by']) = LevGal_Helper_Sanitiser::sanitiseUsernameFromPost('guest_username');
				if ($valid)
				{
					$_SESSION['guest_name'] = $context['item_posted_by'];
				}
				else
				{
					$context['errors'][] = $txt['levgal_error_invalid_user'];
				}
			}

			// Tags
			$context['raw_tags'] = LevGal_Helper_Sanitiser::sanitiseTagFromPost('tag');

			// Then description. It is optional.
			$context['description'] = '';
			if (!$context['description_box']->isEmpty() && $context['description_box']->sanitizeContent())
			{
				$context['description'] = $context['description_box']->getForDB();
			}

			// Save this new item
			$itemID = $this->processNewItem();
			if (!empty($itemID))
			{
				$itemModel = LevGal_Bootstrap::getModel('LevGal_Model_Item');
				$item_details = $itemModel->getItemInfoById($itemID);

				// Did the user want notifications on this item?
				if (!empty($context['new_options']['enable_notify']) && !empty($_POST['enable_notify']))
				{
					$notify = new LevGal_Model_Notify();
					$notify->setNotifyItem($itemID, $user_info['id']);
				}
				// Does anyone else want notifications?
				if (!$context['requires_approval'])
				{
					$this->album_obj->notifyItem($itemID, $itemModel);
				}

				// Need to let a guest see their own unapproved items.
				if ($user_info['is_guest'] && $context['requires_approval'])
				{
					$_SESSION['lgal_items'][] = $itemID;
				}

				redirectexit($item_details['item_url']);
			}
		}

		$context['description_box']->createEditor(array(
			'value' => $context['description_box']->getForForm($context['description']),
			'labels' => array(
				'post_button' => $txt['lgal_add_item'],
			),
			'js' => array(
				'post_button' => 'return is_submittable() && submitThisOnce(this);',
			),
		));
	}

	public function actionAddbulk()
	{
		global $context, $txt, $modSettings;

		$uploadModel = new LevGal_Model_Upload();

		// Before we go any further...
		$uploadModel->assertGalleryWritable();

		// First, permission check.
		if (!$this->album_obj->canUploadItems())
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_additem');
		}
		if (!allowedTo(array('lgal_manage', 'lgal_addbulk')))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_addbulk');
		}

		// Second up, going over the gallery limit. Once it goes over the quota, boom goes the dynamite.
		if (!allowedTo('lgal_manage') && !$uploadModel->isGalleryUnderQuota())
		{
			loadLanguage('levgal_lng/LevGal-Errors');
			$txt['levgal_gallery_over_quota'] = sprintf($txt['levgal_gallery_over_quota'], LevGal_Helper_Format::filesize($uploadModel->getGalleryQuota()));
			LevGal_Helper_Http::fatalError('levgal_gallery_over_quota');
		}

		loadLanguage('levgal_lng/LevGal-Upload');

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_adding_to_album'], $context['album_details']['album_name']);

		$album = $this->album_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album['name'], $album['url']);
		$this->addLinkTree($txt['lgal_add_bulk'], $album['url'] . 'addbulk/');
		$context['canonical_url'] = $album['url'] . 'addbulk/';

		$this->setTemplate('LevGal-Album', 'add_bulk_items');

		loadJavascriptFile(['/dropzone/dropzone.js', 'url_slug.js', 'upload.js'], ['subdir' => 'levgal_res', 'defer' => false]);
		addInlineJavascript('Dropzone.autoDiscover = false;', true);
		loadCSSFile(['/dropzone/dropzone.css'], ['subdir' => 'levgal_res']);

		$context['requires_approval'] = !allowedTo(array('lgal_manage', 'lgal_additem_approve', 'lgal_approve_item'));

		// Get the list of file types. If there aren't any, bail.
		$context['allowed_formats'] = $uploadModel->getDisplayFileFormats();

		// We don't care about externals here since this is strictly uploading bulk files.
		if (!empty($context['allowed_formats']['external']))
		{
			unset ($context['allowed_formats']['external']);
		}
		if (empty($context['allowed_formats']))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_additem');
		}
		if (!empty($context['allowed_formats']))
		{
			$context['lgal_enable_resize'] = !empty($modSettings['lgal_enable_resize']);
			$context['quota_data'] = array(
				'formats' => $uploadModel->getFormatMap(),
				'quotas' => $uploadModel->getAllQuotas(),
			);
		}
		if (!isset($_POST['save']))
		{
			return;
		}
		// When we receive an upload and the file is done, we call this function AJAXively
		// and return some JSON.
		if (isset($_POST['save']) && ($error = checkSession('post', '', false)) !== '')
		{
			LevGal_Helper_Http::jsonResponse(array(
				'error' => 'session_timeout',
				'fatal' => true,
				'session' => $error . ': ' . $context['session_var'] . '=' . $context['session_id'])
			);
		}

		$item_name = trim(urldecode($_POST['async_filename']));
		$context['item_name'] = LevGal_Helper_Sanitiser::sanitiseThingName($item_name);
		// We don't want the extension in the item name.
		$pos = strrpos($context['item_name'], '.');
		if ($pos !== false)
		{
			$context['item_name'] = substr($context['item_name'], 0, $pos);
		}
		$context['item_slug'] = LevGal_Helper_Sanitiser::sanitiseSlug($context['item_name']);

		$context['upload_type'] = !empty($context['allowed_formats']) ? 'file' : 'link';

		if (!$this->album_obj->isLockedForComments())
		{
			$context['new_options'] = array(
				'enable_comments' => array(
					'value' => true,
				),
			);
			$_POST['enable_comments'] = true;
		}

		$context['errors'] = array();

		$itemID = $this->processNewItem();
		if (!empty($itemID) && empty($context['errors']))
		{
			$itemModel = LevGal_Bootstrap::getModel('LevGal_Model_Item');
			$item_details = $itemModel->getItemInfoById($itemID);

			// Does anyone else want notifications?
			if (!$context['requires_approval'])
			{
				$this->album_obj->notifyItem($itemID, $itemModel);
			}

			LevGal_Helper_Http::jsonResponse(array(
				'async' => $context['async_id'],
				'url' => $item_details['item_url']
			), 200);
		}
		else
		{
			LevGal_Helper_Http::jsonResponse(array(
				'async' => $context['async_id'],
				'error' => array_keys($context['errors']))
			);
		}
	}

	public function processNewItem()
	{
		global $user_info, $context, $txt;

		$context['upload_type'] = isset($_POST['upload_type']) && $_POST['upload_type'] === 'link' ? 'link' : 'file';
		$url = LevGal_Helper_Sanitiser::sanitiseUrlFromPost('upload_url');
		$context['upload_url'] = !empty($url) ? $url : LevGal_Helper_Sanitiser::sanitiseTextFromPost('upload_url');

		if (!empty($context['external_formats']) && $context['upload_type'] === 'link')
		{
			if (empty($url))
			{
				$context['errors']['upload_no_link'] = $txt['lgal_upload_no_link'];
			}
		}
		elseif (!empty($context['allowed_formats']) && $context['upload_type'] === 'file')
		{
			// Grab the file details. These we need.
			$context['filename'] = empty($_POST['async_filename']) ? '' : rawurldecode($_POST['async_filename']);
			$context['async_id'] = $_POST['async'] ?? 0;
			$context['async_size'] = isset($_POST['async_size']) && (int) $_POST['async_size'] > 0 ? (int) $_POST['async_size'] : 0;
			if (empty($context['filename']) || empty($context['async_id']) || empty($context['async_size']))
			{
				$context['errors']['upload_no_file'] = $txt['lgal_upload_no_file'];
			}
			else
			{
				$context['existing_upload'] = true;
				$context['filename_display'] = LevGal_Helper_Sanitiser::sanitiseText($context['filename']);
				$context['filename_post'] = rawurlencode($context['filename']);
			}
		}
		else
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_additem');
		}

		// Going back already? Fix the options if we are.
		foreach (array_keys($context['new_options']) as $id)
		{
			$context['new_options'][$id]['value'] = !empty($_POST[$id]);
		}

		// Lastly, custom fields.
		if (!empty($context['custom_fields']))
		{
			$field_results = $context['custom_field_model']->getFieldValuesFromPost($context['custom_fields']);

			if (!empty($field_results['values']))
			{
				foreach ($field_results['values'] as $id_field => $value)
				{
					$context['custom_fields'][$id_field]['value'] = $value;
				}
			}
			if (!empty($field_results['errors']))
			{
				$context['errors'] = array_merge($context['errors'], $field_results['errors']);
			}
		}

		if (!empty($context['errors']))
		{
			return false;
		}

		$uploadModel = new LevGal_Model_Upload();
		/** @var $itemModel \LevGal_Model_Item */
		$itemModel = LevGal_Bootstrap::getModel('LevGal_Model_Item');

		$item_info = array(
			'item_name' => $context['item_name'],
			'item_slug' => $context['item_slug'],
			'id_album' => $this->album_id,
			'poster_info' => array(
				'id' => $user_info['id'],
				'name' => $context['user']['is_guest'] ? $context['item_posted_by'] : $user_info['name'],
			),
			'description' => !empty($context['description']) ? $context['description'] : '',
			'approved' => !$context['requires_approval'],
			'comment_state' => empty($context['new_options']['enable_comments']) || empty($_POST['enable_comments']) ? 2 : 0, // 0 = enable, 1 = no new comments, 2 = no comments
			'mature' => !empty($context['new_options']['mature']) && !empty($_POST['mature']),
			'has_tags' => !empty($context['raw_tags']),
		);

		if ($context['upload_type'] === 'link')
		{
			$externalModel = new LevGal_Model_External();
			$url_data = $externalModel->getURLData($context['upload_url']);
			if (!empty($url_data['provider']))
			{
				// External things don't have filenames or sizes.
				$item_info['filename'] = '';
				$item_info['extension'] = '';
				$item_info['filesize'] = 0;
				$item_info['filehash'] = $uploadModel->getFileHash($context['upload_url']);
				$item_info['mime_type'] = $url_data['mime_type'];
				unset ($url_data['mime_type']);
				$item_info['meta'] = $url_data;

				$itemID = $itemModel->createItem($item_info);

				if ($thumbnail = $externalModel->getThumbnail())
				{
					$itemModel->setThumbnail($thumbnail);
				}

				// And custom fields.
				if (!empty($context['custom_fields']))
				{
					$context['custom_field_model']->setCustomValues($itemID, $context['custom_fields']);
				}

				return $itemID;
			}

			$context['errors']['upload_no_link'] = $txt['lgal_upload_no_link'];
		}
		else
		{
			$result = $uploadModel->validateUpload($context['async_id'], $context['async_size'], $context['filename']);
			if ($result === true)
			{
				$item_info['filename'] = $context['filename'];
				$item_info['extension'] = $uploadModel->getExtension($item_info['filename']);
				$item_info['filesize'] = $context['async_size'];
				$itemID = $itemModel->createItem($item_info);

				if ($hash = $uploadModel->moveUpload($context['async_id'], $itemID, $context['filename']))
				{
					// First, the hash. We need this before we do anything else.
					$itemModel->updateItem(array('hash' => $hash));

					// Did they add tags?
					if (!empty($context['raw_tags']))
					{
						/** @var $tagModel \LevGal_Model_Tag */
						$tagModel = LevGal_Bootstrap::getModel('LevGal_Model_Tag');
						$tagModel->setTagsOnItem($itemID, $context['raw_tags']);
					}

					// Then we can do the fun of meta.
					$meta = $itemModel->getMetadata();
					$opts = array(
						'mime_type' => $meta['mime_type'],
					);

					// Did we get width/height?
					if (isset($meta['meta']['width'], $meta['meta']['height']))
					{
						foreach (array('width', 'height') as $var)
						{
							$opts[$var] = $meta['meta'][$var];
							unset ($meta['meta'][$var]);
						}
					}
					$opts['meta'] = $meta['meta'];

					// Before we do the thumbnail thing we might need to fix the orientation.
					if (!empty($meta['meta']['exif']['IFD0']['Orientation']))
					{
						$array = $itemModel->fixOrientation($meta['meta']['exif']['IFD0']['Orientation']);
						if (!empty($array))
						{
							list($opts['width'], $opts['height'], $opts['filesize']) = $array;
						}
					}

					// Now update the item.
					$itemModel->updateItem($opts);

					// Did we get a thumbnail from meta?
					if (isset($meta['thumbnail']))
					{
						$itemModel->setThumbnail($meta['thumbnail']);
					}
					else
					{
						$itemModel->getThumbnail();
					}

					// And custom fields.
					if (!empty($context['custom_fields']))
					{
						$context['custom_field_model']->setCustomValues($itemID, $context['custom_fields']);
					}

					// And we're done.
					return $itemID;
				}

				$itemModel->deleteItem();
				$context['errors']['upload_no_move'] = $txt['lgal_upload_no_move'];
			}
			elseif (is_string($result))
			{
				$context['errors'][$result] = $txt['lgal_' . $result];
			}
			else
			{
				$context['errors']['upload_no_validate'] = $txt['lgal_upload_no_validate'];
			}
		}

		return false;
	}

	public function actionMarkseen()
	{
		global $user_info, $user_settings, $context;

		if (!$user_info['is_guest'] && !empty($user_settings['lgal_unseen']))
		{
			checkSession('get');
			$this->album_obj->markSeen();
		}

		redirectexit($context['album_details']['album_url']);
	}

	public function actionDelete()
	{
		global $context, $txt, $scripturl;

		// Perms checking is a bit hard here.
		if (!allowedTo('lgal_manage')
			&& !allowedTo('lgal_delete_album_any')
			&& (!allowedTo('lgal_delete_album_own') || !$this->album_obj->isOwnedByUser()))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_delete_album');
		}

		$context['page_title'] = sprintf($txt['lgal_deleting_album'], $context['album_details']['album_name']);

		$album = $this->album_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album['name'], $album['url']);
		$this->addLinkTree($txt['lgal_delete_album_title']);
		$context['canonical_url'] = $album['url'] . 'delete/';

		$this->setTemplate('LevGal-Album', 'delete_album');
		$context['form_url'] = $album['url'] . 'delete/';

		if (isset($_POST['delete']))
		{
			checkSession();
			$this->album_obj->deleteAlbum();
			redirectexit($scripturl . '?media/');
		}

		if (isset($_POST['cancel']))
		{
			redirectexit($context['album_details']['album_url']);
		}
	}

	public function actionFeed()
	{
		global $context, $modSettings;

		if (empty($modSettings['lgal_feed_enable_album']))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_feed');
		}

		$feed = new LevGal_Helper_Feed();
		$feed->title = $context['album_details']['album_name'];
		$feed->alternateUrl = $context['album_details']['album_url'];
		$feed->selfUrl = $context['album_details']['album_url'] . 'feed/';

		$countItems = $this->album_obj->countAlbumItems();
		if (!empty($countItems))
		{
			$items = $this->album_obj->loadAlbumItems($modSettings['lgal_feed_items_album'], 0, 'date', 'desc', true);
			foreach ($items as $item)
			{
				$entry = array(
					'title' => $item['item_name'],
					'link' => $item['item_url'],
					'content' => $item['description'],
					'category' => array($context['album_details']['album_name'], $context['album_details']['album_url']),
					'author' => array($item['poster_name'], $item['id_member']),
					'published' => $item['time_added'],
					'updated' => $item['time_updated'],
				);
				$feed->addEntry($entry);
			}
		}

		$feed->outputFeed();
	}

	public function actionNotify()
	{
		$this->handleNotify('notify');
	}

	public function actionUnnotify()
	{
		$this->handleNotify('unnotify');
	}

	protected function handleNotify($type)
	{
		global $context;

		// First, guests can't do this whatever happens.
		is_not_guest();

		// Second, session check. Round 1: accepting via GET link.
		if (checkSession('get', '', false) === '')
		{
			$notify = new LevGal_Model_Notify();
			$method = $type === 'notify' ? 'setNotifyAlbum' : 'unsetNotifyAlbum';
			$notify->$method($this->album_id, $context['user']['id']);
			redirectexit($context['album_details']['album_url']);
		}

		// Round 2: accepting via POST form from media/album/blah.1/notify/
		if (checkSession('post', '', false) === '' && (!empty($_POST['notify_yes']) || !empty($_POST['notify_no'])))
		{
			$notify = new LevGal_Model_Notify();
			$method = !empty($_POST['notify_yes']) ? 'setNotifyAlbum' : 'unsetNotifyAlbum';
			$notify->$method($this->album_id, $context['user']['id']);
			redirectexit($context['album_details']['album_url']);
		}

		redirectexit($context['album_details']['album_url']);
	}

	public function actionFeature()
	{
		$this->handleFeature('feature');
	}

	public function actionUnfeature()
	{
		$this->handleFeature('unfeature');
	}

	protected function handleFeature($feature)
	{
		global $context;

		// Only gallery managers are allowed to feature albums.
		// This is much simpler than notifications, so too should all the code be.
		isAllowedTo('lgal_manage');
		checkSession('get');

		$this->album_obj->markFeatured($feature === 'feature');
		redirectexit($context['album_details']['album_url']);
	}

	public function actionApprove()
	{
		global $context;
		$this->handleApprove();
		redirectexit($context['album_details']['album_url']);
	}

	public function actionApprove_unapproved()
	{
		global $scripturl;
		$this->handleApprove();
		redirectexit($scripturl . '?media/moderate/unapproved_albums/');
	}

	protected function handleApprove()
	{
		// You need to be able to approve albums, and the album needs to be sort of unapproved to go further.
		if (!allowedTo(array('lgal_manage', 'lgal_approve_album') || $this->album_obj->isApproved()))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_approve_album');
		}

		checkSession('get');

		$this->album_obj->markApproved();
	}

	public function actionDelete_unapproved()
	{
		global $scripturl;

		// Perms checking is a bit hard here.
		if (!allowedTo('lgal_manage')
			&& !allowedTo('lgal_delete_album_any')
			&& (!allowedTo('lgal_delete_album_own') || !$this->album_obj->isOwnedByUser()))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_delete_album');
		}

		checkSession('get');
		$this->album_obj->deleteAlbum();
		redirectexit($scripturl . '?media/moderate/unapproved_albums/');
	}

	public function actionEdit()
	{
		global $context, $txt;

		loadLanguage('levgal_lng/LevGal-AlbumEdit');

		if (!$this->album_obj->isEditable())
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_edit_album');
		}

		$context['page_title'] = sprintf($txt['lgal_editing_album'], $context['album_details']['album_name']);
		$context['destination'] = $context['album_details']['album_url'] . 'edit/';
		$context['canonical_url'] = $context['album_details']['album_url'] . 'edit/';

		$album = $this->album_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album['name'], $album['url']);
		$this->addLinkTree($txt['lgal_edit_album_title']);

		$this->setTemplate('LevGal-Album', 'edit_album');
		// But even though we use the LevGal-Album template we share with the NewAlbum template.
		loadTemplate('levgal_tpl/LevGal-NewAlbum');

		$context['display_featured'] = allowedTo('lgal_manage');
		$context['is_featured'] = $this->album_obj->isFeatured();

		$context['locked_for_items'] = $this->album_obj->isLockedForItems();
		$context['locked_for_comments'] = $this->album_obj->isLockedForComments();

		// There are certain things we will need to have set up to make this work.
		$context['ownership_blocks'] = array();
		$ownership = $this->album_obj->getAlbumOwnership();
		$context['ownership_original'] = $ownership['type'];
		$context['ownership'] = $context['ownership_original'];
		$context['ownership_data'] = array();
		$context['ownership_opts'] = $this->album_obj->getOwnershipOptions();
		$context['group_list'] = $this->album_obj->getAllowableOwnershipGroups();
		$context['access_list'] = $this->album_obj->getAllowableAccessGroups();

		// The album owners can add new members/groups, the managers can do anything.
		if (allowedTo('lgal_manage'))
		{
			$context['ownership_blocks'][] = 'change_type';
		}
		foreach (array('member', 'group') as $type)
		{
			if ($context['ownership'] === $type)
			{
				if (allowedTo('lgal_manage'))
				{
					$context['add_' . $type] = array();
					$context['remove_' . $type] = array();
					$context['ownership_blocks'][] = 'add_owner_' . $type;
					$context['ownership_blocks'][] = 'remove_owner_' . $type;
				}
				elseif ($this->album_obj->isOwnedByUser())
				{
					$context['add_' . $type] = array();
					$context['ownership_blocks'][] = 'add_owner_' . $type;
				}
			}
		}
		if (!empty($context['ownership_blocks']))
		{
			$context['album_owner'] = $this->album_obj->loadOwnerData();
		}

		$context['privacy'] = $context['album_details']['perms']['type'];
		$context['privacy_group'] = $context['album_details']['perms']['type'] === 'custom' ? $context['album_details']['perms']['groups'] : array();
		$context['owner_member'] = array();
		$context['owner_member_display'] = array();
		$context['description'] = $context['album_details']['description_raw'];

		// Sorting options
		$sort_options = $this->album_obj->getSortingOptions();
		$context['sort_options'] = array_keys($sort_options);
		[$context['order_by'], $context['order']] = explode('|', $context['album_details']['sort'], 2);

		// Setup the description
		$context['description_box'] = new LevGal_Helper_Richtext('message');
		$context['description_box']->createEditor(array(
			'value' => $context['description_box']->getForForm($context['description']),
			'labels' => array(
				'post_button' => $txt['lgal_edit_album_title'],
				'name' => 'save',
			),
			'js' => array(
				'post_button' => 'return is_submittable() && submitThisOnce(this);',
			),
		));

		$context['thumbnail_list'] = array(
			'no_change' => 'no_change',
			'upload' => 'upload',
			'folder_colours' => array(
				'folder-red.svg' => 'color_red',
				'folder-orange.svg' => 'color_orange',
				'folder-yellow.svg' => 'color_yellow',
				'folder-green.svg' => 'color_green',
				'folder-cyan.svg' => 'color_cyan',
				'folder-grey.svg' => 'color_grey',
				'folder-magenta.svg' => 'color_magenta',
				'folder.svg' => 'color_blue',
				'folder-violet.svg' => 'color_violet',
				'folder-brown.svg' => 'color_brown',
				'folder-black.svg' => 'color_black',
			),
			'folder_icons' => array(
				'folder-image.svg' => 'icon_picture',
				'folder-documents.svg' => 'icon_documents',
				'folder-sound.svg' => 'icon_audio',
				'folder-video.svg' => 'icon_video',
				'folder-remote.svg' => 'icon_remote',
				'folder-home.svg' => 'icon_home',
				'folder-favorites.svg' => 'icon_favorites',
			),
		);

		if (isset($_POST['save']))
		{
			checkSession();

			$changes = array();

			// Name, description and slug, nice and easy.
			$changes['album_name'] = LevGal_Helper_Sanitiser::sanitiseThingNameFromPost('album_name');
			$changes['album_slug'] = LevGal_Helper_Sanitiser::sanitiseSlugFromPost('album_slug');
			$changes['description'] = $this->album_obj->setAlbumDescription();

			if ($context['display_featured'])
			{
				$context['now_featured'] = !empty($_POST['feature']);
				$change_featured = $context['is_featured'] !== $context['now_featured'];
				// And if we have an error...
				$context['is_featured'] = $context['now_featured'];
			}

			$context['locked_for_items'] = !empty($_POST['lock_items']);
			$context['locked_for_comments'] = !empty($_POST['lock_comments']);
			$changes['locked'] = ($context['locked_for_items'] ? LevGal_Model_Album::LOCKED_ITEMS : 0) | ($context['locked_for_comments'] ? LevGal_Model_Album::LOCKED_COMMENTS : 0);

			$changes['sort'] = $this->album_obj->setAlbumDefaultSort($_POST['default_sort'], $_POST['default_sort_direction']);

			if (isset($_POST['privacy']) && in_array($_POST['privacy'], array('guests', 'members', 'justme', 'custom')))
			{
				$context['privacy'] = $_POST['privacy'];
				$context['privacy_group'] = array();
				$changes['perms'] = array(
					'type' => $_POST['privacy'],
				);
				if ($_POST['privacy'] === 'custom' && isset($_POST['privacy_group']) && is_array($_POST['privacy_group']))
				{
					$changes['perms']['groups'] = array();
					foreach ($_POST['privacy_group'] as $v)
					{
						$v = (int) $v;
						if ($v >= 0)
						{
							$changes['perms']['groups'][] = $v;
						}
					}
					$changes['perms']['groups'] = array_intersect($changes['perms']['groups'], array_keys($context['access_list']));
					$context['privacy_group'] = $changes['perms']['groups'];
				}
			}

			if (in_array('change_type', $context['ownership_blocks'], true) && isset($_POST['ownership']) && in_array($_POST['ownership'], $context['ownership_opts'], true) && $_POST['ownership'] != $context['ownership'])
			{
				if ($_POST['ownership'] === 'site')
				{
					$changing_ownership = true;
					$context['ownership'] = 'site';
					$context['ownership_data'] = array();
				}
				elseif ($_POST['ownership'] === 'group')
				{
					$changing_ownership = true;
					$context['ownership'] = 'group';
					$context['ownership_data'] = isset($_POST['ownership_group']) && is_array($_POST['ownership_group']) ? array_intersect($_POST['ownership_group'], array_keys($context['group_list'])) : array();
					if (empty($context['ownership_data']))
					{
						$context['errors']['one_owner'] = 'levgal_error_at_least_one_owner';
					}
				}
				elseif ($_POST['ownership'] === 'member')
				{
					$memberModel = new LevGal_Model_Member();
					list ($context['owner_member'], $context['owner_member_display']) = $memberModel->getFromAutoSuggest('ownership_member');

					$changing_ownership = true;
					$context['ownership'] = 'member';
					$context['ownership_data'] = $context['owner_member'];
					if (empty($context['ownership_data']))
					{
						$context['errors']['one_owner'] = 'levgal_error_at_least_one_owner';
					}
				}
			}

			if (empty($changing_ownership))
			{
				if ($context['ownership'] === 'member' && in_array('add_owner_member', $context['ownership_blocks'], true))
				{
					$memberModel = new LevGal_Model_Member();
					list ($context['add_member'], $context['add_member_display']) = $memberModel->getFromAutoSuggest('add_member');
				}
				if ($context['ownership'] === 'group'
					&& in_array('add_owner_group', $context['ownership_blocks'], true)
					&& isset($_POST['add_group']) && is_array($_POST['add_group']))
				{
					// So, get the groups the user selected, marry them against the list of valid groups
					// then remove any duplicates we might have.
					$context['add_group'] = array_intersect($_POST['add_group'], array_keys($context['group_list']));
					$context['add_group'] = array_diff($context['add_group'], $context['album_owner']['group']);
				}
				// Removing is actually a bit simpler than adding since it's the same either way.
				foreach (array('member', 'group') as $type)
				{
					if (in_array('remove_owner_' . $type, $context['ownership_blocks'], true) && isset($_POST['remove_' . $type]) && is_array($_POST['remove_' . $type]))
					{
						$values = array_intersect($_POST['remove_' . $type], $context['album_owner'][$type]);
						if (!empty($values))
						{
							$context['remove_' . $type] = $values;
							// Now, this is where it gets complicated.
							if (count(array_diff($context['album_owner'][$type], $values)) === 0)
							{
								if (in_array('site', $context['ownership_opts'], true))
								{
									$context['add_member'] = array();
									$context['remove_member'] = array();
									$context['add_group'] = array();
									$context['remove_group'] = array();
									$context['ownership'] = 'site';
									$context['ownership_data'] = array();
									$changing_ownership = true;
								}
								else
								{
									$context['errors']['one_owner'] = 'levgal_error_at_least_one_owner';
								}
							}
						}
					}
				}
			}

			// See if we're changing thumbnail at all.
			if (!empty($_POST['thumbnail_selector']) && $_POST['thumbnail_selector'] !== 'no_change')
			{
				if ($_POST['thumbnail_selector'] === 'upload')
				{
					if (!empty($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['tmp_name']) && is_uploaded_file($_FILES['thumbnail']['tmp_name']))
					{
						$image = new LevGal_Helper_Image();
						if ($ext = $image->loadImageFromFile($_FILES['thumbnail']['tmp_name']))
						{
							global $modSettings;

							$thumbMax = $modSettings['attachmentThumbWidth'] ?: 125;
							$gal_path = LevGal_Bootstrap::getGalleryDir();
							$upload_path = $gal_path . '/album_' . $context['album_details']['id_album'] . '_' . $context['user']['id'];
							$image->resizeToNewFile($thumbMax, $upload_path, $ext);
						}
					}
				}
				else
				{
					// Build a possible list of what it might be
					$possible_options = array_merge($context['thumbnail_list']['folder_colours'], $context['thumbnail_list']['folder_icons']);
					if (isset($possible_options[$_POST['thumbnail_selector']]))
					{
						$changes['thumbnail'] = $_POST['thumbnail_selector'];
					}
				}
			}

			// Error checking!
			if (isset($changes['album_name']) && empty($changes['album_name']))
			{
				$context['errors'][] = 'levgal_no_edit_album_name';
			}

			if (!empty($context['errors']))
			{
				// So we're going to fall back to the form. Let's make sure we put some values back.
				$context['album_details'] = array_merge($context['album_details'], $changes);
				$context['album_details']['thumbnail_url'] = $this->album_obj->getThumbnailUrl();
			}
			else
			{
				if (!empty($changing_ownership))
				{
					$this->album_obj->setAlbumOwnership($context['ownership'], $context['ownership_data']);
				}
				else
				{
					foreach (array('member', 'group') as $type)
					{
						if (!empty($context['add_' . $type]))
						{
							$this->album_obj->addAlbumOwner($type, $context['add_' . $type]);
						}
						if (!empty($context['remove_' . $type]))
						{
							$this->album_obj->removeAlbumOwner($type, $context['remove_' . $type]);
						}
					}
				}

				if (!empty($change_featured))
				{
					$this->album_obj->markFeatured($context['now_featured']);
				}

				if (!empty($upload_path))
				{
					$this->album_obj->setThumbnailFromFile($upload_path, $ext);
					@unlink($upload_path);
					// If this was the only change... we should still update the table appropriately.
					if (empty($changes))
					{
						$this->album_obj->updateAlbum(array('editable' => 0));
					}
				}

				if (!empty($changes))
				{
					$changes['editable'] = 0;
					$this->album_obj->updateAlbum($changes);
				}

				redirectexit($context['album_details']['album_url']);
			}
		}
	}

	public function actionThumb()
	{
		list ($ext, $thumb) = $this->album_obj->getThumbnailFile();

		// If we didn't get an extension we know this wasn't an actual file we needed, so we should 301 it over to the actual image.
		if (empty($ext))
		{
			LevGal_Helper_Http::hardRedirect($thumb);
		}

		$extensions = array(
			'png' => 'image/png',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
		);
		$format = $extensions[$ext] ?? 'application/octet-stream';

		header('Content-Type: ' . $format);
		echo file_get_contents($thumb);
		obExit(false);
	}
}
