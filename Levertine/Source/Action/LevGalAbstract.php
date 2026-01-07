<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use ElkArte\Helper\HttpReq;
use ElkArte\Languages\Txt;

/**
 * This file provides base functionality to all actions.
 */
abstract class LevGalAbstract
{
	/** @var HttpReq */
	public $_req;

	public function __construct()
	{
		$this->loadResources();

		$this->_req = HttpReq::instance();
	}

	public function getNumericId()
	{
		return $this->_req->getQuery('item', 'intval', 0);
	}

	public function getSlugAndId()
	{
		$item = $this->_req->getQuery('item', 'trim', '0');

		if (preg_match('~^\d+$~', $item))
		{
			return ['', (int) $item];
		}

		[$slug, $id] = explode('.', $item);

		return [$slug, (int) $id];
	}

	public function loadResources()
	{
		global $context;

		// We want our actions to load our CSS and JS. Some actions will want more than this.

		// First the main stylesheet.
		$stylesheets = [];
		$stylesheets[] = 'main.css';
		if ($context['right_to_left'])
		{
			$stylesheets[] = 'rtl.css';
		}
		loadCSSFile($stylesheets, ['stale' => LEVGAL_VERSION, 'subdir' => 'Levertine']);

		// And our JS.
		loadJavascriptFile('levgal.js', ['subdir' => 'Levertine', 'stale' => LEVGAL_VERSION]);

		// And just in case, the main language file and template.
		Txt::load('Levertine/LevGal');
		theme()->getTemplates()->load('Levertine/LevGal');
	}

	protected function prepareResources($resources)
	{
		global $settings;

		$resources = (array) $resources;
		$built_res = [];

		foreach ($resources as $res)
		{
			if (!preg_match('~^https?://~i', $res) && !str_starts_with($res, '//'))
			{
				$res = $settings['default_theme_url'] . '/Levertine/' . $res . '?' . LEVGAL_VERSION;
			}
			$built_res[] = $res;
		}

		return $built_res;
	}

	public function addLinkTree($name, $url = '')
	{
		global $scripturl, $context;

		$item = [
			'name' => $name,
		];
		if (!empty($url))
		{
			$item['url'] = ($url[0] === '?' ? $scripturl : '') . $url;
		}

		$context['linktree'][] = $item;
	}

	public function addStyleSheets($stylesheets)
	{
		$stylesheets = $this->prepareResources($stylesheets);

		loadCSSFile($stylesheets, ['stale' => LEVGAL_VERSION, 'subdir' => 'Levertine']);
	}

	public function setTemplate($base_template, $sub_template, $style_sheets = [])
	{
		global $context;

		if (!empty($base_template))
		{
			theme()->getTemplates()->load('Levertine/' . $base_template);
			loadCSSFile($style_sheets, ['subdir' => 'Levertine', 'stale' => LEVGAL_VERSION]);
		}

		$context['sub_template'] = $sub_template;
	}

	abstract public function actionIndex();
}
