<?php

/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace ElkArte\Mentions\MentionType\Notification;

use ElkArte\Mentions\MentionType\AbstractNotificationMessage;

/**
 * Class Lgnew_Mention
 *
 * Handles mentioning to members for new items added to an album
 */
class Lgnew extends AbstractNotificationMessage
{
	/** {@inheritdoc } */
	protected static $_type = 'lgnew';

	/**
	 * {@inheritdoc }
	 */
	public function getNotificationBody($lang_data, $members)
	{
		if (empty($lang_data['suffix']))
		{
			// Site notification
			return $this->_getNotificationStrings('', ['subject' => static::$_type, 'body' => static::$_type], $members, $this->_task);
		}

		// Some form of email
		$keys = ['subject' => 'notify_lgnew_' . $lang_data['subject'], 'body' => 'notify_lgnew_' . $lang_data['body']];

		$notifier = $this->_task->getNotifierData();
		$replacements = [
			'POSTERNAME' => $notifier['real_name'],
			'ITEMNAME' => $this->_task['source_data']['subject'],
			'ITEMLINK' => $this->_task['source_data']['url'],
		];

		return $this->_getNotificationStrings('notify_lgnew',
			$keys,
			$members,
			$this->_task,
			['levgal_lng/LevGal-Email'],
			$replacements
		);
	}

	/**
	 * What notification methods do we support?
	 */
	public static function isNotAllowed($method)
	{
		// onsite and email are the only ones supported
		return $method === 'emaildaily' || $method === 'emailweekly';
	}
}
