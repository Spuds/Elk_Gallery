<?php

/**
 * This file handles displaying the setup for editing album hierarchies.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_movealbum_header()
{
	global $txt;

	echo '
		<h2 class="lgal_secondary_header secondary_header">', $txt['lgal_arrange_albums'], '</h2>
		<p class="infobox">', $txt['lgal_arrange_albums_desc'], '</p>
		<div class="errorbox" id="errors" style="display:none">
			<div class="error">', $txt['lgal_invalid_saving_order'], '</div>
		</div>';
}

function template_movealbum()
{
	global $context, $txt;

	template_movealbum_header();

	template_album_hierarchy($context['hierarchy'], true);

	echo '
		<br />
		<div class="submitbutton">
			<input type="button" id="savealpha" value="Arrange by name" onclick="return confirm(\'', $txt['lgal_arrange_albums_save_alpha'], '\') && doalphasave();" />
			<input type="button" id="saveorder" value="', $txt['lgal_arrange_albums_save'], '" onclick="return dosave();" />
		</div>
		<br class="clear" />';

	template_movealbum_js();
}

function template_movealbum_js()
{
	global $settings, $context;

	echo '
	<script src="', $settings['default_theme_url'], '/levgal_res/jquery.nestedSortable.js"></script>
	<script>
	$(".album_hierarchy.level_0").nestedSortable({
		handle: "div",
		items: "li",
		listType: "ul",
		errorClass: "sortable_error",
		placeholder: "placeholder",
		forcePlaceholderSize: true,
		maxLevels: 100,
		tabSize: 25,
		revert: 150,
		tolerance: "pointer",
		toleranceElement: "> div",
		update: function (event, ui) { $("#saveorder").show();  $("#savealpha").hide(); },
		rtl: ', $context['right_to_left'] ? 'true' : 'false', '
	});
	$("#saveorder").hide();
	function dosave()
	{
		ajax_indicator(true);
		$("#saveorder").prop(\'disabled\', true);
		$.post(
			elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['form_url']), ',
			"saveorder=1&" + ', JavaScriptEscape($context['session_var']), ' + "=" + ', JavaScriptEscape($context['session_id']), ' + "&" + $(".album_hierarchy.level_0").nestedSortable("serialize"),
			function() {
				ajax_indicator(false);
				$("#saveorder").prop("disabled", false).hide();
				window.location = elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['return_url']), ';
			}
		).fail(function() {
			ajax_indicator(false);
			$("#saveorder").hide();
			$("#errors").show();
		});
	}
	
	/**
	 * Creates and saves the albums list alphabetically.
	 *
	 * This method performs the following actions:
	 * - Reads the HTML and creates an albums array
	 * - Sorts the albums array alphabetically
	 * - Renders the sorted albums list in a hidden div
	 * - Performs an AJAX POST request to save the sorted order
	 *
	 * @return {void}
	 */
	function doalphasave()
	{
		ajax_indicator(true);
		$("#savealpha").prop(\'disabled\', true);
	
		const $albumsList = $(".album_hierarchy.level_0");
		const yourAlbumsArray = readHtmlAndCreateAlbumsArray($albumsList);
	
		// Sort albums
		const sortedAlbums = sortAlbums(yourAlbumsArray);
	
		// Render the sorted albums list in a hidden ul
		$albumsList.after(\'<ul id="sortByAlpha" class="album_hierarchy level_0 hide"></ul>\');	
		renderAlbumsList(sortedAlbums, $("#sortByAlpha"));
		
		// Attach nestedSortable so we can serialize the new UL
		$("#sortByAlpha").nestedSortable({
			items: "li",
			listType: "ul",
			errorClass: "sortable_error",
			maxLevels: 100,
		});

		// Submit it to save the new sorting
		$.post(
			elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['form_url']), ',
				"saveorder=1&" + ', JavaScriptEscape($context['session_var']), ' + "=" + ', JavaScriptEscape($context['session_id']), ' + "&" + $("#sortByAlpha").nestedSortable("serialize"),
			function() {
				ajax_indicator(false);
				$("#savealpha").prop("disabled", false).hide();
				window.location = elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['return_url']), ';
			}
			).fail(function() {
				ajax_indicator(false);
				$("#savealpha").hide();
				$("#errors").show();
			});
	}

	/**
	 * Sort albums in ascending order by name.
	 *
	 * @param {Array} albums - The array of albums to be sorted.
	 * @returns {Array} - The sorted array of albums.
	 */
	function sortAlbums(albums) {
		if (!albums || albums.length === 0) {
			return [];
		}
	
		// Sort albums by name
		albums.sort((a, b) => a.name.localeCompare(b.name));
	
		// Recursively sort subalbums
		albums.forEach(album => {
			if (album.subalbums && album.subalbums.length > 0) {
				album.subalbums = sortAlbums(album.subalbums);
			}
		});
	
		return albums;
	}
	
	/**
	 * Reads the page HTML UL/LI structure and creates an array of albums/subalbums.
	 *
	 * @param {Object} $parent - The parent element to start reading the HTML from.
	 * @param {number} [depth=0] - The depth of the current album.
	 * @returns {Array} - An array of album objects.
	 */
	function readHtmlAndCreateAlbumsArray($parent, depth = 0) {
		const albumsArray = [];
	
		$parent.children("li").each(function () {
			const $album = $(this);
			const albumData = {
				id: $album.attr("id"),
				name: $album.find(".album_name").first().text().replace(/[\n\t]/g, ""),
				depth: depth
			};
	
			const $subList = $album.children("ul");
			if ($subList.length > 0) {
				albumData.subalbums = readHtmlAndCreateAlbumsArray($subList, depth + 1);
			}
	
			albumsArray.push(albumData);
		});
	
		return albumsArray;
	}
	
	/**
	 * Renders a list of albums and their subalbums.  This is done in a hidden ul that
	 * is read / serialized by nestedSortable and submitted as thought the user did the work
	 *
	 * @param {Array} albums - An array of album objects
	 * @param {jQuery} $parent - The jQuery object representing the parent element where the album list will be rendered
	 * @param {boolean} [isNested=false] - Indicates whether the current level of albums is nested within another album
	 */
	function renderAlbumsList(albums, $parent, isNested = false) {
		albums.forEach(album => {
			const $li = $("<li>").text(album.name).attr("id", album.id).attr("class", "album_hierarchy compact");
	
			if (album.subalbums && album.subalbums.length > 0) {
				const $subList = $("<ul>").addClass("album_hierarchy level_" + album.depth)
				renderAlbumsList(album.subalbums, $subList, true);
				$li.append($subList);
			}
			$parent.append($li);
		});
	}
	</script>';
}