<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\Helper\Feed;
use Addons\Levertine\Source\Helper\Http;
use Addons\Levertine\Source\Helper\Richtext;
use Addons\Levertine\Source\Helper\Sanitiser;
use Addons\Levertine\Source\Helper\Verify;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Bookmark;
use Addons\Levertine\Source\Model\Comment;
use Addons\Levertine\Source\Model\Custom;
use Addons\Levertine\Source\Model\External;
use Addons\Levertine\Source\Model\Item as ItemModel;
use Addons\Levertine\Source\Model\ModLog;
use Addons\Levertine\Source\Model\Notify;
use Addons\Levertine\Source\Model\Report;
use Addons\Levertine\Source\Model\Tag;
use Addons\Levertine\Source\Model\Upload;
use ElkArte\Helper\DataValidator;
use ElkArte\Helper\Util;
use ElkArte\Languages\Txt;
use ElkArte\Mentions\Mentioning;
use ElkArte\User;
use function Addons\Levertine\Source\levgal_pageindex;

/**
 * This file provides the handling for items, site/?media/item/*.
 */
class Item extends LevGalAbstract
{
	/** @var bool */
	private $item_id;

	/** @var bool */
	private $item_slug;

	/** @var ItemModel */
	private $item_obj;

	public function __construct()
	{
		global $context;

		// This means we load useful resources.
		parent::__construct();

		// Attempt to get something useful.
		[$this->item_slug, $this->item_id] = $this->getSlugAndId();

		// Fetch some details.
		$this->item_obj = new ItemModel();
		$context['item_details'] = $this->item_obj->getItemInfoById($this->item_id);

		// Does the item even exist? Can they see it if it does?
		if (!$context['item_details'] || !$this->item_obj->isVisible())
		{
			Http::fatalError('error_no_item');
		}

		// Does the item slug provided match the provided slug? If not, run away.
		if ($context['item_details']['item_slug'] != $this->item_slug)
		{
			Http::hardRedirect($context['item_details']['item_url'] . (empty($_GET['sub']) ? '' : $_GET['sub'] . '/'));
		}
	}

	public function actionIndex()
	{
		global $context, $txt, $modSettings;

		if ($this->item_obj->isMature() && $this->item_obj->hidingMature())
		{
			return $this->actionMature();
		}

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_viewing_item'], $context['item_details']['item_name']);

		// Now the linktree. This is a bit complicated since we need the album details.
		$album_details = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$this->setTemplate('LevGal-Item', 'main_item_view');

		$context['item_owner'] = $this->item_obj->loadOwnerData();
		$context['item_owner_link'] = allowedTo('profile_view_any');
		$context['item_display'] = $this->item_obj->getItemParticulars();

		$session_key = 'i' . $context['item_details']['id_item'];
		if (isset($_SESSION['lgal_rep'][$session_key]))
		{
			$context['item_reported'] = true;
			unset ($_SESSION['lgal_rep']['i' . $context['item_details']['id_item']]);
			if (empty($_SESSION['lgal_rep']))
			{
				unset ($_SESSION['lgal_rep']);
			}
		}

		// Certain types have certain dependencies
		$this->loadDependencies();

		$meta_og = $this->item_obj->getMetaOg();
		if (!empty($meta_og))
		{
			foreach ($meta_og as $property => $value)
			{
				LevGalBootstrap::addHtmlHeader('
	<meta name="og:' . $property . '" content="' . $value . '" />');
			}
		}

		$context['canonical_url'] = $context['item_details']['item_url'];

		$context['can_comment'] = $this->item_obj->getCommentState();
		if ($context['can_comment'] !== 'disabled')
		{
			$this->fetchComments();
		}

		$context['item_actions'] = [];
		$context['item_actions']['actions']['album'] = [$txt['lgal_back_to_album'], $album_details['album_url'], 'tab' => true];
		if (!User::$info['is_guest'])
		{
			// Bookmarks
			$bookmark = new Bookmark();
			$action = $bookmark->isBookmarked($this->item_id) ? 'unbookmark' : 'bookmark';
			$context['item_actions']['actions'][$action] = [$txt['lgal_' . $action . '_item'], $item['url'] . $action . '/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'js' => 'onclick="return handleBookmark(this)"', 'tab' => true];

			// Notifications
			$notify = new Notify();
			if (!empty($notify->getSiteEnableNotifications()['lgcomment']))
			{
				$action = $notify->getNotifyItemStatus($this->item_id, User::$info['id']) ? 'unnotify' : 'notify';
				$context['item_actions']['actions'][$action] = [$txt['lgal_' . $action], $item['url'] . $action . '/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_' . $action . '_item_desc']];
			}

			// The Download button is only for members to try to curtail bandwidth shenanigans.
			if (!empty($context['item_display']['urls']['download']))
			{
				$context['item_actions']['actions']['download'] = [$txt['lgal_download_item'], $context['item_display']['urls']['download']];
			}

			// And flagging for moderation.
			$context['item_actions']['moderation']['flag'] = [$txt['lgal_flag_item_title'], $item['url'] . 'flag/'];
		}

		if (!$context['item_details']['approved'] && $this->item_obj->canChangeApproveStatus())
		{
			// We are not concerned with unapproval at this time.
			$context['item_actions']['moderation']['approveitem'] = [$txt['lgal_approve_item_title'], $item['url'] . 'approve/' . $context['session_var'] . '=' . $context['session_id'] . '/'];
		}

		if ($this->item_obj->canUseThumbnail())
		{
			$context['item_actions']['moderation']['setthumbnail'] = [$txt['lgal_set_thumbnail_title'], $item['url'] . 'setthumbnail/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_set_thumbnail_desc']];
		}

		if ($this->item_obj->isEditable())
		{
			$context['item_actions']['actions']['edititem'] = [$txt['lgal_edit_item_title'], $item['url'] . 'edit/','tab' => true, 'sidebar' => false];
			$context['item_actions']['moderation']['edititem'] = [$txt['lgal_edit_item_title'], $item['url'] . 'edit/'];
			$context['item_actions']['moderation']['moveitem'] = [$txt['lgal_move_item_title'], $item['url'] . 'move/'];
		}

		if (allowedTo(['lgal_manage', 'lgal_delete_item_any']) || (allowedTo('lgal_delete_item_own') && $this->item_obj->isOwnedByUser()))
		{
			$context['item_actions']['moderation']['deleteitem'] = [$txt['lgal_delete_item_title'], $item['url'] . 'delete/'];
		}

		$share = [
			'facebook' => 'https://www.facebook.com/sharer.php?u=' . $context['item_details']['item_url'],
			'twitter' => 'https://x.com/share?text=' . urlencode($context['item_details']['item_name']) . '&amp;url=' . urlencode($context['item_details']['item_url']),
			'tumblr' => 'https://www.tumblr.com/share/link?url=' . urlencode($context['item_details']['item_url']) . '&amp;name=' . urlencode($context['item_details']['item_name']),
			'reddit' => 'https://reddit.com/submit?url=' . urlencode($context['item_details']['item_url']),
			'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . urlencode($context['item_details']['item_url']) . '&amp;description=' . urlencode($context['item_details']['item_name']),
		];
		$sharing = empty($modSettings['lgal_social']) ? [] : explode(',', $modSettings['lgal_social']);
		$context['social_icons'] = [];
		foreach ($share as $id => $link)
		{
			if (in_array($id, $sharing, true))
			{
				$context['social_icons']['actions'][$id] = [$txt['lgal_share_' . $id] ?? $id, $link, true];
			}
		}

		// Get likes, if allowed to see profiles let's also linkify things.
		$this->getItemLikes();

		// The order follows how the album was sorted.
		[$orderBy, $orderDir] = explode('|', $album_details['sort'], 2);
		$sortMap = [
			'name' => 'item_name',
			'date' => 'time_added',
			'views' => 'num_views',
			'comments' => 'num_comments',
		];
		$orderBy = $sortMap[$orderBy] ?? 'id_item';

		$context['prev_next'] = $this->item_obj->getPreviousNext($orderBy, $orderDir);
		$context['item_display']['custom_fields'] = $this->item_obj->getCustomFields();
		$context['item_display']['tags'] = $this->item_obj->getTags();

		if (!User::$info['is_guest'])
		{
			$this->item_obj->markSeen();
			$this->markNotificationRead();
		}

		if (empty($context['browser']['possibly_robot']) && (!empty($modSettings['lgal_count_author_views']) || !$this->item_obj->isOwnedByUser()))
		{
			$this->item_obj->increaseViewCount();
		}
	}

	protected function getItemLikes()
	{
		global $context, $scripturl;

		// So, get the data, do linking of things if that's a thing and then set up some other stuff.
		$context['likes'] = $this->item_obj->getLikes();
		if (allowedTo('profile_view_any'))
		{
			foreach ($context['likes'] as $user_id => $user_name)
			{
				$context['likes'][$user_id] = '<a href="' . $scripturl . '?action=profile;u=' . $user_id . '">' . $user_name . '</a>';
			}
		}
		$context['allowed_like'] = !User::$info['is_guest'];
		$context['currently_liking'] = false;
		if (isset($context['likes'][User::$info['id']]))
		{
			$context['currently_liking'] = true;
			unset ($context['likes'][User::$info['id']]);
		}
	}

	protected function fetchComments()
	{
		global $context, $txt, $modSettings;

		$context['item_comments'] = [];
		$context['num_comments'] = 0;
		if (!empty($context['item_details']['num_comments']) || !empty($context['item_details']['num_unapproved_comments']))
		{
			$context['num_comments'] = $this->item_obj->getCountComments();
			$num_pages = ceil($context['num_comments'] / $modSettings['lgal_comments_per_page']);
			$context['this_page'] = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
			$context['item_comments'] = $this->item_obj->getComments(($context['this_page'] - 1) * $modSettings['lgal_comments_per_page'], $modSettings['lgal_comments_per_page']);

			if ($num_pages > 1)
			{
				$context['item_pageindex'] = levgal_pageindex($context['item_details']['item_url'], $context['this_page'], $num_pages);
			}
		}

		if (!empty($context['this_page']) && $context['this_page'] > 1)
		{
			$context['canonical_url'] .= 'page-' . $context['this_page'] . '/';
		}

		$context['display_comment_reply'] = $this->item_obj->canReceiveComments();
		if ($context['display_comment_reply'] !== 'no')
		{
			$context['comment_box'] = new Richtext('lgal_commentbox');
			$context['comment_box']->createEditor([
				'value' => $context['comment_box_value'] ?? '',
				'labels' => [
					'post_button' => $txt['levgal_add_comment'],
				],
			]);
			$context['form_url'] = $context['item_details']['item_url'] . 'comment/';

			if (User::$info['is_guest'])
			{
				$context['comment_user_name'] = $context['comment_user_name'] ?? $_SESSION['guest_name'] ?? '';
				$context['comment_user_email'] = $context['comment_user_email'] ?? $_SESSION['guest_email'] ?? '';
				if (empty($context['verification']))
				{
					$context['verification'] = new Verify('comment');
					$context['verification']->setupOnly();
				}
			}
		}

		if (!empty($modSettings['lgal_feed_enable_item']))
		{
			LevGalBootstrap::addHtmlHeader('
	<link rel="alternate" type="application/atom+xml" title="' . sprintf($txt['lgal_comments_for'], $context['item_details']['item_name']) . '" href="' . $context['item_details']['item_url'] . 'feed/" />');
		}
	}

	public function actionMature()
	{
		global $context, $txt, $modSettings;

		// Page title
		$context['page_title'] = sprintf($txt['lgal_mature_item'], $context['item_details']['item_name']);

		// Now the linktree. This is a bit complicated since we need the album details.
		$album_details = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();

		if (empty($modSettings['lgal_enable_mature']))
		{
			redirectexit($item['url']);
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$context['canonical_url'] = $item['url'] . 'mature/';

		$this->setTemplate('LevGal-Item', 'mature_item');
		$context['form_url'] = $item['url'] . 'mature/';

		[$orderBy, $orderDir] = explode('|', $album_details['sort'], 2);
		$sortMap = [
			'name' => 'item_name',
			'date' => 'time_added',
			'views' => 'num_views',
			'comments' => 'num_comments',
		];
		$orderBy = $sortMap[$orderBy] ?? 'id_item';
		$context['prev_next'] = $this->item_obj->getPreviousNext($orderBy, $orderDir);

		if (isset($_POST['yes']))
		{
			checkSession();
			$_SESSION['lgal_mature'][] = $this->item_id;
			redirectexit($item['url']);
		}

		if (isset($_POST['no']))
		{
			redirectexit($album_details['album_url']);
		}

		return true;
	}

	public function actionBookmark()
	{
		$this->handleBookmark(true);
	}

	public function actionUnbookmark()
	{
		$this->handleBookmark(false);
	}

	protected function handleBookmark($state)
	{
		global $context, $txt;

		is_not_guest();
		checkSession('get');
		$bookmark = new Bookmark();
		$method = empty($state) ? 'unsetBookmark' : 'setBookmark';
		$bookmark->$method($this->item_id);
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
		{
			$action = empty($state) ? 'bookmark' : 'unbookmark';
			Http::jsonResponse(['link' => '<a href="' . $context['item_details']['item_url'] . $action . '/' . $context['session_var'] . '=' . $context['session_id'] . '/" onclick="return handleBookmark(this)"><span class="lgalicon i-' . $action . ($action === 'unbookmark' ? ' colorize-red' : '') .'"></span>' . $txt['lgal_' . $action . '_item'] . '</a>'], 200);
		}
		else
		{
			redirectexit($context['item_details']['item_url']);
		}
	}

	public function actionLike()
	{
		global $context;

		if (!User::$info['is_guest'])
		{
			checkSession('get');

			$likes = $this->item_obj->getLikes();
			if (isset($likes[User::$info['id']]))
			{
				$this->item_obj->unlikeItem();
			}
			else
			{
				$this->item_obj->likeItem();
			}
		}

		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
		{
			$this->getItemLikes();
			theme()->getTemplates()->load('Levertine/LevGal-Item');
			$result = template_return_item_likers();
			Http::jsonResponse(['likes' => $result], 200);
		}
		else
		{
			redirectexit($context['item_details']['item_url']);
		}
	}

	public function actionFeed()
	{
		global $context, $txt, $scripturl, $modSettings;

		if (empty($modSettings['lgal_feed_enable_item']))
		{
			Http::fatalError('cannot_lgal_feed');
		}

		$album_details = $this->item_obj->getParentAlbum();

		$feed = new Feed();
		$feed->title = $context['item_details']['item_name'];
		$feed->subtitle = $context['item_details']['description'];
		$feed->alternateUrl = $context['item_details']['item_url'];
		$feed->selfUrl = $context['item_details']['item_url'] . 'feed/';

		$countComments = $this->item_obj->getCountComments();
		$items = 0;
		if (!empty($countComments))
		{
			$comments = $this->item_obj->getComments(0, $modSettings['lgal_feed_items_item']);
			foreach ($comments as $id_comment => $comment)
			{
				$entry = [
					'title' => sprintf($txt['lgal_comment_feed'], '#' . comma_format($countComments - $items), $context['item_details']['item_name']),
					'link' => $scripturl . '?media/comment/' . $id_comment . '/', // cheeky, I know
					'content' => $comment['comment'],
					'category' => [$album_details['album_name'], $album_details['album_url']],
					'author' => [$comment['author_name'], $comment['id_member']],
					'published' => $comment['time_added'],
					'updated' => empty($comment['modified_time']) ? $comment['time_added'] : $comment['modified_time'],
				];
				$feed->addEntry($entry);

				$items++;
			}
		}

		$feed->outputFeed();
	}

	public function actionComment()
	{
		global $context;

		$context['comment_errors'] = [];

		// Helpfully, this also includes our permissions check.
		$can_comment = $this->item_obj->canReceiveComments();
		if ($can_comment === 'no')
		{
			Http::fatalError('cannot_lgal_add_comment');
		}

		// Still in session?
		if (checkSession('post', '', false) !== '')
		{
			$context['comment_errors'][] = 'session_timeout';
		}

		// So, we can comment, and we already know whether we are going to be approving this one or not.
		$wysiwyg = new Richtext('lgal_commentbox');

		// The user needed to type something in and it needed to contain something useful.
		if ($wysiwyg->isEmpty() || !$wysiwyg->sanitizeContent())
		{
			$context['comment_errors'][] = 'no_comment';
		}

		// Guests have to fill out their verification like the good little people they are.
		if (User::$info['is_guest'])
		{
			$context['verification'] = new Verify('comment');
			$result = $context['verification']->setupAndTest();
			if ($result !== true)
			{
				Txt::load('Errors');
				$context['comment_errors'] = array_merge($context['comment_errors'], $result);
			}

			[$valid, $context['comment_user_name']] = Sanitiser::sanitiseUsernameFromPost('guest_username');
			if ($valid)
			{
				$_SESSION['guest_name'] = $context['comment_user_name'];
			}
			else
			{
				$context['comment_errors'][] = 'invalid_user';
			}

			[$valid, $context['comment_user_email']] = Sanitiser::sanitiseEmailFromPost('guest_email');
			if ($valid)
			{
				$_SESSION['guest_email'] = $context['comment_user_email'];
			}
			else
			{
				$context['comment_errors'][] = 'invalid_email';
			}
		}

		// Errors? Throw it back to the item view (and we'll use what we collected so far)
		if (!empty($context['comment_errors']))
		{
			$context['comment_box_value'] = $wysiwyg->getForForm();

			return $this->actionIndex();
		}

		// So we're all good. Time to create a model, I guess.
		$comment = new Comment();
		if ($context['user']['is_guest'])
		{
			$posterOptions = [
				'id' => 0,
				'name' => $context['comment_user_name'],
				'email' => $context['comment_user_email'],
				'ip' => User::$info['ip'],
			];
		}
		else
		{
			$posterOptions = [
				'id' => $context['user']['id'],
				'name' => $context['user']['name'],
				'email' => $context['user']['email'],
				'ip' => User::$info['ip'],
			];
		}
		$comment_id = $comment->createComment($context['item_details']['id_item'], $wysiwyg->getForDB(), $posterOptions, $can_comment);
		$this->item_obj->notifyComments($comment_id, $comment);
		redirectexit($comment->getCommentURL());
	}

	public function markNotificationRead()
	{
		global $modSettings;

		// Mark the comment as read if requested
		$nid = $this->_req->getQuery('lgread', 'intval');
		if ($nid !== null && ($this->item_obj->isOwnedByUser() || $this->item_obj->isVisible()))
		{
			checkSession('get');

			$mentions = new Mentioning(database(), User::$info['id'], new DataValidator(), $modSettings['enabled_mentions']);
			$mentions->markread($nid);
		}
	}

	public function actionApprove()
	{
		global $context;
		$this->handleApprove();
		redirectexit($context['item_details']['item_url']);
	}

	public function actionApprove_unapproved()
	{
		global $scripturl;
		$this->handleApprove();
		redirectexit($scripturl . '?media/moderate/unapproved_items/');
	}

	protected function handleApprove()
	{
		global $modSettings;

		if (!allowedTo('lgal_manage'))
		{
			if (empty($modSettings['lgal_selfmod_approve_item']) || !$this->item_obj->albumIsOwnedByUser())
			{
				Txt::load('Levertine/LevGal-Errors');
				isAllowedTo('lgal_approve_item');
			}
		}

		checkSession('get');

		$this->item_obj->approveItem();
	}

	public function actionMove()
	{
		global $context, $txt;

		// When it comes to moving items between albums, this is based off the edit item permission.
		if (!$this->item_obj->isEditable())
		{
			Http::fatalError('cannot_lgal_move_item');
		}

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_moving_item'], $context['item_details']['item_name']);

		// Now the linktree. This is a bit complicated since we need the album details.
		$album_details = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();

		$context['item_urls'] = $this->item_obj->getItemURLs();

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$this->addLinkTree($txt['lgal_move_item_title']);

		$this->setTemplate('LevGal-Item', 'move_item');

		$context['form_url'] = $item['url'] . 'move/';
		$context['canonical_url'] = $item['url'] . 'move/';

		[$context['hierarchies'], $album_count] = $this->item_obj->getMoveDestinations();
		if (count($album_count) < 2)
		{
			Http::fatalError('lgal_no_album_destination');
		}

		// Are we saving?
		if (isset($_POST['destalbum']))
		{
			checkSession();

			if (!in_array((int) $_POST['destalbum'], $album_count, true))
			{
				Http::fatalError('lgal_no_album_destination');
			}

			// If we're here at all, we already permission checked before we got here, and now we've session checked and validated input.
			$itemList = LevGalBootstrap::getModel('ItemList');
			$itemList->moveItemsToAlbum($this->item_id, $_POST['destalbum']);

			// And plonk it in the modlog.
			ModLog::logEvent('move_item', ['id_item' => $this->item_id, 'old_album' => $album_details['album_name'], 'id_album' => $_POST['destalbum']]);

			// And back to the item.
			redirectexit($item['url']);
		}
	}

	public function actionDelete()
	{
		global $context, $txt;

		// Perms checking is a bit hard here.
		if (!allowedTo('lgal_manage')
			&& !allowedTo('lgal_delete_item_any')
			&& (!allowedTo('lgal_delete_item_own') || !$this->item_obj->isOwnedByUser()))
		{
			Http::fatalError('cannot_lgal_delete_item');
		}

		// Page title, this level of link tree, canonical URL
		$context['page_title'] = sprintf($txt['lgal_deleting_item'], $context['item_details']['item_name']);

		// Now the linktree. This is a bit complicated since we need the album details.
		$album_details = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();

		$context['item_urls'] = $this->item_obj->getItemURLs();

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$this->addLinkTree($txt['lgal_delete_item_title']);

		$this->setTemplate('LevGal-Item', 'delete_item');

		$context['form_url'] = $item['url'] . 'delete/';
		$context['canonical_url'] = $item['url'] . 'delete/';

		if (isset($_POST['delete']))
		{
			checkSession();
			$this->item_obj->deleteItem();
			redirectexit($album_details['album_url']);
		}

		if (isset($_POST['cancel']))
		{
			// If we hit cancel, which is presumably the deal here, take us back to the item.
			redirectexit($context['item_details']['item_url']);
		}
	}

	public function actionDelete_unapproved()
	{
		global $scripturl;

		// Perms checking is a bit hard here.
		if (!allowedTo('lgal_manage')
			&& !allowedTo('lgal_delete_item_any')
			&& (!allowedTo('lgal_delete_item_own') || !$this->item_obj->isOwnedByUser()))
		{
			Http::fatalError('cannot_lgal_delete_item');
		}
		checkSession('get');

		$this->item_obj->deleteItem();

		redirectexit($scripturl . '?media/moderate/unapproved_items/');
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
		global $context, $txt;

		// First, guests can't do this whatever happens.
		is_not_guest();

		// Second, session check. Round 1: accepting via GET link.
		if (checkSession('get', '', false) === '')
		{
			$notify = new Notify();
			$method = $type === 'notify' ? 'setNotifyItem' : 'unsetNotifyItem';
			$notify->$method($this->item_id, $context['user']['id']);
			redirectexit($context['item_details']['item_url']);
		}
		// Round 2: accepting via POST form from media/item/blah.1/notify/
		elseif (checkSession('post', '', false) === '' && (!empty($_POST['notify_yes']) || !empty($_POST['notify_no'])))
		{
			$notify = new Notify();
			$method = empty($_POST['notify_yes']) ? 'unsetNotifyItem' : 'setNotifyItem';
			$notify->$method($this->item_id, $context['user']['id']);
			redirectexit($context['item_details']['item_url']);
		}
		// Round 3: sending them to the post form (e.g. from notification mail)
		else
		{
			$context['page_title'] = sprintf($txt['lgal_notify_item_title'], $context['item_details']['item_name']);
			$album_details = $this->item_obj->getParentAlbum();
			$item = $this->item_obj->getLinkTreeDetails();
			$context['item_urls'] = $this->item_obj->getItemURLs();

			$this->addLinkTree($txt['levgal'], '?media/');
			$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
			$this->addLinkTree($item['name'], $item['url']);

			$this->setTemplate('LevGal-Item', 'notify_item');
			$context['form_url'] = $item['url'] . 'notify/';
		}
	}

	public function actionFlag()
	{
		global $context, $txt;

		if (User::$info['is_guest'])
		{
			Http::fatalError('cannot_lgal_flag_item');
		}

		Txt::load('Levertine/LevGal-Moderation');

		// So now we're setting up for flagging.
		$album_details = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_details['album_name'], $album_details['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$this->addLinkTree($txt['levgal_flag_item'], $context['item_details']['item_url'] . 'flag/');

		$context['page_title'] = $txt['levgal_flag_item'];
		$context['canonical_url'] = $context['item_details']['item_url'] . 'flag/';

		$this->setTemplate('LevGal-Item', 'flagitem');

		$context['form_url'] = $context['item_details']['item_url'] . 'flag/';

		$report = Sanitiser::sanitiseTextFromPost('report_body');
		if (!empty($report))
		{
			// Saving?
			checkSession();

			$reportModel = new Report();
			$reportModel->createItemReport($context['item_details']['id_item'],
				[
					'id_member' => User::$info['id'],
					'member_name' => User::$info['name'],
					'email_address' => User::$info['email'],
					'ip_address' => User::$info['ip'],
					'body' => $report,
				]
			);
			$_SESSION['lgal_rep']['i' . $context['item_details']['id_item']] = true;
			redirectexit($context['item_details']['item_url']);
		}
	}

	public function actionSetthumbnail()
	{
		global $context;

		if (!$this->item_obj->canUseThumbnail())
		{
			Http::fatalError('cannot_lgal_setthumbnail');
		}

		checkSession('get');

		$this->item_obj->setAlbumThumbnail();
		redirectexit($context['item_details']['item_url']);
	}

	public function actionEdit()
	{
		global $context, $txt, $modSettings;

		if (!$this->item_obj->isEditable())
		{
			Http::fatalError('cannot_lgal_edit_item');
		}

		Txt::load('Levertine/LevGal-Upload');
		$context['page_title'] = sprintf($txt['lgal_editing_item'], $context['item_details']['item_name']);

		$context['album_details'] = $this->item_obj->getParentAlbum();
		$item = $this->item_obj->getLinkTreeDetails();

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($context['album_details']['album_name'], $context['album_details']['album_url']);
		$this->addLinkTree($item['name'], $item['url']);
		$this->addLinkTree($txt['lgal_edit_item_title']);

		$this->setTemplate('LevGal-Item', 'edit_item');

		$context['canonical_url'] = $context['item_details']['item_url'] . 'edit/';
		$context['form_url'] = $context['item_details']['item_url'] . 'edit/';

		// Setting up for the form (we don't use $context['item_details'] because we might get some new ones in saving)
		$context['item_name'] = $context['item_details']['item_name'];
		$context['item_slug'] = $context['item_details']['item_slug'];
		$context['description'] = $context['item_details']['description_raw'];

		if (allowedTo('lgal_manage') && empty($context['item_details']['id_member']))
		{
			$context['poster_name'] = $context['item_details']['poster_name'];
		}

		$context['description_box'] = new Richtext('message');

		$context['new_options'] = [];
		if (!empty($modSettings['lgal_enable_mature']))
		{
			$context['new_options']['mature'] = [
				'label' => $txt['lgal_mark_mature'],
				'value' => !empty($context['item_details']['mature']),
				'type' => 'checkbox',
			];
		}

		if (!$this->item_obj->albumLockedForComments())
		{
			$context['new_options']['enable_comments'] = [
				'label' => $txt['lgal_edit_item_comments'],
				'value' => $this->item_obj->getCommentState(),
				'type' => 'select',
				'opts' => [
					'enabled' => $txt['lgal_comments_enabled'],
					'no_new' => $txt['lgal_comments_no_new'],
					'disabled' => $txt['lgal_comments_disabled'],
				],
			];

			$notify = new Notify();
			$context['new_options']['enable_notify'] = [
				'label' => $txt['lgal_enable_notify'],
				'value' => $notify->getNotifyItemStatus($this->item_id, User::$info['id']),
				'type' => 'checkbox',
			];
		}
		if ($this->item_obj->canChangeApproveStatus())
		{
			$context['new_options']['approved'] = [
				'label' => $txt['lgal_item_is_approved'],
				'value' => $this->item_obj->isApproved(),
				'type' => 'checkbox',
			];
		}
		if (allowedTo('lgal_manage'))
		{
			$context['new_options']['silent'] = [
				'label' => $txt['lgal_silent_update'],
				'value' => true,
				'type' => 'checkbox',
			];
		}

		// Get all the places we could move this item to, and if we can't move, don't offer to move it.
		[$context['hierarchies'], $album_count] = $this->item_obj->getMoveDestinations();
		if (count($album_count) < 2)
		{
			unset ($context['hierarchies']);
		}

		// Tags are mildly fussy.
		loadJavascriptFile(['jquery.flexdatalist.min.js'], ['subdir' => 'Levertine', 'defer' => false]);
		theme()->addInlineJavascript('
			$(".flexdatalist").flexdatalist({
				minLength: 0,
				limitOfValues: 5,' . ((!empty($modSettings['lgal_tag_items_list_more']) || allowedTo('lgal_manage')) ? '
				noResultsText: "' . $txt['lgal_item_tag_notfound'] . '"' : '
				selectionRequired: true') . '
			});', true);
		loadCSSFile(['jquery.flexdatalist.css'], ['subdir' => 'Levertine']);

		/** @var $tagModel Tag */
		$tagModel = LevGalBootstrap::getModel('Tag');
		$context['tags'] = $tagModel->getSiteTags();
		$context['can_add_tags'] = !empty($modSettings['lgal_tag_items_list_more']) || allowedTo('lgal_manage');

		// Existing item tags
		$tags = $this->item_obj->getTags();
		if (!empty($tags))
		{
			$tag_list = [];
			foreach ($tags as $tag)
			{
				$tag_list[] = $tag['name'];
			}

			theme()->addInlineJavascript('
			$(".flexdatalist").flexdatalist("add", "' . implode(', ', $tag_list) . '");', true);
		}

		// Now let's set up editing the file itself or URL.
		/** @var $uploadModel Upload */
		$uploadModel = LevGalBootstrap::getModel('Upload');
		$context['allowed_formats'] = $uploadModel->getDisplayFileFormats();
		$context['external_formats'] = [];
		if (!empty($context['allowed_formats']['external']))
		{
			$context['external_formats'] = $context['allowed_formats']['external'];
			unset ($context['allowed_formats']['external']);
		}

		$context['editing'] = 'none';
		if (!empty($context['external_formats']) && str_starts_with($context['item_details']['mime_type'], 'external/'))
		{
			$context['editing'] = 'link';
			$context['external_model'] = new External($context['item_details']['meta']);
			$link_details = $context['external_model']->getDisplayProperties();
			$context['original_url'] = $context['edit_url'] = Util::htmlspecialchars($link_details['external_url']);
		}
		elseif (!empty($context['allowed_formats']) && !str_starts_with($context['item_details']['mime_type'], 'external'))
		{
			Txt::load('Levertine/LevGal-Upload');
			$context['editing'] = 'file';
			$context['lgal_enable_resize'] = !empty($modSettings['lgal_enable_resize']);
			$context['quota_data'] = [
				'formats' => $uploadModel->getFormatMap(),
				'quotas' => $uploadModel->getAllQuotas(),
			];
			loadJavascriptFile(['/dropzone/min/dropzone.min.js', 'upload.js'], ['subdir' => 'Levertine', 'defer' => false]);
			theme()->addInlineJavascript('Dropzone.autoDiscover = false;', true);
			loadCSSFile(['/dropzone/dropzone.css'], ['subdir' => 'Levertine']);
		}

		// Custom fields
		$context['custom_field_model'] = new Custom();
		$context['custom_fields'] = $context['custom_field_model']->prepareFieldInputs($context['album_details']['id_album'], $context['item_details']['id_item']);

		if (isset($_POST['save']))
		{
			checkSession();

			$changes = [];
			$context['errors'] = [];

			// Name and slug.
			$context['item_name'] = Sanitiser::sanitiseThingNameFromPost('item_name');
			if (empty($context['item_name']))
			{
				$context['errors']['upload_no_title'] = $txt['lgal_upload_no_title'];
			}

			$context['item_slug'] = Sanitiser::sanitiseSlugFromPost('item_slug');

			// Poster's username
			if (isset($context['poster_name']))
			{
				[$valid_username, $context['poster_name']] = Sanitiser::sanitiseUsernameFromPost('guest_username');
				if (!$valid_username)
				{
					$context['errors']['invalid_user'] = $txt['levgal_error_invalid_user'];
				}
			}

			// Which album it is in.
			if (!empty($context['hierarchies']))
			{
				$moved_album = isset($_POST['destalbum'], $album_count) && in_array((int) $_POST['destalbum'], $album_count, true) ? (int) $_POST['destalbum'] : 0;
				if (!empty($moved_album) && $moved_album !== $context['item_details']['id_album'])
				{
					$changes['id_album'] = $moved_album;
					$changes['time_updated'] = time();
				}
			}

			if ($context['description_box']->isEmpty() || !$context['description_box']->sanitizeContent())
			{
				$context['description'] = '';
			}
			else
			{
				$context['description'] = $context['description_box']->getForDB();
			}

			// Tags
			$context['raw_tags'] = Sanitiser::sanitiseTagFromPost('tag');
			$context['tags'] = Sanitiser::sanitiseTagFromPost('tag', true);

			// Changing up the file: URL first
			if ($context['editing'] === 'link')
			{
				$context['edit_url'] = Sanitiser::sanitiseUrlFromPost('upload_url');
				// It's actually different, right?
				if ($context['edit_url'] !== $context['original_url'])
				{
					if (!empty($context['edit_url']))
					{
						$externalModel = new External();
						$context['url_data'] = $externalModel->getURLData($context['edit_url']);
					}

					if (empty($context['url_data']['provider']))
					{
						$context['edit_url'] = Sanitiser::sanitiseTextFromPost('upload_url');
						$context['errors']['upload_no_link'] = $txt['lgal_upload_no_link'];
					}
				}
			}
			elseif ($context['editing'] === 'file')
			{
				// Grab the file details. These we need.
				$context['filename'] = empty($_POST['async_filename']) ? '' : rawurldecode($_POST['async_filename']);
				$context['async_id'] = $_POST['async'] ?? 0;
				$context['async_size'] = isset($_POST['async_size']) && (int) $_POST['async_size'] > 0 ? (int) $_POST['async_size'] : 0;
				if (!empty($context['filename']) || !empty($context['async_id']) || !empty($context['async_size']))
				{
					if (empty($context['filename']) || empty($context['async_id']) || empty($context['async_size']))
					{
						$context['errors']['upload_no_file'] = $txt['lgal_upload_no_file'];
					}
					else
					{
						$context['existing_upload'] = true;
						$context['filename_display'] = Sanitiser::sanitiseText($context['filename']);
						$context['filename_post'] = rawurlencode($context['filename']);

						if (!$uploadModel->validateUpload($context['async_id'], $context['async_size'], $context['filename']))
						{
							$context['errors']['upload_no_validate'] = $txt['lgal_upload_no_validate'];
						}
					}
				}
			}

			// And custom fields
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

			if (empty($context['errors']))
			{
				if ($context['item_name'] !== $context['item_details']['item_name'])
				{
					$changes['item_name'] = $context['item_name'];
				}
				if ($context['item_slug'] !== $context['item_details']['item_slug'])
				{
					$changes['item_slug'] = $context['item_slug'];
				}
				if (isset($context['poster_name']) && $context['poster_name'] !== $context['item_details']['poster_name'])
				{
					$changes['poster_name'] = $context['poster_name'];
				}
				if (isset($context['description']) && $context['description'] !== $context['item_details']['description'])
				{
					$changes['description'] = $context['description'];
				}
				// Updating an existing URL is a bit tricky.
				if (!empty($context['edit_url']) && $context['edit_url'] !== $context['original_url'])
				{
					$this->item_obj->deleteFiles();
					$changes['filehash'] = $uploadModel->getFileHash($context['edit_url']);
					$changes['mime_type'] = $context['url_data']['mime_type'];
					unset ($context['url_data']['mime_type']);
					$changes['meta'] = $context['url_data'];
					$changes['time_updated'] = time();
				}
				// Updating a file is even more complicated.
				elseif (!empty($context['filename']))
				{
					$this->item_obj->deleteFiles();
					$hash = $uploadModel->moveUpload($context['async_id'], $context['item_details']['id_item'], $context['filename']);
					$this->item_obj->updateItem([
						'hash' => $hash,
						'extension' => $uploadModel->getExtension($context['filename']),
						'filename' => $context['filename'],
						'filesize' => $context['async_size'],
					]);
					// Then we can do the fun of meta.
					$meta = $this->item_obj->getMetadata();
					$opts = [
						'mime_type' => $meta['mime_type'],
					];

					// Did we get width/height? Make sure we fix if it had one before.
					$opts['width'] = 0;
					$opts['height'] = 0;
					if (isset($meta['meta']['width'], $meta['meta']['height']))
					{
						foreach (['width', 'height'] as $var)
						{
							$opts[$var] = $meta['meta'][$var];
							unset ($meta['meta'][$var]);
						}
					}
					$opts['meta'] = $meta['meta'];

					// Before we do the thumbnail thing we might need to fix the orientation.
					if (!empty($meta['meta']['exif']['IFD0']['Orientation']))
					{
						$array = $this->item_obj->fixOrientation($meta['meta']['exif']['IFD0']['Orientation']);
						if (!empty($array))
						{
							[$opts['width'], $opts['height'], $opts['filesize']] = $array;
						}
					}

					// Now update the item.
					$this->item_obj->updateItem($opts);
					$changes['time_updated'] = time();

					// Did we get a thumbnail from meta?
					if (isset($meta['thumbnail']))
					{
						$this->item_obj->setThumbnail($meta['thumbnail']);
					}
					else
					{
						$this->item_obj->getThumbnail();
					}
				}
				foreach ($context['new_options'] as $id => $option)
				{
					if ($option['type'] === 'checkbox')
					{
						$old_value = (bool) $option['value'];
						$new_value = !empty($_POST[$id]);
						if ($old_value !== $new_value)
						{
							$changes[$id] = $new_value;
						}
					}
					elseif ($option['type'] === 'select')
					{
						if (!isset($_POST[$id], $option['opts'][$_POST[$id]]))
						{
							continue;
						}
						if ($id === 'enable_comments')
						{
							$array = [
								'enabled' => 0,
								'no_new' => 1,
								'disabled' => 2,
							];
							if ($array[$_POST[$id]] != $context['item_details']['comment_state'])
							{
								$changes['comment_state'] = $array[$_POST[$id]];
							}
						}
						else
						{
							$changes[$id] = $_POST[$id];
						}
					}
				}
				if (!empty($changes))
				{
					// We need to mark this as no longer editable and also update the edit time.
					$changes['editable'] = 0;

					if (isset($_POST['silent']) && allowedTo('lgal_manage'))
					{
						unset ($changes['time_updated']);
					}

					if (empty($context['raw_tags']))
					{
						if (!empty($tags))
						{
							// There were some, now there are not.
							$changes['has_tag'] = 0;
							$tagModel->removeTagsFromItems($context['item_details']['id_item']);
						}
					}
					else
					{
						// If there are tags, delete the old ones.
						if (!empty($tags))
						{
							$tagModel->removeTagsFromItems($context['item_details']['id_item']);
						}
						// And add the new ones
						$changes['has_tag'] = 1;
						$tagModel->setTagsOnItem($context['item_details']['id_item'], $context['raw_tags']);
					}

					// Notifications are not handled at the table level.
					if (isset($changes['enable_notify']))
					{
						$notify = new Notify();
						$method = $changes['enable_notify'] ? 'setNotifyItem' : 'unsetNotifyItem';
						$notify->$method($context['item_details']['id_item'], $context['user']['id']);
						unset ($changes['enable_notify']);
					}

					// Item approval is a lot more complex than just setting the flag, so let's do that.
					if (isset($changes['approved']))
					{
						$method = $changes['approved'] ? 'approveItem' : 'unapproveItem';
						$this->item_obj->$method();
						unset ($changes['approved']);
					}

					// Moving is complicated too.
					if (!empty($changes['id_album']))
					{
						$itemList = LevGalBootstrap::getModel('ItemList');
						$itemList->moveItemsToAlbum($this->item_id, $changes['id_album']);
						ModLog::logEvent('move_item', ['id_item' => $this->item_id, 'old_album' => $context['album_details']['album_name'], 'id_album' => $changes['id_album']]);
						unset ($changes['id_album']);
					}

					if (!empty($context['custom_fields']))
					{
						$context['custom_field_model']->setCustomValues($this->item_id, $context['custom_fields']);
					}

					$this->item_obj->updateItem($changes);

					// Having done this, we should look at thumbnails.
					if (!empty($context['external_model']) && $thumbnail = $context['external_model']->getThumbnail())
					{
						$this->item_obj->deleteFiles();
						$this->item_obj->setThumbnail($thumbnail);
					}
				}
				// Since the slug might have changed, we should refresh what we have and leave.
				$context['item_details'] = $this->item_obj->getItemInfoById($context['item_details']['id_item']);
				// Make sure we mark it as seen because the new edit time may confuse it otherwise.
				$this->item_obj->markSeen(true);
				redirectexit($context['item_details']['item_url']);
			}
			elseif (!empty($changes['id_album']))
			{
				$context['item_details']['id_album'] = $changes['id_album'];
			}
		}

		$context['description_box']->createEditor([
			'value' => $context['description_box']->getForForm($context['description']),
			'labels' => [
				'post_button' => $txt['lgal_edit_item_title'],
			],
			'js' => [
				'post_button' => 'return is_submittable() && submitThisOnce(this);',
			],
		]);
	}

	private function loadDependencies()
	{
		global $context;

		if ($context['item_display']['display_template'] === 'picture' && !empty($context['item_display']['needs_lightbox']))
		{
			loadCSSFile('glightbox.min.css', ['subdir' => 'Levertine/lightbox']);
			loadJavascriptFile('glightbox.min.js', ['subdir' => 'Levertine/lightbox', 'defer' => true]);
			theme()->addInlineJavascript('document.addEventListener("DOMContentLoaded", () => {const lightbox = GLightbox({touchNavigation: true});});', true);
		}

		if ($context['item_display']['display_template'] === 'audio' || $context['item_display']['display_template'] === 'video')
		{
			loadCSSFile('plyr.min.css', ['subdir' => 'Levertine/plyr']);
			loadJavascriptFile('plyr.min.js', ['subdir' => 'Levertine/plyr', 'defer' => true]);
			theme()->addInlineJavascript('document.addEventListener("DOMContentLoaded", () => {const player = new Plyr("#plyr");});', true);
		}
	}
}
