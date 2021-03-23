<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

use BBC\ParserWrapper;

/**
 * This file deals with reporting items and comments.
 */
class LevGal_Model_Report
{
	/** @var array */
	private $current_report;

	public function getReportById($id_report)
	{
		global $scripturl;

		$db = database();

		if ($id_report <= 0)
		{
			return false;
		}

		if (!empty($this->current_report['id_report']) && $this->current_report['id_report'] = $id_report)
		{
			return $this->current_report;
		}

		$request = $db->query('', '
			SELECT 
				lr.id_report, lr.id_comment, lr.id_item, lr.id_album, IFNULL(mem.id_member, 0) AS content_id_poster,
				IFNULL(mem.real_name, lr.content_poster_name) AS content_poster_name, lr.body, lr.time_started,
				lr.time_updated, lr.num_reports, lr.closed, li.item_name, li.item_slug
			FROM {db_prefix}lgal_reports AS lr
				LEFT JOIN {db_prefix}members AS mem ON (lr.content_id_poster = mem.id_member)
				INNER JOIN {db_prefix}lgal_items AS li ON (lr.id_item = li.id_item)
			WHERE id_report = {int:report}',
			array(
				'report' => $id_report,
			)
		);
		if ($db->num_rows($request))
		{
			$parser = ParserWrapper::instance();
			$this->current_report = $db->fetch_assoc($request);
			if (!empty($this->current_report['body']))
			{
				$this->current_report['body'] = $parser->parseMessage($this->current_report['body'], true);
			}
			$this->current_report['report_url'] = $scripturl . '?media/moderate/' . $id_report . '/report/';
			$this->current_report['item_url'] = $scripturl . '?media/item/' . (!empty($this->current_report['item_slug']) ? $this->current_report['item_slug'] . '.' . $this->current_report['id_item'] : $this->current_report['id_item']) . '/';
			$this->current_report['time_started_format'] = LevGal_Helper_Format::time($this->current_report['time_started']);
			$this->current_report['time_updated_format'] = LevGal_Helper_Format::time($this->current_report['time_updated']);
		}
		$db->free_result($request);

		return $this->current_report;
	}

	public function isOpen()
	{
		return !empty($this->current_report) && isset($this->current_report['closed']) && empty($this->current_report['closed']);
	}

	public function getReportType()
	{
		if (empty($this->current_report))
		{
			return false;
		}

		return !empty($this->current_report['id_comment']) ? 'comment' : 'item';
	}

	public function resetReportCount()
	{
		$db = database();

		$reports = array('items' => 0, 'comments' => 0);

		$request = $db->query('', '
			SELECT 
				id_report, id_comment
			FROM {db_prefix}lgal_reports
			WHERE closed = 0');
		while ($row = $db->fetch_assoc($request))
		{
			// If the comment is empty, it's not about a comment.
			$reports[empty($row['id_comment']) ? 'items' : 'comments']++;
		}
		$db->free_result($request);

		updateSettings(array('lgal_reports' => serialize($reports)));
	}

	protected function increaseReportCount($type)
	{
		global $modSettings;

		if ($type === 'items' || $type === 'comments')
		{
			$reports = @unserialize($modSettings['lgal_reports']);
			if (!isset($reports[$type]))
			{
				$reports[$type] = 1;
			}
			else
			{
				$reports[$type]++;
			}

			updateSettings(array('lgal_reports' => serialize($reports)));
		}
	}

	protected function decreaseReportCount($type)
	{
		global $modSettings;

		if ($type === 'items' || $type === 'comments')
		{
			$reports = @unserialize($modSettings['lgal_reports']);
			if (!isset($reports[$type]) || $reports[$type] <= 1)
			{
				$reports[$type] = 0;
			}
			else
			{
				$reports[$type]--;
			}

			updateSettings(array('lgal_reports' => serialize($reports)));
		}
	}

	public function getReportCount($type, $open = 'open')
	{
		global $modSettings;

		$db = database();

		// Open reports are easy, we can do this easy.
		if ($open === 'open')
		{
			$reports = @unserialize($modSettings['lgal_reports']);

			return $reports[$type] ?? 0;
		}
		else
		{
			$request = $db->query('', '
				SELECT COUNT(*)
				FROM {db_prefix}lgal_reports
				WHERE id_comment {raw:is_comment}
					AND closed = {int:closed}',
				array(
					'is_comment' => $type === 'comments' ? '!= 0' : '= 0',
					'closed' => 1,
				)
			);
			list ($count) = $db->fetch_row($request);
			$db->free_result($request);

			return $count;
		}
	}

	public function createCommentReport($id_comment, $report_details)
	{
		$db = database();

		// First we need to see if there's an existing report or not, because if there is, we might just want to update it.
		$request = $db->query('', '
			SELECT 
				id_report
			FROM {db_prefix}lgal_reports
			WHERE id_comment = {int:comment}
				AND closed = {int:not_closed}',
			array(
				'comment' => $id_comment,
				'not_closed' => 0,
			)
		);
		$new_report = true;
		if ($row = $db->fetch_assoc($request))
		{
			// So we have an existing report. Update the time on it and increment the count on it so we know we have multiples.
			$this->getReportById($row['id_report']);
			$this->updateReport(array(
				'time_updated' => time(),
				'num_reports' => $this->current_report['num_reports'] + 1,
			));
			$id_report = $row['id_report'];
			$new_report = false;
		}
		else
		{
			// We will need a few bits and pieces.
			$comment = LevGal_Bootstrap::getModel('LevGal_Model_Comment');
			$comment_details = $comment->getCommentById($id_comment);
			$item_details = $comment->getParentItem();

			$report = array(
				'id_comment' => $id_comment,
				'id_item' => $item_details['id_item'],
				'id_album' => $item_details['id_album'],
				'content_id_poster' => $item_details['id_member'],
				'content_poster_name' => $item_details['poster_name'],
				'body' => $comment_details['comment'],
				'time_started' => time(),
				'time_updated' => time(),
				'num_reports' => 1,
				'closed' => 0,
			);

			$db->insert('insert',
				'{db_prefix}lgal_reports',
				array('id_comment' => 'int', 'id_item' => 'int', 'id_album' => 'int', 'content_id_poster' => 'int',
					  'content_poster_name' => 'string', 'body' => 'string', 'time_started' => 'int', 'time_updated' => 'int',
					  'num_reports' => 'int', 'closed' => 'int'),
				$report,
				array('id_report')
			);
			$id_report = $db->insert_id('{db_prefix}lgal_reports');
		}

		if (empty($id_report))
		{
			return false;
		}

		// So, we have an id for our report, whether it's a new one or not. Now let's see about adding this instance of report.
		$db->insert('insert',
			'{db_prefix}lgal_report_body',
			array('id_report' => 'int', 'id_member' => 'int', 'member_name' => 'string', 'email_address' => 'string',
				  'ip_address' => 'string', 'body' => 'string', 'time_sent' => 'int'),
			array($id_report, $report_details['id_member'], $report_details['member_name'], $report_details['email_address'],
				  $report_details['ip_address'], $report_details['body'], time()),
			array('id_rep_body')
		);

		if ($new_report)
		{
			$this->increaseReportCount('comments');
		}

		return true;
	}

	public function createItemReport($id_item, $report_details)
	{
		$db = database();

		// First we need to see if there's an existing report or not, because if there is, we might just want to update it.
		$request = $db->query('', '
			SELECT 
				id_report
			FROM {db_prefix}lgal_reports
			WHERE id_item = {int:item}
				AND id_comment = {int:is_item_report}
				AND closed = {int:not_closed}',
			array(
				'item' => $id_item,
				'is_item_report' => 0,
				'not_closed' => 0,
			)
		);
		$new_report = true;
		if ($row = $db->fetch_assoc($request))
		{
			// So we have an existing report. Update the time on it and increment the count on it so we know we have multiples.
			$this->getReportById($row['id_report']);
			$this->updateReport(array(
				'time_updated' => time(),
				'num_reports' => $this->current_report['num_reports'] + 1,
			));
			$id_report = $row['id_report'];
			$new_report = false;
		}
		else
		{
			// So, we need a few bits and pieces.
			$item = LevGal_Bootstrap::getModel('LevGal_Model_Item');
			$item_details = $item->getItemInfoById($id_item);

			$report = array(
				'id_comment' => 0,
				'id_item' => $id_item,
				'id_album' => $item_details['id_album'],
				'content_id_poster' => $item_details['id_member'],
				'content_poster_name' => $item_details['poster_name'],
				'body' => '',
				'time_started' => time(),
				'time_updated' => time(),
				'num_reports' => 1,
				'closed' => 0,
			);

			$db->insert('insert',
				'{db_prefix}lgal_reports',
				array('id_comment' => 'int', 'id_item' => 'int', 'id_album' => 'int', 'content_id_poster' => 'int',
					  'content_poster_name' => 'string', 'body' => 'string', 'time_started' => 'int', 'time_updated' => 'int',
					  'num_reports' => 'int', 'closed' => 'int'),
				$report,
				array('id_report')
			);
			$id_report = $db->insert_id('{db_prefix}lgal_reports');
		}
		$db->free_result($request);

		if (empty($id_report))
		{
			return false;
		}

		// So, we have an id for our report, whether it's a new one or not. Now let's see about adding this instance of report.
		$db->insert('insert',
			'{db_prefix}lgal_report_body',
			array('id_report' => 'int', 'id_member' => 'int', 'member_name' => 'string', 'email_address' => 'string',
				  'ip_address' => 'string', 'body' => 'string', 'time_sent' => 'int'),
			array($id_report, $report_details['id_member'], $report_details['member_name'], $report_details['email_address'],
				  $report_details['ip_address'], $report_details['body'], time()),
			array('id_rep_body')
		);

		if ($new_report)
		{
			$this->increaseReportCount('items');
		}

		return true;
	}

	public function addComment($report)
	{
		$db = database();

		$db->insert('insert',
			'{db_prefix}lgal_report_comment',
			array('id_report' => 'int', 'id_member' => 'int', 'member_name' => 'string', 'log_time' => 'int', 'comment' => 'string'),
			array($this->current_report['id_report'], $report['id_member'], $report['member_name'], time(), $report['comment']),
			array('id_rep_comment')
		);
	}

	public function updateReport($opts)
	{
		$db = database();

		if (empty($this->current_report))
		{
			return false;
		}

		$criteria = array();
		$values = array(
			'id_report' => $this->current_report['id_report'],
		);

		// Check the things we know are numbers.
		foreach (array('time_updated', 'num_reports') as $column)
		{
			if (isset($opts[$column]))
			{
				$criteria[] = $column . ' = {int:' . $column . '}';
				$values[$column] = $opts[$column];
			}
		}
		// And the booleans masquerading as numbers. Or numbers masquerading as bools?
		foreach (array('closed') as $column)
		{
			if (isset($opts[$column]))
			{
				$criteria[] = $column . ' = {int:' . $column . '}';
				$values[$column] = !empty($opts[$column]) ? 1 : 0;
			}
		}

		if (!empty($criteria))
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_reports
				SET ' . implode(', ', $criteria) . '
				WHERE id_report = {int:id_report}',
				$values
			);
		}

		$this->current_report = array_merge($this->current_report, $values);
	}

	public function closeReport()
	{
		if (empty($this->current_report))
		{
			return false;
		}

		$this->updateReport(array('closed' => 1));
		$this->decreaseReportCount(empty($this->current_report['id_comment']) ? 'items' : 'comments');
	}

	public function openReport()
	{
		if (empty($this->current_report))
		{
			return false;
		}

		$this->updateReport(array('closed' => 0));
		$this->increaseReportCount(empty($this->current_report['id_comment']) ? 'items' : 'comments');
	}

	public function itemsMovedAlbum($items, $album)
	{
		$db = database();

		if (empty($items))
		{
			return;
		}

		$items = (array) $items;

		$db->query('', '
			UPDATE {db_prefix}lgal_reports
			SET id_album = {int:album}
			WHERE id_item IN ({array_int:items})',
			array(
				'items' => $items,
				'album' => $album,
			)
		);
	}

	public function getReportersForReports($reports)
	{
		global $scripturl;

		$db = database();

		if (empty($reports))
		{
			return array();
		}

		$reporters = array();
		$request = $db->query('', '
			SELECT 
				lrb.id_report, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, lrb.member_name) AS member_name
			FROM {db_prefix}lgal_report_body AS lrb
				LEFT JOIN {db_prefix}members AS mem ON (lrb.id_member = mem.id_member)
			WHERE lrb.id_report IN ({array_int:reports})
			ORDER BY lrb.id_report, member_name',
			array(
				'reports' => $reports,
			)
		);
		$view_profile = allowedTo('profile_view_any');
		while ($row = $db->fetch_assoc($request))
		{
			$reporters[$row['id_report']][] = $view_profile && !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a>' : $row['member_name'];
		}
		$db->free_result($request);

		// Weed out duplicates.
		foreach ($reporters as $id_report => $people)
		{
			$reporters[$id_report] = array_unique($people);
		}

		return $reporters;
	}

	public function getReportBodies()
	{
		global $scripturl;

		$db = database();

		if (empty($this->current_report))
		{
			return array();
		}

		$view_profile = allowedTo('profile_view_any');

		$bodies = array();
		$request = $db->query('', '
			SELECT 
				lrb.id_rep_body, lrb.body, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, lrb.member_name) AS member_name, lrb.time_sent
			FROM {db_prefix}lgal_report_body AS lrb
				LEFT JOIN {db_prefix}members AS mem ON (lrb.id_member = mem.id_member)
			WHERE lrb.id_report = {int:id_report}
			ORDER BY lrb.id_rep_body ASC',
			array(
				'id_report' => $this->current_report['id_report'],
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$id_rep_body = array_shift($row);
			$row['author'] = $view_profile && !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a>' : $row['member_name'];
			$row['time_sent_format'] = LevGal_Helper_Format::time($row['time_sent']);
			$bodies[$id_rep_body] = $row;
		}
		$db->free_result($request);

		return $bodies;
	}

	public function getModeratorComments()
	{
		global $scripturl;

		$db = database();

		if (empty($this->current_report))
		{
			return array();
		}

		$view_profile = allowedTo('profile_view_any');

		$comments = array();
		$request = $db->query('', '
			SELECT 
				lrc.id_rep_comment, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, lrc.member_name) AS member_name, lrc.log_time, lrc.comment
			FROM {db_prefix}lgal_report_comment AS lrc
				LEFT JOIN {db_prefix}members AS mem ON (lrc.id_member = mem.id_member)
			WHERE lrc.id_report = {int:id_report}
			ORDER BY lrc.id_rep_comment ASC',
			array(
				'id_report' => $this->current_report['id_report'],
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$id_rep_comment = array_shift($row);
			$row['author'] = $view_profile && !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a>' : $row['member_name'];
			$row['log_time_format'] = LevGal_Helper_Format::time($row['log_time']);
			$comments[$id_rep_comment] = $row;
		}
		$db->free_result($request);

		return $comments;
	}

	public function deleteReportsByComments($comments)
	{
		$db = database();

		if (empty($comments))
		{
			return;
		}

		$comments = (array) $comments;

		// First, find all the reports for these comments.
		$reports = array();
		$request = $db->query('', '
			SELECT 
				id_report
			FROM {db_prefix}lgal_reports
			WHERE id_comment IN ({array_int:comments})',
			array(
				'comments' => $comments,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$reports[] = $row['id_report'];
		}
		$db->free_result($request);

		if (!empty($reports))
		{
			$this->deleteReportsByIds($reports);
		}
	}

	public function deleteReportsByItems($items)
	{
		$db = database();

		if (empty($items))
		{
			return;
		}

		$items = (array) $items;

		// First, find all the reports for these comments.
		$reports = array();
		$request = $db->query('', '
			SELECT 
				id_report
			FROM {db_prefix}lgal_reports
			WHERE id_item IN ({array_int:items})',
			array(
				'items' => $items,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$reports[] = $row['id_report'];
		}
		$db->free_result($request);

		if (!empty($reports))
		{
			$this->deleteReportsByIds($reports);
		}
	}

	public function deleteReportsByIds($reports)
	{
		$db = database();

		// So we have some reports. Before we delete them, we have to delete the bodies and comments of the reports.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_report_body
			WHERE id_report IN ({array_int:reports})',
			array(
				'reports' => $reports,
			)
		);
		$db->query('', '
			DELETE FROM {db_prefix}lgal_report_comment
			WHERE id_report IN ({array_int:reports})',
			array(
				'reports' => $reports,
			)
		);
		// And now the reports themselves.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_reports
			WHERE id_report IN ({array_int:reports})',
			array(
				'reports' => $reports,
			)
		);

		// And update the counts of everything.
		$this->resetReportCount();
	}
}
