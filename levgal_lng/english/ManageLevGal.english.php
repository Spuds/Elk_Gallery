<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1
 * @package levgal
 * @since 1.0
 */
// Version: 1.0; ManageLevGal

// Important! Before editing these language files please read the text at the top of index.english.php.

// Generics
$txt['levgal_admin_js'] = 'The administration of Levertine Gallery really requires your browser to support JavaScript in order to function.';

// Scheduled tasks.
$txt['scheduled_task_levgal_maintenance'] = 'Levertine Gallery maintenance';
$txt['scheduled_task_desc_levgal_maintenance'] = 'This carries out essential daily maintenance for the gallery, and *should not* be disabled.';

// General admin stuff
$txt['levgal_admindash'] = 'Media Dashboard';
$txt['levgal_admindash_desc'] = 'From this area you can get an overview of your media.';

// The rest of the dashboard stats are in LevGal-Stats.language.php
$txt['levgal_stats_installed_time'] = 'Installed on:';
$txt['levgal_support_information'] = 'Support Information';
$txt['levgal_support'] = 'If you have any questions about Levertine Gallery, please contact <a href="http://levertine.com/">Levertine.com</a> who can assist you.';
$txt['levgal_versions_elk'] = 'ElkArte Version:';
$txt['levgal_versions_lgal'] = 'Levertine Gallery:';
$txt['levgal_versions_php'] = 'PHP:';
$txt['levgal_versions_GD'] = 'GD library:';
$txt['levgal_versions_Imagick'] = 'ImageMagick (Imagick):';
$txt['levgal_support_notavailable'] = '(not available)';
$txt['levgal_support_available'] = '(available)';
$txt['levgal_support_warning'] = '(not configured correctly)';
$txt['levgal_uploaded_items'] = 'Uploaded Items';
$txt['levgal_news_from_home'] = 'News from Levertine.com';
$txt['levgal_news_item'] = '%1$s by %2$s, %3$s';
$txt['levgal_news_not_available'] = 'News is not currently available.';
$txt['levgal_out_of_date'] = 'Your version is %1$s, the current version is %2$s, you should probably update the gallery.';

// Settings
$txt['levgal_settings'] = 'Media Settings';
$txt['levgal_settings_desc'] = 'This page allows you to configure some global options within the gallery area.';
$txt['lgal_count_author_views'] = 'Count author views on items';
$txt['lgal_enable_mature'] = 'Enable mature items';
$txt['lgal_enable_mature_desc'] = 'Items can be marked mature; this means a warning is shown to users before they view such items.';
$txt['lgal_feed_enable_album'] = 'Enable RSS feed for items in an album';
$txt['lgal_feed_items_album'] = 'Number of most recent items to show an album\'s RSS feed';
$txt['lgal_feed_enable_item'] = 'Enable RSS feed for comments on an item';
$txt['lgal_feed_items_item'] = 'Number of most recent comments to show in an item\'s RSS feed';
$txt['lgal_feed_items_limits'] = '(1-50 items)';
$txt['lgal_settings_social'] = 'Allow users to easily share items on social networks';
$txt['lgal_settings_select_networks'] = 'Select social networks';
$txt['lgal_settings_metadata'] = 'Display additional metadata';
$txt['lgal_settings_metadata_desc'] = 'Pictures, audio and video usually have extra information in them that can be interesting or useful to show to users.';
$txt['lgal_settings_metadata_types'] = 'Metadata options';
$txt['lgal_settings_metadata_images'] = 'Images (photographs)';
$txt['lgal_settings_metadata_audio'] = 'Audio files';
$txt['lgal_settings_metadata_video'] = 'Video files';
$txt['lgal_opts_metadata_datetime'] = 'Time taken';
$txt['lgal_opts_metadata_make'] = 'Camera make/model';
$txt['lgal_opts_metadata_flash'] = 'Flash settings';
$txt['lgal_opts_metadata_exposure_time'] = 'Exposure time';
$txt['lgal_opts_metadata_fnumber'] = 'F-number (e.g. <em>&#402;</em>/2.4, see <a href="https://en.wikipedia.org/wiki/F-number" class="new_win" target="_blank">Wikipedia</a> for more)';
$txt['lgal_opts_metadata_shutter_speed'] = 'Shutter speed';
$txt['lgal_opts_metadata_focal_length'] = 'Focal length';
$txt['lgal_opts_metadata_digitalzoom'] = 'Digital zoom';
$txt['lgal_opts_metadata_brightness'] = 'Brightness';
$txt['lgal_opts_metadata_contrast'] = 'Contrast';
$txt['lgal_opts_metadata_sharpness'] = 'Sharpness';
$txt['lgal_opts_metadata_isospeed'] = 'ISO Speed Rating';
$txt['lgal_opts_metadata_lightsource'] = 'Light source';
$txt['lgal_opts_metadata_exposure_prog'] = 'Exposure Program';
$txt['lgal_opts_metadata_metering_mode'] = 'Metering mode';
$txt['lgal_opts_metadata_sensitivity'] = 'Sensitivity type';
$txt['lgal_opts_metadata_title'] = 'Title';
$txt['lgal_opts_metadata_artist'] = 'Artist';
$txt['lgal_opts_metadata_album_artist'] = 'Album artist (if different to track artist)';
$txt['lgal_opts_metadata_album'] = 'Album name';
$txt['lgal_opts_metadata_track_number'] = 'Track number';
$txt['lgal_opts_metadata_genre'] = 'Genre';
$txt['lgal_opts_metadata_playtime'] = 'Running time';
$txt['lgal_opts_metadata_bitrate'] = 'Bitrate';
$txt['lgal_opts_metadata_subject'] = 'Subject';
$txt['lgal_opts_metadata_author'] = 'Author';
$txt['lgal_opts_metadata_keywords'] = 'Keywords';
$txt['lgal_opts_metadata_comment'] = 'Comment';

// Permissions
$txt['levgal_perms'] = 'Media Permissions';
$txt['levgal_perms_general'] = 'General Permissions';
$txt['permissionname_lgal_view'] = 'Groups that can view the gallery';
$txt['permissionname_lgal_manage'] = 'Groups that can manage the gallery';
$txt['lgal_manage_note'] = 'Gallery managers have full permissions within the gallery and can do anything.';
$txt['levgal_perms_album'] = 'Album Permissions';
$txt['permissionname_lgal_adduseralbum'] = 'Groups that can add new personal albums';
$txt['permissionname_lgal_addgroupalbum'] = 'Groups that can add new group albums';
$txt['permissionname_lgal_addalbum_approve'] = 'Groups that can add albums (subject to the above) without having to wait for approval';
$txt['permissionname_lgal_approve_album'] = 'Groups that can approve new gallery albums';
$txt['permissionname_lgal_edit_album_own'] = 'Groups that can edit their own albums';
$txt['permissionname_lgal_edit_album_any'] = 'Groups that can edit any album';
$txt['permissionname_lgal_delete_album_own'] = 'Groups that can delete their own albums';
$txt['permissionname_lgal_delete_album_any'] = 'Groups that can delete any albums';
$txt['levgal_perms_item'] = 'Item Permissions';
$txt['permissionname_lgal_additem_own'] = 'Groups that can add items to their own albums';
$txt['permissionname_lgal_additem_any'] = 'Groups that can add items to any albums';
$txt['permissionname_lgal_addbulk'] = 'Groups that can add items in bulk to albums (subject to the above)';
$txt['permissionname_lgal_additem_approve'] = 'Groups that can add items (subject to the above) without having to wait for approval';
$txt['permissionname_lgal_approve_item'] = 'Groups that can approve gallery items';
$txt['permissionname_lgal_edit_item_own'] = 'Groups that can edit their own items';
$txt['permissionname_lgal_edit_item_any'] = 'Groups that can edit any items';
$txt['permissionname_lgal_delete_item_own'] = 'Groups that can delete their own items';
$txt['permissionname_lgal_delete_item_any'] = 'Groups that can delete any items';
$txt['levgal_perms_comments'] = 'Commenting Permissions';
$txt['permissionname_lgal_comment'] = 'Groups that can add comments';
$txt['permissionname_lgal_comment_appr'] = 'Groups that can comment (subject to the above) without having to wait for approval';
$txt['permissionname_lgal_approve_comment'] = 'Groups that can approve member comments';
$txt['permissionname_lgal_edit_comment_own'] = 'Groups that can edit their own comments';
$txt['permissionname_lgal_edit_comment_any'] = 'Groups that can edit any comments';
$txt['permissionname_lgal_delete_comment_own'] = 'Groups that can remove their own comments';
$txt['permissionname_lgal_delete_comment_any'] = 'Groups that can remove any comments';
$txt['levgal_perms_moderation'] = 'Moderation Permissions';
$txt['levgal_perms_moderation_desc'] = 'The above permissions are for very broad groups; you may wish to let users do moderation on the things they upload without granting lots of permissions.';
$txt['lgal_selfmod_approve_item'] = 'Users can approve others\' items being posted in their albums';
$txt['lgal_selfmod_approve_comment'] = 'Users can approve others\' comments on their items';
$txt['lgal_selfmod_edit_comment'] = 'Users can edit others\' comments on their items';
$txt['lgal_selfmod_delete_comment'] = 'Users can delete others\' comments on their items';
$txt['lgal_selfmod_lock_comment'] = 'Users can lock their items from being commented upon';
$txt['lgal_media_prefix'] = '[Media] %1$s';

// ACP Media notification Settings
$txt['setting_lglike'] = 'Gallery Likes';
$txt['setting_lgcomment'] = 'Gallery Comments';
$txt['setting_lgnew'] = 'Gallery New Items';

// Quotas; the rest is in ManageLevGal-Quotas.language.php
$txt['levgal_quotas'] = 'Media Quotas and File Types';

// Custom Fields; the rest is in ManageLevGal-CFields.language.php
$txt['levgal_cfields'] = 'Media Custom Fields';

// Maintenance; the rest is in ManageLevGal-Maint.language.php
$txt['levgal_maint'] = 'Media Maintenance';

// Moderation Log
$txt['levgal_modlog'] = 'Media Moderation Log';
$txt['levgal_modlog_desc'] = 'From here you can observe all the moderation actions carried out in the media area.';
$txt['levgal_modlog_empty'] = 'There are no events in the moderation log.';
$txt['levgal_modlog_action'] = 'Action taken';
$txt['levgal_modlog_time'] = 'Date/Time';
$txt['levgal_modlog_member'] = 'Member';
$txt['levgal_modlog_position'] = 'Position';
$txt['levgal_modlog_ip'] = 'IP Addr.';
$txt['levgal_modlog_remove'] = 'Remove';
$txt['levgal_modlog_removeall'] = 'Remove All';

// Credits
$txt['levgal_credits'] = 'Media Credits';
$txt['levgal_credits_title'] = 'Levertine Gallery Credits';
$txt['levgal_credits_desc'] = 'All the lovely people who contributed to Levertine Gallery\'s development.';
$txt['levgal_credits_developers_title'] = 'Developers';
$txt['levgal_credits_developers_desc'] = 'The people who built Levertine Gallery:';
$txt['levgal_credits_components_title'] = 'Components';
$txt['levgal_credits_components_desc'] = 'Components and additional software used by Levertine Gallery:';
$txt['levgal_credits_images_title'] = 'Images';
$txt['levgal_credits_images_desc'] = 'Images and icons used by Levertine Gallery:';
$txt['levgal_credits_translators_title'] = 'Translators';
$txt['levgal_credits_translators_desc'] = 'The people that helped make it world-ready:';
$txt['levgal_credits_people_title'] = 'Thanks';
$txt['levgal_credits_people_desc'] = 'Other people the author would like to thank:';

// Importers; the rest is in ManageLevGal-Importer.language.php
$txt['levgal_importers'] = 'Media Importers';
