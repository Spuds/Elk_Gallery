<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

namespace ElkArte\sources\subs\MentionType;

/**
 * Class Lgcomment_Mention
 *
 * Handles mentioning of members whose gallery items have been commented on
 */
class Lgcomment_Mention extends Mention_BoardAccess_Abstract
{
	/** {@inheritdoc } */
	protected static $_type = 'lgcomment';

	/** {@inheritdoc } */
	protected static $_frequency = ['notification', 'email'];

	/**
	 * {@inheritdoc }
	 */
	public function getUsersToNotify()
	{
		return (array) $this->_task['source_data']['id_members'];
	}

	/**
	 * {@inheritdoc }
	 */
	public function getNotificationBody($lang_data, $members)
	{
		if (empty($lang_data['suffix']))
		{
			// Site notification
			return $this->_getNotificationStrings('', array('subject' => static::$_type, 'body' => static::$_type), $members, $this->_task);
		}
		else
		{
			// Some form of email
			$keys = array('subject' => 'notify_lgcomment_' . $lang_data['subject'], 'body' => 'notify_lgcomment_' . $lang_data['body']);
		}

		$notifier = $this->_task->getNotifierData();
		$replacements = array(
			'POSTERNAME' => $notifier['real_name'],
			'ITEMNAME' => $this->_task['source_data']['subject'],
			'COMMENTLINK' => $this->_task['source_data']['url'],
		);

		return $this->_getNotificationStrings('notify_lgcomment',
			$keys,
			$members,
			$this->_task,
			array('levgal_lng/LevGal-Email'),
			$replacements
		);
	}

	/**
	 * "template" for what will appear in the notifications comments gallery tab
	 *
	 * @param string $type
	 * @param array $mentions
	 * @return bool
	 */
	public function view($type, &$mentions)
	{
		global $txt;

		$commentModel = \LevGal_Bootstrap::getModel('LevGal_Model_Comment');

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
				$mentions[$key]['message'] = '<a href="' . $commentModel->getCommentURL() . '">' . $txt['levgal_comment_on'] . ' ' . $item_details['item_name'] . '</a>';
			}
			else
			{
				unset($mentions[$key]);
			}
		}

		return true;
	}

	/**
	 * Unsubscribes a member from a given item comment notification
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
			$notify = new \LevGal_Model_Notify();
			$notify->unsetNotifyItem($itemID, $member['id_member']);
		}

		return true;
	}
}
