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
 * Class Lglike_Mention
 *
 * Handles the notification of gallery likes.
 */
class Lglike_Mention extends Mention_BoardAccess_Abstract
{
	/** {@inheritdoc } */
	protected static $_type = 'lglike';

	/** {@inheritdoc } */
	protected static $_frequency = ['notification'];

	/**
	 * {@inheritdoc }
	 */
	public function getUsersToNotify()
	{
		return (array) $this->_task['source_data']['id_members'];
	}

	/**
	 * We only support site notification here (no template)
	 */
	public function getNotificationBody($lang_data, $members)
	{
		return $this->_getNotificationStrings('', array('subject' => static::$_type), $members, $this->_task);
	}

	/**
	 * "template" for what will appear in the notifications like gallery tab
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
			$current_album->getAlbumById($item_details['id_album']);
			if ($current_album->isVisible() && $item_details['approved'])
			{
				$mentions[$key]['message'] = '<a href="' . $item_details['item_url'] . '">' . $txt['lgal_liked_your'] . ' ' . $item_details['item_name'] . '</a>';
			}
			else
			{
				unset($mentions[$key]);
			}
		}

		return true;
	}
}
