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
 * Class Lgcomment
 *
 * Handles notification of members whose gallery items have been commented on
 */
class Lgcomment extends AbstractNotificationMessage
{
	/** {@inheritdoc } */
	protected static $_type = 'lgcomment';

	/**
	 * {@inheritdoc }
	 */
	public function getNotificationBody($lang_data, $members)
	{
		if (empty($lang_data['suffix']))
		{
			// Site notification
			return $this->_getNotificationStrings('', [
				'subject' => static::$_type,
				'body' => static::$_type],
				$members, $this->_task);
		}

		// Some form of email
		$keys = [
			'subject' => 'notify_lgcomment_' . $lang_data['subject'],
			'body' => 'notify_lgcomment_' . $lang_data['body']
		];

		$notifier = $this->_task->getNotifierData();
		$replacements = [
			'POSTERNAME' => $notifier['real_name'],
			'ITEMNAME' => $this->_task['source_data']['subject'],
			'COMMENTLINK' => $this->_task['source_data']['url'],
		];

		return $this->_getNotificationStrings('notify_lgcomment',
			$keys,
			$members,
			$this->_task,
			['Levertine/LevGal-Email'],
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
