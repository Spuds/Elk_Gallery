<?php
// Version: 1.0.4; Levertine Gallery tags template

/**
 * This file handles displaying the tagged items, either the cloud of all tags, or the items tagged with a single tag.
 *
 * @package levgal
 * @since 1.0
 */

function template_tagcloud()
{
	template_tags_sidebar();
	template_tagcloud_display();

	echo '
		<br class="clear" />';
}

function template_tags_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	// Information block
	echo '
			<h3 class="secondary_header">
				', $txt['levgal_tagcloud'], '
			</h3>
			<div class="content">
				<dl class="album_details">
					<dt></dt>
					<dd>
						<ul class="sidebar_actions">';
	foreach ($context['tags'] as $id_tag => $tag)
	{
		echo '
							<li><a href="', $tag['url'], '">', $context['selected_tag'] == $id_tag ? '<strong>' . $tag['name'] . '</strong>' : $tag['name'], '</a> (', comma_format($tag['count']), ')</li>';
	}
	echo '
						</ul>
					</dd>
				</dl>
			</div>';

	echo '
		</div>';
}

function template_tagcloud_display()
{
	global $context, $settings;

	echo '
	<div id="item_main">
		<h3 class="secondary_header">', $context['page_title'], '</h3>
		<div class="content">
			<div id="jqcloud" class="jqcloud"></div>
		</div>
	</div>
	<script src="', $settings['default_theme_url'], '/levgal_res/jqcloud/jqcloud.min.js"></script>
	<script>
		var tags = ', json_encode($context['json_export']), ';
		$(function() {
			$("#jqcloud").jQCloud(tags, {
				height: 300,
				autoResize: true
			})
		});
	</script>';
}

function template_tagmain()
{
	template_tags_sidebar();
	template_tagmain_display();

	echo '
		<br class="clear" />';
}

function template_tagmain_display()
{
	global $context;

	echo '
		<div id="item_main">
			<h3 class="secondary_header">', $context['page_title'], '</h3>';

	template_item_list('tagged_items');

	echo '
		</div>';
}
