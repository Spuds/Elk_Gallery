<?php
// Version: 1.1.1; Levertine Gallery front page template

/**
 * This file handles displaying the front page of the gallery.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.1 / elkarte
 */

function template_main()
{
	global $context;

	echo '
	<div id="gallery_contain">';

	template_album_list_action_tabs($context['gallery_actions']);
	template_main_page_sidebar();
	template_main_page_display();

	echo '
	</div>';
}

function template_main_page_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	// Information block
	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $txt['lgal_gallery_info'], '
			</h3>
			<div class="content">
				<dl class="album_info">';

	foreach ($context['stats'] as $str => $value)
	{
		echo '
					<dt>', $txt[$str], '</dt>
					<dd>', $value, '</dd>';
	}

	echo '
				</dl>
			</div>';

	// And show the list of things we can do.
	template_sidebar_action_list($txt['lgal_gallery_actions'], $context['gallery_actions']);

	echo '
		</div>';
}

function template_main_page_display()
{
	global $context;

	echo '
		<div id="item_main">';

	if (!empty($context['featured_albums']))
	{
		template_display_featured_albums();

	}

	template_display_latest_items();

	template_display_random_items();

	echo '
		</div>';
}

function template_display_featured_albums()
{
	global $txt;
	echo '
			<h3 class="lgal_secondary_header secondary_header">', $txt['levgal_featured_albums'], '</h3>';

	template_display_album_list('featured_albums');
}

function template_display_album_list($list)
{
	global $context;

	echo '
			<div class="album_container">';

	foreach ($context[$list] as $album)
	{
		$album['num_items'] = $album['num_items'] ?? 0;
		$album['album_count'] = get_child_count($album);

		echo '
				<a class="album_featured well" href="', $album['album_url'], '">
					<div class="floatleft album_thumb">
						<img src="', $album['thumbnail_url'], '" alt="" />
					</div>
					<div class="album_desc lefttext">
						', empty($album['featured']) ? '' : '<i class="lgalicon i-star colorize-gold"></i> ',
						$album['album_name'], '<br />', $album['description_short'], '
					</div>
					<div class="centertext clear">
						<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_items', $album['num_items']), ' / ', LevGal_Helper_Format::numstring('lgal_albums', $album['album_count']), '
					</div>
				</a>';
	}

	echo '
			</div>';
}

function get_child_count($album)
{
	global $context;

	$counts = $album['album_counts'] ?? $context['album_counts'] ?? 0;
	if (empty($counts))
	{
		return 0;
	}

	// Determine the total number of child albums under all owners
	$child_albums = 0;
	foreach ($album['owner_cache'] as $owner_type => $owners)
	{
		$owners = array_unique((array) $owners);
		foreach ($owners as $owner)
		{
			$child_albums += $counts[$owner_type][$owner];
		}
	}

	return $child_albums;
}

function template_display_latest_items()
{
	global $context, $txt;

	echo '
			<h3 class="lgal_secondary_header secondary_header">', $txt['lgal_latest_items'], '</h3>';

	if (empty($context['latest_items']))
	{
		template_no_items();
	}
	else
	{
		template_item_list('latest_items');
	}
}

function template_display_random_items()
{
	global $context, $txt;

	echo '
			<h3 class="lgal_secondary_header secondary_header">', $txt['lgal_random_items'], '</h3>';

	if (empty($context['latest_items']))
	{
		template_no_items();
	}
	else
	{
		template_item_list('random_items');
	}
}

function template_no_items()
{
	global $txt;

	echo '
			<div class="content">
				<div class="centertext">', $txt['lgal_no_items'], '</div>
			</div>';
}

function template_item_list($list)
{
	global $context, $txt, $settings;

	$slideshow = array();

	echo '
			<div class="album_container">';

	foreach ($context[$list] as $item)
	{
		echo '
				<div class="album_entry">
					<div class="well">';
		if (!empty($item['item_name']))
		{
			if (!empty($item['item_url']))
			{
				echo '
						<div class="thumb_name">
							<a href="', $item['item_url'], '">', $item['item_name'], '</a>
						</div>';
			}
			else
			{
				echo '
						<div class="thumb_name">', $item['item_name'], '</div>';
			}
		}

		echo '
						<div class="thumb_container">';
		if (!empty($item['item_url']))
		{
			// Build the Slideshow
			if (substr($item['mime_type'],0,6) === 'image/' && $item['extension'] !== 'tif')
			{
				$link = '<a class="linkbutton largetext" href="' . $item['item_url'] . '">'. $txt['lgal_item_info']  . '</a>';
				$slideshow[] = array(
					'href' => $item['mature'] && $item['hide_mature'] ? $item['thumbnail'] : substr($item['thumbnail'], 0, -6),
					'type' => 'image',
					'title' => $item['mature'] && $item['hide_mature'] ? sprintf($txt['lgal_mature_item'], $link) : $link
				);
			}
			else
			{
				$slideshow[] = array(
					'href' => $item['preview'] ?? $item['thumbnail'],
					'type' => 'image',
					'title' => '<a class="linkbutton largetext" href="' . $item['item_url'] . '">' . $txt['lgal_click_to_view'] . '</a>'
				);
			}

			echo '
							<a class="lgtip" href="', $item['item_url'], '" data-details="', $item['item_name'], '">
								<img src="', $item['thumbnail'], '"', $item['thumb_html'] ?? '', ' alt="', $item['item_name'], '" loading="lazy" />
							</a>';
		}
		elseif (!empty($item['thumbnail']))
		{
			echo '
							<a class="lgtip" href="#" data-details="', $item['item_name'], '">
								<img src="', $item['thumbnail'], '"', $item['thumb_html'] ?? '', ' alt="', $item['item_name'], '" loading="lazy" />
							</a>';
		}
		else
		{
			$title = sprintf($txt['lgal_missing_item'], $item['item_name']);
			echo '
							<a class="lgtip" href="#" title="', $title, '" >
								<img src="', $settings['default_theme_url'], '/levgal_res/icons/_invalid.png" alt="', $title, '" loading="lazy" />
							</a>';
		}

		echo '
							<br />';
		if (empty($item['approved']))
		{
			echo '
							<span class="lgalicon i-warning colorize-orange" title="', $txt['lgal_unapproved_item'], '"></span>';
		}

		echo '
							<span class="lgalicon i-view"></span> ', comma_format($item['num_views']), '
							<span class="lgalicon i-comments"></span> ', isset($item['total_comments']) ? comma_format($item['total_comments']) : comma_format($item['num_comments']), '
						</div>
					</div>
				</div>';
	}

	echo '
			</div>';

	addInlineJavascript('
		const myGallery = GLightbox({elements: 
		' . json_encode($slideshow, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . ',
		preload: false,
		touchNavigation: true
	});', true);
}

function template_sidebar_action_list($title, $action_list)
{
	global $txt;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $title, '
			</h3>
			<div class="content">
				<dl class="album_details">';

	$display_title = count($action_list) > 1;
	foreach ($action_list as $action_group => $actions)
	{
		echo '
					<dt>', $display_title ? $txt['lgal_item_actions_' . $action_group] : '', '</dt>
					<dd>
						<ul class="sidebar_actions">';

		foreach ($actions as $id_action => $action)
		{
			if (isset($action['sidebar']) && $action['sidebar'] === false)
			{
				continue;
			}
			echo '
							<li id="sidebar_', $action_group, '_', $id_action, '">
								<a href="', $action[1], '"', empty($action[2]) ? '' : ' class="new_win" target="_blank"', empty($action['title']) ? '' : ' title="' . $action['title'] . '"', empty($action['js']) ? '' : ' ' . $action['js'], '>
									<span class="lgalicon i-', colorize_actions($id_action), '"></span>', $action[0], '
								</a>
							</li>';
		}

		echo '
						</ul>
					</dd>';
	}

	echo '
				</dl>
			</div>';
}

function colorize_actions($id_action)
{
	switch ($id_action)
	{
		case 'deletealbum':
		case 'flag':
		case 'deleteitem':
		case 'unbookmark':
		case 'unnotify':
		case 'moderate':
			$id_action .= ' colorize-red';
			break;
		case 'editalbum':
		case 'edititem':
		case 'moveitem':
		case 'edit':
		case 'tag':
			$id_action .= ' colorize-dark-yellow';
			break;
		case 'feature_album':
		case 'stats':
			$id_action .= ' colorize-gold';
			break;
		case 'additem':
		case 'addalbum':
		case 'addbulk':
		case 'open':
			$id_action .= ' colorize-green';
			break;
		case 'movealbum':
		case 'search':
			$id_action .= ' colorize-blue';
			break;
	}

	return $id_action;
}

function template_action_strip($actions)
{
	echo '
			<ul class="reset smalltext lgalactions">';
	foreach ($actions as $action_id => $action_info)
	{
		echo '
				<li>
					<i class="icon i-' . colorize_actions($action_id) . '"></i>
					<a href="', $action_info['url'], '">', $action_info['title'], '</a>
				</li>';
	}
	echo '
			</ul>';
}

function template_album_hierarchy($hierarchy, $compact = false)
{
	global $txt;

	$last_level = -1;
	foreach ($hierarchy as $id_album => $album)
	{
		// This is strange but if we nest <ul>, we have to put them inside the last li.
		if ($album['album_level'] === $last_level)
		{
			// Same level as the last one. Just end the last item, and all is good.
			echo '
			</li>';
		}
		elseif ($album['album_level'] > $last_level)
		{
			// Oh, indenting another level, eh?
			echo '
			<ul class="album_hierarchy level_', $album['album_level'], '">';
			$last_level = $album['album_level'];
		}
		elseif ($album['album_level'] < $last_level)
		{
			// So we're leaving a level. Finish the right number of levels of tags. No point adding all the right numbers of tabs though.
			$levels = $last_level - $album['album_level'];
			for ($i = 0; $i < $levels; $i++)
			{
				echo '
			</li>
			</ul>';
			}
			// And one to close the containing <li>
			echo '
			</li>';
			$last_level = $album['album_level'];
		}

		echo '
			<li id="album_', $id_album, '" class="album_hierarchy' . ($compact ? ' compact' : '') . '">',
			$compact ? '' : '
			<a href="' . $album['album_url'] . '">', '
				<div class="well">
					<p class="floatleft sortable_album_thumb">
						<img src="', $album['thumbnail_url'], '" alt="" />
					</p>
					<p class="floatleft">', $compact
						? '<a class="album_name" href="' . $album['album_url'] . '"><strong>' . $album['album_name'] . '</strong></a>'
						: '<span class="album_name"><strong>' . $album['album_name'] . '</strong></span>', '
					</p>';

		if (empty($album['approved']))
		{
			echo '
					<p class="floatright">', $txt['lgal_unapproved_album'], '</p>';
		}

		if (!empty($album['featured']))
		{
			echo '
					<p class="lgal_profile_featured floatleft smalltext">
						<i class="lgalicon i-feature_album colorize-gold"></i> ', $txt['levgal_featured_album'], '
					</p>';
		}

		if (!$compact)
		{
			echo '
					<br />
					<p class="lgal_profile_album_contents floatleft">
						', $album['description_short'], '<br />
						<span class="lgalicon i-album" style="margin: 0"></span> ', LevGal_Helper_Format::numstring('lgal_items', $album['num_items']);

			if (!empty($album['see_unapproved']))
			{
				echo ',
						<span class="error"><i class="lgalicon i-flag colorize-red"></i>', $txt['lgal_unapproved'], ' [', LevGal_Helper_Format::numstring('lgal_items', $album['num_unapproved_items']), ']</span>';
			}

			echo '
					</p>';
		}

		echo '
				</div>', $compact ? '' : '</a>';
	}

	// And to finish off.
	for ($i = 0; $i <= $last_level; $i++)
	{
		echo '
			</li>
			</ul>';
	}
}

function template_album_hierarchy_compact($hierarchy)
{
	$last_level = -1;
	foreach ($hierarchy as $id_album => $album)
	{
		if ($album['album_level'] === $last_level)
		{
			// Same level as the last one. Just end the last item, and all is good.
			echo '
			</li>';
		}
		elseif ($album['album_level'] > $last_level)
		{
			// Oh, indenting another level, eh?
			echo '
			<ul class="album_hierarchy level_', $album['album_level'], '">';
			$last_level = $album['album_level'];
		}
		elseif ($album['album_level'] < $last_level)
		{
			// So we're leaving a level. Finish the right number of levels of tags. No point adding all the right numbers of tabs though.
			$levels = $last_level - $album['album_level'];
			for ($i = 0; $i < $levels; $i++)
			{
				echo '
			</li>
			</ul>';
			}
			// And one to close the containing <li>
			echo '
			</li>';
			$last_level = $album['album_level'];
		}

		echo '
			<li id="album_', $id_album, '" class="album_hierarchy_compact">
				<span class="lgal_profile_itemname">
					<a  href="', $album['album_url'], '">', $album['album_name'], '</a>
				</span>';

		echo '
				<span class="lgal_profile_itemnum">';

		if (!empty($album['num_items']))
		{
			echo '
					<i class="lgalicon i-album"></i> [', $album['num_items'], ']';
		}
		else {
			echo '
					--';
		}

		echo '
				</span>';
	}

	// And to finish off.
	for ($i = 0; $i <= $last_level; $i++)
	{
		echo '
			</li>
		</ul>';
	}
}

function template_album_list_none()
{
	global $txt;

	echo '
		<div class="content">
			<div class="centertext">', $txt['lgal_no_albums'], '</div>
		</div>';
}

function template_album_list_main($tree_view = false)
{
	global $context, $txt, $scripturl;

	echo '
	<div id="gallery_contain">';

	if (!empty($context['album_actions']))
	{
		template_album_list_action_tabs($context['album_actions'], !empty($context['nested_hierarchy']));
	}

	template_album_list_sidebar();

	if (!empty($context['hierarchy']))
	{
		echo '
			<div id="item_main">';
		template_album_list_header();
		template_album_hierarchy($context['hierarchy']);
		echo '
			</div>';
	}
	elseif (!empty($context['nested_hierarchy']))
	{
		echo '
			<div class="album_container">';

		foreach ($context['nested_hierarchy'] as $member => $hierarchy)
		{
			$data = array_values($hierarchy)[0];
			if (isset($data['id_member']))
			{
				$link = '<a href="' . $scripturl . '?media/albumlist/' . $data['id_member'] . '/member/">&nbsp;<strong>' . $member . '</strong></a>';
			}
			else
			{
				$link = '<a href="' . $scripturl . '?media/albumlist/' . $data['id_group'] . '/group/">&nbsp;<strong>' . $member . '</strong></a>';
			}

			if (!empty($tree_view))
			{
				echo '
				<h3 class="lgal_secondary_header secondary_header">' . sprintf($txt['lgal_albums_owned_site'], $link) . '</h3>';

				template_album_hierarchy($hierarchy);
			}
			else
			{
				echo '
				<div class="album_featured_compact">
					<h3 class="lgal_secondary_header secondary_header">' . sprintf($txt['lgal_albums_owned_site'], $link) . '</h3>
					<div class="content">';

				template_album_hierarchy_compact($hierarchy);

				echo '
					</div>
				</div>';
			}
		}

		if (!empty($context['item_pageindex']))
		{
			echo '
				<div class="pagesection" style="width:100%;margin:0 10px">', $context['item_pageindex'], '</div>';
		}

		echo '
			</div>';
	}
	else
	{
		echo '
		<div id="item_main">';
		$headings = ['site' => 'lgal_albums_site', 'groups' => 'lgal_albums_group', 'members' => 'lgal_albums_member'];
		foreach (['site', 'groups', 'members'] as $albumType)
		{
			echo '
			<div id="', $albumType, '">';

			if (!empty($context['album_owners'][$albumType]))
			{
				echo '
				<h3 class="lgal_secondary_header secondary_header">', $txt[$headings[$albumType]], '</h3>';

				if ($albumType === 'members' && !empty($context['item_pageindex']))
				{
					echo '
				<div class="pagesection">', $context['item_pageindex'], '</div>';
				}

				echo '
				<div class="album_container">';

				template_album_placecard($context['sidebar'][$albumType]['items'], $albumType === 'members');

				echo '
				</div>';

				if ($albumType === 'members' && !empty($context['item_pageindex']))
				{
					echo '
				<div class="pagesection">', $context['item_pageindex'], '</div>';
				}
			}

			echo '
			</div>';
		}
		echo '
			</div>';
	}

	// bottom navigation / page on the non-compact view
	if (!empty($context['album_actions']) && !empty($context['nested_hierarchy']) && !empty($context['item_pageindex']))
	{
		template_album_list_action_tabs($context['album_actions'], true);
	}

	echo '
	</div>';
}

function template_album_placecard($albumItems, $useAvatar = null)
{
	global $settings, $memberContext, $context;

	foreach ($albumItems as $item)
	{
		// Don't show the placard for members we did not load due to pagination
		if ($useAvatar && !empty($context['item_pageindex']) && empty($memberContext[$item['id']]))
		{
			continue;
		}

		echo '
					<a class="album_placard well" href="', $item['url'], '">
						<div class="floatleft album_thumb">';

		if ($useAvatar === true)
		{
			echo '
							', empty($memberContext[$item['id']]['avatar']['image']) ? '' : $memberContext[$item['id']]['avatar']['image'];
		}
		else
		{
			echo '
							<img src="', $settings['default_theme_url'], '/levgal_res/albums/folder-image.svg" alt="" />';
		}

		echo '
						</div>
						<div class="album_desc lefttext">
							', $item['title'], '<br />
							<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_albums', $item['count']), '
						</div>
					</a>';
	}
}

function template_album_list_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	foreach ($context['sidebar'] as $section)
	{
		echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $section['title'], '
			</h3>
			<div class="content">
				<dl class="album_details">
				<dt></dt>';
		foreach ($section['items'] as $item)
		{
			echo '
					<dd>
						<a href="', $item['url'], '">',
							empty($item['active']) ? $item['title'] : '<strong>' . $item['title'] . '</strong>', '
						</a> (', $item['count'], ')</dd>';
		}
		echo '
				</dl>
			</div>';
	}

	if (!empty($context['album_actions']['actions']))
	{
		template_sidebar_action_list($txt['lgal_album_actions'], $context['album_actions']);
	}

	echo '
		</div>';
}

function template_album_list_action_tabs($actions_groups, $page = null)
{
	global $context;

	if (empty($actions_groups['actions']))
	{
		return;
	}

	echo '
	<div class="levgal_navigation">';

	if ($page && !empty($context['item_pageindex']))
	{
		echo '
		<div class="pagesection">', $context['item_pageindex'], '</div>';
	}

	if (!empty($actions_groups['actions']))
	{
		echo '
		<ul class="levgal_tabs">';

		foreach ($actions_groups['actions'] as $id_action => $action)
		{
			if (!isset($action['tab']))
			{
				continue;
			}

			// Show active as bold
			$item = $action[0];
			if (!empty($action['active']))
				$item = '<strong>' . $item . '</strong>';

			echo '
			<li class="listlevel1 ', $id_action, '">
				<a class="linklevel1" href="', $action[1], '"', empty($action[2]) ? '' : ' class="new_win" target="_blank"', empty($action['title']) ? '' : ' title="' . $action['title'] . '"', empty($action['js']) ? '' : ' ' . $action['js'], '>
					<span class="lgalicon i-', colorize_actions($id_action), '"></span>', $item, '
				</a>
			</li>';
		}

		echo '
		</ul>';
	}

	echo '
		</div>';
}

function template_album_list_header()
{
	global $context;

	echo '
			<h2 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h2>';
}

function template_lgal_error_list($title, $list)
{
	global $txt;

	$errors = array();
	foreach ($list as $error)
	{
		$errors[] = $txt['levgal_error_' . $error] ?? $txt['error_' . $error] ?? $txt[$error] ?? $error;
	}

	echo '
		<div class="errorbox" id="errors"', empty($list) ? ' style="display:none"' : '', '>
			<dl>
				<dt>
					<strong id="error_serious">', $title, '</strong>
				</dt>
				<dd class="error" id="error_list">
					', implode('<br />', $errors), '
				</dd>
			</dl>
		</div>';
}
