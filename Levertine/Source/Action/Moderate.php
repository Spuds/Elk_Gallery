<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\Helper\Http;
use Addons\Levertine\Source\Helper\Sanitiser;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\AlbumList;
use Addons\Levertine\Source\Model\Moderate as ModerateModel;
use Addons\Levertine\Source\Model\Report;
use ElkArte\Languages\Txt;
use ElkArte\User;
use function Addons\Levertine\Source\levgal_pageindex;

/**
 * This file provides the handling for the main moderation area, site/?media/moderate/.
 */
class Moderate extends LevGalAbstract
{
	/** @var AlbumList */
	private $album_list;

	public function __construct()
	{
		parent::__construct();

		Txt::load('Levertine/LevGal-Moderation');
	}

	public function actionIndex()
	{
		global $txt, $context, $modSettings, $scripturl;

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		$context['canonical_url'] = $scripturl . '?media/moderate/';
		$this->setTemplate('LevGal-Moderate', 'moderate_main', 'admin_lg.css');
		loadCSSFile('admin.css');

		$context['page_title'] = $txt['levgal_moderate'];

		// Yay for permissions checks.
		$context['mod_blocks'] = [];

		if (allowedTo(['lgal_manage', 'lgal_approve_album']))
		{
			$context['mod_blocks'][] = 'unapproved_albums';
		}

		if (allowedTo(['lgal_manage', 'lgal_approve_item']) || !empty($modSettings['lgal_selfmod_approve_item']))
		{
			$context['mod_blocks'][] = 'unapproved_items';
		}

		if (allowedTo('lgal_manage'))
		{
			$context['mod_blocks'][] = 'reported_comments';
			$context['mod_blocks'][] = 'reported_items';
		}

		if (allowedTo(['lgal_manage', 'lgal_approve_comment']) || !empty($modSettings['lgal_selfmod_approve_comment']))
		{
			$context['mod_blocks'][] = 'unapproved_comments';
		}

		if (empty($context['mod_blocks']))
		{
			Http::fatalError('cannot_lgal_moderate');
		}

		foreach ($context['mod_blocks'] as $block)
		{
			$method = 'block' . ucfirst($block);
			$context['moderation'][$block] = $this->$method();
		}
	}

	protected function getVisibleAlbums()
	{
		if (allowedTo('lgal_manage'))
		{
			return true;
		}

		if (empty($this->album_list))
		{
			$this->album_list = new AlbumList();
		}

		return $this->album_list->getVisibleAlbums();
	}

	protected function getUserAlbums()
	{
		if (allowedTo('lgal_manage'))
		{
			return true;
		}

		if (empty($this->album_list))
		{
			$this->album_list = new AlbumList();
		}

		return $this->album_list->getUserAlbums();
	}

	protected function blockUnapproved_comments()
	{
		$albums = $this->getVisibleAlbums();
		if (empty($albums))
		{
			return [];
		}

		return (new ModerateModel())->getVisibleUnapprovedComments(0, 10, 'desc', $albums);
	}

	protected function blockUnapproved_items()
	{
		// If can approve anything, it doesn't have to be just the user's own items
		$viewing_all = allowedTo(['lgal_manage', 'lgal_approve_item']);

		$albums = $viewing_all ? $this->getVisibleAlbums() : $this->getUserAlbums();
		if (empty($albums))
		{
			return [];
		}

		return (new ModerateModel())->getVisibleUnapprovedItems(0, 10, 'desc', $albums);
	}

	protected function blockUnapproved_albums()
	{
		$albums = $this->getVisibleAlbums();
		if (empty($albums))
		{
			return [];
		}

		return (new ModerateModel())->getVisibleUnapprovedAlbums(0, 10, 'desc', $albums);
	}

	protected function blockReported_comments()
	{
		return (new ModerateModel())->getReportedComments(0, 10, 'desc');
	}

	protected function blockReported_items()
	{
		return (new ModerateModel())->getReportedItems(0, 10, 'desc');
	}

	public function actionUnapproved_comments()
	{
		global $context, $txt, $modSettings, $scripturl;

		if (!allowedTo(['lgal_manage', 'lgal_approve_comment']) && empty($modSettings['lgal_selfmod_approve_comment']))
		{
			Http::fatalError('cannot_lgal_moderate');
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		$this->addLinkTree($txt['levgal_unapproved_comments'], '?media/moderate/unapproved_comments/');
		$context['canonical_url'] = $scripturl . '?media/moderate/unapproved_comments/';
		$this->setTemplate('LevGal-Moderate', 'moderate_unapproved_comments');

		$context['page_title'] = $txt['levgal_unapproved_comments'];
		$context['comments'] = [];

		// First, we need to know how many unapproved comments there are.
		$moderate = new ModerateModel();
		$comment_count = $moderate->getUnapprovedCommentsCount();

		$per_page = 20;
		$num_pages = ceil($comment_count / $per_page);
		$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['pageindex'] = levgal_pageindex($scripturl . '?media/moderate/unapproved_comments/', $this_page, $num_pages);
		if ($this_page > 1)
		{
			$context['canonical_url'] .= 'page-' . $this_page . '/';
		}

		// We got some? Great, go get them and do something exciting with them.
		if ($comment_count > 0)
		{
			$albums = $this->getVisibleAlbums();
			if (!empty($albums))
			{
				$context['comments'] = $moderate->getVisibleUnapprovedComments(($this_page - 1) * $per_page, $per_page, 'asc', $albums);

				foreach ($context['comments'] as $id_comment => $comment)
				{
					// If we're here, we're able to see the item and approve it.
					$context['comments'][$id_comment]['actions']['browse'] = ['url' => $comment['comment_url'], 'title' => $txt['levgal_comment_browse']];
					$context['comments'][$id_comment]['actions']['approve'] = ['url' => $comment['comment_url'] . 'approve_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_approve']];
					if (allowedTo('lgal_delete_comment_any') || (allowedTo('lgal_delete_comment_own') && $comment['id_member'] == User::$info['id']) || (!empty($modSettings['lgal_selfmod_delete_comment']) && $comment['item_poster'] == User::$info['id']))
					{
						$context['comments'][$id_comment]['actions']['delete'] = ['url' => $comment['comment_url'] . 'delete_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_delete']];
					}
				}
			}
		}
	}

	public function actionUnapproved_items()
	{
		global $context, $txt, $scripturl, $modSettings;

		if (!allowedTo(['lgal_manage', 'lgal_approve_item']) && empty($modSettings['lgal_selfmod_approve_item']))
		{
			Http::fatalError('cannot_lgal_moderate');
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		$this->addLinkTree($txt['levgal_unapproved_items'], '?media/moderate/unapproved_items/');
		$context['canonical_url'] = $scripturl . '?media/moderate/unapproved_items/';
		$this->setTemplate('LevGal-Moderate', 'moderate_unapproved_items');

		$context['page_title'] = $txt['levgal_unapproved_items'];
		$context['items'] = [];

		// First, we need to know how many unapproved items there are.
		$moderate = new ModerateModel();
		$item_count = $moderate->getUnapprovedItemsCount();

		$per_page = 20;
		$num_pages = ceil($item_count / $per_page);
		$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['pageindex'] = levgal_pageindex($scripturl . '?media/moderate/unapproved_items/', $this_page, $num_pages);
		if ($this_page > 1)
		{
			$context['canonical_url'] .= 'page-' . $this_page . '/';
		}

		if ($item_count > 0)
		{
			// Anyone who can manage or generically approve can approve in any album they can see. Failing that, it'll be inside their own albums only.
			$albums = allowedTo(['lgal_manage', 'lgal_approve_item']) ? $this->getVisibleAlbums() : $this->getUserAlbums();
			if (!empty($albums))
			{
				$context['items'] = $moderate->getVisibleUnapprovedItems(($this_page - 1) * $per_page, $per_page, 'asc', $albums);

				foreach ($context['items'] as $id_item => $item)
				{
					// If we're here, we're able to see the item and approve it.
					$context['items'][$id_item]['actions']['browse'] = ['url' => $item['item_url'], 'title' => $txt['levgal_comment_browse']];
					$context['items'][$id_item]['actions']['approve'] = ['url' => $item['item_url'] . 'approve_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_approve']];
					if (allowedTo('lgal_delete_item_any') || (allowedTo('lgal_delete_item_own') && $item['id_member'] == User::$info['id']))
					{
						$context['items'][$id_item]['actions']['delete'] = ['url' => $item['item_url'] . 'delete_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_delete']];
					}
				}
			}
		}
	}

	public function actionUnapproved_albums()
	{
		global $context, $txt, $scripturl;

		if (!allowedTo(['lgal_manage', 'lgal_approve_album']))
		{
			Http::fatalError('cannot_lgal_moderate');
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		$this->addLinkTree($txt['levgal_unapproved_albums'], '?media/moderate/unapproved_albums/');
		$context['canonical_url'] = $scripturl . '?media/moderate/unapproved_albums/';
		$this->setTemplate('LevGal-Moderate', 'moderate_unapproved_albums');

		$context['page_title'] = $txt['levgal_unapproved_albums'];
		$context['items'] = [];

		// First, we need to know how many unapproved items there are.
		$moderate = new ModerateModel();
		$album_count = $moderate->getUnapprovedAlbumsCount();

		$per_page = 20;
		$num_pages = ceil($album_count / $per_page);
		$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['pageindex'] = levgal_pageindex($scripturl . '?media/moderate/unapproved_albums/', $this_page, $num_pages);
		if ($this_page > 1)
		{
			$context['canonical_url'] .= 'page-' . $this_page . '/';
		}

		if ($album_count > 0)
		{
			// Anyone who can manage or generically approve can approve in any album they can see.
			$albums = $this->getVisibleAlbums();
			if (!empty($albums))
			{
				$context['albums'] = $moderate->getVisibleUnapprovedAlbums(($this_page - 1) * $per_page, $per_page, 'asc', $albums);

				foreach ($context['albums'] as $id_album => $album)
				{
					// If we're here, we're able to see the album and approve it.
					$context['albums'][$id_album]['actions']['browse'] = ['url' => $album['album_url'], 'title' => $txt['levgal_comment_browse']];
					$context['albums'][$id_album]['actions']['approve'] = ['url' => $album['album_url'] . 'approve_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_approve']];
					if (allowedTo('lgal_delete_album_any'))
					{
						$context['albums'][$id_album]['actions']['delete'] = ['url' => $album['album_url'] . 'delete_unapproved/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['levgal_comment_delete']];
					}
				}
			}
		}
	}

	public function actionReported_comments()
	{
		$this->getReportedComments('open');
	}

	public function actionReported_comments_closed()
	{
		$this->getReportedComments('closed');
	}

	protected function getReportedComments($comment_type)
	{
		global $context, $txt, $scripturl;

		$context['open_reports'] = $comment_type === 'open';

		isAllowedTo('lgal_manage');

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		if ($context['open_reports'])
		{
			$this->addLinkTree($txt['levgal_reported_comments'], '?media/moderate/reported_comments/');
			$context['canonical_url'] = $scripturl . '?media/moderate/reported_comments/';
		}
		else
		{
			$this->addLinkTree($txt['lgal_closed_reported_comments'], '?media/moderate/reported_comments_closed/');
			$context['canonical_url'] = $scripturl . '?media/moderate/reported_comments_closed/';
		}
		$this->setTemplate('LevGal-Moderate', 'moderate_reported_comments');

		$context['page_title'] = $context['open_reports'] ? $txt['levgal_reported_comments'] : $txt['lgal_closed_reported_comments'];
		$context['comments'] = [];

		$report = new Report();
		$count = $report->getReportCount('comments', $context['open_reports'] ? 'open' : 'closed');
		$moderate = new ModerateModel();

		$per_page = 20;
		$num_pages = ceil($count / $per_page);
		$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['pageindex'] = levgal_pageindex($scripturl . '?media/moderate/' . ($context['open_reports'] ? 'reported_comments' : 'reported_comments_closed') . '/', $this_page, $num_pages);
		if ($this_page > 1)
		{
			$context['canonical_url'] .= 'page-' . $this_page . '/';
		}

		// We're not invoking the beast that is createMenu, just a fraction of the markup.
		$context['tabs'] = [
			[
				'url' => $scripturl . '?media/moderate/reported_comments/',
				'title' => $txt['lgal_open_reports'],
				'active' => $context['open_reports'],
			],
			[
				'url' => $scripturl . '?media/moderate/reported_comments_closed/',
				'title' => $txt['lgal_closed_reports'],
				'active' => !$context['open_reports'],
			],
		];

		if ($count > 0)
		{
			$context['comments'] = $moderate->getReportedComments(($this_page - 1) * $per_page, $per_page, 'asc', $context['open_reports'] ? 'open' : 'closed');

			// We need to get the list of reporters for the above comments.
			$reporters = $report->getReportersForReports(array_keys($context['comments']));
			foreach ($reporters as $id_report => $people)
			{
				$context['comments'][$id_report]['reporters'] = $people;
			}

			// And then the actions for each.
			foreach ($context['comments'] as $id_report => $comment)
			{
				$context['comments'][$id_report]['actions']['browse'] = ['url' => $comment['report_url'], 'title' => $txt['lgal_see_report']];
				if ($context['open_reports'])
				{
					$context['comments'][$id_report]['actions']['close'] = ['url' => $scripturl . '?media/moderate/' . $id_report . '/close/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_close_report']];
				}
				else
				{
					$context['comments'][$id_report]['actions']['open'] = ['url' => $scripturl . '?media/moderate/' . $id_report . '/open/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_open_report']];
				}
			}
		}
	}

	public function actionReported_items()
	{
		$this->getReportedItems('open');
	}

	public function actionReported_items_closed()
	{
		$this->getReportedItems('closed');
	}

	protected function getReportedItems($item_type)
	{
		global $context, $txt, $scripturl;

		$context['open_reports'] = $item_type === 'open';

		isAllowedTo('lgal_manage');

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		if ($context['open_reports'])
		{
			$this->addLinkTree($txt['levgal_reported_items'], '?media/moderate/reported_items/');
			$context['canonical_url'] = $scripturl . '?media/moderate/reported_items/';
		}
		else
		{
			$this->addLinkTree($txt['lgal_closed_reported_items'], '?media/moderate/reported_items_closed/');
			$context['canonical_url'] = $scripturl . '?media/moderate/reported_items_closed/';
		}
		$this->setTemplate('LevGal-Moderate', 'moderate_reported_items');

		$context['page_title'] = $context['open_reports'] ? $txt['levgal_reported_items'] : $txt['lgal_closed_reported_items'];
		$context['items'] = [];

		$report = new Report();
		$count = $report->getReportCount('items', $context['open_reports'] ? 'open' : 'closed');
		$moderate = new ModerateModel();

		$per_page = 20;
		$num_pages = ceil($count / $per_page);
		$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
		$context['pageindex'] = levgal_pageindex($scripturl . '?media/moderate/' . ($context['open_reports'] ? 'reported_items' : 'reported_items_closed') . '/', $this_page, $num_pages);
		if ($this_page > 1)
		{
			$context['canonical_url'] .= 'page-' . $this_page . '/';
		}

		// We're not invoking the beast that is createMenu, just a fraction of the markup.
		$context['tabs'] = [
			[
				'url' => $scripturl . '?media/moderate/reported_items/',
				'title' => $txt['lgal_open_reports'],
				'active' => $context['open_reports'],
			],
			[
				'url' => $scripturl . '?media/moderate/reported_items_closed/',
				'title' => $txt['lgal_closed_reports'],
				'active' => !$context['open_reports'],
			],
		];

		if ($count > 0)
		{
			$context['items'] = $moderate->getReportedItems(($this_page - 1) * $per_page, $per_page, 'asc', $context['open_reports'] ? 'open' : 'closed', true);

			// We need to get the list of reporters for the above items.
			$reporters = $report->getReportersForReports(array_keys($context['items']));
			foreach ($reporters as $id_report => $people)
			{
				$context['items'][$id_report]['reporters'] = $people;
			}

			// And then the actions for each.
			foreach ($context['items'] as $id_report => $comment)
			{
				$context['items'][$id_report]['actions']['browse'] = ['url' => $comment['report_url'], 'title' => $txt['lgal_see_report']];
				if ($context['open_reports'])
				{
					$context['items'][$id_report]['actions']['close'] = ['url' => $scripturl . '?media/moderate/' . $id_report . '/close/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_close_report']];
				}
				else
				{
					$context['items'][$id_report]['actions']['open'] = ['url' => $scripturl . '?media/moderate/' . $id_report . '/open/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_open_report']];
				}
			}
		}
	}

	public function actionReport()
	{
		global $txt, $context, $scripturl;

		isAllowedTo('lgal_manage');

		$report_id = $this->getNumericId();
		$report = new Report();
		if (!empty($report_id))
		{
			$context['report_details'] = $report->getReportById($report_id);
		}

		// Did we have a report?
		if (empty($context['report_details']))
		{
			Http::fatalError('lgal_report_not_found');
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_moderate'], '?media/moderate/');
		if ($report->getReportType() === 'comment')
		{
			if ($report->isOpen())
			{
				$this->addLinkTree($txt['levgal_reported_comments'], '?media/moderate/reported_comments/');
			}
			else
			{
				$this->addLinkTree($txt['lgal_closed_reported_comments'], '?media/moderate/reported_comments_closed/');
			}
			$context['page_title'] = sprintf($txt['lgal_reported_comment'], $context['report_details']['item_name']);
			$context['report_title'] = $context['page_title'];
			$context['section_title'] = $txt['levgal_reported_comments'];
			$context['section_desc'] = $txt['lgal_reported_comments_desc'];
		}
		else
		{
			if ($report->isOpen())
			{
				$this->addLinkTree($txt['levgal_reported_items'], '?media/moderate/reported_items/');
			}
			else
			{
				$this->addLinkTree($txt['lgal_closed_reported_items'], '?media/moderate/reported_items_closed/');
			}
			$context['page_title'] = sprintf($txt['lgal_reported_item'], $context['report_details']['item_name']);
			$context['report_title'] = sprintf($txt['lgal_reported_item'], '<a href="' . $context['report_details']['item_url'] . '">' . $context['report_details']['item_name'] . '</a>');
			$context['section_title'] = $txt['levgal_reported_items'];
			$context['section_desc'] = $txt['lgal_reported_items_desc'];
		}
		$this->addLinkTree($context['page_title'], $context['report_details']['report_url']);
		$context['canonical_url'] = $context['report_details']['report_url'];

		$this->setTemplate('LevGal-Moderate', 'showreport');

		// And set up the navigation. We're not invoking the beast that is createMenu, just a fraction of the markup.
		$context['tabs'] = [
			[
				'url' => $scripturl . '?media/moderate/reported_comments/',
				'title' => $txt['lgal_open_reports'],
				'active' => $report->isOpen(),
			],
			[
				'url' => $scripturl . '?media/moderate/reported_comments_closed/',
				'title' => $txt['lgal_closed_reports'],
				'active' => !$report->isOpen(),
			],
		];

		// So, now we have to get the actual bodies of reports as well as any moderator comments.
		$context['report_bodies'] = $report->getReportBodies();
		$context['report_comments'] = $report->getModeratorComments();

		$context['can_comment'] = $report->isOpen();

		$context['report_actions'] = [];
		if ($report->isOpen())
		{
			$context['report_actions']['close'] = ['url' => $scripturl . '?media/moderate/' . $context['report_details']['id_report'] . '/close/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_close_report']];
		}
		else
		{
			$context['report_actions']['open'] = ['url' => $scripturl . '?media/moderate/' . $context['report_details']['id_report'] . '/open/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'title' => $txt['lgal_open_report']];
		}
	}

	public function actionComment()
	{
		global $context;

		isAllowedTo('lgal_manage');

		$report_id = $this->getNumericId();
		$report = new Report();
		if (!empty($report_id))
		{
			$context['report_details'] = $report->getReportById($report_id);
		}

		// Did we have a report?
		if (empty($context['report_details']))
		{
			Http::fatalError('lgal_report_not_found');
		}

		// Is it open?
		if (!$report->isOpen())
		{
			Http::fatalError('lgal_cannot_comment');
		}

		$mod_comment = Sanitiser::sanitiseTextFromPost('mod_comment');
		// We're going back the same way whatever... but only if we have something do we need to do anything with it.
		if (!empty($mod_comment))
		{
			checkSession();

			$report->addComment([
				'id_member' => User::$info['id'],
				'member_name' => User::$info['name'],
				'comment' => $mod_comment,
			]);
		}

		redirectexit($context['report_details']['report_url']);
	}

	public function actionClose()
	{
		global $scripturl;
		isAllowedTo('lgal_manage');

		checkSession('get');

		$report_id = $this->getNumericId();
		$report = new Report();
		if (!empty($report_id))
		{
			$report_details = $report->getReportById($report_id);
		}

		// Did we have a report? Is it open?
		if (empty($report_details) || !$report->isOpen())
		{
			Http::fatalError('lgal_report_not_found');
		}

		// OK, so close it and figure out where we should be going.
		$report->closeReport();

		$type = $report->getReportType();
		redirectexit($scripturl . '?media/moderate/' . ($type === 'comment' ? 'reported_comments/' : 'reported_items/'));
	}

	public function actionOpen()
	{
		global $scripturl;
		isAllowedTo('lgal_manage');

		checkSession('get');

		$report_id = $this->getNumericId();
		$report = new Report();
		if (!empty($report_id))
		{
			$report_details = $report->getReportById($report_id);
		}

		// Did we have a report? Is it open?
		if (empty($report_details) || $report->isOpen())
		{
			Http::fatalError('lgal_report_not_found');
		}

		// OK, so close it and figure out where we should be going.
		$report->openReport();

		$type = $report->getReportType();
		redirectexit($scripturl . '?media/moderate/' . ($type === 'comment' ? 'reported_comments_closed/' : 'reported_items_closed/'));
	}
}
