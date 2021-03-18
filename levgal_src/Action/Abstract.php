<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file provides base functionality to all actions.
 */
abstract class LevGal_Action_Abstract
{
	public function getNumericId()
	{
		return empty($_GET['item']) ? 0 : (int) $_GET['item'];
	}

	public function getSlugAndId()
	{
		$item = empty($_GET['item']) ? '0' : $_GET['item'];

		if (preg_match('~^\d+$~', $item))
		{
			return array('', (int) $item);
		}
		else
		{
			list ($slug, $id) = explode('.', $item);

			return array($slug, (int) $id);
		}
	}

	public function __construct()
	{
		$this->loadResources();
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
		$this->addStyleSheets($stylesheets);

		// And our JS.
		loadJavascriptFile('levgal.js', ['subdir' => 'levgal_res']);

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

		loadCSSFile($stylesheets);
	}

	public function setTemplate($base_template, $sub_template, $style_sheets = array())
	{
		global $context;

		if (!empty($base_template))
		{
			Templates::instance()->load('levgal_tpl/' . $base_template);
			loadCSSFile($style_sheets, ['subdir' => 'levgal_res']);
		}

		$context['sub_template'] = $sub_template;
	}

	abstract function actionIndex();
}
