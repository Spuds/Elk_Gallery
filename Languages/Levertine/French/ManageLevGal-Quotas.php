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

$txt['levgal_quotas_desc'] = 'Cette page vous permet de configurer toutes les différentes limites, stockage et types de fichiers, pour les personnes pouvant téléverser sur votre médiathèque.';
$txt['lgal_max_space'] = 'L\'espace serveur maximal autorisé à être utilisé par tous les fichiers de la médiathèque : ';
$txt['lgal_max_space_note'] = 'Vous pouvez utiliser des nombres avec K, M ou G : par exemple 100M signifie que 100 mégaoctets d\'espace seront utilisés.';

$txt['lgal_enable_resize'] = 'Redimensionner les images au-delà des limites du quota : ';
$txt['lgal_enable_resize_note'] = 'Cela tentera de redimensionner les images PNG et JPG qui dépassent les limites du quota au lieu de les rejeter immédiatement. Si vous n\'avez pas installé Imagick, vous perdrez les données EXIF pour les images redimensionnées.';

$txt['levgal_allowed_file_types'] = 'Types de fichiers autorisés';
$txt['levgal_max_file_size'] = 'Taille maximale d\'un fichier (poids)';
$txt['levgal_quota_groups'] = 'Groupes';
$txt['levgal_gallery_managers'] = '(gestionnaires de la médiathèque)';
$txt['levgal_no_upload'] = 'Les groupes qui n\'ont pas de quota de téléversement(et donc pas de téléversement possible) :';
$txt['levgal_none'] = '(sans)';
$txt['levgal_add_quota'] = 'Ajouter un quota';
$txt['levgal_update'] = 'Mettre à jour';
$txt['levgal_cancel'] = 'Annuler';
$txt['levgal_remove'] = 'Supprimer';
$txt['levgal_quota_no_groups_selected'] = 'Vous n\'avez sélectionné aucun groupe, un quota s\'applique à au moins un groupe.';
$txt['levgal_quota_invalid_filesize'] = 'Vous n\'avez pas entré une taille de fichier valide. Il doit s\'agir d\'un nombre suivi de K, M ou G, par ex. 100M pour 100Mo.';
$txt['levgal_quota_invalid_imagesize'] = 'Vous devez spécifier une largeur et une hauteur en pixels pour la plus grande image que vous souhaitez autoriser, par ex. 1000 X 1000 pixels.';
$txt['levgal_changes_not_saved'] = '<strong>Note:</strong> Vos modifications n\'ont pas encore été enregistrées. N\'oubliez pas d\'utiliser le bouton [Enregistrer] en bas pour enregistrer toutes les modifications.';

$txt['levgal_quotas_image_title'] = 'Images';
$txt['lgal_enable_image'] = 'Autoriser les utilisateurs à téléverser des fichiers image : ';
$txt['levgal_max_image_size'] = 'Taille maximale en Pixels';
$txt['levgal_max_image_size_unlimited'] = '(illimitée)';
$txt['levgal_max_image_size_defined'] = 'Pas plus grand que : ';
$txt['levgal_max_image_size_placeholder'] = '%1$s &times; %2$s pixels';
$txt['lgal_allowed_types_image'] = 'Types de fichiers image que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_image_jpg'] = 'JPEG images (.jpg, .jpeg)';
$txt['lgal_image_gif'] = 'GIF images (.gif)';
$txt['lgal_image_png'] = 'PNG images (.png)';
$txt['lgal_image_webp'] = 'WEBP images (.webp)';
$txt['lgal_image_psd'] = 'Photoshop files (.psd)';
$txt['lgal_image_tiff'] = 'TIFF images (.tiff, .tif)';
$txt['lgal_image_mng'] = 'MNG images (.mng)';
$txt['lgal_image_iff'] = 'IFF images (.iff, .lbm)';

$txt['levgal_quotas_audio_title'] = 'Audio';
$txt['lgal_enable_audio'] = 'Autoriser les utilisateurs à téléverser des fichiers audio : ';
$txt['lgal_allowed_types_audio'] = 'Les types de fichiers audio que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_audio_mp3'] = 'MP3 audio (.mp3)';
$txt['lgal_audio_m4a'] = 'MP4/M4A audio (.mp4, .m4a)';
$txt['lgal_audio_oga'] = 'Ogg audio (.ogg, .oga)';
$txt['lgal_audio_flac'] = 'FLAC (sans perte) (.flac)';
$txt['lgal_audio_wav'] = 'Wave (.wav)';

$txt['levgal_quotas_video_title'] = 'Video';
$txt['lgal_enable_video'] = 'Autoriser les utilisateurs à téléverser des fichiers vidéo : ';
$txt['lgal_allowed_types_video'] = 'Les types de fichiers vidéo que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_video_m4v'] = 'MP4 video (.mp4, .m4v)';
$txt['lgal_video_ogv'] = 'Ogg video (.ogg, .ogv)';
$txt['lgal_video_mov'] = 'QuickTime video (.mov, .qt, .mqv)';
$txt['lgal_video_webm'] = 'WebM video (.webm)';
$txt['lgal_video_mkv']='Matroskavideo(.mkv)';


$txt['levgal_quotas_document_title'] = 'Documents';
$txt['lgal_enable_document'] = 'Autoriser les utilisateurs à téléverser des documents : ';
$txt['lgal_allowed_types_document'] = 'Types de documents que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_document_doc'] = 'Word, OpenOffice/LibreOffice Writer documents';
$txt['lgal_document_xls'] = 'Excel, OpenOffice/LibreOffice Calc feuilles de calculs';
$txt['lgal_document_ppt'] = 'Powerpoint, OpenOffice/LibreOffice Présentations Impress';
$txt['lgal_document_pdf'] = 'Adobe PDF';
$txt['lgal_document_txt'] = 'Text';
$txt['lgal_document_html'] = 'HTML (page web)';
$txt['lgal_document_xml'] = 'XML documents';

$txt['levgal_quotas_archive_title'] = 'Archives (zip)';
$txt['levgal_quotas_archive_title_short'] = 'Archives';
$txt['lgal_enable_archive'] = 'Autoriser les utilisateurs à téléverser des archives : ';
$txt['lgal_allowed_types_archive'] = 'Types de fichiers d\'archives que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_archive_zip'] = 'Zip (.zip)';
$txt['lgal_archive_rar'] = 'Rar (.rar)';
$txt['lgal_archive_targz'] = 'Tar/gzip/bz2 (.tar, .gz, .tgz, .bz2, .tbz2, .z)';
$txt['lgal_archive_7z'] = '7-Zip (.7z)';
$txt['lgal_archive_dmg'] = 'Mac images (.dmg)';
$txt['lgal_archive_sit'] = 'Stuff-It (.sit)';
$txt['lgal_archive_lz'] = 'LZ compressé (.lz, .lzma)';

$txt['levgal_quotas_generic_title'] = 'Autres fichiers';
$txt['levgal_quotas_generic_title_short'] = 'Autres';
$txt['lgal_enable_generic'] = 'Autoriser les utilisateurs à téléverser d\'autres types de fichiers : ';
$txt['lgal_allowed_types_generic'] = 'Autres types de fichiers que les utilisateurs sont autorisés à téléverser : ';
$txt['lgal_generic_exe'] = 'Exécutable/binaire (.bin, .dll, .exe)';
$txt['lgal_generic_ttf'] = 'Polices (.ttf, .otf)';

$txt['levgal_quotas_external_title'] = 'Sites Externes';
$txt['levgal_quotas_external_title_short'] = 'Externes';
$txt['lgal_enable_external'] = 'Autoriser les utilisateurs à créer des liens vers des sites externes : ';
$txt['lgal_allowed_types_external'] = 'Sites externes que les utilisateurs sont autorisés à intégrer dans la médiathèque : ';
$txt['lgal_external_youtube'] = 'YouTube (video)';
$txt['lgal_external_vimeo'] = 'Vimeo (video)';
$txt['lgal_external_dailymotion'] = 'DailyMotion (video)';
$txt['lgal_external_metacafe'] = 'MetaCafe (video)';

$txt['levgal_quota_header'] = 'Quota pour ces fichiers';
