<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0
 * @package levgal
 * @since 1.0
 */
// Version: 1.0; LevGal errors

// Important! Before editing these language files please read the text at the top of index.english.php.

// General errors
$txt['levgal_invalid_action'] = 'Sorry, the requested page could not be found.';
$txt['levgal_no_image_support'] = 'Sorry, no valid support was found for resizing images.';
$txt['error_no_album'] = 'Sorry, no album was found. It may be off limits or have been removed.';
$txt['error_no_item'] = 'Sorry, no item was found. It may be off limits or have been removed.';
$txt['error_no_tags'] = 'Sorry, no items that you can see have been tagged with anything.';
$txt['levgal_gallery_over_quota'] = 'The media gallery has a limit of %1$s of files to be uploaded. More cannot be added until back under that quota, or the administrator changes the quota.';

// Permission dependent
$txt['cannot_lgal_view'] = 'Sorry, you are not permitted to see the media gallery.';
$txt['cannot_lgal_moderate'] = 'Sorry, you are not permitted to see the media gallery moderation area.';

$txt['cannot_lgal_addalbum'] = 'Sorry, you are not permitted to create a new album.';
$txt['cannot_lgal_approve_album'] = 'Sorry, you are not permitted to approve this album.';
$txt['cannot_lgal_edit_album'] = 'Sorry, you are not permitted to edit this album.';
$txt['cannot_lgal_delete_album'] = 'Sorry, you are not permitted to delete this album.';

$txt['cannot_lgal_additem'] = 'Sorry, you are not permitted to add a new item here.';
$txt['cannot_lgal_addbulk'] = 'Sorry, you are not permitted to upload items in bulk.';
$txt['cannot_lgal_approve_item'] = 'Sorry, you are not permitted to approve this item.';
$txt['cannot_lgal_edit_item'] = 'Sorry, you are not permitted to edit this item.';
$txt['cannot_lgal_delete_item'] = 'Sorry, you are not permitted to delete this item.';
$txt['cannot_lgal_move_item'] = 'Sorry, you are not permitted to move this item.';

$txt['cannot_lgal_add_comment'] = 'Sorry, you are not permitted to comment on this item.';
$txt['cannot_lgal_approve_comment'] = 'Sorry, you are not permitted to approve that comment.';
$txt['cannot_lgal_edit_comment'] = 'Sorry, you are not permitted to edit that comment.';
$txt['cannot_lgal_delete_comment'] = 'Sorry, you are not permitted to delete that comment.';

$txt['cannot_lgal_flag_item'] = 'Sorry, you are not permitted to report items to the moderators.';
$txt['cannot_lgal_flag_comment'] = 'Sorry, you are not permitted to report comments to the moderators.';
$txt['cannot_lgal_setthumbnail'] = 'Sorry, you are not permitted to use this item as album thumbnail.';

$txt['cannot_lgal_feed'] = 'Sorry, this feed is disabled.';

// Situation dependent
$txt['lgal_no_album_destination'] = 'Sorry, you cannot move this item; there are no other albums to move it to.';
$txt['lgal_missing_required_field'] = 'You need to fill in the field marked "%1$s".';
$txt['lgal_invalid_number_range'] = 'The field marked "%1$s" is required to be a number between %2$s and %3$s.';
$txt['lgal_string_too_long'] = 'The field marked "%1$s" is too long; only %2$s characters are allowed.';
$txt['lgal_invalid_email_field'] = 'The field marked "%1$s" is required to be an email address.';
$txt['lgal_invalid_numbers_field'] = 'The field marked "%1$s" is required to be all numeric digits (0-9)';
$txt['lgal_invalid_field'] = 'The field marked "%1$s" is not valid.';
