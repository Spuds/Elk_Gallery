<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with maintenance of the gallery.
 */
class ManageLevGalMaint_Controller extends Action_Controller
{
	public function pre_dispatch()
	{
		loadLanguage('levgal_lng/ManageLevGal-Maint');

		parent::pre_dispatch();
	}

	public function action_index()
	{
		global $context, $txt;

		$context['maint_tasks'] = array(
			'recount' => [$this, 'action_levgal_maint_recount'],
			'findfix' => [$this, 'action_levgal_maint_findfix'],
			'rebuildsearch' => [$this, 'action_levgal_maint_rebuildsearch'],
			'rebuildthumbs' => [$this, 'action_levgal_maint_rebuildthumbs'],
		);

		call_integration_hook('integrate_lgal_maint');

		if (isset($_GET['activity'], $context['maint_tasks'][$_GET['activity']]) && !isset($_GET['done']))
		{
			checkSession('request');
			levgal_extend_admin_time();

			return $context['maint_tasks'][$_GET['activity']]();
		}

		if (isset($_GET['activity'], $context['maint_tasks'][$_GET['activity']], $_GET['done']))
		{
			$context['success'] = sprintf($txt['levgal_maint_success'], $txt['levgal_task_' . $_GET['activity']]);
		}

		// Otherwise we're displaying the list of things.
		Templates::instance()->load('levgal_tpl/ManageLevGal-Maint');
		$context['sub_template'] = 'levgal_maint';
		$context['page_title'] = $txt['levgal_maint'];
	}

	public function action_levgal_maint_recount($url = '', $do_redirect = true)
	{
		// First, flush the unseen count for everyone.
		$unseenModel = new LevGal_Model_Unseen();
		$unseenModel->markForRecount();

		// Second, fix total items, comments etc.
		$maintModel = new LevGal_Model_Maintenance();
		$maintModel->recalculateTotalItems();
		$maintModel->recalculateTotalComments();

		// Third, fix unapproved counts
		$commentModel = new LevGal_Model_Comment();
		$commentModel->updateUnapprovedCount();

		// Fourth, fix report counts
		$reportModel = new LevGal_Model_Report();
		$reportModel->resetReportCount();

		// Fix master counts for things
		$maintModel->fixItemStats();
		$maintModel->fixAlbumStats();

		// All done?
		if ($do_redirect)
		{
			redirectexit(!empty($url) ? $url : 'action=admin;area=lgalmaint;activity=recount;done');
		}
	}

	public function action_levgal_maint_findfix()
	{
		global $context, $txt;

		$maintModel = new LevGal_Model_Maintenance();

		$steps = array(
			'fixOrphanAlbumHierarchy', // Fix cases of all kinds of weird broken album details.
			'fixOrphanItems', // This will also create a new album in event of finding any.
			'fixOrphanComments',
			'fixOrphanBookmarks',
			'fixOrphanLikes',
			'fixOrphanTags',
			'fixOrphanNotify',
			'fixOrphanUnseen',
			'fixOrphanReports',
			'fixOrphanCustomFields',
			'checkMissingFiles',
			'checkExtraFiles',
			'checkAlbumFiles',
			'recount',
		);
		call_integration_hook('integrate_lgal_maint_findfix', array(&$steps));
		if (empty($_SESSION['lgalmaint']))
		{
			$_SESSION['lgalmaint'] = array();
		}
		$step = isset($_REQUEST['step'], $steps[$_REQUEST['step']]) ? (int) $_REQUEST['step'] : 0;
		$substep = isset($_POST['substep']) ? (int) $_POST['substep'] : 0;

		$method = $steps[$step];
		if ($method === 'recount')
		{
			// If we changed anything, we really need to internally rerun the fix-every-count routine too.
			$url = 'action=admin;area=lgalmaint;activity=findfix;done';
			if (!empty($_SESSION['lgalmaint']))
			{
				$this->action_levgal_maint_recount($url);
			}
			redirectexit($url);
		}

		if ($substep === 0)
		{
			unset ($_SESSION['lgalmaint'][$method]);
		}
		list ($step_complete, $total_substeps, $other_data) = $maintModel->$method($substep);

		if (!empty($other_data))
		{
			if (empty($_SESSION['lgalmaint'][$method]))
			{
				$_SESSION['lgalmaint'][$method] = 0;
			}
			$_SESSION['lgalmaint'][$method] += is_array($other_data) ? count($other_data) : (int) $other_data;
		}

		Templates::instance()->load('Admin');
		$context['page_title'] = $txt['levgal_task_findfix'];
		$context['continue_countdown'] = 3;
		$context['continue_get_data'] = '?action=admin;area=lgalmaint;activity=findfix';
		$context['continue_post_data'] = '
	<input type="hidden" name="step" value="' . ($step_complete ? $step + 1 : $step) . '" />
	<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '" />
	<input type="hidden" name="substep" value="' . ($step_complete ? 0 : $substep + 1) . '" />';
		$context['sub_template'] = 'not_done';
		$context['continue_percent'] = round(($step + 1) / count($steps) * 100);

		$context['substep_enabled'] = true;
		$context['substep_continue_percent'] = round($substep / $total_substeps * 100);
		$context['substep_title'] = $txt['levgal_findfix_substep_' . $steps[$step]];
	}

	public function action_levgal_maint_rebuildsearch()
	{
		global $context, $txt;

		$context['steps'] = array(
			array(
				'name' => 'album',
				'pc' => 20,
			),
			array(
				'name' => 'item',
				'pc' => 80,
			),
		);

		$context['step'] = isset($_REQUEST['step'], $context['steps'][$_REQUEST['step']])
			? (int) $_REQUEST['step'] : 0;

		// We are setting up for the not-done template ElkArte provides so we don't have do it ourselves.
		Templates::instance()->load('Admin');
		$context['continue_countdown'] = 3;
		$context['continue_get_data'] = '?action=admin;area=lgalmaint;activity=rebuildsearch;' . $context['session_var'] . '=' . $context['session_id'];
		$context['continue_post_data'] = '';
		$context['sub_template'] = 'not_done';
		$context['page_title'] = $txt['levgal_task_rebuildsearch'];

		$function = 'levgal_maint_rebuildsearch_' . $context['steps'][$context['step']]['name'];
		$last_step = $this->$function() ? $context['step'] + 1 : $context['step'];

		$context['continue_percent'] = 0;
		for ($i = 0; $i < $last_step; $i++)
		{
			$context['continue_percent'] += $context['steps'][$i]['pc'];
		}
	}

	private function levgal_maint_rebuildsearch_album()
	{
		global $context, $txt;

		$db = database();

		$searchModel = new LevGal_Model_Search();
		$searchModel->deleteExistingSearches();

		$rows = array();
		$request = $db->query('', '
		SELECT 
		    id_album, album_name
		FROM {db_prefix}lgal_albums'
		);
		while ($row = $db->fetch_assoc($request))
		{
			$rows[] = $row;
		}
		$db->free_result($request);

		$searchModel->emptyAlbumIndex();
		$searchModel->createAlbumEntries($rows);

		// This is (for now?) a one step operation, no suboperation, but we make it look like there is.
		$context['continue_post_data'] .= '<input type="hidden" name="step" value="' . ($context['step'] + 1) . '" />';
		$context['substep_enabled'] = true;
		$context['substep_title'] = $txt['levgal_task_rebuildsearch_album_subtitle'];
		$context['substep_continue_percent'] = 100;

		return true;
	}

	private function levgal_maint_rebuildsearch_item()
	{
		global $context, $txt;

		$db = database();

		$searchModel = new LevGal_Model_Search();

		$request = $db->query('', '
		SELECT 
		    MAX(id_item)
		FROM {db_prefix}lgal_items'
		);
		list ($max_id) = $db->fetch_row($request);
		$db->free_result($request);

		$start = isset($_REQUEST['startitem']) ? (int) $_REQUEST['startitem'] : 1;

		$step_size = min(25, ceil($max_id / 10));

		// Are we done? Let's get out of here if we are and this is the last task so, go home.
		if (empty($step_size) || empty($max_id) || $max_id < $start)
		{
			redirectexit('action=admin;area=lgalmaint;activity=rebuildsearch;done');
		}

		$ids = range($start, $start + $step_size - 1);

		$itemList = new LevGal_Model_ItemList();
		$items = $itemList->getItemsById($ids, true);
		$descriptions = $itemList->getItemDescriptionsById($ids, true, false); // (bypass permission check, don't parse bbc)

		$rows = array();
		foreach ($items as $item)
		{
			$rows[] = array(
				'id_item' => $item['id_item'],
				'item_name' => $item['item_name'],
				'description' => $descriptions[$item['id_item']] ?? '',
				'item_type' => $item['item_type'],
			);
		}
		$searchModel->deleteItemEntries($ids);
		$searchModel->createItemEntries($rows);

		// So, we've done some stuff now. Are we actually done or not by now?
		if ($start + $step_size > $max_id)
		{
			// If, for example, we were on item 1 and doing 10 steps, this would make the next iteration start at 11, but if the last id was 10
			// there's nothing for us to do, so leave.
			redirectexit('action=admin;area=lgalmaint;activity=rebuildsearch;done');
		}

		$context['continue_post_data'] .= '<input type="hidden" name="step" value="' . $context['step'] . '" />
	<input type="hidden" name="startitem" value="' . ($start + $step_size) . '" />';
		$context['substep_enabled'] = true;
		$context['substep_title'] = $txt['levgal_task_rebuildsearch_item_subtitle'];
		$context['substep_continue_percent'] = floor(100 * $start / $max_id);

		return false;
	}

	public function action_levgal_maint_rebuildthumbs()
	{
		global $context, $txt;

		$db = database();

		$items_per_step = 10;

		// First, get the count of items.
		$request = $db->query('', '
		SELECT
		    COUNT(id_item)
		FROM {db_prefix}lgal_items');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		$total_steps = ceil($count / $items_per_step);
		$context['step'] = isset($_REQUEST['step']) ? (int) $_REQUEST['step'] : 0;
		if ($context['step'] >= $total_steps)
		{
			redirectexit('action=admin;area=lgalmaint;activity=rebuildthumbs;done');
		}

		$itemModel = new LevGal_Model_Item();

		// Now, get the items.
		$request = $db->query('', '
		SELECT 
			id_item, filename, filehash, extension, mime_type, meta
		FROM {db_prefix}lgal_items
		ORDER BY id_item
		LIMIT {int:start}, {int:limit}',
			array(
				'start' => $context['step'] * $items_per_step,
				'limit' => $items_per_step,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			// If the user uploaded a thumbnail or it was produced by some mechanism other than by LevGal itself, we need to not rebuild it.
			$meta = !empty($row['meta']) ? Util::unserialize($row['meta'], ['allowed_classes' => false]) : array();
			if (!empty($meta) && !empty($meta['thumb_uploaded']))
			{
				continue;
			}

			$itemModel->buildFromSurrogate($row);

			if (strpos($row['mime_type'], 'external/') === 0)
			{
				$externalModel = new LevGal_Model_External($meta);
				if ($thumbnail = $externalModel->getThumbnail())
				{
					$itemModel->deleteFiles(array('preview', 'thumb'));
					$itemModel->setThumbnail($thumbnail);
				}
			}
			else
			{
				$meta = $itemModel->getMetadata();
				// Did we get a thumbnail from meta?
				$itemModel->deleteFiles(array('preview', 'thumb'));
				if (isset($meta['thumbnail']))
				{
					$itemModel->setThumbnail($meta['thumbnail']);
				}
				else
				{
					$itemModel->getThumbnail();
				}
			}
		}

		// We are setting up for the not-done template ElkArte provides so we don't have do it ourselves.
		Templates::instance()->load('Admin');
		$context['continue_countdown'] = 3;
		$context['continue_get_data'] = '?action=admin;area=lgalmaint;activity=rebuildthumbs;' . $context['session_var'] . '=' . $context['session_id'];
		$context['continue_post_data'] = '<input type="hidden" name="step" value="' . ($context['step'] + 1) . '" />';
		$context['sub_template'] = 'not_done';
		$context['page_title'] = $txt['levgal_task_rebuildthumbs'];
		$context['continue_percent'] = round($context['step'] / $total_steps * 100);
		if (empty($context['continue_percent']))
		{
			$context['continue_percent'] = 1; // so it *always* shows something.
		}
	}
}