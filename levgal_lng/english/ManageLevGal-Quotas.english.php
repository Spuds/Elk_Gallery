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
// Version: 1.0; ManageLevGal quotas

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['levgal_quotas_desc'] = 'This page allows you to configure all the different limits of how much people can upload to your gallery.';
$txt['lgal_max_space'] = 'The maximum server space that is allowed to be used by all your gallery files';
$txt['lgal_max_space_note'] = 'You can use numbers with K, M or G, for example where 100M means 100 megabytes of space will be used.';

$txt['lgal_enable_resize'] = 'Resize images over quota dimensions.';
$txt['lgal_enable_resize_note'] = 'This will attempt to resize PNG and JPG images that are over the quota dimensions instead of immediately rejecting them.  If you do not have Imagick installed you will loose the EXIF data for resized images.';

$txt['levgal_allowed_file_types'] = 'Allowed File Types';
$txt['levgal_max_file_size'] = 'Maximum file size';
$txt['levgal_quota_groups'] = 'Groups';
$txt['levgal_gallery_managers'] = '(gallery managers)';
$txt['levgal_no_upload'] = 'Groups that have no upload quota (and thus no uploads):';
$txt['levgal_none'] = '(none)';
$txt['levgal_add_quota'] = 'Add Quota';
$txt['levgal_update'] = 'Update';
$txt['levgal_cancel'] = 'Cancel';
$txt['levgal_remove'] = 'Remove';
$txt['levgal_quota_no_groups_selected'] = 'You did not select any groups, a quota applies to at least one group.';
$txt['levgal_quota_invalid_filesize'] = 'You did not enter a valid file size. It needs to be a number followed by K, M or G, e.g. 100M for 100MB.';
$txt['levgal_quota_invalid_imagesize'] = 'You need to specify a width and a height in pixels for the largest image you want to allow, e.g. 1000 &times; 1000 pixels.';
$txt['levgal_changes_not_saved'] = '<strong>Note:</strong> Your changes have not yet been saved. Please remember to use the [Save] button at the bottom to save all changes.';

$txt['levgal_quotas_image_title'] = 'Images';
$txt['lgal_enable_image'] = 'Allow users to upload image files';
$txt['levgal_max_image_size'] = 'Maximum pixel size';
$txt['levgal_max_image_size_unlimited'] = '(no maximum size)';
$txt['levgal_max_image_size_defined'] = 'Set to no larger than:';
$txt['levgal_max_image_size_placeholder'] = '%1$s &times; %2$s pixels';
$txt['lgal_allowed_types_image'] = 'Types of image file users are allowed to upload';
$txt['lgal_image_jpg'] = 'JPEG images (.jpg, .jpeg)';
$txt['lgal_image_gif'] = 'GIF images (.gif)';
$txt['lgal_image_png'] = 'PNG images (.png)';
$txt['lgal_image_psd'] = 'Photoshop files (.psd)';
$txt['lgal_image_tiff'] = 'TIFF images (.tiff)';
$txt['lgal_image_mng'] = 'MNG images (.mng)';
$txt['lgal_image_iff'] = 'IFF images (.iff, .lbm)';

$txt['levgal_quotas_audio_title'] = 'Audio';
$txt['lgal_enable_audio'] = 'Allow users to upload audio files';
$txt['lgal_allowed_types_audio'] = 'Types of audio file users are allowed to upload';
$txt['lgal_audio_mp3'] = 'MP3 audio (.mp3)';
$txt['lgal_audio_m4a'] = 'MP4/M4A audio (.mp4, .m4a)';
$txt['lgal_audio_oga'] = 'Ogg audio (.ogg, .oga)';
$txt['lgal_audio_flac'] = 'FLAC lossless (.flac)';
$txt['lgal_audio_wav'] = 'Wave files (.wav)';

$txt['levgal_quotas_video_title'] = 'Video';
$txt['lgal_enable_video'] = 'Allow users to upload video files';
$txt['lgal_allowed_types_video'] = 'Types of video file users are allowed to upload';
$txt['lgal_video_m4v'] = 'MP4 video (.mp4, .m4v)';
$txt['lgal_video_ogv'] = 'Ogg video (.ogg, .ogv)';
$txt['lgal_video_mov'] = 'QuickTime video (.mov, .qt, .mqv)';
$txt['lgal_video_webm'] = 'WebM video (.webm)';

$txt['levgal_quotas_document_title'] = 'Documents';
$txt['lgal_enable_document'] = 'Allow users to upload documents';
$txt['lgal_allowed_types_document'] = 'Types of documents users are allowed to upload';
$txt['lgal_document_doc'] = 'Word, OpenOffice/LibreOffice Writer documents';
$txt['lgal_document_xls'] = 'Excel, OpenOffice/LibreOffice Calc spreadsheets';
$txt['lgal_document_ppt'] = 'Powerpoint, OpenOffice/LibreOffice Impress presentations';
$txt['lgal_document_pdf'] = 'Adobe PDF files';
$txt['lgal_document_txt'] = 'Text files';
$txt['lgal_document_html'] = 'HTML (web page) files';
$txt['lgal_document_xml'] = 'XML documents';

$txt['levgal_quotas_archive_title'] = 'Archives (zip files)';
$txt['levgal_quotas_archive_title_short'] = 'Archives';
$txt['lgal_enable_archive'] = 'Allow users to upload archives';
$txt['lgal_allowed_types_archive'] = 'Types of archive file users are allowed to upload';
$txt['lgal_archive_zip'] = 'Zip files (.zip)';
$txt['lgal_archive_rar'] = 'Rar files (.rar)';
$txt['lgal_archive_targz'] = 'Tar/gzip/bz2 files (.tar, .gz, .tgz, .bz2, .tbz2, .z)';
$txt['lgal_archive_7z'] = '7-Zip files (.7z)';
$txt['lgal_archive_dmg'] = 'Mac images (.dmg)';
$txt['lgal_archive_sit'] = 'Stuff-It files (.sit)';
$txt['lgal_archive_lz'] = 'LZ compressed (.lz, .lzma)';

$txt['levgal_quotas_generic_title'] = 'Other Files';
$txt['levgal_quotas_generic_title_short'] = 'Other';
$txt['lgal_enable_generic'] = 'Allow users to upload other kinds of files';
$txt['lgal_allowed_types_generic'] = 'Other types of file users are allowed to upload';
$txt['lgal_generic_exe'] = 'Executable/binary files (.bin, .dll, .exe)';
$txt['lgal_generic_ttf'] = 'Font files (.ttf, .otf)';

$txt['levgal_quotas_external_title'] = 'External Sites';
$txt['levgal_quotas_external_title_short'] = 'External';
$txt['lgal_enable_external'] = 'Allow users to link to external sites';
$txt['lgal_allowed_types_external'] = 'External sites users are allowed to embed in the gallery';
$txt['lgal_external_youtube'] = 'YouTube (video)';
$txt['lgal_external_vimeo'] = 'Vimeo (video)';
$txt['lgal_external_dailymotion'] = 'DailyMotion (video)';
$txt['lgal_external_metacafe'] = 'MetaCafe (video)';

$txt['levgal_quota_header'] = 'Quota for these files';
