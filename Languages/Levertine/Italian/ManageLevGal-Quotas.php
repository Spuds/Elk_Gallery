<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.2
 * @package levgal
 * @since 1.0
 */
// Version: 1.2.1; ManageLevGal quotas

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['levgal_quotas_desc'] = 'Questa pagina ti permette di configurare tutti i limiti di caricamento nella galleria.';
$txt['lgal_max_space'] = 'Spazio massimo sul server utilizzabile da tutti i file della galleria';
$txt['lgal_max_space_note'] = 'Puoi usare numeri seguiti da K, M o G: ad esempio 100M significa 100 megabyte di spazio.';

$txt['lgal_enable_resize'] = 'Ridimensiona le immagini che superano le dimensioni consentite.';
$txt['lgal_enable_resize_note'] = 'Prova a ridimensionare le immagini PNG, JPG e WEBP che superano le dimensioni consentite, invece di rifiutarle subito. Se Imagick non è installato, le immagini ridimensionate perderanno i dati EXIF.';

$txt['levgal_allowed_file_types'] = 'Tipi di file consentiti';
$txt['levgal_max_file_size'] = 'Dimensione massima del file';
$txt['levgal_quota_groups'] = 'Gruppi';
$txt['levgal_gallery_managers'] = '(gestori della galleria)';
$txt['levgal_no_upload'] = 'Gruppi senza quota di caricamento (che quindi non possono caricare):';
$txt['levgal_none'] = '(nessuno)';
$txt['levgal_add_quota'] = 'Aggiungi quota';
$txt['levgal_update'] = 'Aggiorna';
$txt['levgal_cancel'] = 'Annulla';
$txt['levgal_remove'] = 'Rimuovi';
$txt['levgal_quota_no_groups_selected'] = 'Non hai selezionato nessun gruppo: una quota deve riguardare almeno un gruppo.';
$txt['levgal_quota_invalid_filesize'] = 'Non hai inserito una dimensione valida. Deve essere un numero seguito da K, M o G, ad esempio 100M per 100 MB.';
$txt['levgal_quota_invalid_imagesize'] = 'È necessario specificare una larghezza e un\'altezza in pixel per l\'immagine più grande che si desidera consentire, es. 1000 &times; 1000 pixel.';
$txt['levgal_changes_not_saved'] = '<strong>Nota:</strong> le modifiche non sono ancora state salvate. Ricorda di usare il pulsante [Salva] in fondo alla pagina per salvarle tutte.';

$txt['levgal_quotas_image_title'] = 'Immagini';
$txt['lgal_enable_image'] = 'Consenti agli utenti di caricare immagini';
$txt['levgal_max_image_size'] = 'Dimensione massima in pixel';
$txt['levgal_max_image_size_unlimited'] = '(nessuna dimensione massima)';
$txt['levgal_max_image_size_defined'] = 'Non più grande di:';
$txt['levgal_max_image_size_placeholder'] = '%1$s &times; %2$s pixel';
$txt['lgal_allowed_types_image'] = 'Tipi di immagini che gli utenti possono caricare';
$txt['lgal_image_jpg'] = 'Immagini JPEG (.jpg, .jpeg)';
$txt['lgal_image_gif'] = 'Immagini GIF (.gif)';
$txt['lgal_image_png'] = 'Immagini PNG (.png)';
$txt['lgal_image_webp'] = 'Immagini WEBP (.webp)';
$txt['lgal_image_psd'] = 'File Photoshop (.psd)';
$txt['lgal_image_tiff'] = 'Immagini TIFF (.tiff, .tif)';
$txt['lgal_image_mng'] = 'Immagini MNG (.mng)';
$txt['lgal_image_iff'] = 'Immagini IFF (.iff, .lbm)';

$txt['levgal_quotas_audio_title'] = 'Audio';
$txt['lgal_enable_audio'] = 'Consenti agli utenti di caricare file audio';
$txt['lgal_allowed_types_audio'] = 'Tipi di file audio che gli utenti possono caricare';
$txt['lgal_audio_mp3'] = 'Audio MP3 (.mp3)';
$txt['lgal_audio_m4a'] = 'Audio MP4/M4A (.mp4, .m4a)';
$txt['lgal_audio_oga'] = 'Audio Ogg (.ogg, .oga)';
$txt['lgal_audio_flac'] = 'FLAC senza perdita (.flac)';
$txt['lgal_audio_wav'] = 'File Wave (.wav)';

$txt['levgal_quotas_video_title'] = 'Video';
$txt['lgal_enable_video'] = 'Consenti agli utenti di caricare file video';
$txt['lgal_allowed_types_video'] = 'Tipi di file video che gli utenti possono caricare';
$txt['lgal_video_m4v'] = 'Video MP4 (.mp4, .m4v)';
$txt['lgal_video_ogv'] = 'Video Ogg (.ogg, .ogv)';
$txt['lgal_video_mov'] = 'Video QuickTime (.mov, .qt, .mqv)';
$txt['lgal_video_webm'] = 'Video WebM (.webm)';
$txt['lgal_video_mkv'] = 'Video Matroska (.mkv)';


$txt['levgal_quotas_document_title'] = 'Documenti';
$txt['lgal_enable_document'] = 'Consenti agli utenti di caricare documenti';
$txt['lgal_allowed_types_document'] = 'Tipi di documenti che gli utenti possono caricare';
$txt['lgal_document_doc'] = 'Documenti Word, OpenOffice/LibreOffice Writer';
$txt['lgal_document_xls'] = 'Fogli Excel, OpenOffice/LibreOffice Calc';
$txt['lgal_document_ppt'] = 'Presentazioni Powerpoint, OpenOffice/LibreOffice Impress';
$txt['lgal_document_pdf'] = 'File Adobe PDF';
$txt['lgal_document_txt'] = 'File di testo';
$txt['lgal_document_html'] = 'File HTML (pagine web)';
$txt['lgal_document_xml'] = 'Documenti XML';

$txt['levgal_quotas_archive_title'] = 'Archivi (file zip)';
$txt['levgal_quotas_archive_title_short'] = 'Archivi';
$txt['lgal_enable_archive'] = 'Consenti agli utenti di caricare archivi';
$txt['lgal_allowed_types_archive'] = 'Tipi di archivi che gli utenti possono caricare';
$txt['lgal_archive_zip'] = 'File Zip (.zip)';
$txt['lgal_archive_rar'] = 'File Rar (.rar)';
$txt['lgal_archive_targz'] = 'File Tar/gzip/bz2 (.tar, .gz, .tgz, .bz2, .tbz2, .z)';
$txt['lgal_archive_7z'] = 'File 7-Zip (.7z)';
$txt['lgal_archive_dmg'] = 'Immagini Mac (.dmg)';
$txt['lgal_archive_sit'] = 'File Stuff-It (.sit)';
$txt['lgal_archive_lz'] = 'File compressi LZ (.lz, .lzma)';

$txt['levgal_quotas_generic_title'] = 'Altri file';
$txt['levgal_quotas_generic_title_short'] = 'Altro';
$txt['lgal_enable_generic'] = 'Consenti agli utenti di caricare altri tipi di file';
$txt['lgal_allowed_types_generic'] = 'Altri tipi di file che gli utenti possono caricare';
$txt['lgal_generic_exe'] = 'File eseguibili/binari (.bin, .dll, .exe)';
$txt['lgal_generic_ttf'] = 'Caratteri (.ttf, .otf)';
$txt['lgal_generic_stl'] = 'File per la stampa 3D (.stl, .stp, .step, .obj, .3mf)';

$txt['levgal_quotas_external_title'] = 'Siti esterni';
$txt['levgal_quotas_external_title_short'] = 'Esterni';
$txt['lgal_enable_external'] = 'Consenti agli utenti di collegare siti esterni';
$txt['lgal_allowed_types_external'] = 'Siti esterni che gli utenti possono incorporare nella galleria';
$txt['lgal_external_youtube'] = 'YouTube (video)';
$txt['lgal_external_vimeo'] = 'Vimeo (video)';
$txt['lgal_external_dailymotion'] = 'DailyMotion (video)';
$txt['lgal_external_metacafe'] = 'MetaCafe (video)';

$txt['levgal_quota_header'] = 'Quota per questi file';
