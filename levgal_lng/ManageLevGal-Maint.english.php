<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.0
 * @package levgal
 * @since 1.0
 */
// Version: 1.0; ManageLevGal maintenance

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['levgal_maint_desc'] = 'From here you can carry out various functions in the event of unexpected behavior in the gallery.';
$txt['levgal_run_task'] = 'Run this task now';
$txt['levgal_maint_success'] = 'Maintenance task "%1$s" completed successfully.';
$txt['levgal_recovered_album'] = 'Recovered Album';
$txt['levgal_task_recount'] = 'Recount Statistics';
$txt['levgal_task_desc_recount'] = 'Force a recount of statistics and other internal figures. Useful if the stats appear to be wrong for any reason.';
$txt['levgal_task_findfix'] = 'Find and Fix Errors';
$txt['levgal_task_desc_findfix'] = 'Examine the database for inconsistencies and attempt to fix them. This can be a long process. You should probably put the forum into maintenance mode before running this.';
$txt['levgal_findfix_substep_fixOrphanAlbumHierarchy'] = 'Checking for albums without proper owners...';
$txt['levgal_findfix_substep_fixOrphanItems'] = 'Checking for items in non-existent albums...';
$txt['levgal_findfix_substep_fixOrphanComments'] = 'Checking for comments on non-existent items...';
$txt['levgal_findfix_substep_fixOrphanBookmarks'] = 'Checking for bookmarks on non-existent items or users...';
$txt['levgal_findfix_substep_fixOrphanLikes'] = 'Checking for likes on non-existent items or users...';
$txt['levgal_findfix_substep_fixOrphanTags'] = 'Checking for non-existent tags or tags on non-existent items...';
$txt['levgal_findfix_substep_fixOrphanNotify'] = 'Checking for notifications on non-existent albums, items or users...';
$txt['levgal_findfix_substep_fixOrphanUnseen'] = 'Checking for unseen logs for non-existent items or users...';
$txt['levgal_findfix_substep_fixOrphanReports'] = 'Checking for moderation reports with missing or non-existent details...';
$txt['levgal_findfix_substep_fixOrphanCustomFields'] = 'Checking for custom fields with non-existent fields or items...';
$txt['levgal_findfix_substep_checkMissingFiles'] = 'Checking for missing files...';
$txt['levgal_findfix_substep_checkExtraFiles'] = 'Checking for extraneous files...';
$txt['levgal_findfix_substep_checkAlbumFiles'] = 'Checking for extraneous album-related files...';

$txt['levgal_task_rebuildsearch'] = 'Rebuild Search Index';
$txt['levgal_task_desc_rebuildsearch'] = 'A search index enables searching of the media items to happen quickly. If there are problems with the search function, it may mean the index has become corrupted; a rebuild should fix this.';
$txt['levgal_task_rebuildsearch_album_subtitle'] = 'Rebuilding album index...';
$txt['levgal_task_rebuildsearch_item_subtitle'] = 'Rebuilding item index...';
$txt['levgal_task_rebuildthumbs'] = 'Rebuild Item Thumbnails';
$txt['levgal_task_desc_rebuildthumbs'] = 'If there are issues with thumbnails or previews, this maintenance option will rebuild them from the item where possible, and if they were not manually uploaded by a user.';
