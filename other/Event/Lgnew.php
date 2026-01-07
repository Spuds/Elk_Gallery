<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace ElkArte\Mentions\MentionType\Event;

use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Album;
use Addons\Levertine\Source\Model\Notify;
use ElkArte\Mentions\MentionType\AbstractEventBoardAccess;

/**
 * Class Lgnew_Mention
 *
 * Handles mentioning to members for new items added to an album
 */
class Lgnew extends AbstractEventBoardAccess
{
	/** {@inheritdoc } */
	protected static $_type = 'lgnew';

	/**
	 * "template" for what will appear in the notifications new items gallery tab
	 *
	 * @param string $type
	 * @param array $mentions
	 * @return bool
	 */
	public function view($type, &$mentions)
	{
		global $txt, $context;

		$itemModel = LevGalBootstrap::getModel('Item');

		foreach ($mentions as $key => $row)
		{
			// To ensure it is not done twice
			if (empty(static::$_type) || $row['mention_type'] !== static::$_type)
			{
				continue;
			}

			$item_details = $itemModel->getItemInfoById($row['id_target']);

			// These are associated to gallery items and require album permission checks
			$current_album = new Album();
			$album_details = $current_album->getAlbumById($item_details['id_album']);
			if ($item_details['approved'] && $current_album->isVisible())
			{
				$details = 'lgread=' . $row['id_mention'] . '/' . $context['session_var'] . '=' . $context['session_id'] . '/';

				$mentions[$key]['message'] = '<a href="' . $item_details['item_url'] . $details . '">' .
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
			$itemModel = LevGalBootstrap::getModel('Item');
			$item_details = $itemModel->getItemInfoById($itemID);

			// No need to see if its on
			$notify = new Notify();
			$notify->unsetNotifyAlbum($item_details['id_album'], $member['id_member']);
		}

		return true;
	}
}
