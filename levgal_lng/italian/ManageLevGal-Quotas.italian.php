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

$txt['levgal_quotas_desc'] = 'Questa pagina ti consente di configurare i vari limiti di quanto le persone possono caricare nella tua galleria .';
$txt['lgal_max_space'] = 'Lo spazio massimo sul server che può essere utilizzato da tutti i file della galleria';
$txt['lgal_max_space_note'] = 'È possibile utilizzare numeri con K, M o G, ad esempio 100M significa che verranno utilizzati 100 megabyte di spazio .';

$txt['lgal_enable_resize'] = 'Ridimensiona le immagini in base alle quote satbilite.';
$txt['lgal_enable_resize_note'] = 'Questo tenterà di ridimensionare le immagini PNG e JPG che superano le dimensioni della quota invece di rifiutarle immediatamente. Se non hai installato Imagick perderai i dati EXIF per le immagini ridimensionate.';

$txt['levgal_allowed_file_types'] = 'Tipi di file consentiti';
$txt['levgal_max_file_size'] = 'Dimensione massima del file';
$txt['levgal_quota_groups'] = 'Gruppi';
$txt['levgal_gallery_managers'] = '(gestori galleria)';
$txt['levgal_no_upload'] = 'Gruppi che non hanno quote di caricamento (quindi nessun caricamento):';
$txt['levgal_none'] = '(nessuno)';
$txt['levgal_add_quota'] = 'Aggiungi quota';
$txt['levgal_update'] = 'Aggiorna';
$txt['levgal_cancel'] = 'Annulla';
$txt['levgal_remove'] = 'Rimuovi';
$txt['levgal_quota_no_groups_selected'] = 'Non hai selezionato alcun gruppo, una quota si applica ad almeno un gruppo.';
$txt['levgal_quota_invalid_filesize'] = 'Non hai inserito una dimensione di file valida. Deve essere un numero seguito da K, M o G, es. 100M per 100MB.';
$txt['levgal_quota_invalid_imagesize'] = 'È necessario specificare una larghezza e un\'altezza in pixel per l\'immagine più grande che si desidera consentire, es. 1000 &times; 1000 pixel.';
$txt['levgal_changes_not_saved'] = '<strong>Note:</strong> Le tue modifiche non sono ancora state salvate. Ricordarsi di utilizzare il pulsante in basso [Salva], per salvare tutte le modifiche.';

$txt['levgal_quotas_image_title'] = 'Immagini';
$txt['lgal_enable_image'] = 'Consenti agli utenti di caricare file di immagini ';
$txt['levgal_max_image_size'] = 'Dimensione massima in pixel';
$txt['levgal_max_image_size_unlimited'] = '(nessuna dimensione massima)';
$txt['levgal_max_image_size_defined'] = 'Impostato a non più grande di:';
$txt['levgal_max_image_size_placeholder'] = '%1$s &times; %2$s pixels';
$txt['lgal_allowed_types_image'] = 'Tipi di file immagini consentiti:';
$txt['lgal_image_jpg'] = 'JPEG (.jpg, .jpeg)';
$txt['lgal_image_gif'] = 'GIF (.gif)';
$txt['lgal_image_png'] = 'PNG (.png)';
$txt['lgal_image_psd'] = 'File Photoshop (.psd)';
$txt['lgal_image_tiff'] = 'TIFF (.tiff)';
$txt['lgal_image_mng'] = 'MNG (.mng)';
$txt['lgal_image_iff'] = 'IFF (.iff, .lbm)';

$txt['levgal_quotas_audio_title'] = 'Audio';
$txt['lgal_enable_audio'] = 'Consenti agli utenti di caricare file audio';
$txt['lgal_allowed_types_audio'] = 'Tipi di file audio consentiti:';
$txt['lgal_audio_mp3'] = 'MP3 audio (.mp3)';
$txt['lgal_audio_m4a'] = 'MP4/M4A audio (.mp4, .m4a)';
$txt['lgal_audio_oga'] = 'Ogg audio (.ogg, .oga)';
$txt['lgal_audio_flac'] = 'FLAC lossless (.flac)';
$txt['lgal_audio_wav'] = 'Wave files (.wav)';

$txt['levgal_quotas_video_title'] = 'Video';
$txt['lgal_enable_video'] = 'Consenti agli utenti di caricare file video';
$txt['lgal_allowed_types_video'] = 'Tipi di file video consentiti:';
$txt['lgal_video_m4v'] = 'MP4 video (.mp4, .m4v)';
$txt['lgal_video_ogv'] = 'Ogg video (.ogg, .ogv)';
$txt['lgal_video_mov'] = 'QuickTime video (.mov, .qt, .mqv)';
$txt['lgal_video_webm'] = 'WebM video (.webm)';

$txt['levgal_quotas_document_title'] = 'Documenti';
$txt['lgal_enable_document'] = 'Consenti agli utenti di caricare file di documenti';
$txt['lgal_allowed_types_document'] = 'Tipi di file documenti consentiti:';
$txt['lgal_document_doc'] = 'Documenti Word, OpenOffice/LibreOffice Writer';
$txt['lgal_document_xls'] = 'Fogli Excel, OpenOffice/LibreOffice Calc';
$txt['lgal_document_ppt'] = 'Presentazioni Powerpoint, OpenOffice/LibreOffice Impress';
$txt['lgal_document_pdf'] = 'File Adobe PDF';
$txt['lgal_document_txt'] = 'File di Testo';
$txt['lgal_document_html'] = 'File HTML (pagine web)';
$txt['lgal_document_xml'] = 'Documenti XML';

$txt['levgal_quotas_archive_title'] = 'Archivi (file zip)';
$txt['levgal_quotas_archive_title_short'] = 'Archivi';
$txt['lgal_enable_archive'] = 'Consenti agli utenti di caricare archivi';
$txt['lgal_allowed_types_archive'] = 'Tipi di file archivio consentiti';
$txt['lgal_archive_zip'] = 'File Zip (.zip)';
$txt['lgal_archive_rar'] = 'File Rar (.rar)';
$txt['lgal_archive_targz'] = 'Tar/gzip/bz2 files (.tar, .gz, .tgz, .bz2, .tbz2, .z)';
$txt['lgal_archive_7z'] = 'File 7-Zip (.7z)';
$txt['lgal_archive_dmg'] = 'Immagini Mac (.dmg)';
$txt['lgal_archive_sit'] = 'File Stuff-It (.sit)';
$txt['lgal_archive_lz'] = 'File compressi LZ (.lz, .lzma)';

$txt['levgal_quotas_generic_title'] = 'Altro tipo di file';
$txt['levgal_quotas_generic_title_short'] = 'Altro';
$txt['lgal_enable_generic'] = 'Consenti agli utenti di caricare altri tipi di file';
$txt['lgal_allowed_types_generic'] = 'Altri tipi di file consentiti';
$txt['lgal_generic_exe'] = 'Executable/binary files (.bin, .dll, .exe)';
$txt['lgal_generic_ttf'] = 'Font files (.ttf, .otf)';

$txt['levgal_quotas_external_title'] = 'Siti esterni';
$txt['levgal_quotas_external_title_short'] = 'External';
$txt['lgal_enable_external'] = 'Consenti agli utenti link a siti esterni';
$txt['lgal_allowed_types_external'] = 'Siti esterni consentiti da incorporare nella galleria';
$txt['lgal_external_youtube'] = 'YouTube (video)';
$txt['lgal_external_vimeo'] = 'Vimeo (video)';
$txt['lgal_external_dailymotion'] = 'DailyMotion (video)';
$txt['lgal_external_metacafe'] = 'MetaCafe (video)';

$txt['levgal_quota_header'] = 'Quota per questi file';
