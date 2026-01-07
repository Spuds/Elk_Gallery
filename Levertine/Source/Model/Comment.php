<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Helper\Format;
use BBC\ParserWrapper;
use ElkArte\User;

/**
 * This file deals with the internals of comments.
 */
class Comment
{
	/** @var bool|array  */
	private $current_comment = false;

	/** @var Item  */
	private $current_item;

	public function getCommentById($commentId)
	{
		$db = database();

		// It's a uint, anything like this can disappear.
		if ($commentId <= 0)
		{
			return false;
		}

		$request = $db->query('', '
			SELECT 
				lc.id_comment, lc.id_item, IFNULL(mem.id_member, lc.id_author) AS id_author, IFNULL(mem.real_name, lc.author_name) AS author_name,
				lc.author_email, lc.author_ip, lc.comment, lc.approved, lc.time_added, lc.modified_name, lc.modified_time
			FROM {db_prefix}lgal_comments AS lc
				LEFT JOIN {db_prefix}members AS mem ON (lc.id_author = mem.id_member)
			WHERE lc.id_comment = {int:id_comment}',
			[
				'id_comment' => $commentId,
			]
		);
		if ($request->num_rows() > 0)
		{
			$parser = ParserWrapper::instance();
			$this->current_comment = $request->fetch_assoc();
			$this->current_comment['comment_parsed'] = $parser->parseMessage($this->current_comment['comment'], true);
			$this->current_comment['time_added_format'] = Format::time($this->current_comment['time_added']);
		}
		$request->free_result();

		return $this->current_comment;
	}

	public function getParentItem()
	{
		if (empty($this->current_comment))
		{
			return false;
		}
		if (!empty($this->current_item))
		{
			return $this->current_item->getItemInfoById($this->current_comment['id_item']);
		}

		$this->current_item = new Item();

		return $this->current_item->getItemInfoById($this->current_comment['id_item']);
	}

	public function itemIsOwnedByUser()
	{
		$this->getParentItem();

		return $this->current_item->isOwnedByUser();
	}

	public function isVisible()
	{
		global $modSettings;

		if (empty($this->current_comment))
		{
			return false;
		}

		// Oh dear, looks like we need the item model.
		$this->getParentItem();

		// If you can't see the item, you can't see the item's comments, regardless of anything else.
		// Or if commenting is disabled, bye.
		if (!$this->current_item->isVisible() || $this->current_item->getCommentState() === 'disabled')
		{
			return false;
		}

		if (allowedTo(['lgal_manage', 'lgal_approve_comment']))
		{
			return true;
		}

		// So the user can see the item. If the comment is approved or they're the item owner, they can see it.
		if ($this->isApproved() || $this->isOwnedByUser() || ($this->current_item->isOwnedByUser() && !empty($modSettings['lgal_selfmod_approve_comment'])))
		{
			return true;
		}

		// Otherwise no.
		return false;
	}

	public function isOwnedByUser()
	{
		return !empty($this->current_comment['id_author']) && $this->current_comment['id_author'] == User::$info['id'];
	}

	public function isApproved()
	{
		return !empty($this->current_comment) && !empty($this->current_comment['approved']);
	}

	public function createComment($id_item, $comment, $posterOptions, $approvedState, $time = 0)
	{
		$db = database();

		if (empty($time))
		{
			$time = time();
		}

		$this->current_comment = [
			'id_item' => $id_item,
			'id_author' => $posterOptions['id'],
			'author_name' => $posterOptions['name'],
			'author_email' => $posterOptions['email'],
			'author_ip' => $posterOptions['ip'],
			'comment' => $comment,
			'approved' => $approvedState === 'yes' ? 1 : 0,
			'time_added' => $time,
			'modified_name' => '',
		];
		$db->insert('',
			'{db_prefix}lgal_comments',
			['id_item' => 'int', 'id_author' => 'int', 'author_name' => 'string', 'author_email' => 'string',
				  'author_ip' => 'string', 'comment' => 'string', 'approved' => 'int', 'time_added' => 'int', 'modified_name' => 'string'],
			$this->current_comment,
			['id_comment']
		);
		if ($id_comment = $db->insert_id('{db_prefix}lgal_comments'))
		{
			$this->current_comment['id_comment'] = $id_comment;

			// If this is a guest, add it to the list.
			if (User::$info['is_guest'] && !$this->current_comment['approved'])
			{
				$_SESSION['lgal_comments'][] = $id_comment;
			}

			// Now notify the item it has a new comment.
			$this->getParentItem();
			$this->current_item->addedComment($this->current_comment['approved']);

			if (!$this->current_comment['approved'])
			{
				$this->updateUnapprovedCount();
			}

			call_integration_hook('integrate_lgal_create_comment', [$this->current_comment]);
		}
		else
		{
			$this->current_comment = false;
		}

		return $id_comment;
	}

	public function approveComment()
	{
		if (empty($this->current_comment))
		{
			return false;
		}

		if (!$this->isApproved())
		{
			// First up, mark the comment itself as approved.
			$this->updateComment([
				'approved' => 1,
			]);

			// Secondly, add this to the event log.
			ModLog::logEvent('approve_comment', ['id_comment' => $this->current_comment['id_comment'], 'id_item' => $this->current_comment['id_item']]);

			// Now notify the stats of such.
			$this->updateUnapprovedCount();

			// Now notify the item that it has an approved comment. It will do the rest.
			$this->getParentItem();

			return $this->current_item->approvedComment();
		}

		return false;
	}

	public function updateComment($details)
	{
		$db = database();

		// Bools
		$opt = 'approved';
		if (isset($details[$opt]))
		{
			$clauses[$opt] = $opt . ' = {int:' . $opt . '}';
			$values[$opt] = !empty($details[$opt]) ? 1 : 0;
		}

		// Standard strings
		foreach (['comment', 'author_name', 'author_email'] as $opt)
		{
			if (isset($details[$opt]))
			{
				$clauses[$opt] = $opt . ' = {string:' . $opt . '}';
				$values[$opt] = $details[$opt];
			}
		}

		// Specialities.
		if (isset($details['modified_name']))
		{
			$clauses['modified_name'] = 'modified_name = {string:modified_name}';
			$values['modified_name'] = $details['modified_name'];
			$clauses['modified_time'] = 'modified_time = {int:modified_time}';
			$values['modified_time'] = !empty($details['modified_time']) ? $details['modified_time'] : time();
		}

		if (!empty($clauses))
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_comments
				SET ' . implode(', ', $clauses) . '
				WHERE id_comment = {int:id_comment}',
				array_merge($values, ['id_comment' => $this->current_comment['id_comment']])
			);

			// If we're altering approved status, fix the overall.
			if (isset($details['approved']))
			{
				$this->updateUnapprovedCount();
			}

			$this->current_comment = array_merge($this->current_comment, $values);
		}
	}

	public function deleteComment()
	{
		$db = database();

		// First, delete the comment.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_comments
			WHERE id_comment = {int:comment}',
			[
				'comment' => $this->current_comment['id_comment'],
			]
		);

		// If it wasn't approved, deal with it.
		if (!$this->current_comment['approved'])
		{
			$this->updateUnapprovedCount();
		}

		// Next, notify the item.
		$item = $this->getParentItem();
		$success = $this->current_item->deletedComment($this->current_comment['approved']);

		// And the moderation log.
		ModLog::logEvent('delete_comment', ['id_item' => $item['id_item']]);

		// And reported comments
		$reportModel = new Report();
		$reportModel->deleteReportsByComments($this->current_comment['id_comment']);

		// And integration hooks.
		call_integration_hook('integrate_lgal_delete_comment', [$this->current_comment['id_comment']]);

		// Last, nuke the object.
		$this->current_comment = false;

		return $success;
	}

	public function getItemURL()
	{
		$this->getParentItem();
		$items = $this->current_item->getItemURLs();

		return $items['item'];
	}

	public function getCommentURL()
	{
		global $modSettings;

		$db = database();

		if (!empty($this->current_comment['comment_url']))
		{
			return $this->current_comment['comment_url'];
		}

		$criteria = [];
		$criteria[] = '
			id_item = {int:id_item}';
		$criteria[] = '
			id_comment >= {int:id_comment}';
		if (User::$info['is_guest'])
		{
			$criteria[] = '
			approved = {int:approved}';
		}
		elseif (!allowedTo('lgal_manage') && !allowedTo('lgal_approve_comment'))
		{
			$criteria[] = '
			(approved = {int:approved} OR id_author = {int:user_id})';
		}

		$request = $db->query('', '
			SELECT COUNT(id_comment)
			FROM {db_prefix}lgal_comments
			WHERE' . implode(' AND ', $criteria),
			[
				'id_item' => $this->current_comment['id_item'],
				'id_comment' => $this->current_comment['id_comment'],
				'approved' => 1,
				'user_id' => User::$info['id'],
			]
		);
		[$after] = $request->fetch_row();
		$request->free_result();

		$page = ceil($after / $modSettings['lgal_comments_per_page']);
		if ($page == 0)
		{
			$page = 1;
		}

		$this->current_comment['comment_url'] = $this->getItemURL() . ($page != 1 ? 'page-' . $page . '/' : '') . '#comment-' . $this->current_comment['id_comment'];

		return $this->current_comment['comment_url'];
	}

	public function deleteCommentsByItems($item_ids)
	{
		$db = database();

		$item_ids = (array) $item_ids;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_comments
			WHERE id_item IN ({array_int:id_item})',
			[
				'id_item' => $item_ids,
			]
		);
		// We don't have to notify the item since the item should already be more than aware of it...

		// If we're doing this in bulk, we should fix the master counts too.
		$this->updateUnapprovedCount();
	}

	public function updateUnapprovedCount()
	{
		$db = database();

		$request = $db->query('', '
			SELECT COUNT(*)
			FROM {db_prefix}lgal_comments
			WHERE approved = {int:not_approved}',
			[
				'not_approved' => 0,
			]
		);
		[$unapproved] = $request->fetch_row();
		$request->free_result();

		// Also, if we have a cache locally in session, dump it.
		unset ($_SESSION['lgal_uc']);

		updateSettings(['lgal_unapproved_comments' => $unapproved]);
	}
}
