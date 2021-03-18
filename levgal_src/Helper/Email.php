<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with sending emails, as a simple replacement for loadEmailTemplate
 * which relies on our own templates only.
 */
class LevGal_Helper_Email
{
	/** @var array */
	private $template;
	/** @var array */
	private $recipients;
	/** @var array */
	private $replacements;

	public function __construct($template)
	{
		global $context, $txt;

		// We will need this elsewhere.
		require_once(SUBSDIR . '/Post.subs.php');

		$this->template = $template;

		$this->addReplacement('FORUMNAME', $context['forum_name']);
		$this->addReplacement('REGARDS', $txt['regards_team']);
	}

	public function addRecipient($user_name, $email, $language)
	{
		$this->recipients[$language][] = array($user_name, $email);
	}

	public function addReplacement($search_key, $replacement)
	{
		$this->replacements['{' . $search_key . '}'] = $replacement;
	}

	public function getMemberDetails($users)
	{
		global $language;

		$db = database();

		if (empty($users))
		{
			return;
		}

		$request = $db->query('', '
			SELECT 
				id_member, real_name, email_address, lngfile
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:users})',
			array(
				'users' => $users,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$this->addRecipient($row['real_name'], $row['email_address'], !empty($row['lngfile']) ? $row['lngfile'] : $language);
		}
	}

	public function sendEmails()
	{
		global $txt;

		// This is needed for sendmail().
		require_once(SUBSDIR . '/Mail.subs.php');

		if (empty($this->recipients))
		{
			return;
		}

		foreach ($this->recipients as $language => $recipients)
		{
			// 1. Load the language file for whatever language we need.
			loadLanguage('levgal_lng/LevGal-Email', $language, false, true);

			// 2. Get the bits we need.
			$subject = $txt['lgal_email_subject_' . $this->template];
			$body = $txt['lgal_email_body_' . $this->template];

			foreach ($recipients as $recipient)
			{
				// 3. Perform some replacements.
				$replace = array_merge($this->replacements, array(
					'{USERNAME}' => $recipient[0],
					'{EMAIL}' => $recipient[1], // Unlikely it'll ever be used but might as well if we have it.
				));
				$subject = strtr($subject, $replace);
				$body = strtr($body, $replace);

				// 4. And send.
				sendmail($recipient[1], $subject, $body);
			}
		}
	}
}
