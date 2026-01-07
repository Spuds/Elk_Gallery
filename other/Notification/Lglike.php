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
 * Class Lglike_Mention
 *
 * Handles the notification of gallery likes.
 */
class Lglike extends AbstractNotificationMessage
{
	/** {@inheritdoc } */
	protected static $_type = 'lglike';

	/**
	 * We only support site notification here (no template)
	 */
	public function getNotificationBody($lang_data, $members)
	{
		return $this->_getNotificationStrings('', array('subject' => static::$_type), $members, $this->_task);
	}

	/**
	 * What notification methods do we support?
	 */
	public static function isNotAllowed($method)
	{
		// onsite is the only one supported
		return $method !== 'notification';
	}
}
