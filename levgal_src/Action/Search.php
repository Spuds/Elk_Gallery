<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides the search page, site/?media/search/.
 */
class LevGal_Action_Search extends LevGal_Action_Abstract
{
	public function actionIndex()
	{
		global $context, $txt, $scripturl, $user_profile;

		$this->setTemplate('LevGal-Search', 'search');
		loadLanguage('levgal_lng/LevGal-Search');

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_search'], '?media/search/');
		$context['canonical_url'] = $scripturl . '?media/search/';
		$context['form_url'] = $scripturl . '?media/search/';

		$context['page_title'] = $txt['levgal_search'];

		// Now we need to get the albums.
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['hierarchies'] = $album_list->getAllHierarchies();
		$context['album_count'] = 0;

		if (!empty($context['hierarchies']['site']))
		{
			$context['album_count'] += count($context['hierarchies']['site']);
		}

		foreach (array('member', 'group') as $type)
		{
			if (!empty($context['hierarchies'][$type]))
			{
				foreach ($context['hierarchies'][$type] as $hierarchy)
				{
					$context['album_count'] += count($hierarchy['albums']);
				}
			}
		}

		$context['search_types'] = $this->getSearchTypes();

		$cfModel = LevGal_Bootstrap::getModel('LevGal_Model_Custom');
		$context['searchable_fields'] = $cfModel->getSearchableFields();

		$context += $this->getDefaultValues();

		if (!empty($context['existing_search']))
		{
			$context = array_merge($context, $context['existing_search']);
			unset ($context['existing_search']);
		}

		if (!empty($context['search_member']))
		{
			$loaded = loadMemberData($context['search_member'], false, 'minimal');
			foreach ($loaded as $member)
			{
				$context['search_member_display'][$member] = $user_profile[$member]['real_name'];
			}
		}

		if (!empty($_POST['submit']))
		{
			checkSession();

			// Get search text and type
			$context['search_text'] = LevGal_Helper_Sanitiser::sanitiseTextFromPost('search');

			// Get what we're searching in
			foreach (array('search_album_names', 'search_item_names', 'search_item_descs') as $type)
			{
				$context[$type] = !empty($_POST[$type]);
			}

			// Get member searching.
			list ($context['search_member'], $context['search_member_display']) = (new LevGal_Model_Member())->getFromAutoSuggest('search_member');

			// Get selected custom fields
			$context['selected_fields'] = array();
			foreach ($context['searchable_fields'] as $field)
			{
				if (!empty($_POST['search_field_' . $field['id_field']]))
				{
					$context['selected_fields'][] = $field['id_field'];
				}
			}

			// Get selected item types
			$context['selected_search_types'] = array();
			foreach ($context['search_types'] as $type)
			{
				if (!empty($_POST['search_' . $type]))
				{
					$context['selected_search_types'][] = $type;
				}
			}

			// Get selected album list
			$context['all_albums'] = true;

			$albums = isset($_POST['alb']) && is_array($_POST['alb']) ? $_POST['alb'] : array();
			$selected_albums = array();
			if (!empty($context['hierarchies']['site']))
			{
				foreach (array_keys($context['hierarchies']['site']) as $album_id)
				{
					if (!empty($albums['site_' . $album_id]))
					{
						$selected_albums[$album_id] = true;
					}
					else
					{
						$context['all_albums'] = false;
					}
				}
			}
			foreach (array('member', 'group') as $type)
			{
				foreach ($context['hierarchies'][$type] as $hier_id => $hierarchy)
					{
						foreach (array_keys($hierarchy['albums']) as $album_id)
						{
							if (!empty($albums[$type . '_' . $hier_id . '_' . $album_id]))
							{
								$selected_albums[$album_id] = true;
							}
							else
							{
								$context['all_albums'] = false;
							}
						}
					}
			}
			$context['selected_albums'] = array_keys($selected_albums);

			// And now we do the testing to see if we are actually going to do any searching or not.
			$context['errors'] = array();

			if (empty($context['search_text']))
			{
				$context['errors'][] = $txt['lgal_error_no_text'];
			}
			if (empty($context['selected_albums']))
			{
				$context['errors'][] = $txt['lgal_error_no_albums'];
			}
			if (empty($context['selected_search_types']))
			{
				$context['errors'][] = $txt['lgal_error_no_filetypes'];
			}
			if (empty($context['search_album_names']) && empty($context['search_item_names']) && empty($context['search_item_descs']) && empty($context['selected_fields']))
			{
				$context['errors'][] = $txt['lgal_error_no_search'];
			}

			if (empty($context['errors']))
			{
				$this->performSearch();
			}
		}
	}

	public function actionRefine()
	{
		global $context, $scripturl;

		$search_id = $this->getNumericId();
		$search_details = null;
		if (!empty($search_id))
		{
			$search = new LevGal_Model_Search();
			$search_details = $search->fetchSearchResult($search_id);
		}

		if (empty($search_details))
		{
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/search/');
		}

		$context['existing_search'] = $search_details;

		$this->actionIndex();
	}

	public function actionResult()
	{
		global $context, $txt, $scripturl;

		loadLanguage('levgal_lng/LevGal-Search');

		$search_id = $this->getNumericId();
		if (!empty($search_id))
		{
			$search = new LevGal_Model_Search();
			$search_details = $search->fetchSearchResult($search_id);
		}

		if (empty($search_details))
		{
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/search/');
		}

		$context['show_albums'] = !empty($search_details['search_album_names']);
		if (!empty($search_details['results']['albums']))
		{
			$albumList = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$results = $albumList->getAlbumsById($search_details['results']['albums']);
			$context['search_albums'] = array();
			foreach ($search_details['results']['albums'] as $album)
			{
				if (isset($results[$album]))
				{
					$context['search_albums'][$album] = $results[$album];
				}
			}
		}

		$context['show_items'] = !empty($search_details['search_item_names']) || !empty($search_details['search_item_descs']) || !empty($search_details['selected_fields']);
		if (!empty($search_details['results']['items']))
		{
			$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
			$results = $itemList->getItemsById($search_details['results']['items']);
			$context['search_items'] = array();
			foreach ($search_details['results']['items'] as $item)
			{
				if (isset($results[$item]))
				{
					$context['search_items'][$item] = $results[$item];
				}
			}
		}

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_search'], '?media/search/');
		$this->addLinkTree($txt['lgal_search_results'], $scripturl . '?media/search/' . $search_id . '/result/');
		$context['page_title'] = $txt['lgal_search_results'];
		$context['canonical_url'] = $scripturl . '?media/search/' . $search_id . '/result/';
		$context['refine_link'] = $scripturl . '?media/search/' . $search_id . '/refine/';
		if (empty($context['search_albums']) && empty($context['search_items']))
		{
			$this->setTemplate('LevGal-Search', 'no_results');
		}
		else
		{
			$this->setTemplate('LevGal-Search', 'search_results');
		}
	}

	protected function getDefaultValues()
	{
		global $context;
		$selected_fields = array();
		foreach ($context['searchable_fields'] as $field)
		{
			$selected_fields[] = $field['id_field'];
		}

		return array(
			'all_albums' => true,
			'selected_albums' => array(),
			'selected_search_types' => $context['search_types'],
			'search_member' => array(),
			'search_text' => '',
			'selected_fields' => $selected_fields,
			'search_album_names' => true,
			'search_item_names' => true,
			'search_item_descs' => true,
		);
	}

	protected function getSearchTypes()
	{
		global $modSettings;

		$enabled_types = array();
		$types = array('image', 'audio', 'video', 'document', 'archive', 'generic', 'external');
		foreach ($types as $type)
		{
			if (!empty($modSettings['lgal_enable_' . $type]))
			{
				$enabled_types[] = $type;
			}
		}

		return $enabled_types;
	}

	protected function performSearch()
	{
		global $context, $scripturl, $txt;

		$search = new LevGal_Model_Search();
		$criteria = array(
			'search_text',
			'selected_albums',
			'selected_search_types',
			'search_member',
			'selected_fields',
			'search_album_names',
			'search_item_names',
			'search_item_descs',
		);
		foreach ($criteria as $criterion)
		{
			$search->addCriteria($criterion, $context[$criterion]);
		}

		list ($search_id, $has_results) = $search->performSearch();

		if (empty($search_id))
		{
			redirectexit($scripturl . '?media/search/');
		}

		if ($has_results)
		{
			redirectexit($scripturl . '?media/search/' . $search_id . '/result/');
		}

		$this->addLinkTree($txt['lgal_search_results']);
		$context['page_title'] = $txt['lgal_search_results'];
		$context['refine_link'] = $scripturl . '?media/search/' . $search_id . '/refine/';
		$this->setTemplate('LevGal-Search', 'no_results');
	}
}
