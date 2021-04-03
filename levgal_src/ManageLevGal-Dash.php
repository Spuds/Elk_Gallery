<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.0 / elkarte
 */

/**
 * This file deals with the dashboard stuff for the gallery.
 */
class ManageLevGalDash_Controller extends Action_Controller
{
	public function action_index()
	{
		global $context, $txt;

		Templates::instance()->load('levgal_tpl/ManageLevGal');

		$subActions = array(
			'index' => [$this, 'levgal_adminDash_index'],
			'modlog' => [$this, 'levgal_adminDash_modlog'],
			'credits' => [$this, 'levgal_adminDash_credits'],
		);

		// Get ready for some action
		$action = new Action();

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['levgal_admindash'],
			'description' => $txt['levgal_admindash_desc'],
			'tabs' => array(
				'index' => array(),
				'modlog' => array(
					'description' => $txt['levgal_modlog_desc'],
				),
				'credits' => array(
					'description' => $txt['levgal_credits_desc'],
				),
			),
		);

		// Get the subAction
		$subAction = $action->initialize($subActions, 'index');
		$context['sub_action'] = $subAction;

		// Finally go to where we want to go
		$action->dispatch($subAction);
	}

	/**
	 * Default action, shows the dashboard with useful stats
	 */
	public function levgal_adminDash_index()
	{
		global $context, $txt;

		// Things we need: title, multiple language files (due to reuse), our CSS
		loadCSSFile(['main.css', 'admin_lg.css', 'admin.css'], ['stale' => LEVGAL_VERSION, 'subdir' => 'levgal_res']);

		loadLanguage('levgal_lng/LevGal-Stats');
		loadLanguage('levgal_lng/ManageLevGal-Quotas');

		$context['page_title'] = $txt['levgal_admindash'];
		$context['sub_template'] = 'levgal_dash';

		$statsModel = new LevGal_Model_Stats();
		$total_items = $statsModel->getTotalItems();
		$total_comments = $statsModel->getTotalComments();
		$total_albums = $statsModel->getTotalAlbums();
		$installed_time = $statsModel->getInstalledTime();

		$context['general_stats'] = array(
			'installed_time' => $installed_time['time_formatted'],
			'total_items' => comma_format($total_items),
			'total_comments' => comma_format($total_comments),
			'total_albums' => comma_format($total_albums),
		);

		// Total file size.
		$size = $statsModel->getTotalGallerySize();
		if ($size !== false)
		{
			$context['general_stats']['total_filesize'] = LevGal_Helper_Format::filesize($size);
		}

		$item_breakdown = $statsModel->getCountsByItemType();

		// Borrowed from the Chart.js main website. Not sure if any of this should change yet.
		// But the code's available should I want to do so.
		$colors = array(
			'red' => array('#bf616a'),
			'orange' => array('#d08770'),
			'yellow' => array('#ebcb8b'),
			'green' => array('#a3be8c'),
			'teal' => array('#96b5b4'),
			'pale_blue' => array('#8fa1b3'),
			'blue' => array('#5b90bf'),
			'purple' => array('#b48ead'),
			'brown' => array('#ab7967'),
		);
		$item_colors = array(
			'image' => 'blue',
			'audio' => 'red',
			'video' => 'yellow',
			'document' => 'teal',
			'archive' => 'brown',
			'generic' => 'orange',
			'external' => 'purple',
		);
		$context['item_breakdown'] = array();
		foreach ($item_breakdown as $item_type => $count)
		{
			$context['item_breakdown'][] = array(
				'value' => $count,
				'color' => $colors[$item_colors[$item_type]][0],
				'highlight' => isset($colors[$item_colors[$item_type]][1]) ? $colors[$item_colors[$item_type]][1] : $colors[$item_colors[$item_type]][0],
				'label' => isset($txt['levgal_quotas_' . $item_type . '_title_short']) ? $txt['levgal_quotas_' . $item_type . '_title_short'] : $txt['levgal_quotas_' . $item_type . '_title'],
			);
		}

		$context['support'] = array(
			'elk' => FORUM_VERSION,
			'lgal' => LEVGAL_VERSION,
			'php' => phpversion(),
		);

		// Get an image handler - but don't error out if none exists.
		$image = new LevGal_Helper_Image(false);
		$handlers = $image->availableHandlers();
		$versions = $image->getHandlerVersions();

		foreach ($handlers as $handler => $state)
		{
			if ($state === false || !isset($versions[$handler]))
			{
				$context['support'][$handler] = '<span class="lgaladmin i-close" title="' . $txt['levgal_support_notavailable'] . '"></span> ' . $txt['levgal_support_notavailable'];
			}
			elseif ($state === true)
			{
				$context['support'][$handler] = '<span class="lgaladmin i-check" title="' . $txt['levgal_support_available'] . '"></span> ' . $versions[$handler];
			}
			else
			{
				$context['support'][$handler] = '<span class="lgaladmin i-warning" title="' . $txt['levgal_support_warning'] . '"></span> ' . $versions[$handler];
			}
		}

		// @todo ... No site to fetch news or releases from.
		$context['latest_version'] = '';
		$context['latest_news'] = array();
		$context['news_loaded'] = true;
	}

	public function levgal_adminDash_modlog()
	{
		global $context, $txt, $scripturl;
		$context['page_title'] = $txt['levgal_modlog'];
		$context['can_delete'] = allowedTo('admin_forum');

		if ($context['can_delete'])
		{
			if (isset($_POST['removeall']))
			{
				checkSession();
				LevGal_Model_ModLog::emptyLog();
			}
			elseif (!empty($_POST['remove']) && isset($_POST['delete']) && is_array($_POST['delete']))
			{
				checkSession();
				LevGal_Model_ModLog::removeItems(array_unique($_POST['delete']));
			}
		}

		$listOptions = array(
			'id' => 'levgal_modlog',
			'title' => $context['page_title'],
			'width' => '100%',
			'items_per_page' => 30,
			'no_items_label' => $txt['levgal_modlog_empty'],
			'base_href' => $scripturl . '?action=admin;area=lgaldash;sa=modlog',
			'default_sort_col' => 'time',
			'get_items' => array(
				'function' => 'LevGal_Model_ModLog::getItems',
			),
			'get_count' => array(
				'function' => 'LevGal_Model_ModLog::getCountItems',
			),
			'columns' => array(
				'event' => array(
					'header' => array(
						'value' => $txt['levgal_modlog_action'],
						'class' => 'lefttext',
					),
					'data' => array(
						'db' => 'event_text',
						'class' => 'smalltext',
					),
					'sort' => array(
						'default' => 'le.event',
						'reverse' => 'le.event DESC',
					),
				),
				'time' => array(
					'header' => array(
						'value' => $txt['levgal_modlog_time'],
						'class' => 'lefttext',
					),
					'data' => array(
						'db' => 'time',
						'class' => 'smalltext',
					),
					'sort' => array(
						'default' => 'le.timestamp DESC',
						'reverse' => 'le.timestamp',
					),
				),
				'person' => array(
					'header' => array(
						'value' => $txt['levgal_modlog_member'],
						'class' => 'lefttext',
					),
					'data' => array(
						'db' => 'member',
						'class' => 'smalltext',
					),
					'sort' => array(
						'default' => 'mem.real_name',
						'reverse' => 'mem.real_name DESC',
					),
				),
				'position' => array(
					'header' => array(
						'value' => $txt['levgal_modlog_position'],
						'class' => 'lefttext',
					),
					'data' => array(
						'db' => 'position',
						'class' => 'smalltext',
					),
					'sort' => array(
						'default' => 'mg.group_name',
						'reverse' => 'mg.group_name DESC',
					),
				),
				'ip' => array(
					'header' => array(
						'value' => $txt['levgal_modlog_ip'],
						'class' => 'lefttext',
					),
					'data' => array(
						'db' => 'ip',
						'class' => 'smalltext',
					),
					'sort' => array(
						'default' => 'le.ip',
						'reverse' => 'le.ip DESC',
					),
				),
				'delete' => array(
					'header' => array(
						'value' => '<input type="checkbox" name="all" class="input_check" onclick="invertAll(this, this.form);" />',
					),
					'data' => array(
						'function' => function($entry) {
							return '<input type="checkbox" class="input_check" name="delete[]" value="' . $entry['id'] . '"' . ' />';
						}
					),
					'style' => 'text-align: center;',
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=lgaldash;sa=modlog',
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '
					' . ($context['can_delete'] ? '
					<div class="submitbutton">
						<input type="submit" name="remove" value="' . $txt['levgal_modlog_remove'] . '" />
						<input type="submit" name="removeall" value="' . $txt['levgal_modlog_removeall'] . '" />
					</div>' : ''),
				),
			),
		);

		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'levgal_modlog';
	}

	public function levgal_adminDash_credits()
	{
		global $context, $txt;

		$context['page_title'] = $txt['levgal_credits_title'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['levgal_credits_title'];
		$context['sub_template'] = 'levgal_credits';
		$context['levgal_credits'] = array(
			'developers' => array(
				'Peter Spicer, levertine.com'
			),
			'components' => array(
				'<a href="https://mediaelementjs.com/">MediaElement.js</a>, &copy; 2020 John Dyer, under the <a href="https://github.com/johndyer/mediaelement/">MIT License</a>',
				'<a href="https://gist.github.com/sgmurphy/3095196">url_slug.js</a> &copy; 2012 Sean Murphy, under the <a href="http://creativecommons.org/publicdomain/zero/1.0/">CC0 license</a>',
				'<a href="https://www.dropzonejs.com/">Dropzone.js</a> &copy; 2021 Matias Meno under the <a href="https://github.com/dropzone/dropzone/blob/main/LICENSE">MIT License</a>',
				'<a href="https://biati-digital.github.io/glightbox/">Glightbox</a> &copy; 2018 Biati Digital, under the <a href="https://github.com/biati-digital/glightbox/blob/master/license.md">MIT License</a>',
				'<a href="https://github.com/ilikenwf/nestedSortable">nestedSortable</a> &copy; 2010-2016 Manuele J Sarfatti and <a href="https://github.com/ilikenwf/nestedSortable/graphs/contributors">others</a>, under the <a href="http://opensource.org/licenses/MIT">MIT License</a>',
				'<a href="https://github.com/nnnick/Chart.js">Chart.js</a> &copy; 2013-2014 Nick Downie, under the <a href="https://github.com/nnnick/Chart.js/blob/master/LICENSE.md">MIT License</a>',
				'<a href="https://github.com/lucaong/jQCloud">jQCloud</a> &copy; 2014-2017 Damien "Mistic" Sorel, under the <a href="https://github.com/mistic100/jQCloud/blob/master/LICENSE.txt">MIT License</a>',
				'<a href="https://github.com/zenorocha/clipboard.js">Clipboard.js</a> &copy; Zeno Rocha 2021 under the <a href="https://github.com/zenorocha/clipboard.js/blob/master/LICENSE">MIT License</a>',
			),
			'images' => array(
				'<a href="https://p.yusukekamiyamane.com/">Fugue Icons</a>, &copy; 2013 Yusuke Kamiyamane, under <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC-SA-3.0</a>',
				'<a href="https://p.yusukekamiyamane.com/">Diagona Icons</a>, &copy; 2012 Yusuke Kamiyamane, under <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC-SA-3.0</a>',
				'<a href="https://github.com/pasnox/oxygen-icons-png">Breeze Icons</a>, &copy; 2014 Uri Herrera and others, under the LGPL',
				'<a href="https://www.fatcow.com/free-icons">FatCow-Farm Fresh Icons</a>, &copy; 2013 FatCow Web Hosting, under the <a href="http://creativecommons.org/licenses/by/3.0/">CC-BY-3.0</a>',
			),
			'translators' => array(
				'Peter Spicer (English, English British)',
				'Justyne (German)',
			),
			'people' => array(
				'Justyne, for helping make sense of permissions and generally keeping sanity',
				'Caitlin, for being awesome and helping me make this work',
				'Runic, for helping me with testing and theme advice',
				'And to everyone who supplied bug reports and feedback (lurk, Steve, TheDDude, Kindred and anyone else I forgot), thank you!',
			),
		);
	}
}
