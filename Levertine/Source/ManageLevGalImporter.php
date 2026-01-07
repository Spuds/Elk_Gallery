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
use ElkArte\AbstractController;
use ElkArte\Languages\Txt;

/**
 * This file deals with the underlying logic of importing things from other gallery software.
 */
class ManageLevGalImporter extends AbstractController
{
	public function pre_dispatch()
	{
		theme()->getTemplates()->load('Levertine/ManageLevGal-Importer');
		Txt::load('Levertine/ManageLevGal-Importer');
		parent::pre_dispatch();
	}

	public function action_index()
	{
		$this->levgal_adminImport();
	}

	public function levgal_adminImport()
	{
		global $txt, $context, $scripturl;

		if (isset($_GET['done']))
		{
			if (isset($_POST['admin']))
			{
				redirectexit('action=admin;area=lgaldash');
			}

			if (isset($_POST['gallery']))
			{
				redirectexit($scripturl . '?media/');
			}
		}

		$context['page_title'] = $txt['levgal_importers'];
		$context['valid_importers'] = [];
		// So, the possible importers we have?
		$context['possible_importers'] = [
			'aeva' => '\Addons\Levertine\Source\Model\Importer\Aeva',
			'smfgallerylite' => '\Addons\Levertine\Source\Model\Importer\SGL',
			'smfgallerypro' => '\Addons\Levertine\Source\Model\Importer\SGP',
			//'smfdownloadslite' => '\Addons\Levertine\Source\Model\Importer\Download',
			//'smfdownloadspro' => '\Addons\Levertine\Source\Model\Importer\DownloadPro',
			'smfpacks' => '\Addons\Levertine\Source\Model\Importer\SMFPacks',
		];
		call_integration_hook('integrate_lgal_importers');

		if (isset($_POST['importer'], $context['possible_importers'][$_POST['importer']]))
		{
			levgal_extend_admin_time();
			checkSession();

			$context['importer_name'] = $_POST['importer'];
			$context['importer'] = new $context['possible_importers'][$_POST['importer']];
			$context['importer_supports'] = $context['importer']->stepsForImport();

			if (empty($_POST['step']))
			{
				// Step 0 is special. We do configuration if there is any to do and we tell the user what will be imported.
				if (method_exists($context['importer'], 'configure'))
				{
					$context['configurables'] = $context['importer']->configure();
				}
				$context['import_warning'] = $context['importer']->doesOverwrite();

				$context['sub_template'] = 'importer_pre_import';
				$context['page_title'] = sprintf($txt['levgal_importing_from'], $txt['levgal_importer_' . $_POST['importer']]);
			}
			else
			{
				$steps = array_keys($context['importer_supports']);
				// $_POST['step'] will be 0 for the first 'step' but its first actual step is 1 - and our
				// array is zero-bounded at this point, so let's fix that.
				$step = (int) $_POST['step'] - 1;

				if (!isset($steps[$step]))
				{
					redirectexit($scripturl . '?action=admin;area=lgalimport');
				}

				$context['page_title'] = sprintf($txt['levgal_importing_from'], $txt['levgal_importer_' . $_POST['importer']]);
				$context['substep_enabled'] = true;

				if ($steps[$step] === 'done')
				{
					$context['page_title'] = $txt['levgal_importer_done'];
					$context['sub_template'] = 'importer_done';
					$context['results'] = [];

					$list = [
						'albums' => 'lgal_albums',
						'docs' => 'lgal_docs',
						'items' => 'lgal_items',
						'comments' => 'lgal_comments',
					];
					foreach ($list as $item => $string)
					{
						if (!empty($_SESSION['lgalimport'][$item]))
						{
							$context['results'][$item] = Format::numstring($string, $_SESSION['lgalimport'][$item]);
						}
					}

					$list_simple = ['customfields', 'tags', 'unseen', 'notify', 'bookmarks'];
					foreach ($list_simple as $item)
					{
						if (!empty($_SESSION['lgalimport'][$item]))
						{
							$context['results'][$item] = $txt['levgal_importer_results_' . $item];
						}
					}

					unset ($_SESSION['lgalimport']);

					return;
				}

				$substep = isset($_POST['substep']) ? (int) $_POST['substep'] : 0;
				$method = 'import' . ucfirst($steps[$step]);
				[$step_complete, $total_substeps] = $context['importer']->$method($substep);
				// So whatever happens we're going back to the whole thing of the not-done template, which is lotsafun.
				theme()->getTemplates()->load('Admin');
				$context['continue_countdown'] = 2;
				$context['continue_get_data'] = '?action=admin;area=lgalimport';
				$context['continue_post_data'] = '
					<input type="hidden" name="importer" value="' . $_POST['importer'] . '" />
					<input type="hidden" name="step" value="' . ($step_complete ? $step + 2 : $step + 1) . '" />
					<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '" />
					<input type="hidden" name="substep" value="' . ($step_complete ? 0 : $substep + 1) . '" />';
				$context['sub_template'] = 'not_done';
				$context['continue_percent'] = round(($step + ($substep / $total_substeps)) / count($steps) * 100);

				if ($context['substep_enabled'])
				{
					$context['substep_continue_percent'] = round($substep / $total_substeps * 100);
					$context['substep_title'] = $txt['levgal_importer_substep_' . $steps[$step]];
				}
			}
		}
		else
		{
			// Find valid importers.
			foreach ($context['possible_importers'] as $import_id => $class)
			{
				$importer = new $class;
				if ($importer->isValid())
				{
					$context['valid_importers'][] = $import_id;
				}
			}

			$context['sub_template'] = !empty($context['valid_importers']) ? 'importer_home' : 'no_valid_importers';
		}
	}
}
