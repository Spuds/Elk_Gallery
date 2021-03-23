<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.0 / elkarte
 */

namespace ElkArte\sources\subs\MentionType;

/**
 * Class Lgnew_Mention
 *
 * Handles mentioning to members for new items added to an album
 */
class Lgnew_Mention extends Mention_BoardAccess_Abstract
{
	/** {@inheritdoc } */
	protected static $_type = 'lgnew';

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
			$keys = array('subject' => 'notify_lgnew_' . $lang_data['subject'], 'body' => 'notify_lgnew_' . $lang_data['body']);
		}

		$notifier = $this->_task->getNotifierData();
		$replacements = array(
			'POSTERNAME' => $notifier['real_name'],
			'ITEMNAME' => $this->_task['source_data']['subject'],
			'ITEMLINK' => $this->_task['source_data']['url'],
		);

		return $this->_getNotificationStrings('notify_lgnew',
			$keys,
			$members,
			$this->_task,
			array('levgal_lng/LevGal-Email'),
			$replacements
		);
	}

	/**
	 * "template" for what will appear in the notifications new items gallery tab
	 *
	 * @param string $type
	 * @param mixed[] $mentions
	 * @return bool
	 */
	public function view($type, &$mentions)
	{
		global $txt;

		$itemModel = \LevGal_Bootstrap::getModel('LevGal_Model_Item');

		foreach ($mentions as $key => $row)
		{
			// To ensure it is not done twice
			if (empty(static::$_type) || $row['mention_type'] !== static::$_type)
			{
				continue;
			}

			$item_details = $itemModel->getItemInfoById($row['id_target']);

			// These are associated to gallery items and require album permission checks
			$current_album = new \LevGal_Model_Album();
			$album_details = $current_album->getAlbumById($item_details['id_album']);
			if ($current_album->isVisible() && $item_details['approved'])
			{
				$mentions[$key]['message'] = '<a href="' . $item_details['item_url'] . '">' .
					sprintf($txt['levgal_album_added_new'], $item_details['item_name'], $album_details['album_name']) . '</a>';
			}
			else
			{
				unset($mentions[$key]);
			}
		}

		return true;
	}

	/**
	 * Simply unsubscribes a member from a given item comment notification
	 *
	 * @param array $member
	 * @param string $area
	 * @param int $itemID
	 * @return bool
	 */
	public function unsubscribe($member, $area, $itemID)
	{
		if ($area === 'lgnew')
		{
			$itemModel = \LevGal_Bootstrap::getModel('LevGal_Model_Item');
			$item_details = $itemModel->getItemInfoById($itemID);

			// No need to see if its on
			$notify = new \LevGal_Model_Notify();
			$notify->unsetNotifyAlbum($item_details['id_album'], $member['id_member']);
		}

		return true;
	}
}
