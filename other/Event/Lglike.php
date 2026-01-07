<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace ElkArte\Mentions\MentionType\Event;

use Addons\Levertine\Source\Model\Album;
use ElkArte\Mentions\MentionType\AbstractEventBoardAccess;
use Addons\Levertine\Source\LevGalBootstrap;

/**
 * Class Lglike
 *
 * Handles the notification of gallery likes.
 */
class Lglike extends AbstractEventBoardAccess
{
	/** {@inheritdoc} */
	protected static $_type = 'lglike';

	/**
	 * "template" for what will appear in the notifications like gallery tab
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
			$current_album->getAlbumById($item_details['id_album']);
			if ($item_details['approved'] && $current_album->isVisible())
			{
				$details = 'lgread=' . $row['id_mention'] . '/' . $context['session_var'] . '=' . $context['session_id'] . '/';

				$mentions[$key]['message'] = '<a href="' . $item_details['item_url'] . $details . '">' . $txt['lgal_liked_your'] . ' ' . $item_details['item_name'] . '</a>';
			}
			else
			{
				unset($mentions[$key]);
			}
		}

		return true;
	}
}
