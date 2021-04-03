<?php
// Version: 1.1.1; Levertine Gallery front page template

/**
 * This file handles displaying the front page of the gallery.
 *
 * @package levgal
 * @since 1.0
 */

function template_main()
{
	template_main_page_sidebar();
	template_main_page_display();

	echo '
		<br class="clear" />';
}

function template_main_page_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	// Information block
	echo '
			<h3 class="secondary_header">
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

		echo '
			<br />';
	}

	template_display_latest_items();

	echo '
			<br />';

	template_display_random_items();

	echo '
		</div>';
}

function template_display_featured_albums()
{
	global $txt;
	echo '
			<h3 class="secondary_header">', $txt['levgal_featured_albums'], '</h3>';

	template_display_album_list('featured_albums');
}

function template_display_album_list($list)
{
	global $context;

	echo '
			<div class="album_container">';

	foreach ($context[$list] as $album)
	{
		echo '
				<div class="album_featured well">
					<div class="floatleft album_thumb">
						<img src="', $album['thumbnail_url'], '" alt="" />
					</div>
					<div class="album_desc lefttext">
						', empty($album['featured']) ? '' : '<span class="lgalicon i-star colorize-gold"></span> ', '<a href="', $album['album_url'], '">', $album['album_name'], '</a><br />
					</div>
					<div class="lefttext">
						<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_items', $album['num_items']), ' / ', LevGal_Helper_Format::numstring('lgal_albums', $album['album_count']), '
					</div>
				</div>';
	}

	echo '
				<br class="clear" />
			</div>';
}

function template_display_latest_items()
{
	global $context, $txt;
	echo '
			<h3 class="secondary_header">', $txt['lgal_latest_items'], '</h3>';

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
			<h3 class="secondary_header">', $txt['lgal_random_items'], '</h3>';

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
		if (!empty($item['item_url'])) {
			echo '
							<a href="', $item['item_url'], '">
								<img src="', $item['thumbnail'], '" alt="', $item['item_name'], '" title="', $item['item_name'], '" />
							</a>';
		} elseif (!empty($item['thumbnail'])) {
			echo '
							<a href="#">
								<img src="', $item['thumbnail'], '" alt="', $item['item_name'], '" title="', $item['item_name'], '" />
							</a>';
		} else
		{
			$title = sprintf($txt['lgal_missing_item'], $item['item_name']);
			echo '
							<a href="#">
								<img src="', $settings['default_theme_url'], '/levgal_res/icons/_invalid.png" alt="', $title, '" title="', $title, '" />
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
}

function template_sidebar_action_list($title, $action_list)
{
	global $txt;

	echo '
			<h3 class="secondary_header">
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
			$id_action .= ' colorize-red';
			break;
		case 'editalbum':
		case 'edititem':
		case 'moveitem':
			$id_action .= ' colorize-dark-yellow';
			break;
		case 'feature_album':
			$id_action .= ' colorize-gold';
			break;
		case 'additem':
		case 'addbulk':
		case 'setthumbnail':
			$id_action .= ' colorize-green';
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
				<li class="lgal_', $action_id, '"><a href="', $action_info['url'], '">', $action_info['title'], '</a></li>';
	}
	echo '
			</ul>';
}

function template_album_hierarchy($hierarchy)
{
	global $txt, $settings;

	$last_level = -1;
	foreach ($hierarchy as $id_album => $album)
	{
		// This is strange but if we nest <ul>, we have to put them inside the last li.
		if ($album['album_level'] == $last_level)
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
			<li id="album_', $id_album, '" class="album_hierarchy">
					<div class="well">
						<p class="floatleft sortable_album_thumb">
							<img src="', $album['thumbnail_url'], '" alt="" />
						</p>
						<p class="lgal_profile_itemname floatleft">
							<a href="', $album['album_url'], '">', $album['album_name'], '</a>
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
							<img src="', $settings['default_theme_url'], '/levgal_res/buttons/feature.png" alt="" /> ', $txt['levgal_featured_album'], '
						</p>';
		}

		echo '
						<br />
						<p class="lgal_profile_album_contents floatleft">
							<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_items', $album['num_items']);

		if (!empty($album['see_unapproved']))
		{
			echo ',
							<span class="error">', $txt['lgal_unapproved'], ' [', LevGal_Helper_Format::numstring('lgal_items', $album['num_unapproved_items']), ']</span>';
		}

		echo '
						</p>';

		echo '
						<br class="clear" />
				</div>';
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

function template_album_list_main()
{
	global $context, $txt, $memberContext, $settings;

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
	else
	{
		echo '
			<div id="item_main">';

		if (!empty($context['album_owners']['members']))
		{
			echo '
			<h3 class="secondary_header">', $txt['lgal_albums_member'], '</h3>
			<div class="album_container">';

			foreach ($context['sidebar']['members']['items'] as $member)
			{
				echo '
				<div class="album_featured well">
					<div class="floatleft album_thumb">
						', empty($memberContext[$member['id']]['avatar']['image']) ? '' : $memberContext[$member['id']]['avatar']['image'], '
					</div>
					<div class="album_desc lefttext">
						<a href="', $member['url'], '">', $member['title'], '</a><br />
						<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_albums', $member['count']), '
					</div>
				</div>';
			}

			echo '
			</div>';
		}

		if (!empty($context['album_owners']['groups']))
		{
			echo '
			<h3 class="secondary_header">', $txt['lgal_albums_group'], '</h3>
			<div class="album_container">';

			foreach ($context['sidebar']['groups']['items'] as $group)
			{
				echo '
				<div class="album_featured well">
					<div class="floatleft album_thumb">
						<img src="', $settings['default_theme_url'], '/levgal_res/albums/folder-image.png" alt="" />
					</div>
					<div class="album_desc lefttext">
						<a href="', $group['url'], '">', $group['title'], '</a><br />
						<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_albums', $group['count']), '
					</div>
				</div>';
			}

			echo '
			</div>';
		}

		echo '
			</div>';
	}

	echo '
		<br class="clear" />';
}

function template_album_list_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	foreach ($context['sidebar'] as $section)
	{
		echo '
			<h3 class="secondary_header">
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

	if (!empty($context['album_actions']))
	{
		template_sidebar_action_list($txt['lgal_album_actions'], $context['album_actions']);
	}

	echo '
		</div>';
}

function template_album_list_header()
{
	global $context;

	echo '
			<h3 class="secondary_header">', $context['page_title'], '</h3>';
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
