<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.1
 * @package levgal
 * @since 1.0
 */
// Version: 1.2.1; ManageLevGal quotas

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['levgal_quotas_desc'] = 'Auf dieser Seite können Sie die verschiedenen Beschränkungen für die Anzahl der Dateien konfigurieren, die in Ihre Galerie hochgeladen werden können.';
$txt['lgal_max_space'] = 'Der maximale Speicherplatz auf dem Server, der für alle Ihre Galeriedateien verwendet werden darf.';
$txt['lgal_max_space_note'] = 'Sie können Zahlen mit K, M oder G verwenden, wobei 100M zum Beispiel bedeutet, dass 100 Megabyte Speicherplatz verwendet werden.';
$txt['lgal_enable_resize'] = 'Ändern Sie die Größe von Bildern über die Kontingentgröße hinaus.';
$txt['lgal_enable_resize_note'] = 'Es wird versucht, die Größe von PNG- und JPG-Bildern zu ändern, die die Kontingentgröße überschreiten, anstatt sie sofort abzulehnen.  Wenn Sie Imagick nicht installiert haben, verlieren Sie die EXIF-Daten für Bilder mit geänderter Größe.';

$txt['levgal_allowed_file_types'] = 'Erlaubte Dateitypen';
$txt['levgal_max_file_size'] = 'Maximale Dateigröße';
$txt['levgal_quota_groups'] = 'Gruppen';
$txt['levgal_gallery_managers'] = '(Galeriemanager)';
$txt['levgal_no_upload'] = 'Gruppen, die keine Uploadkontingent haben (und somit keine Uploads):';
$txt['levgal_none'] = '(keine)';
$txt['levgal_add_quota'] = 'Kontingent hinzufügen'
;$txt['levgal_update'] = 'Aktualisieren';
$txt['levgal_cancel'] = 'Abbrechen';
$txt['levgal_remove'] = 'Entfernen';
$txt['levgal_quota_no_groups_selected'] = ' Sie haben keine Gruppen ausgewählt, eine Kontingentierung gilt für mindestens eine Gruppe.';
$txt['levgal_quota_invalid_filesize'] = 'Sie haben keine gültige Dateigröße eingegeben. Es muss eine Zahl gefolgt von K, M oder G sein, z.B. 100M für 100MB.';
$txt['levgal_quota_invalid_imagesize'] = 'Sie müssen eine Breite und eine Höhe in Pixeln für das größte Bild, das Sie zulassen möchten, angeben, z. B. 1000 mal 1000 Pixel.';
$txt['levgal_changes_not_saved'] = '<strong>Hinweis:</strong> Ihre Änderungen sind noch nicht gespeichert worden. Bitte denken Sie daran, die Schaltfläche [Speichern] am unteren Rand zu verwenden, um alle Änderungen zu speichern.';

$txt['levgal_quotas_image_title'] = 'Bilder';
$txt['lgal_enable_image'] = 'Benutzern das Hochladen von Bilddateien erlauben';
$txt['levgal_max_image_size'] = 'Maximale Pixelgröße';
$txt['levgal_max_image_size_unlimited'] = '(keine maximale Größe)';
$txt['levgal_max_image_size_defined'] = 'Festgelegt auf nicht größer als:';
$txt['levgal_max_image_size_placeholder'] = '%1$s &mal; %2$s Pixel';
$txt['lgal_allowed_types_image'] = 'Arten von Bilddateien, die Benutzer hochladen dürfen';
$txt['lgal_image_jpg'] = 'JPEG-Bilder (.jpg, .jpeg)';
$txt['lgal_image_gif'] = 'GIF-Bilder (.gif)';
$txt['lgal_image_png'] = 'PNG-Bilder (.png)';
$txt['lgal_image_webp'] = 'WEBP images (.webp)';
$txt['lgal_image_psd'] = 'Photoshop-Dateien (.psd)';
$txt['lgal_image_tiff'] = 'TIFF-Bilder (.tiff, .tif)';
$txt['lgal_image_mng'] = 'MNG-Bilder (.mng)';
$txt['lgal_image_iff'] = 'IFF-Bilder (.iff, .lbm)';

$txt['levgal_quotas_audio_title'] = 'Audio';
$txt['lgal_enable_audio'] = 'Benutzern das Hochladen von Audiodateien erlauben';
$txt['lgal_allowed_types_audio'] = 'Arten von Audiodateien, die Benutzer hochladen dürfen';
$txt['lgal_audio_mp3'] = 'MP3-Audio (.mp3)';
$txt['lgal_audio_m4a'] = 'MP4/M4A-Audio (.mp4, .m4a)';
$txt['lgal_audio_oga'] = 'Ogg-Audio (.ogg, .oga)';
$txt['lgal_audio_flac'] = 'FLAC verlustfrei (.flac)';
$txt['lgal_audio_wav'] = 'Wave-Dateien (.wav)';

$txt['levgal_quotas_video_title'] = 'Video';
$txt['lgal_enable_video'] = 'Benutzern das Hochladen von Videodateien erlauben';
$txt['lgal_allowed_types_video'] = 'Typen von Videodateien, die Benutzer hochladen dürfen';
$txt['lgal_video_m4v'] = 'MP4-Video (.mp4, .m4v)';
$txt['lgal_video_ogv'] = 'Ogg-Video (.ogg, .ogv)';
$txt['lgal_video_mov'] = 'QuickTime-Video (.mov, .qt, .mqv)';
$txt['lgal_video_webm'] = 'WebM-Video (.webm)';

$txt['levgal_quotas_document_title'] = 'Dokumente';
$txt['lgal_enable_document'] = 'Benutzern das Hochladen von Dokumenten erlauben';
$txt['lgal_allowed_types_document'] = 'Arten von Dokumenten, die Benutzer hochladen dürfen';
$txt['lgal_document_doc'] = 'Word, OpenOffice/LibreOffice Writer Dokumente';
$txt['lgal_document_xls'] = 'Excel, OpenOffice/LibreOffice Calc Tabellenkalkulationen';
$txt['lgal_document_ppt'] = 'Powerpoint, OpenOffice/LibreOffice Impress Präsentationen';
$txt['lgal_document_pdf'] = 'Adobe PDF-Dateien';
$txt['lgal_document_txt'] = 'Textdateien';
$txt['lgal_document_html'] = 'HTML (Webseiten)-Dateien';
$txt['lgal_document_xml'] = 'XML-Dokumente';

$txt['levgal_quotas_archive_title'] = 'Archive (Zip-Dateien)';
$txt['levgal_quotas_archive_title_short'] = 'Archive';
$txt['lgal_enable_archive'] = 'Benutzern das Hochladen von Archiven erlauben';
$txt['lgal_allowed_types_archive'] = 'Typen von Archivdateien, die Benutzer hochladen dürfen';
$txt['lgal_archive_zip'] = 'Zip-Dateien (.zip)';
$txt['lgal_archive_rar'] = 'Rar-Dateien (.rar)';
$txt['lgal_archive_targz'] = 'Tar/gzip/bz2-Dateien (.tar, .gz, .tgz, .bz2, .tbz2, .z)';
$txt['lgal_archive_7z'] = '7-Zip-Dateien (.7z)';
$txt['lgal_archive_dmg'] = 'Mac-Bilder (.dmg)';
$txt['lgal_archive_sit'] = 'Stuff-It-Dateien (.sit)';
$txt['lgal_archive_lz'] = 'LZ-komprimiert (.lz, .lzma)';

$txt['levgal_quotas_generic_title'] = 'Andere Dateien';
$txt['levgal_quotas_generic_title_short'] = 'Andere';
$txt['lgal_enable_generic'] = 'Benutzern das Hochladen anderer Arten von Dateien erlauben';
$txt['lgal_allowed_types_generic'] = 'Andere Dateitypen, die Benutzer hochladen dürfen';
$txt['lgal_generic_exe'] = 'Ausführbare/Binärdateien (.bin, .dll, .exe)';
$txt['lgal_generic_ttf'] = 'Schriftartdateien (.ttf, .otf)';

$txt['levgal_quotas_external_title'] = 'Externe Seiten';
$txt['levgal_quotas_external_title_short'] = 'Extern';
$txt['lgal_enable_external'] = 'Nutzern erlauben, auf externe Seiten zu verlinken';
$txt['lgal_allowed_types_external'] = 'Externe Seiten, die Benutzer in die Galerie einbetten dürfen';
$txt['lgal_external_youtube'] = 'YouTube (Video)';
$txt['lgal_external_vimeo'] = 'Vimeo (Video)';
$txt['lgal_external_dailymotion'] = 'DailyMotion (Video)';
$txt['lgal_external_metacafe'] = 'MetaCafe (Video)';

$txt['levgal_quota_header'] = 'Kontingent für diese Dateien';
