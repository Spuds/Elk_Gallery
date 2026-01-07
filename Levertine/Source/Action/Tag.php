<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\Helper\Http;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Tag as TagModel;

/**
 * This file provides the tag cloud pages, site/?media/tag/cloud/ and the list of
 * items per tag site/?media/tag/my-tag.1/.
 */
class Tag extends LevGalAbstract
{
	public function __construct()
	{
		global $context, $txt;

		parent::__construct();

		$tagModel = LevGalBootstrap::getModel('Tag');
		/** @var $tagModel TagModel */
		$context['tags'] = $tagModel->getTagCloud();

		// We need us some tags.
		if (empty($context['tags']))
		{
			$context['tags'] = [0 => [
				'name' => $txt['levgal_tagcloud_none'],
				'url' => '',
				'count' => 0]
			];
		}
	}

	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		[$tag_slug, $tag_id] = $this->getSlugAndId();
		$tag_list = [];
		if (!empty($tag_id))
		{
			$tagModel = LevGalBootstrap::getModel('Tag');
			$tag_list = $tagModel->getItemsByTagId($tag_id);
		}

		if (empty($tag_list) || empty($tag_list['items']))
		{
			Http::fatalError('error_no_tags');
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
			Http::hardRedirect($context['canonical_url']);
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
		$context['json_export'] = [];
		foreach ($context['tags'] as $tag)
		{
			$context['json_export'][] = ['text' => $tag['name'], 'weight' => $tag['count'], 'link' => $tag['url']];
		}
	}
}
