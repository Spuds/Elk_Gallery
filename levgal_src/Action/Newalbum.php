<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file provides the handling for new albums, site/?media/newalbum/.
 */
class LevGal_Action_Newalbum extends LevGal_Action_Abstract
{
	public function __construct()
	{
		global $context;

		// This means we load useful resources.
		parent::__construct();

		// Permissions check real quick.
		if (!allowedTo(array('lgal_adduseralbum', 'lgal_addgroupalbum', 'lgal_manage')))
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_addalbum');
		}

		// There are certain things we will need to have set up to make this work.
		$albumModel = new LevGal_Model_Album();
		$context['ownership_opts'] = $albumModel->getOwnershipOptions();

		// Now we need a list of groups, partly for ownership, partly for privacy.
		$context['group_list'] = $albumModel->getAllowableOwnershipGroups();
		$context['access_list'] = $albumModel->getAllowableAccessGroups();

		if (empty($context['group_list']))
		{
			$context['ownership_opts'] = array_diff($context['ownership_opts'], array('group'));
		}
	}

	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		// Linktree and other errata
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_newalbum'], '?media/newalbum/');

		$this->setTemplate('LevGal-NewAlbum', 'newalbum', 'admin');
		loadLanguage('levgal_lng/LevGal-AlbumEdit');

		$context['page_title'] = $txt['levgal_newalbum'];
		$context['canonical_url'] = $scripturl . '?media/newalbum/';
		$context['destination'] = $scripturl . '?media/newalbum/save/';

		// Now set up some defaults
		if (!isset($context['album_name']))
		{
			$this->getDefaults();
		}
	}

	protected function getDefaults()
	{
		global $context, $user_info;

		$context['album_name'] = '';
		$context['album_slug'] = '';
		$context['ownership'] = in_array('member', $context['ownership_opts']) ? 'member' : (in_array('group', $context['ownership_opts']) ? 'group' : 'site');
		// Take the user's primary group as default
		$context['primary_group'] = (int) $user_info['groups'][0];
		$context['ownership_group'] = $context['primary_group'];
		$context['privacy'] = 'members';
		$context['privacy_group'] = array($context['primary_group']);
	}

	public function actionSave()
	{
		global $context;

		$this->getDefaults();

		checkSession();

		// First, the album name.
		$context['album_name'] = LevGal_Helper_Sanitiser::sanitiseThingNameFromPost('album_name');

		// Next, dust off the slug.
		$context['album_slug'] = LevGal_Helper_Sanitiser::sanitiseSlugFromPost('album_slug');

		// Next, ownership
		$default_ownership = $context['ownership'];
		$context['ownership'] = isset($_POST['ownership']) && in_array($_POST['ownership'], $context['ownership_opts']) ? $_POST['ownership'] : $default_ownership;
		if ($context['ownership'] === 'group')
		{
			$context['ownership_group'] = isset($_POST['ownership_group'], $context['group_list'][$_POST['ownership_group']]) ? (int) $_POST['ownership_group'] : $context['primary_group'];
		}

		// Next, privacy
		$context['privacy'] = isset($_POST['privacy']) && in_array($_POST['privacy'], array('guests', 'members', 'justme', 'custom')) ? $_POST['privacy'] : 'justme'; // While the default is members, if in doubt, revert to 'just me' for safety.
		if ($context['privacy'] === 'custom')
		{
			$groups = isset($_POST['privacy_group']) && is_array($_POST['privacy_group']) ? $_POST['privacy_group'] : array();
			foreach ($groups as $k => $v)
			{
				$v = (int) $v;
				if ($v < 0)
				{
					unset ($groups[$k]);
				}
				$groups[$k] = $v;
			}
			// We don't need to record admin access even though the form has it
			// because, frankly, that's for show (group 1 auto has access anyway)
			$context['privacy_group'] = array_diff(array_unique($groups), array(1));
			// And attach it against actual groups.
			$context['privacy_group'] = array_intersect($context['privacy_group'], array_keys($context['access_list']));
		}

		// I think we might be done here. Let's just see if we have a name (since we have sane values
		// for everything else) and if so, we'll hand off to the model to create it.
		if (empty($context['album_name']))
		{
			$context['errors'][] = 'levgal_no_album_name';

			return $this->actionIndex();
		}

		$approved = allowedTo(array('lgal_manage', 'lgal_addalbum_approve'));

		$album = new LevGal_Model_Album();
		$album->createAlbum($context['album_name'], $context['album_slug'], $approved);
		switch ($context['ownership'])
		{
			case 'group':
				$album->setAlbumOwnership($context['ownership'], $context['ownership_group']);
				break;
			case 'site':
				$album->setAlbumOwnership($context['ownership'], 0);
				break;
			default:
				$album->setAlbumOwnership($context['ownership'], (int) $context['user']['id']);
		}
		$album->setAlbumPrivacy($context['privacy'], $context['privacy_group']);

		// Now, depending on a few things, we might have to change this album.
		// $editable in this context means we will be able to edit it any time. If we can't do that,
		// we need to allow the user the choice to edit it as a 'finalising' operation because of
		// the two-stage thing we do. So if that's the case, we need to flag it as editable by owner.
		$editable = allowedTo(array('lgal_manage', 'lgal_edit_album_any', 'lgal_edit_album_own'));
		if (!$editable)
		{
			$album->updateAlbum(array('editable' => 1));
		}

		// And now we want to go to the album editing page.
		$albumUrl = $album->getAlbumUrl();
		if ($albumUrl !== false)
		{
			// If they can't edit it permanently, take them to the page where they can edit it.
			// If they can edit it permanently, take them back to the album itself and they can
			// change it later if they want.
			if (!$editable)
			{
				redirectexit($albumUrl . 'edit/');
			}
			else
			{
				redirectexit($albumUrl);
			}
		}
		else
		{
			LevGal_Helper_Http::fatalError('cannot_lgal_addalbum');
		}
	}
}
