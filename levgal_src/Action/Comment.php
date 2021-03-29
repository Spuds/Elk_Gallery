<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file provides the handling for comments-related behaviour, site/?media/comment/*.
 */
class LevGal_Action_Comment extends LevGal_Action_Abstract
{
	/** @var int */
	private $comment_id;
	/** @var \LevGal_Model_Comment */
	private $comment_obj;

	public function __construct()
	{
		global $context;

		// This means we load useful resources.
		parent::__construct();

		$this->comment_id = $this->getNumericId();
		$this->comment_obj = new LevGal_Model_Comment();
		$context['comment_details'] = $this->comment_obj->getCommentById($this->comment_id);

		// Does it exist? Can they see it?
		if (!$context['comment_details'] || !$this->comment_obj->isVisible())
		{
			LevGal_Helper_Http::fatalError('error_lgal_no_comment');
		}
	}

	public function actionIndex()
	{
		// So if you go to ?media/comment/1, it will redirect you appropriate, how cool is that?
		redirectexit($this->comment_obj->getCommentURL());
	}

	public function actionFlag()
	{
		global $user_info, $context, $txt, $scripturl;

		if ($user_info['is_guest'])
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_flag_comment');
		}

		loadLanguage('levgal_lng/LevGal-Moderation');

		// So now we're setting up for flagging.
		$context['item_details'] = $this->comment_obj->getParentItem();
		$album = new LevGal_Model_Album();
		$album->getAlbumById($context['item_details']['id_album']);

		$album_linktree = $album->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_linktree['name'], $album_linktree['url']);
		$this->addLinkTree($context['item_details']['item_name'], $context['item_details']['item_url']);
		$this->addLinkTree($txt['levgal_flag_comment'], '?media/comment/' . $context['comment_details']['id_comment'] . '/');

		$context['page_title'] = $txt['levgal_flag_comment'];
		$context['canonical_url'] = $scripturl . '?media/comment/' . $context['comment_details']['id_comment'] . '/flag/';

		$this->setTemplate('LevGal-Comment', 'flagcomment');

		$context['form_url'] = $scripturl . '?media/comment/' . $context['comment_details']['id_comment'] . '/flag/';

		$report = LevGal_Helper_Sanitiser::sanitiseTextFromPost('report_body');
		if (!empty($report))
		{
			// Saving?
			checkSession();

			$reportModel = new LevGal_Model_Report();
			$reportModel->createCommentReport($context['comment_details']['id_comment'],
				array(
					'id_member' => $user_info['id'],
					'member_name' => $user_info['name'],
					'email_address' => $user_info['email'],
					'ip_address' => $user_info['ip'],
					'body' => $report,
				)
			);
			$_SESSION['lgal_rep']['c' . $context['comment_details']['id_comment']] = true;
			redirectexit($this->comment_obj->getCommentURL());
		}
	}

	public function actionApprove()
	{
		$this->handleApprove();
		redirectexit($this->comment_obj->getCommentURL());
	}

	public function actionApprove_unapproved()
	{
		global $scripturl;
		$this->handleApprove();
		redirectexit($scripturl . '?media/moderate/unapproved_comments/');
	}

	protected function handleApprove()
	{
		global $modSettings;

		// First, permission check. We already know we can see the comment, whatever comment it is.
		// But do we have permission to approve it?
		if (!allowedTo('lgal_manage'))
		{
			// Maybe the user can approve it by way of it being their item?
			if (empty($modSettings['lgal_selfmod_approve_comment']) || !$this->comment_obj->itemIsOwnedByUser())
			{
				loadLanguage('levgal_lng/LevGal-Errors');
				isAllowedTo('lgal_approve_comment');
			}
		}

		// Now session check.
		checkSession('get');

		// Do the approval.
		$this->comment_obj->approveComment();
	}

	public function actionEdit()
	{
		global $modSettings, $context, $txt, $scripturl;

		if (!allowedTo('lgal_manage'))
		{
			if (!allowedTo('lgal_edit_comment_any') && (!allowedTo('lgal_edit_comment_own') || !$this->comment_obj->isOwnedByUser()))
			{
				// Maybe the user can be a moderator of sorts?
				if (empty($modSettings['lgal_selfmod_edit_comment']) || !$this->comment_obj->isOwnedByUser())
				{
					loadLanguage('levgal_lng/LevGal-Errors');
					is_not_guest($txt['cannot_lgal_edit_comment']);
					LevGal_Helper_Http::fatalError('cannot_lgal_edit_comment');
				}
			}
		}

		// So now we're setting up for editing.
		$item_details = $this->comment_obj->getParentItem();
		$album = new LevGal_Model_Album();
		$album->getAlbumById($item_details['id_album']);

		$album_linktree = $album->getLinkTreeDetails();
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($album_linktree['name'], $album_linktree['url']);
		$this->addLinkTree($item_details['item_name'], $item_details['item_url']);
		$this->addLinkTree($txt['levgal_edit_comment'], '?media/comment/' . $context['comment_details']['id_comment'] . '/');

		$context['page_title'] = $txt['levgal_edit_comment'];
		$context['canonical_url'] = $scripturl . '?media/comment/' . $context['comment_details']['id_comment'] . '/edit/';

		$context['editing_guest'] = allowedTo('lgal_manage') && empty($context['comment_details']['id_author']);

		$this->setTemplate('LevGal-Comment', 'editcomment');

		$item_link = '<a href="' . $item_details['item_url'] . '">' . $item_details['item_name'] . '</a>';
		$author_link = empty($context['comment_details']['id_author']) ? $context['comment_details']['author_name'] : '<a href="' . $scripturl . '?action=profile;u=' . $context['comment_details']['id_author'] . '">' . $context['comment_details']['author_name'] . '</a>';
		$context['display_title'] = sprintf($txt['levgal_edit_comment_full'], $item_link, $author_link);

		$context['comment_box'] = new LevGal_Helper_Richtext('lgal_commentbox');

		// If there's something to save, save it (and if we're done, exit there.
		if (isset($_POST['lgal_commentbox']))
		{
			$this->saveEdit();
		}

		$context['comment_box']->createEditor(array(
			'value' => $context['comment_box']->getForForm($context['comment_details']['comment']),
			'labels' => array(
				'post_button' => $txt['levgal_save_comment'],
			),
		));

		$context['form_url'] = $scripturl . '?media/comment/' . $context['comment_details']['id_comment'] . '/edit/';
	}

	private function saveEdit()
	{
		global $context;

		// Still in session?
		if (checkSession('post', '', false) !== '')
		{
			$context['comment_errors'][] = 'session_timeout';
		}

		// The user needed to type something in and it needed to contain something useful.
		if ($context['comment_box']->isEmpty() || !$context['comment_box']->sanitizeContent())
		{
			$context['comment_errors'][] = 'no_comment';
		}

		// Are we editing a guest post?
		if (!empty($context['editing_guest']))
		{
			list($valid_username, $context['comment_details']['author_name']) = LevGal_Helper_Sanitiser::sanitiseUsernameFromPost('author_name');
			list($valid_email, $context['comment_details']['author_email']) = LevGal_Helper_Sanitiser::sanitiseEmailFromPost('author_email');
			if (!$valid_username)
			{
				$context['comment_errors'][] = 'invalid_user';
			}
			if (!$valid_email)
			{
				$context['comment_errors'][] = 'invalid_email';
			}
		}

		// No errors? Let's save this then and be on our way.
		if (empty($context['comment_errors']))
		{
			$changes = array(
				'comment' => $context['comment_box']->getForDB(),
				'modified_name' => $context['user']['name'],
			);
			if (!empty($context['editing_guest']))
			{
				$changes['author_name'] = $context['comment_details']['author_name'];
				$changes['author_email'] = $context['comment_details']['author_email'];
			}
			$this->comment_obj->updateComment($changes);
			redirectexit($this->comment_obj->getCommentURL());
		}
		else
		{
			// This is going to fall back through but we have to update the local version of the comment suitably for the editor.
			$context['comment_details']['comment'] = $context['comment_box']->getForDB();
		}
	}

	public function actionDelete()
	{
		$item_url = $this->handleDelete();
		redirectexit($item_url);
	}

	public function actionDelete_unapproved()
	{
		global $scripturl;

		$this->handleDelete();
		redirectexit($scripturl . '?media/moderate/unapproved_comments/');
	}

	protected function handleDelete()
	{
		global $txt, $modSettings;

		// Permissions. Funky.
		if (!allowedTo('lgal_manage'))
		{
			if (!allowedTo('lgal_delete_comment_any') && (!allowedTo('lgal_delete_comment_own') || !$this->comment_obj->isOwnedByUser()))
			{
				// Maybe the user can be a moderator of sorts?
				if (empty($modSettings['lgal_selfmod_delete_comment']) || !$this->comment_obj->isOwnedByUser())
				{
					loadLanguage('levgal_lng/LevGal-Errors');
					is_not_guest($txt['cannot_lgal_delete_comment']);
					LevGal_Helper_Http::fatalError('cannot_lgal_delete_comment');
				}
			}
		}

		checkSession('get');
		$item_url = $this->comment_obj->getItemURL(); // This sort of won't exist in just a moment.
		$this->comment_obj->deleteComment();

		// In case we want this.
		return $item_url;
	}
}
