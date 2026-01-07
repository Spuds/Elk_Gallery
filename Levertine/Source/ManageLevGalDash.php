<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source;

use Addons\Levertine\Source\Helper\Format;
use Addons\Levertine\Source\Helper\Image;
use Addons\Levertine\Source\Model\ModLog;
use Addons\Levertine\Source\Model\Stats;
use ElkArte\AbstractController;
use ElkArte\Action;
use ElkArte\Languages\Txt;

/**
 * This file deals with the dashboard stuff for the gallery.
 */
class ManageLevGalDash extends AbstractController
{
	public function action_index()
	{
		global $context, $txt;

		theme()->getTemplates()->load('Levertine/ManageLevGal');

		$subActions = [
			'index' => [$this, 'levgal_adminDash_index'],
			'modlog' => [$this, 'levgal_adminDash_modlog'],
			'credits' => [$this, 'levgal_adminDash_credits'],
		];

		// Get ready for some action
		$action = new Action();

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['levgal_admindash'],
			'description' => $txt['levgal_admindash_desc'],
			'tabs' => [
				'index' => [],
				'modlog' => [
					'description' => $txt['levgal_modlog_desc'],
				],
				'credits' => [
					'description' => $txt['levgal_credits_desc'],
				],
			],
		];

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
		loadCSSFile(['main.css', 'admin_lg.css'], ['stale' => LEVGAL_VERSION, 'subdir' => 'Levertine']);

		Txt::load('Levertine/LevGal-Stats');
		Txt::load('Levertine/ManageLevGal-Quotas');

		$context['page_title'] = $txt['levgal_admindash'];
		$context['sub_template'] = 'levgal_dash';

		$statsModel = new Stats();
		$total_items = $statsModel->getTotalItems();
		$total_comments = $statsModel->getTotalComments();
		$total_albums = $statsModel->getTotalAlbums();
		$installed_time = $statsModel->getInstalledTime();

		$context['general_stats'] = [
			'installed_time' => $installed_time['time_formatted'],
			'total_items' => comma_format($total_items),
			'total_comments' => comma_format($total_comments),
			'total_albums' => comma_format($total_albums),
		];

		// Total file size.
		$size = $statsModel->getTotalGallerySize();
		if ($size !== false)
		{
			$context['general_stats']['total_filesize'] = Format::filesize($size);
		}

		$item_breakdown = $statsModel->getCountsByItemType();

		// Borrowed from the Chart.js main website. Not sure if any of this should change yet.
		// But the code's available should I want to do so.
		$colors = [
			'red' => ['#bf616a'],
			'orange' => ['#d08770'],
			'yellow' => ['#ebcb8b'],
			'green' => ['#a3be8c'],
			'teal' => ['#96b5b4'],
			'pale_blue' => ['#8fa1b3'],
			'blue' => ['#5b90bf'],
			'purple' => ['#b48ead'],
			'brown' => ['#ab7967'],
		];
		$item_colors = [
			'image' => 'blue',
			'audio' => 'red',
			'video' => 'yellow',
			'document' => 'teal',
			'archive' => 'brown',
			'generic' => 'orange',
			'external' => 'purple',
		];
		$context['item_breakdown'] = [];
		foreach ($item_breakdown as $item_type => $count)
		{
			$context['item_breakdown']['labels'][] = $txt['levgal_quotas_' . $item_type . '_title_short'] ?? $txt['levgal_quotas_' . $item_type . '_title'];
			$context['item_breakdown']['datasets']['data'][] = $count;
			$context['item_breakdown']['datasets']['backgroundColor'][] = $colors[$item_colors[$item_type]][0];
		}

		$context['support'] = [
			'elk' => FORUM_VERSION,
			'lgal' => LEVGAL_VERSION,
			'php' => PHP_VERSION,
		];

		// Get an image handler - but don't error out if none exists.
		$image = new Image(false);
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

		$support = $image->hasWebpSupport();
		if ($support !== false)
		{
			$context['support']['webp'] = '<span class="lgaladmin i-check" title="' . $txt['levgal_support_available'] . '"></span> (' . $support . ')';
		}
		else
		{
			$context['support']['webp'] = '<span class="lgaladmin i-close" title="' . $txt['levgal_support_notavailable'] . '"></span> ' . $txt['levgal_support_notavailable'];
		}
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
				ModLog::emptyLog();
			}
			elseif (!empty($_POST['remove']) && isset($_POST['delete']) && is_array($_POST['delete']))
			{
				checkSession();
				ModLog::removeItems(array_unique($_POST['delete']));
			}
		}

		$listOptions = [
			'id' => 'levgal_modlog',
			'title' => $context['page_title'],
			'width' => '100%',
			'items_per_page' => 30,
			'no_items_label' => $txt['levgal_modlog_empty'],
			'base_href' => $scripturl . '?action=admin;area=lgaldash;sa=modlog',
			'default_sort_col' => 'time',
			'get_items' => [
				'function' => '\Addons\Levertine\Source\Model\ModLog::getItems',
			],
			'get_count' => [
				'function' => '\Addons\Levertine\Source\Model\ModLog::getCountItems',
			],
			'columns' => [
				'event' => [
					'header' => [
						'value' => $txt['levgal_modlog_action'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'event_text',
						'class' => 'smalltext',
					],
					'sort' => [
						'default' => 'le.event',
						'reverse' => 'le.event DESC',
					],
				],
				'time' => [
					'header' => [
						'value' => $txt['levgal_modlog_time'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'time',
						'class' => 'smalltext',
					],
					'sort' => [
						'default' => 'le.timestamp DESC',
						'reverse' => 'le.timestamp',
					],
				],
				'person' => [
					'header' => [
						'value' => $txt['levgal_modlog_member'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'member',
						'class' => 'smalltext',
					],
					'sort' => [
						'default' => 'mem.real_name',
						'reverse' => 'mem.real_name DESC',
					],
				],
				'position' => [
					'header' => [
						'value' => $txt['levgal_modlog_position'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'position',
						'class' => 'smalltext',
					],
					'sort' => [
						'default' => 'mg.group_name',
						'reverse' => 'mg.group_name DESC',
					],
				],
				'ip' => [
					'header' => [
						'value' => $txt['levgal_modlog_ip'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'ip',
						'class' => 'smalltext',
					],
					'sort' => [
						'default' => 'le.ip',
						'reverse' => 'le.ip DESC',
					],
				],
				'delete' => [
					'header' => [
						'value' => '<input type="checkbox" name="all" class="input_check" onclick="invertAll(this, this.form);" />',
					],
					'data' => [
						'function' => function ($entry) {
							return '<input type="checkbox" class="input_check" name="delete[]" value="' . $entry['id'] . '"' . ' />';
						}
					],
					'style' => 'text-align: center;',
				],
			],
			'form' => [
				'href' => $scripturl . '?action=admin;area=lgaldash;sa=modlog',
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => [
					$context['session_var'] => $context['session_id'],
				],
			],
			'additional_rows' => [
				[
					'position' => 'below_table_data',
					'value' => '
					' . ($context['can_delete'] ? '
					<div class="submitbutton">
						<input type="submit" name="remove" value="' . $txt['levgal_modlog_remove'] . '" />
						<input type="submit" name="removeall" value="' . $txt['levgal_modlog_removeall'] . '" />
					</div>' : ''),
				],
			],
		];

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
		$context['levgal_credits'] = [
			'developers' => [
				'Peter Spicer, levertine.com'
			],
			'components' => [
				'<a href="https://github.com/nnnick/Chart.js">Chart.js</a> &copy; 2014-2022 Chart.js Contributors, under the <a href="https://github.com/nnnick/Chart.js/blob/master/LICENSE.md">MIT License</a>',
				'<a href="https://github.com/zenorocha/clipboard.js">Clipboard.js</a> &copy; Zeno Rocha 2021 under the <a href="https://github.com/zenorocha/clipboard.js/blob/master/LICENSE">MIT License</a>',
				'<a href="https://www.dropzonejs.com/">Dropzone.js</a> &copy; 2021 Matias Meno under the <a href="https://github.com/dropzone/dropzone/blob/main/LICENSE">MIT License</a>',
				'<a href="https://projects.sergiodinislopes.pt/flexdatalist">Flexdatalist.js</a> &copy; Sérgio Dinis Lopes, under the <a href="https://github.com/sergiodlopes/jquery-flexdatalist/blob/master/LICENSE">MIT License</a>',
				'<a href="https://biati-digital.github.io/glightbox/">Glightbox</a> &copy; 2018 Biati Digital, under the <a href="https://github.com/biati-digital/glightbox/blob/master/license.md">MIT License</a>',
				'<a href="https://github.com/lucaong/jQCloud">jQCloud</a> &copy; 2014-2017 Damien "Mistic" Sorel, under the <a href="https://github.com/mistic100/jQCloud/blob/master/LICENSE.txt">MIT License</a>',
				'<a href="https://github.com/ilikenwf/nestedSortable">nestedSortable</a> &copy; 2010-2016 Manuele J Sarfatti and <a href="https://github.com/ilikenwf/nestedSortable/graphs/contributors">others</a>, under the <a href="https://opensource.org/licenses/MIT">MIT License</a>',
				'<a href="https://github.com/sampotts/plyr">plyr.js</a> &copy; 2017 Sam Potts, under the <a href="https://github.com/dropzone/dropzone/blob/main/LICENSE">MIT License</a>',
				'<a href="https://gist.github.com/sgmurphy/3095196">url_slug.js</a> &copy; 2012 Sean Murphy, under the <a href="https://creativecommons.org/publicdomain/zero/1.0/">CC0 license</a>',
			],
			'images' => [
				'<a href="https://p.yusukekamiyamane.com/">Fugue Icons</a>, &copy; 2013 Yusuke Kamiyamane, under <a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-SA-3.0</a>',
				'<a href="https://github.com/pasnox/oxygen-icons-png">Breeze Icons</a>, &copy; 2014 Uri Herrera and others, under the LGPL',
			],
			'translators' => [
				'Peter Spicer (English, English British)',
				'Augras (French)',
				'McFly (German)',
				'Radu81 (Italian)',
			],
			'people' => [
				'Justyne, for helping make sense of permissions and generally keeping sanity',
				'Caitlin, for being awesome and helping me make this work',
				'Runic, for helping me with testing and theme advice',
				'And to everyone who supplied bug reports and feedback (lurk, Steve, TheDDude, Kindred and anyone else I forgot), thank you!',
			],
		];
	}
}
