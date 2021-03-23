<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

use BBC\ParserWrapper;

/**
 * This file deals with file internals.
 */
class LevGal_Model_Item extends LevGal_Model_File
{
	/** @var int  */
	const SEEN_THRESHOLD = 120;
	/** @var bool  */
	protected $current_item = false;
	/** @var bool  */
	protected $current_album = false;

	public function getItemInfoById($itemId)
	{
		global $scripturl;

		$db = database();

		// It's a uint, anything like this can disappear.
		if ($itemId <= 0)
		{
			return false;
		}

		// This can be called multiple times, potentially, for the same item.
		if (!empty($this->current_item['id_item']) && $itemId = $this->current_item['id_item'])
		{
			return $this->current_item;
		}

		$request = $db->query('', '
			SELECT 
				li.id_item, li.id_album, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, li.poster_name) AS poster_name, li.item_name, li.item_slug,
				li.filename, li.filehash, li.extension, li.mime_type, li.time_added, li.time_updated, li.description, li.approved, li.editable, li.comment_state,
				li.filesize, li.width, li.height, li.mature, li.num_views, li.num_comments, li.num_unapproved_comments, li.has_custom, li.has_tag, li.meta
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}members AS mem ON (li.id_member = mem.id_member)
			WHERE id_item = {int:itemId}',
			array(
				'itemId' => $itemId,
			)
		);

		if ($db->num_rows($request) > 0)
		{
			$parser = ParserWrapper::instance();
			$this->current_item = $db->fetch_assoc($request);
			censor($this->current_item['item_name']);
			$this->current_item['description_raw'] = $this->current_item['description'];
			censor($this->current_item['description']);
			$this->current_item['description'] = !empty($this->current_item['description']) ? $parser->parseMessage($this->current_item['description'], true) : '';
			$this->current_item['meta'] = !empty($this->current_item['meta']) ? @unserialize($this->current_item['meta']) : array();
			$this->current_item['item_url'] = $scripturl . '?media/item/' . (!empty($this->current_item['item_slug']) ? $this->current_item['item_slug'] . '.' . $itemId : $itemId) . '/';
			foreach (array('time_added', 'time_updated') as $item)
			{
				$this->current_item[$item . '_format'] = LevGal_Helper_Format::time($this->current_item[$item]);
			}
		}
		$db->free_result($request);

		return $this->current_item;
	}

	public function getLinkTreeDetails()
	{
		if (empty($this->current_item))
		{
			return array();
		}

		return array(
			'name' => $this->current_item['item_name'],
			'url' => $this->current_item['item_url'],
		);
	}

	// This isn't pretty but it means we can reuse all the exciting other methods without having
	// to expressly requery anything.
	public function buildFromSurrogate($details)
	{
		global $scripturl;

		$this->current_item = $details;
		$this->current_item['meta'] = !empty($this->current_item['meta']) ? @unserialize($this->current_item['meta']) : array();
		$this->current_item['item_url'] = $scripturl . '?media/item/' . (!empty($this->current_item['item_slug']) ? $this->current_item['item_slug'] . '.' . $this->current_item['id_item'] : $this->current_item['id_item']) . '/';
		$this->current_item['is_surrogate'] = true;
	}

	public function getParentAlbum()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		if (empty($this->current_album))
		{
			$this->current_album = new LevGal_Model_Album();
		}

		return $this->current_album->getAlbumById($this->current_item['id_album']);
	}

	public function albumIsOwnedByUser()
	{
		$this->getParentAlbum();

		return $this->current_album->isOwnedByUser();
	}

	public function canUseThumbnail()
	{
		$this->getParentAlbum();

		return $this->current_album->isEditable();
	}

	public function canChangeApproveStatus()
	{
		global $modSettings;

		// If we don't have an item, we can't approve it.
		if (empty($this->current_item))
		{
			return false;
		}

		return allowedTo(array('lgal_manage', 'lgal_approve_item')) || (!empty($modSettings['lgal_selfmod_approve_item']) && $this->albumIsOwnedByUser());
	}

	public function isEditable()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		// If they're a gallery manager, or they can edit any item, or they can edit their own
		// items (and this is their item), let them edit.
		if (allowedTo(array('lgal_manage', 'lgal_edit_item_any')) || (allowedTo('lgal_edit_item_own') && $this->isOwnedByUser()))
		{
			return true;
		}

		// If, however, it's not any of these, it *might* still be editable if it's their item and
		// not yet finalised e.g. they want to adjust the thumbnail, something we don't offer them the initial page.
		if ($this->isOwnedByUser() && !empty($this->current_item['editable']))
		{
			return true;
		}

		return false;
	}

	public function getItemURLs()
	{
		global $scripturl, $settings;
		$urls = array();

		if (empty($this->current_item))
		{
			return $urls;
		}

		// We use existence of physical files to determine what is available.
		$files = $this->getFilePaths();
		if (empty($files['raw']) && strpos($this->current_item['mime_type'], 'external') !== 0)
		{
			return $urls;
		}

		$base_url = $scripturl . '?media/file/' . (!empty($this->current_item['item_slug']) ? $this->current_item['item_slug'] . '.' : '') . $this->current_item['id_item'] . '/';

		// Everything has a download link and a raw link. Main difference is headers.
		$urls['item'] = $scripturl . '?media/item/' . (!empty($this->current_item['item_slug']) ? $this->current_item['item_slug'] . '.' : '') . $this->current_item['id_item'] . '/';
		// Can't have a download link for externals.
		if (strpos($this->current_item['mime_type'], 'external') !== 0)
		{
			$urls['download'] = $base_url . 'download/';
		}
		$urls['raw'] = $base_url;

		if ($this->isMature() && $this->hidingMature())
		{
			$urls['preview'] = $urls['thumb'] = $settings['default_theme_url'] . '/levgal_res/icons/_mature.png';

			return $urls;
		}

		// So, the raw file itself. If this is an image, we want index.php?media/file/my-item.1/ not a download link
		if (in_array($this->current_item['mime_type'], array('image/jpg', 'image/gif', 'image/jpeg', 'image/png')))
		{
			// Is there a preview? This is the tricky one. A preview image should exist only if the main image is big enough.
			if (!empty($files['preview']))
			{
				$urls['preview'] = $base_url . 'preview/';
			}

			// Is there a thumbnail?
			if (!empty($files['thumb']))
			{
				$urls['thumb'] = $base_url . 'thumb/';
			}
			else
			{
				$urls['thumb'] = $this->getIconUrl('jpg'); // Any image file will do.
			}
		}
		elseif (strpos($this->current_item['mime_type'], 'external') === 0)
		{
			if (!empty($files['thumb']))
			{
				$urls['preview'] = $urls['thumb'] = $base_url . 'thumb/';
			}
			else
			{
				$urls['preview'] = $urls['thumb'] = $settings['default_theme_url'] . '/levgal_res/icons/' . substr($this->current_item['mime_type'], 9) . '.png';
			}
		}
		// Something lovely and generic.
		else
		{
			// Not an image. As a result, it may or may not have a suitable preview image.
			if (!empty($files['preview']))
			{
				$urls['preview'] = $base_url . 'preview/';
			}

			if (!empty($files['thumb']))
			{
				$urls['thumb'] = $base_url . 'thumb/';
			}

			if (empty($urls['preview']) || empty($urls['thumb']))
			{
				// We need to determine which it is.
				$extension = !empty($this->current_item['extension']) ? $this->current_item['extension'] : '';
				$fallback_icon = $this->getIconUrl($extension);
				foreach (array('preview', 'thumb') as $type)
				{
					if (empty($urls[$type]))
					{
						$urls[$type] = $fallback_icon;
						$urls['generic'][$type] = true;
					}
				}
			}
		}

		return $urls;
	}

	public function isMature()
	{
		global $modSettings;

		return !empty($modSettings['lgal_enable_mature']) && !empty($this->current_item['mature']);
	}

	public function hidingMature()
	{
		global $options;

		return empty($options['lgal_show_mature']) && (empty($_SESSION['lgal_mature']) || !in_array($this->current_item['id_item'], $_SESSION['lgal_mature']));
	}

	private function getIconUrl($file_ext)
	{
		global $settings;

		$mapping = null;
		// We do it this way because it's less irritating to maintain this way.
		// We have a much more readable source that gets expanded which is easier on the code later.
		// See also LevGal_Model_Upload::getAlternateExtensions for more.
		if ($mapping === null)
		{
			$types = array(
				'_audio' => array('flac', 'mp3', 'm4a', 'oga', 'ogg', 'wav'),
				'_binary' => array('bin', 'dll', 'exe'),
				'_font' => array('otf', 'ttf'),
				'_image' => array('gif', 'iff', 'jpeg', 'jpg', 'lbm', 'mng', 'png', 'psd', 'tiff'),
				'_video' => array('ogv', 'm4v', 'mp4', 'mov', 'qt', 'mqv', 'webm'),
				'doc' => array(
					'doc', 'dot', 'docm', 'docx', 'dotm', 'dotx', // MS Word
					'fodt', 'odt', // OpenDocument / LibreOffice
					'stw', 'sxg', 'sxw', // StarOffice/OpenOffice Writer
				),
				'html' => array('htm', 'html', 'mhtm', 'mhtml'),
				'pdf' => array('pdf'),
				'ppt' => array(
					'pot', 'potm', 'potx', 'ppam', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx', 'sldm', 'sldx', // MS Powerpoint
					'fodp', 'odp', // OpenDocument / LibreOffice
					'sti', 'sxi', // StarOffice/OpenOffice Impress
				),
				'txt' => array('txt'),
				'xls' => array(
					'xla', 'xlam', 'xll', 'xlm', 'xls', 'xlw', 'xlsb', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx', // MS Excel
					'fods', 'ods', // OpenDocument / LibreOffice
					'stc', 'sxc', // StarOffice/OpenOffice Calc
				),
				'xml' => array('xml'),
				'zip' => array('7z', 'bz2', 'dmg', 'gz', 'lz', 'lzma', 'rar', 'sit', 'tar', 'tbz2', 'tgz', 'z', 'zip'),
			);
			foreach ($types as $thumb => $exts)
			{
				foreach ($exts as $ext)
				{
					$mapping[$ext] = $thumb;
				}
			}
		}

		$icon_url = $settings['default_theme_url'] . '/levgal_res/icons';
		$file_ext = strtolower($file_ext);

		return $icon_url . '/' . ($mapping[$file_ext] ?? 'unknown') . '.png';
	}

	public function loadOwnerData()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		if (!empty($this->current_item['id_member']))
		{
			$loaded = loadMemberData($this->current_item['id_member']);
			if (!empty($loaded))
			{
				loadMemberContext($this->current_item['id_member']);
			}
		}

		return !empty($loaded) ? (int) $this->current_item['id_member'] : $this->current_item['poster_name'];
	}

	public function getItemType()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		$mime_type = !empty($this->current_item['mime_type']) ? $this->current_item['mime_type'] : 'application/octet-stream';
		list ($mime_class) = explode('/', $mime_type);
		if ($mime_class === 'image' && !empty($this->current_item['width']) && !empty($this->current_item['height']))
		{
			return 'image';
		}

		if ($mime_class === 'audio')
		{
			return 'audio';
		}

		if ($mime_class === 'video')
		{
			return 'video';
		}

		// Not *real* mime type of course. But we don't have a file for those.
		if ($mime_class === 'external')
		{
			return 'external';
		}

		// Documents and archives have a whole bunch of this stuff. Let's deal with the ones that are quite straightforward.
		switch ($this->current_item['mime_type'])
		{
			case 'application/pdf':
			case 'text/plain':
			case 'text/html':
			case 'application/x-mimearchive':
			case 'text/xml':
				return 'document';
			case 'application/zip':
			case 'application/x-rar-compressed':
			case 'application/x-gzip':
			case 'application/x-bzip2':
			case 'application/x-7z-compressed':
			case 'application/x-apple-diskimage':
			case 'application/x-stuffit':
			case 'application/x-lzip':
				return 'archive';
		}

		// Naturally, MSOffice couldn't be simple. As per LevGal_Model_Mime_Extension, there's a bunch of suffixes we don't care about.
		// Open Document Format is at least a bit more sane about this for a single container MIME type as well as the old Sun vendor types.
		$mime_type_prefixes = array(
			'application/vnd.ms-word',
			'application/vnd.ms-excel',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument',
			'application/vnd.sun.xml',
			'application/vnd.oasis.opendocument',
		);
		foreach ($mime_type_prefixes as $prefix)
		{
			if (strpos($this->current_item['mime_type'], $prefix) === 0)
			{
				return 'document';
			}
		}

		return 'generic';
	}

	public function getItemParticulars()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		if (!empty($this->current_item['meta']))
		{
			$metaModel = new LevGal_Model_Metadata_Display($this->current_item['meta']);
		}

		$type = $this->getItemType();
		if ($type === 'image')
		{
			// If it's got a width + height, it's probably an image
			return array(
				// Specifics that might be useful
				'width' => $this->current_item['width'],
				'width_format' => comma_format($this->current_item['width']),
				'height' => $this->current_item['height'],
				'height_format' => comma_format($this->current_item['height']),
				// Things we definitely need
				'display_string' => 'lgal_picture_size',
				'display_value' => $this->current_item['width'] . ' &times; ' . $this->current_item['height'],
				'display_template' => 'picture',
				'display_size' => $this->getDisplayFilesize(),
				'needs_lightbox' => $this->current_item['width'] > 500 || $this->current_item['height'] > 500,
				'urls' => $this->getItemURLs(),
				'meta' => !empty($metaModel) ? $metaModel->getExifInfo() : array(),
			);
		}

		if ($type === 'audio')
		{
			return array(
				'display_template' => 'audio',
				'display_size' => $this->getDisplayFilesize(),
				'urls' => $this->getItemURLs(),
				'meta' => !empty($metaModel) ? $metaModel->getAudioInfo() : array(),
			);
		}

		if ($type === 'video')
		{
			return array(
				'display_template' => 'video',
				'display_size' => $this->getDisplayFilesize(),
				'urls' => $this->getItemURLs(),
				'meta' => !empty($metaModel) ? $metaModel->getVideoInfo() : array(),
			);
		}

		if ($type === 'external')
		{
			$external = new LevGal_Model_External($this->current_item['meta']);
			$array = array(
				'urls' => $this->getItemURLs(),
			);

			return array_merge($array, $external->getDisplayProperties());
		}

		return array(
			'display_template' => 'generic',
			'display_size' => $this->getDisplayFilesize(),
			'urls' => $this->getItemURLs(),
		);
	}

	public function getDisplayFilesize()
	{
		global $txt;

		if (empty($this->current_item))
		{
			return $txt['not_applicable'];
		}

		return LevGal_Helper_Format::filesize($this->current_item['filesize']);
	}

	public function getLikes()
	{
		if (empty($this->current_item))
		{
			return array();
		}

		$likesModel = new LevGal_Model_Like();

		return $likesModel->getLikesByItem($this->current_item['id_item']);
	}

	public function likeItem()
	{
		$likeModel = new LevGal_Model_Like();
		$likeModel->likeItem($this->current_item['id_item']);
	}

	public function unlikeItem()
	{
		$likeModel = new LevGal_Model_Like();
		$likeModel->unlikeItem($this->current_item['id_item']);
	}

	public function albumLockedForComments()
	{
		$this->getParentAlbum();

		return $this->current_album->isLockedForComments();
	}

	public function getCommentState()
	{
		// If we're not loaded or we don't have any information, assume the worst.
		if (empty($this->current_item) || !isset($this->current_item['comment_state']))
		{
			return 'disabled';
		}

		// If the current album is set to locked, just exit with nothing new.
		$this->getParentAlbum();
		if ($this->current_album->isLockedForComments())
		{
			return 'no_new';
		}

		switch ($this->current_item['comment_state'])
		{
			case 0:
				return 'enabled';
			case 1:
				return 'no_new';
			case 2:
			default:
				return 'disabled';
		}
	}

	public function canReceiveComments()
	{
		// First up, comment state. If no-new-comments or comments outright disabled, no regardless.
		$comments = $this->getCommentState();
		if ($comments === 'no_new' || $comments === 'disabled')
		{
			return 'no';
		}

		// If managers, they're good.
		if (allowedTo('lgal_manage'))
		{
			return 'yes';
		}

		// They can comment. Is that with or without approval?
		if (allowedTo('lgal_comment'))
		{
			return (allowedTo(array('lgal_approve_comment', 'lgal_comment_appr'))) ? 'yes' : 'approval';
		}

		return 'no';
	}

	public function getCountComments()
	{
		global $modSettings, $user_info;

		$db = database();

		// For other uses we reuse the pre-stored counts. But this time around, we need actual amounts.
		// If you're an admin or manager, or you can approve comments, or it's your item (+ you get such approval)
		// we can use the full count easily.
		if (allowedTo(array('lgal_manage', 'lgal_approve_comment')) || ($this->isOwnedByUser() && !empty($modSettings['lgal_selfmod_approve_comment'])))
		{
			return $this->current_item['num_comments'] + $this->current_item['num_unapproved_comments'];
		}

		// Guests on the other hand get it simple: they will only ever see the number of approved comments.
		// Unless they made some comments... which we capture in session.
		if ($user_info['is_guest'] && empty($_SESSION['lgal_comments']))
		{
			return $this->current_item['num_comments'];
		}

		$comments = !empty($_SESSION['lgal_comments']) ? $_SESSION['lgal_comments'] : array();

		$request = $db->query('', '
			SELECT COUNT(id_comment)
			FROM {db_prefix}lgal_comments
			WHERE id_item = {int:id_item}
				AND (approved = {int:approved}' . (!empty($comments) ? '
				OR id_comment IN ({array_int:comments})' : '') . '
				OR id_author = {int:user_id})',
			array(
				'id_item' => $this->current_item['id_item'],
				'approved' => 1,
				'comments' => $comments,
				'user_id' => $user_info['id'],
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function getComments($start, $limit)
	{
		global $user_info, $memberContext, $scripturl, $context, $txt, $modSettings;

		$db = database();

		// No item, no comments
		if (empty($this->current_item))
		{
			return array();
		}

		$permissions = array('lgal_approve_comment', 'lgal_edit_comment_own', 'lgal_edit_comment_any', 'lgal_delete_comment_own', 'lgal_delete_comment_any');
		foreach ($permissions as $permission)
		{
			$perm_cache[$permission] = allowedTo(array('lgal_manage', $permission));
		}
		foreach (array('approve_comment', 'edit_comment', 'delete_comment') as $perm)
		{
			$perm_cache['lgal_selfmod_' . $perm] = !empty($modSettings['lgal_selfmod_' . $perm]);
		}

		$comments = array();
		$session_comments = !empty($_SESSION['lgal_comments']) ? $_SESSION['lgal_comments'] : array();
		$clauses[] = 'lc.id_item = {int:item}';
		// Guests see only approved items, period.
		if ($user_info['is_guest'])
		{
			$clauses[] = empty($session_comments) ? '(approved = {int:approved})' : '(approved = {int:approved} OR id_comment IN ({array_int:session_comments}))';
		}
		// People who aren't managers/approvers or specifically privileged... can only see their own.
		elseif (!allowedTo(array('lgal_manage', 'lgal_approve_comment')) && (!$this->isOwnedByUser() || empty($modSettings['lgal_selfmod_approve_comment'])))
		{
			$clauses[] = '(approved = {int:approved} OR id_author = {int:user_id})';
		}

		$request = $db->query('', '
			SELECT lc.id_comment, lc.id_author, IFNULL(mem.real_name, lc.author_name) AS author_name,
				lc.author_email, lc.author_ip, lc.comment, lc.approved, lc.time_added, lc.modified_name, lc.modified_time
			FROM {db_prefix}lgal_comments AS lc
				LEFT JOIN {db_prefix}members AS mem ON (lc.id_author = mem.id_member)
			WHERE ' . implode(' AND ', $clauses) . '
			ORDER BY id_comment DESC
			LIMIT {int:start}, {int:limit}',
			array(
				'item' => $this->current_item['id_item'],
				'approved' => 1,
				'user_id' => $user_info['id'],
				'session_comments' => $session_comments,
				'start' => $start,
				'limit' => $limit,
			)
		);
		$members = array();
		$parser = ParserWrapper::instance();
		while ($row = $db->fetch_assoc($request))
		{
			$id_comment = array_shift($row);
			// Comment unapproved = visible to admins/managers, people with approval, item owners who can moderate, and lastly themselves.
			if (empty($row['approved']))
			{
				// Guests will never see unapproved comments except ones in their session. Failing that, anyone who is a manager/admin or approver will have approval permission.
				if (($user_info['is_guest'] && !in_array($id_comment, $session_comments)) || (!$perm_cache['lgal_approve_comment'] && (!$perm_cache['lgal_selfmod_approve_comment'] || !$this->isOwnedByUser()) && ($row['id_author'] != $user_info['id'])))
				{
					continue;
				}
			}

			censor($row['comment']);
			if (!empty($row['id_author']))
			{
				$members[] = $row['id_author'];
			}
			$comments[$id_comment] = array(
				'id_member' => $row['id_author'],
				'author_name' => $row['author_name'],
				'author_link' => $row['author_name'],
				'author_email' => $row['author_email'],
				'author_ip' => $row['author_ip'],
				'modified_name' => $row['modified_name'],
				'modified_time' => $row['modified_time'],
				'modified_time_format' => LevGal_Helper_Format::time($row['modified_time']),
				'comment' => $parser->parseMessage($row['comment'], true),
				'approved' => !empty($row['approved']),
				'time_added' => $row['time_added'],
				'time_added_format' => LevGal_Helper_Format::time($row['time_added']),
				'options' => array(),
			);
			// Was this one reported before? If so, prune from session.
			if (isset($_SESSION['lgal_rep']['c' . $id_comment]))
			{
				$comments[$id_comment]['reported'] = true;
				unset ($_SESSION['lgal_rep']['c' . $id_comment]);
				if (empty($_SESSION['lgal_rep']))
				{
					unset ($_SESSION['lgal_rep']);
				}
			}

			// So, now we need to assemble the buttons.
			if (!$user_info['is_guest'])
			{
				$comments[$id_comment]['options']['flag'] = array('title' => $txt['levgal_comment_flag'], 'url' => $scripturl . '?media/comment/' . $id_comment . '/flag/');
			}
			if (empty($row['approved']) && ($perm_cache['lgal_approve_comment'] || ($perm_cache['lgal_selfmod_approve_comment'] && $this->isOwnedByUser())))
			{
				$comments[$id_comment]['options']['approve'] = array('title' => $txt['levgal_comment_approve'], 'url' => $scripturl . '?media/comment/' . $id_comment . '/approve/' . $context['session_var'] . '=' . $context['session_id'] . '/');
			}
			if ($perm_cache['lgal_edit_comment_any'] || ($row['id_author'] == $user_info['id'] && ($perm_cache['lgal_edit_comment_own']) || ($perm_cache['lgal_selfmod_edit_comment'] && $this->isOwnedByUser())))
			{
				$comments[$id_comment]['options']['edit'] = array('title' => $txt['levgal_comment_edit'], 'url' => $scripturl . '?media/comment/' . $id_comment . '/edit/');
			}
			if ($perm_cache['lgal_delete_comment_any'] || ($row['id_author'] == $user_info['id'] && ($perm_cache['lgal_delete_comment_own'] || ($perm_cache['lgal_selfmod_delete_comment'] && $this->isOwnedByUser()))))
			{
				$comments[$id_comment]['options']['delete'] = array('title' => $txt['levgal_comment_delete'], 'url' => $scripturl . '?media/comment/' . $id_comment . '/delete/' . $context['session_var'] . '=' . $context['session_id'] . '/');
			}
		}

		call_integration_hook('integrate_lgal_comments', array(&$comments));

		if (!empty($members))
		{
			$members = loadMemberData($members);
			foreach ($members as $member)
				{
					loadMemberContext($member);
				}

			// And splice in the member details.
			foreach ($comments as $id_comment => $comment)
			{
				if (isset($memberContext[$comment['id_member']]))
				{
					$comments[$id_comment]['author_name'] = $memberContext[$comment['id_member']]['name'];
					$comments[$id_comment]['author_link'] = $memberContext[$comment['id_member']]['link'];
					$comments[$id_comment]['avatar'] = $memberContext[$comment['id_member']]['avatar']['image'];
				}
			}
		}

		return $comments;
	}

	public function addedComment($wasApproved)
	{
		// So something has added a comment to this item.
		$db = database();

		if (empty($this->current_item))
		{
			return false;
		}

		// So, update the item.
		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET {raw:column} = {raw:column} + 1
			WHERE id_item = {int:item}',
			array(
				'column' => !empty($wasApproved) ? 'num_comments' : 'num_unapproved_comments',
				'item' => $this->current_item['id_item'],
			)
		);

		$this->increaseCommentCount();

		// And the parent album.
		$this->getParentAlbum();

		return $this->current_album->addedComment($wasApproved);
	}

	public function notifyComments($comment_id, $comment_obj)
	{
		global $modSettings;

		// So, who wants notifications and who is going to *get* notifications?
		$album = $this->getParentAlbum();
		$comment = $comment_obj->getCommentById($comment_id);

		$notifyModel = new LevGal_Model_Notify();
		$members = $notifyModel->getNotifyForItem($this->current_item['id_item']);

		if ($comment['approved'])
		{
			// If it's approved, it's easy, anyone that can see the album is on the list of possible candidates.
			$members = $this->current_album->usersCanSeeAlbum($members);
		}
		else
		{
			// If not, it gets a lot more complex. Managers, approvers, and item owners if the relevant option is set can see this one.
			$groupModel = new LevGal_Model_Group();
			$groups = $groupModel->allowedTo('lgal_manage');
			$groups = array_merge($groups, $groupModel->allowedTo('lgal_approve_comment'));

			// If it's a group owner, we can bolt that on easily enough.
			if (!empty($modSettings['lgal_selfmod_approve_comment']) && !empty($album['owner_cache']['group']))
			{
				$groups = array_merge($groups, $album['owner_cache']['group']);
			}

			// First we just tackle that little lot. We'll worry about the owner+approver combination in a moment.
			$notifying = $groupModel->matchUsersInGroups($members, $groups);

			if (!empty($modSettings['lgal_selfmod_approve_comment']) && !empty($album['owner_cache']['member']))
			{
				$notifying = array_merge($notifying, array_intersect($members, $album['owner_cache']['member']));
			}
			$members = array_unique($notifying);
		}

		if (!empty($members))
		{
			$email = new LevGal_Helper_Email('newcomment');
			$email->addReplacement('ITEMNAME', $this->current_item['item_name']);
			$email->addReplacement('POSTERNAME', $comment['author_name']);
			$email->addReplacement('COMMENTLINK', $comment_obj->getCommentURL());
			$email->addReplacement('UNSUBSCRIBELINK', $this->current_item['item_url'] . 'notify/');

			// Now to get the members.
			$email->getMemberDetails($members);
			$email->sendEmails();
		}
	}

	public function approvedComment()
	{
		// So something has approved a comment to this item.
		$db = database();

		if (empty($this->current_item))
		{
			return false;
		}

		// So, update the item.
		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET num_comments = num_comments + 1,
				num_unapproved_comments = num_unapproved_comments - 1
			WHERE id_item = {int:item}',
			array(
				'item' => $this->current_item['id_item'],
			)
		);

		$this->increaseCommentCount();

		// And the parent album.
		$this->getParentAlbum();

		return $this->current_album->approvedComment();
	}

	private function increaseCommentCount()
	{
		global $modSettings;

		// And the master stats.
		updateSettings(array('lgal_total_comments' => !empty($modSettings['lgal_total_comments']) ? true : 1), !empty($modSettings['lgal_total_comments']));
	}

	public function deletedComment($wasApproved)
	{
		$db = database();

		if (empty($this->current_item))
		{
			return false;
		}

		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET {raw:column} = {raw:column} - 1
			WHERE id_item = {int:item}',
			array(
				'column' => !empty($wasApproved) ? 'num_comments' : 'num_unapproved_comments',
				'item' => $this->current_item['id_item'],
			)
		);

		$this->decreaseCommentCount();

		// And the parent album.
		$this->getParentAlbum();

		return $this->current_album->deletedComment($wasApproved);
	}

	private function decreaseCommentCount()
	{
		global $modSettings;

		// And the master stats.
		updateSettings(array('lgal_total_comments' => !empty($modSettings['lgal_total_comments']) ? false : 0), !empty($modSettings['lgal_total_comments']));
	}

	public function markSeen($force = false)
	{
		global $user_info, $user_settings;

		$db = database();

		$time = time();
		if ($force || !isset($_SESSION['lgal_seen'][$this->current_item['id_item']]) || $time - $_SESSION['lgal_seen'][$this->current_item['id_item']] > self::SEEN_THRESHOLD)
		{
			$db->insert('replace',
				'{db_prefix}lgal_log_seen',
				array('id_item' => 'int', 'id_member' => 'int', 'view_time' => 'int'),
				array($this->current_item['id_item'], $user_info['id'], $time),
				array('id_item', 'id_member')
			);
			$_SESSION['lgal_seen'][$this->current_item['id_item']] = $time;

			// Now we need to mark that this person has seen it. Since we don't collate the actual amount of unseen normally, we have to guess.
			// Only flag for recounting if we're not already flagged and if we think there are unseen items.
			if (!empty($user_settings['lgal_unseen']) && empty($user_settings['lgal_new']))
			{
				$unseenModel = new LevGal_Model_Unseen();
				$unseenModel->markForRecount($user_info['id']);
			}
		}

		// Quick prune.
		if (!empty($_SESSION['lgal_seen']))
		{
			foreach ($_SESSION['lgal_seen'] as $item => $timestamp)
			{
				if ($time - $timestamp > self::SEEN_THRESHOLD)
				{
					unset ($_SESSION['lgal_seen'][$item]);
				}
			}
		}
	}

	public function increaseViewCount()
	{
		$db = database();

		if (empty($this->current_item))
		{
			return false;
		}

		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET num_views = num_views + 1
			WHERE id_item = {int:item}',
			array(
				'item' => $this->current_item['id_item'],
			)
		);

		return true;
	}

	public function getMetaOg()
	{
		if (empty($this->current_item))
		{
			return array();
		}

		$item_urls = $this->getItemURLs();

		$meta_og = array(
			'title' => $this->current_item['item_name'],
			'type' => 'article',
			'url' => $this->current_item['item_url'],
			'image' => $item_urls['thumb'],
		);

		if (strpos($this->current_item['mime_type'], 'image') === 0 && !empty($this->current_item['width']) && !empty($this->current_item['height']))
		{
			$meta_og['image:width'] = $this->current_item['width'];
			$meta_og['image:height'] = $this->current_item['height'];
			$meta_og['image:type'] = $this->current_item['mime_type'];
		}

		return $meta_og;
	}

	public function deleteItem()
	{
		if (empty($this->current_item))
		{
			return false;
		}

		// Call the big scary delete-items routine just for this one. While there's some optimisation that could be had,
		// the maintenance benefit of 'everything in one place' wins here. Just don't update the album there, we can do that.
		$itemList = new LevGal_Model_ItemList();
		$itemList->deleteItemsByIds($this->current_item['id_item'], false);

		// Then, notify the album with whether the item was approved or not, and the number of comments to adjust by.
		$this->getParentAlbum();
		$this->current_album->deletedItem($this->current_item['approved'], $this->current_item['num_comments'], $this->current_item['num_unapproved_comments']);

		// And, log this in the event log.
		LevGal_Model_ModLog::logEvent('delete_item', array('item_name' => $this->current_item['item_name']));

		// Lastly, nuke the item itself.
		$this->current_item = false;
	}

	public function createItem($item_info)
	{
		global $modSettings;
		$time_added = $time_updated = time();
		$db = database();

		$db->insert('insert',
			'{db_prefix}lgal_items',
			array('id_album' => 'int', 'id_member' => 'int', 'poster_name' => 'string', 'item_name' => 'string', 'item_slug' => 'string',
				  'filename' => 'string', 'filehash' => 'string', 'extension' => 'string', 'mime_type' => 'string',
				  'time_added' => 'int', 'time_updated' => 'int', 'description' => 'string', 'approved' => 'int',
				  'comment_state' => 'int', 'filesize' => 'int', 'width' => 'int', 'height' => 'int', 'mature' => 'int',
				  'num_views' => 'int', 'num_comments' => 'int', 'num_unapproved_comments' => 'int', 'meta' => 'string',
			),
			array(
				$item_info['id_album'], $item_info['poster_info']['id'], $item_info['poster_info']['name'], $item_info['item_name'], $item_info['item_slug'],
				$item_info['filename'], $item_info['filehash'] ?? '', $item_info['extension'] ?? '', $item_info['mime_type'] ?? '',
				$time_added, $time_updated, $item_info['description'], $item_info['approved'] ? 1 : 0,
				$item_info['comment_state'] ?? 0, $item_info['filesize'], 0, 0, !empty($item_info['mature']) ? 1 : 0,
				0, 0, 0, !empty($item_info['meta']) && is_array($item_info['meta']) ? serialize($item_info['meta']) : '',
			),
			array('id_item')
		);
		$id = $db->insert_id('{db_prefix}lgal_albums');

		if ($id !== false)
		{
			// So get the album because we need to notify the album itself.
			$this->getItemInfoById($id);
			$this->getParentAlbum();
			$this->current_album->addedItem($item_info['approved']);

			// Mark it read for the poster.
			if (!empty($item_info['poster_info']['id']))
			{
				$db->insert('replace',
					'{db_prefix}lgal_log_seen',
					array('id_item' => 'int', 'id_member' => 'int', 'view_time' => 'int'),
					array($id, $item_info['poster_info']['id'], time()),
					array('id_item', 'id_member')
				);
			}

			// Notify people about this.
			$unseenModel = new LevGal_Model_Unseen();
			$unseenModel->markForRecount();

			// Update the total items.
			if ($item_info['approved'])
			{
				updateSettings(array('lgal_total_items' => !empty($modSettings['lgal_total_items']) ? true : 1), !empty($modSettings['lgal_total_items']));
			}
			else
			{
				$this->updateUnapprovedCount();
			}

			// And add to the search index.
			$searchModel = new LevGal_Model_Search();
			$searchModel->createItemEntries(
				array(
					array(
						'id_item' => $id,
						'item_name' => $item_info['item_name'],
						'description' => $item_info['description'],
						'item_type' => $this->getItemType(),
					)
				)
			);

			// Send a hook to notify people.
			$item_info['id_item'] = $id;
			call_integration_hook('integrate_lgal_create_item', array($item_info));
		}

		return $id;
	}

	public function approveItem()
	{
		return $this->setApproveState(true);
	}

	public function unapproveItem()
	{
		return $this->setApproveState(false);
	}

	protected function setApproveState($state)
	{
		if (empty($this->current_item))
		{
			return false;
		}

		if ((!empty($state) && $this->isApproved()) || (empty($state) && !$this->isApproved()))
		{
			return false;
		}

		// First update the DB.
		$this->updateItem(array(
			'approved' => !empty($state) ? 1 : 0,
		));

		// Log the change
		LevGal_Model_ModLog::logEvent(!empty($state) ? 'approve_item' : 'unapprove_item', array('id_item' => $this->current_item['id_item']));

		// Update the stats.
		$this->updateUnapprovedCount();

		// Call a hook if we want to notify them about this kind of thing.
		call_integration_hook(!empty($state) ? 'integrate_lgal_approve_item' : 'integrate_lgal_unapprove_item', array($this->current_item['id_item']));

		// And notify the album.
		$this->getParentAlbum();
		$method = !empty($state) ? 'approvedItem' : 'unapprovedItem';

		return $this->current_album->$method();
	}

	public function updateItem($opts)
	{
		$db = database();

		// Regular strings
		foreach (array('item_name', 'item_slug', 'description', 'mime_type', 'poster_name', 'filename', 'extension') as $string)
		{
			if (isset($opts[$string]))
			{
				$criteria[] = $string . '= {string:' . $string . '}';
				$params[$string] = $opts[$string];
			}
		}

		// Special strings
		if (isset($opts['hash']))
		{
			$criteria[] = 'filehash = {string:filehash}';
			$params['filehash'] = $opts['hash'];
		}
		if (isset($opts['meta']) && is_array($opts['meta']))
		{
			$original_meta = $opts['meta'];
			$criteria[] = 'meta = {string:meta}';
			$params['meta'] = serialize($opts['meta']);
		}

		// Ints
		foreach (array('width', 'height', 'filesize', 'time_updated', 'comment_state') as $int)
		{
			if (isset($opts[$int]) && is_int($opts[$int]))
			{
				$criteria[] = $int . ' = {int:' . $int . '}';
				$params[$int] = $opts[$int];
			}
		}

		// Pretend bools
		foreach (array('approved', 'mature', 'editable', 'has_tag') as $bool)
		{
			if (isset($opts[$bool]))
			{
				$criteria[] = $bool . ' = {int:' . $bool . '}';
				$params[$bool] = !empty($opts[$bool]) ? 1 : 0;
			}
		}

		if (!empty($criteria))
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_items
				SET ' . implode(', ', $criteria) . '
				WHERE id_item = {int:id_item}',
				array_merge(array('id_item' => $this->current_item['id_item']), $params)
			);
			$this->current_item = array_merge($this->current_item, $params);
			if (isset($original_meta))
			{
				$this->current_item['meta'] = $original_meta;
			}
		}

		$searchModel = new LevGal_Model_Search();
		$searchModel->updateItemEntry($this->current_item['id_item'], $opts['item_name'] ?? null, $opts['description'] ?? null, isset($opts['mime_type']) ? $this->getItemType() : null);
	}

	public function getMetadata($updateModel = false)
	{
		$files = $this->getFilePaths();
		$meta = array();

		$metaModel = new LevGal_Model_Metadata($files['raw'], $this->current_item['filename']);
		$meta['meta'] = $metaModel->getMetadata();
		$raw_id3 = $meta['meta']['raw_id3'] ?? array();
		unset ($meta['meta']['raw_id3']);
		// If we got a thumbnail from the metadata, grab it, split it off and throw it back separately.
		if (isset($meta['meta']['thumbnail']))
		{
			$meta['thumbnail'] = $meta['meta']['thumbnail'];
			unset ($meta['meta']['thumbnail']);
		}

		// And maybe, we'll have a MIME type too!
		if (!empty($meta['meta']['mime_type']))
		{
			$meta['mime_type'] = $meta['meta']['mime_type'];
			// Sometimes, getID3 is actually a little *too* good.
			$exceptions = new LevGal_Model_Mime_Rules($raw_id3, $this->current_item['extension']);
			$new_mime_type = $exceptions->ApplyExceptions();
			if (!empty($new_mime_type))
			{
				$meta['mime_type'] = $new_mime_type;
			}
			unset ($meta['meta']['mime_type']);
		}

		if (empty($meta['mime_type']))
		{
			$meta['mime_type'] = 'application/octet-stream';
		}

		if ($updateModel)
		{
			if (!empty($meta['meta']['width']) && !empty($meta['meta']['height']))
			{
				$this->current_item['width'] = $meta['meta']['width'];
				$this->current_item['height'] = $meta['meta']['height'];
			}
			$this->current_item['mime_type'] = $meta['mime_type'];
			$this->current_item['meta'] = $meta['meta'];
		}

		return $meta;
	}

	public function getThumbnail()
	{
		$files = $this->getFilePaths();
		$thumbModel = new LevGal_Model_Thumbnail($files['raw']);

		return $thumbModel->createFromFile() && $thumbModel->generateThumbnails();
	}

	public function setThumbnail($thumbnail)
	{
		if (!isset($thumbnail['data'], $thumbnail['image_mime']))
		{
			return false;
		}

		$files = $this->getFilePaths();
		// If we're building this for an actual file, we'll have an actual file. Otherwise... we will have to fake it.
		if (!empty($files['raw']))
		{
			$thumbModel = new LevGal_Model_Thumbnail($files['raw']);
		}
		elseif (!empty($files['fake_raw']))
		{
			$this->makePath($files['filehash']); // If this is the case, we may not *have* a path.
			$thumbModel = new LevGal_Model_Thumbnail($files['fake_raw']);
		}

		return $thumbModel->createFromString($thumbnail['data'], $thumbnail['image_mime']) && $thumbModel->generateThumbnails();
	}

	public function fixOrientation($orientation)
	{
		$files = $this->getFilePaths();
		$image = new LevGal_Helper_Image();
		if ($image->loadImageFromFile($files['raw']))
		{
			if ($image->fixOrientation($orientation))
			{
				$image->saveImageToFile($files['raw']);
			}

			// It might have changed!
			$result = $image->getImageSize();
			$result[] = filesize($files['raw']);

			return $result;
		}

		return false;
	}

	public function getPreviousNext()
	{
		global $scripturl;
		$can_see_all = $this->current_album->canSeeAllItems();
		$items = $this->getItemListByAlbum($this->current_item['id_album'], $can_see_all);
		if (empty($items))
		{
			return array();
		}

		$keys = array_keys($items);
		$match = array_search($this->current_item['id_item'], $keys);
		if ($match === false)
		{
			return array();
		}

		$return = array();
		// Did we match on something that wasn't the very first item in the array?
		if ($match > 0)
		{
			$return['previous'] = $items[$keys[$match - 1]];
			$return['previous']['id_item'] = $keys[$match - 1];
		}
		// What about before the end of the array?
		if ($match < count($keys) - 1)
		{
			$return['next'] = $items[$keys[$match + 1]];
			$return['next']['id_item'] = $keys[$match + 1];
		}

		// Now build the item URLs.
		foreach ($return as $key => $item_entry)
		{
			$return[$key]['item_url'] = $scripturl . '?media/item/' . (!empty($item_entry['item_slug']) ? $item_entry['item_slug'] . '.' . $item_entry['id_item'] : $item_entry['id_item']) . '/';
		}

		return $return;
	}

	public function getItemListByAlbum($album_id, $can_see_all = true, $descending = true)
	{
		global $user_info;

		$db = database();

		$cache_key = 'lgal_itemlist_a' . $album_id . ($can_see_all ? '_all' : '') . ($user_info['is_guest'] ? '_guest' : '');
		$cache_ttl = 120;
		if (($items = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$criteria = 'id_album = {int:album_id}';
			if (!$can_see_all)
			{
				if ($user_info['is_guest'])
				{
					if (!empty($_SESSION['lgal_items']))
					{
						$criteria .= '
					AND (approved = 1 OR id_item IN ({array_int:my_items}))';
					}
					else
					{
						$criteria .= '
					AND (approved = 1)';
					}
				}
				else
				{
					$criteria .= '
					AND (approved = 1 OR id_member = {int:current_member})';
				}
			}

			$request = $db->query('', '
				SELECT id_item, item_name, item_slug
				FROM {db_prefix}lgal_items
				WHERE ' . $criteria . '
				ORDER BY {raw:order}',
				array(
					'order' => $descending ? 'id_item DESC' : 'id_item',
					'album_id' => $album_id,
					'current_member' => $user_info['id'],
					'my_items' => !empty($_SESSION['lgal_items']) ? $_SESSION['lgal_items'] : array(),
				)
			);
			$items = array();
			while ($row = $db->fetch_assoc($request))
			{
				$id_item = array_shift($row);
				$items[$id_item] = $row;
			}
			$db->free_result($request);
			cache_put_data($cache_key, $items, $cache_ttl);
		}

		return $items;
	}

	public function updateUnapprovedCount()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				COUNT(*)
			FROM {db_prefix}lgal_items
			WHERE approved = {int:not_approved}',
			array(
				'not_approved' => 0,
			)
		);
		list ($unapproved) = $db->fetch_row($request);
		$db->free_result($request);

		// Also, if we have a cache locally in session, dump it.
		unset ($_SESSION['lgal_ui']);

		updateSettings(array('lgal_unapproved_items' => $unapproved));
	}

	public function getCustomFields()
	{
		global $txt;

		// Maybe we already have it cached locally?
		if (!empty($this->current_item['custom_fields']))
		{
			return $this->current_item['custom_fields'];
		}

		$this->current_item['custom_fields'] = array(
			'main' => array(),
			'meta' => array(),
		);

		if (empty($this->current_item) || empty($this->current_item['has_custom']))
		{
			return $this->current_item['custom_fields'];
		}

		$cfModel = LevGal_Bootstrap::getModel('LevGal_Model_Custom');
		$possible_fields = $cfModel->getCustomFieldsByAlbum($this->current_item['id_album']);
		$placements = array(
			0 => 'meta',
			1 => 'main',
			2 => 'desc',
		);
		if (!empty($possible_fields))
		{
			$custom_fields = $cfModel->getCustomFieldValues($this->current_item['id_item'], $this->current_item['id_album']);
			foreach ($possible_fields as $id_field => $field)
			{
				if (isset($custom_fields[$id_field]))
				{
					if ($field['field_type'] === 'checkbox')
					{
						$custom_fields[$id_field] = !empty($custom_fields[$id_field]) ? $txt['yes'] : $txt['no'];
					}
					$placement = $placements[$field['placement']];
					$this->current_item['custom_fields'][$placement][$id_field] = $field;
					$this->current_item['custom_fields'][$placement][$id_field]['value'] = $custom_fields[$id_field]['display'];
				}
			}
		}

		return $this->current_item['custom_fields'];
	}

	public function getTags()
	{
		// Maybe we already have it cached locally?
		if (!empty($this->current_item['tags']))
		{
			return $this->current_item['tags'];
		}

		$this->current_item['tags'] = array();
		if (empty($this->current_item) || empty($this->current_item['has_tag']))
		{
			return $this->current_item['tags'];
		}

		$tagModel = LevGal_Bootstrap::getModel('LevGal_Model_Tag');
		$this->current_item['tags'] = $tagModel->getTagsByItemId($this->current_item['id_item']);

		return $this->current_item['tags'];
	}

	public function setAlbumThumbnail()
	{
		global $settings;

		$this->getParentAlbum();

		// So, figuring out the item thumbnail
		$urls = $this->getItemURLs();
		if (!empty($urls['generic']['thumb']))
		{
			$thumbnail = str_replace($settings['default_theme_url'] . '/levgal_res/icons/', '', $urls['thumb']);
			$this->current_album->setGenericThumbnail($thumbnail);
		}
		else
		{
			$files = $this->getFilePaths();
			if (substr($files['thumb'], -7) === 'png.dat' || $this->current_item['mime_type'] === 'image/png')
			{
				$format = 'png';
			}
			elseif (substr($files['thumb'], -7) === 'jpg.dat')
			{
				$format = 'jpg';
			}
			else
			{
				$format = $this->current_item['extension'];
			}
			$this->current_album->setThumbnailFromFile($files['thumb'], $format);
		}
	}

	public function getMoveDestinations()
	{
		global $context, $user_info;

		// So, get me some hierarchies.
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$hierarchies = $album_list->getAllHierarchies();

		// We don't necessarily want all hierarchies, though. If the user is not a special user, we only want the hierarchies they own.
		if (allowedTo(array('lgal_manage', 'lgal_edit_item_any')))
		{
			$result = $hierarchies;
		}
		else
		{
			$result = array(
				'site' => array(),
				'member' => array(),
				'group' => array(),
			);

			if (isset($hierarchies['member'][$context['user']['id']]))
			{
				$result['member'][$context['user']['id']] = $hierarchies['member'][$context['user']['id']];
			}

			if (!empty($hierarchies['group']))
			{
				foreach (array_keys($hierarchies['group']) as $group)
				{
					if (in_array($group, $user_info['groups']))
					{
						$result['group'][$group] = $hierarchies['group'][$group];
					}
				}
			}
		}

		// Now see how many albums there are, so we can check there is somewhere other than the current album to move it to.
		$album_count = array_keys($result['site']);
		foreach (array('member', 'group') as $type)
		{
			if (!empty($result[$type]))
			{
				foreach ($result[$type] as $entry)
				{
					if (!empty($entry['albums']))
					{
						$album_count = array_merge($album_count, array_keys($entry['albums']));
					}
				}
			}
		}
		$album_count = array_unique($album_count);

		return array($result, $album_count);
	}
}
