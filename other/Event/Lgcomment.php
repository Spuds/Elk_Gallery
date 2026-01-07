<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace ElkArte\Mentions\MentionType\Event;

use Addons\Levertine\Source\Model\Comment;
use Addons\Levertine\Source\Model\Notify;
use ElkArte\Mentions\MentionType\AbstractEventBoardAccess;
use Addons\Levertine\Source\LevGalBootstrap;

/**
 * Class Lgcomment
 *
 * Handles mentioning of members whose gallery items have been commented on
 */
class Lgcomment extends AbstractEventBoardAccess
{
	/** {@inheritdoc} */
	protected static $_type = 'lgcomment';

	/**
	 * "template" for what will appear in the notification comments gallery tab
	 *
	 * @param string $type
	 * @param array $mentions
	 * @return bool
	 */
	public function view($type, &$mentions)
	{
		global $txt, $context;

		/** @var $commentModel Comment */
		$commentModel = LevGalBootstrap::getModel('Comment');

		foreach ($mentions as $key => $row)
		{
			// To ensure it is not done twice
			if (empty(static::$_type) || $row['mention_type'] !== static::$_type)
			{
				continue;
			}

			$comment_details = $commentModel->getCommentById($row['id_target']);

			// These are associated to gallery items and require permission checks
			if ($commentModel->isVisible() && $comment_details['approved'])
			{
				$item_details = $commentModel->getParentItem();
				$details = '/lgread=' . $row['id_mention'] . '/' . $context['session_var'] . '=' . $context['session_id'] . '/';
				$mentions[$key]['message'] = '<a href="' . str_replace('/#', $details . '#', $commentModel->getCommentURL()) . '">' . $txt['levgal_comment_on'] . ' ' . $item_details['item_name'] . '</a>';

				//$mentions[$key]['message'] = '<a href="' . $commentModel->getCommentURL() . '">' . $txt['levgal_comment_on'] . ' ' . $item_details['item_name'] . '</a>';
			}
			else
			{
				unset($mentions[$key]);
			}
		}

		return true;
	}

	/**
	 * Unsubscribes a member from a given item comments, triggered from an email unsubscribe link
	 *
	 * Called from \ElkArte\Mentions\MentionType\Event::unsubscribe() via _unsubscribeModuleToggle
	 *
	 * @param array $member
	 * @param string $area
	 * @param int $itemID
	 * @return bool
	 */
	public function unsubscribe($member, $area, $itemID)
	{
		if ($area === 'lgcomment')
		{
			// No need to see if its on
			$notify = new Notify();
			$notify->unsetNotifyItem($itemID, $member['id_member']);
		}

		return true;
	}
}
