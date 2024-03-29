<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides base functionality to all actions.
 */
abstract class LevGal_Action_Abstract
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
			return array('', (int) $item);
		}

		list ($slug, $id) = explode('.', $item);

		return array($slug, (int) $id);
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
		loadCSSFile($stylesheets, ['stale' => LEVGAL_VERSION, 'subdir' => 'levgal_res']);

		// And our JS.
		loadJavascriptFile('levgal.js', ['subdir' => 'levgal_res', 'stale' => LEVGAL_VERSION]);

		// And just in case, the main language file and template.
		loadLanguage('levgal_lng/LevGal');
		Templates::instance()->load('levgal_tpl/LevGal');
	}

	protected function prepareResources($resources)
	{
		global $settings;

		$resources = (array) $resources;
		$built_res = array();

		foreach ($resources as $res)
		{
			if (!preg_match('~^https?://~i', $res) && strpos($res, '//') !== 0)
			{
				$res = $settings['default_theme_url'] . '/levgal_res/' . $res . '?' . LEVGAL_VERSION;
			}
			$built_res[] = $res;
		}

		return $built_res;
	}

	public function addLinkTree($name, $url = '')
	{
		global $scripturl, $context;

		$item = array(
			'name' => $name,
		);
		if (!empty($url))
		{
			$item['url'] = ($url[0] === '?' ? $scripturl : '') . $url;
		}

		$context['linktree'][] = $item;
	}

	public function addStyleSheets($stylesheets)
	{
		$stylesheets = $this->prepareResources($stylesheets);

		loadCSSFile($stylesheets, ['stale' => LEVGAL_VERSION, 'subdir' => 'levgal_res']);
	}

	public function setTemplate($base_template, $sub_template, $style_sheets = array())
	{
		global $context;

		if (!empty($base_template))
		{
			Templates::instance()->load('levgal_tpl/' . $base_template);
			loadCSSFile($style_sheets, ['subdir' => 'levgal_res', 'stale' => LEVGAL_VERSION]);
		}

		$context['sub_template'] = $sub_template;
	}

	abstract public function actionIndex();
}
