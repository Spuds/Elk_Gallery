<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file provides the tag cloud pages, site/?media/tag/cloud/ and the list of items per tag site/?media/tag/my-tag.1/.
 */
class LevGal_Action_Tag extends LevGal_Action_Abstract
{
	public function __construct()
	{
		global $context;

		parent::__construct();

		$tagModel = LevGal_Bootstrap::getModel('LevGal_Model_Tag');
		$context['tags'] = $tagModel->getTagCloud();

		// We need us some tags.
		if (empty($context['tags']))
		{
			LevGal_Helper_Http::fatalError('error_no_tags');
		}
	}

	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		list ($tag_slug, $tag_id) = $this->getSlugAndId();
		$tag_list = array();
		if (!empty($tag_id))
		{
			$tagModel = LevGal_Bootstrap::getModel('LevGal_Model_Tag');
			$tag_list = $tagModel->getItemsByTagId($tag_id);
		}

		if (empty($tag_list) || empty($tag_list['items']))
		{
			LevGal_Helper_Http::fatalError('error_no_tags');
		}

		$context['page_title'] = $txt['lgal_tagged_as'] . ' ' . $tag_list['tag_name'];
		$context['canonical_url'] = $scripturl . '?media/tag/' . (empty($tag_list['tag_slug']) ? $tag_id : $tag_list['tag_slug'] . '.' . $tag_id) . '/';
		$context['selected_tag'] = $tag_id;
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_tagcloud'], '?media/tag/cloud/');
		$this->addLinkTree($context['page_title'], '?media/tag/' . (empty($tag_list['tag_slug']) ? $tag_id : $tag_list['tag_slug'] . '.' . $tag_id) . '/');
		$this->setTemplate('LevGal-Tags', 'tagmain');

		if ($tag_slug != $tag_list['tag_slug'])
		{
			LevGal_Helper_Http::hardRedirect($context['canonical_url']);
		}

		$context['tagged_items'] = $tag_list['items'];
	}

	public function actionCloud()
	{
		global $context, $txt, $scripturl;

		// Stuff we will need
		$this->setTemplate('LevGal-Tags', 'tagcloud');
		$this->addStyleSheets('jqcloud/jqcloud.css');

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_tagcloud'], '?media/tag/cloud/');
		$context['canonical_url'] = $scripturl . '?media/tag/cloud/';
		$context['selected_tag'] = false;

		$context['page_title'] = $txt['levgal_tagcloud'];

		// We need this in a slightly different format for exporting purposes.
		$context['json_export'] = array();
		foreach ($context['tags'] as $tag)
		{
			$context['json_export'][] = array('text' => $tag['name'], 'weight' => $tag['count'], 'link' => $tag['url']);
		}
	}
}
