<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2
 * @package levgal
 * @since 1.0
 */
// Version: 1.2 / elkarte; ManageLevGal

// Important! Before editing these language files please read the text at the top of index.english.php.

// Generics
$txt['levgal_admin_js'] = 'L\'administration de Levertine Gallery nécessite vraiment que votre navigateur supporte JavaScript pour fonctionner.';

// Scheduled tasks.
$txt['scheduled_task_levgal_maintenance'] = 'Maintenance de Levertine Gallery';
$txt['scheduled_task_desc_levgal_maintenance'] = 'Cela effectue l\'entretien quotidien essentiel de la médiathèque et *ne devrait pas* être désactivé.';

// General admin stuff
$txt['levgal_admindash'] = 'Tableau de bord de la médiathèque';
$txt['levgal_admindash_desc'] = 'A partir de cette zone, vous pouvez obtenir un aperçu de votre médiathèque.';

// The rest of the dashboard stats are in LevGal-Stats.language.php
$txt['levgal_stats_installed_time'] = 'Installé le :';
$txt['levgal_support_information'] = 'Informations de support';
$txt['levgal_support'] = 'Si vous avez des questions à propos de Levertine Gallery, veuillez contacter <a href="http://levertine.com/">Levertine.com</a> qui peut vous aider.';
$txt['levgal_versions_elk'] = 'ElkArte Version:';
$txt['levgal_versions_lgal'] = 'Levertine Gallery:';
$txt['levgal_versions_php'] = 'PHP:';
$txt['levgal_versions_GD'] = 'GD library:';
$txt['levgal_versions_Imagick'] = 'ImageMagick (Imagick):';
$txt['levgal_versions_webp'] = 'Webp support:';
$txt['levgal_support_notavailable'] = '(indisponible)';
$txt['levgal_support_available'] = '(disponible)';
$txt['levgal_support_warning'] = '(pas configuré correctement)';
$txt['levgal_uploaded_items'] = 'Éléments téléversés';
$txt['levgal_news_from_home'] = 'Nouvelles et annonces';
$txt['levgal_news_item'] = '%1$s par %2$s, %3$s';
$txt['levgal_news_not_available'] = 'Les actualités ne sont pas disponibles actuellement.';
$txt['levgal_out_of_date'] = 'Votre version est %1$s, la version actuelle est %2$s, vous devriez probablement mettre à jour la médiathèque.';

// Settings
$txt['levgal_settings'] = 'Paramètres de la médiathèque';
$txt['levgal_settings_desc'] = 'Cette page vous permet de configurer certaines options globales de la médiathèque.';
$txt['lgal_count_author_views'] = 'Compter les vues de l\'auteur sur les éléments';
$txt['lgal_enable_mature'] = 'Activer les contenus pour adultes';
$txt['lgal_enable_mature_desc'] = 'Les éléments peuvent être marqués en tant que contenu pour adultes : cela signifie qu\'un avertissement est affiché aux utilisateurs avant qu\'ils ne voient de tels éléments.';
$txt['lgal_feed_enable_album'] = 'Activer le flux RSS pour les éléments d\'un album';
$txt['lgal_feed_items_album'] = 'Nombre d\'éléments les plus récents à afficher dans le flux RSS d\'un album';
$txt['lgal_feed_enable_item'] = 'Activer le flux RSS pour les commentaires sur un élément';
$txt['lgal_feed_items_item'] = 'Nombre de commentaires les plus récents à afficher dans le flux RSS d\'un élément';
$txt['lgal_feed_items_limits'] = '(1-50 éléments)';
$txt['lgal_tag_items_list'] = 'Comma seperated list of allowed tags';
$txt['lgal_tag_items_list_more'] = 'Allow users to use their own tags in addition to the above';
$txt['lgal_items_per_page'] = 'Number of items to show on a page view';
$txt['lgal_comments_per_page'] = 'Number of comments to show per page';
$txt['lgal_per_page_limits'] = '(10-50 items)';
$txt['lgal_import_rendering'] = 'Permettre le rendu (partiel) des codes BBC d\'autres galeries';
$txt['lgal_open_link_new_tab'] = '"Click to view" opens item in a new tab, otherwise the current one';
$txt['lgal_settings_social'] = 'Permettre aux utilisateurs de partager facilement des éléments sur les réseaux sociaux';
$txt['lgal_settings_select_networks'] = 'Sélectionnez les réseaux sociaux';
$txt['lgal_settings_metadata'] = 'Afficher des métadonnées supplémentaires';
$txt['lgal_settings_metadata_desc'] = 'Les images, les fichiers audio et vidéo contiennent généralement des informations supplémentaires qui peuvent être intéressantes ou utiles à montrer aux utilisateurs.';
$txt['lgal_settings_metadata_types'] = 'Options des métadonnées';
$txt['lgal_settings_metadata_images'] = 'Images (photographies)';
$txt['lgal_settings_metadata_audio'] = 'Audio';
$txt['lgal_settings_metadata_video'] = 'Vidéo';
$txt['lgal_opts_metadata_datetime'] = 'Heure de la prise de vue';
$txt['lgal_opts_metadata_make'] = 'Appareil marque/model';
$txt['lgal_opts_metadata_flash'] = 'Paramètres du flash';
$txt['lgal_opts_metadata_exposure_time'] = 'Temps d\'exposition';
$txt['lgal_opts_metadata_fnumber'] = 'Ouverture Focale (par ex. <em>&#402;</em>/2.4, voir <a href="https://en.wikipedia.org/wiki/F-number" class="new_win" target="_blank">Wikipedia</a> pour plus d\'informations)';
$txt['lgal_opts_metadata_shutter_speed'] = 'Vitesse d\'obturation';
$txt['lgal_opts_metadata_focal_length'] = 'Distance focale';
$txt['lgal_opts_metadata_digitalzoom'] = 'Zoom numérique';
$txt['lgal_opts_metadata_brightness'] = 'Luminosité';
$txt['lgal_opts_metadata_contrast'] = 'Contraste';
$txt['lgal_opts_metadata_sharpness'] = 'Netteté';
$txt['lgal_opts_metadata_isospeed'] = 'Vitesse ISO';
$txt['lgal_opts_metadata_lightsource'] = 'Source de lumière';
$txt['lgal_opts_metadata_exposure_prog'] = 'Programme d\'exposition';
$txt['lgal_opts_metadata_metering_mode'] = 'Mode de mesure';
$txt['lgal_opts_metadata_sensitivity'] = 'Type de sensibilité';
$txt['lgal_opts_metadata_title'] = 'Titre';
$txt['lgal_opts_metadata_artist'] = 'Artiste';
$txt['lgal_opts_metadata_album_artist'] = 'Artiste de l\'album (si différent de l\'artiste du titre)';
$txt['lgal_opts_metadata_album'] = 'Nom de l\'album';
$txt['lgal_opts_metadata_track_number'] = 'Titre numéro';
$txt['lgal_opts_metadata_genre'] = 'Genre';
$txt['lgal_opts_metadata_playtime'] = 'Durée';
$txt['lgal_opts_metadata_bitrate'] = 'Débit';
$txt['lgal_opts_metadata_subject'] = 'Sujet';
$txt['lgal_opts_metadata_author'] = 'Auteur';
$txt['lgal_opts_metadata_keywords'] = 'Mots clés';
$txt['lgal_opts_metadata_comment'] = 'Commentaires';

// Permissions
$txt['levgal_perms'] = 'Permissions de la médiathèque';
$txt['levgal_perms_general'] = 'Permissions générales';
$txt['permissionname_lgal_view'] = 'Groupes pouvant voir la médiathèque';
$txt['permissionname_lgal_manage'] = 'Groupes pouvant gérer la médiathèque';
$txt['lgal_manage_note'] = 'Les gestionnaires de la médiathèque ont toutes les autorisations dans la médiathèque et peuvent faire n\'importe quoi.';
$txt['levgal_perms_album'] = 'Permissions sur les albums';
$txt['permissionname_lgal_adduseralbum'] = 'Groupes pouvant ajouter de nouveaux albums personnels';
$txt['permissionname_lgal_addgroupalbum'] = 'Groupes pouvant ajouter de nouveaux albums appartenant à un groupe';
$txt['permissionname_lgal_addalbum_approve'] = 'Groupes pouvant ajouter des albums (sous réserve de ce qui précède) sans avoir à attendre l\'approbation';
$txt['permissionname_lgal_approve_album'] = 'Groupes pouvant approuver de nouveaux albums dans la médiathèque';
$txt['permissionname_lgal_edit_album_own'] = 'Groupes pouvant modifier leurs propres albums';
$txt['permissionname_lgal_edit_album_any'] = 'Groupes pouvant modifier n\'importe quel album';
$txt['permissionname_lgal_delete_album_own'] = 'Groupes pouvant supprimer leurs propres albums';
$txt['permissionname_lgal_delete_album_any'] = 'Groupes pouvant supprimer n\'importe quel album';
$txt['levgal_perms_item'] = 'Permissions sur les éléments des albums';
$txt['permissionname_lgal_additem_own'] = 'Groupes pouvant ajouter des éléments à leurs propres albums';
$txt['permissionname_lgal_additem_any'] = 'Groupes pouvant ajouter des éléments à n\'importe quel album';
$txt['permissionname_lgal_addbulk'] = 'Groupes pouvant ajouter des éléments en masse aux albums (sous réserve de ce qui précède)';
$txt['permissionname_lgal_additem_approve'] = 'Groupes pouvant ajouter des éléments (sous réserve de ce qui précède) sans avoir à attendre une approbation';
$txt['permissionname_lgal_approve_item'] = 'Groupes pouvant approuver les éléments de la médiathèque';
$txt['permissionname_lgal_edit_item_own'] = 'Groupes pouvant modifier leurs propres éléments';
$txt['permissionname_lgal_edit_item_any'] = 'Groupes pouvant modifier n\'importe quel élément';
$txt['permissionname_lgal_delete_item_own'] = 'Groupes pouvant supprimer leurs propres éléments';
$txt['permissionname_lgal_delete_item_any'] = 'Groupes pouvant supprimer n\'importe quel élément';
$txt['levgal_perms_comments'] = 'Permissions sur les commentaires';
$txt['permissionname_lgal_comment'] = 'Groupes pouvant ajouter des commentaires';
$txt['permissionname_lgal_comment_appr'] = 'Groupes qui peuvent commenter (sous réserve de ce qui précède) sans avoir à attendre une approbation';
$txt['permissionname_lgal_approve_comment'] = 'Groupes pouvant approuver les commentaires des membres';
$txt['permissionname_lgal_edit_comment_own'] = 'Groupes pouvant modifier leurs propres commentaires';
$txt['permissionname_lgal_edit_comment_any'] = 'Groupes pouvant modifier n\'importe quel commentaire';
$txt['permissionname_lgal_delete_comment_own'] = 'Groupes pouvant supprimer leurs propres commentaires';
$txt['permissionname_lgal_delete_comment_any'] = 'Groupes pouvant supprimer n\'importe quels  commentaires';
$txt['levgal_perms_moderation'] = 'Permissions de modération';
$txt['levgal_perms_moderation_desc'] = 'Les autorisations ci-dessus sont pour des groupes très larges : ici vous pouvez choisir de laisser les utilisateurs modérer les éléments qu\'ils téléversent sans accorder beaucoup d\'autorisations ci-dessus.';
$txt['lgal_selfmod_approve_item'] = 'Les utilisateurs peuvent approuver les éléments que les autres publient dans leurs albums';
$txt['lgal_selfmod_approve_comment'] = 'Les utilisateurs peuvent approuver les commentaires des autres sur leurs propres éléments';
$txt['lgal_selfmod_edit_comment'] = 'Les utilisateurs peuvent modifier les commentaires des autres sur leurs propres éléments';
$txt['lgal_selfmod_delete_comment'] = 'Les utilisateurs peuvent supprimer les commentaires des autres sur leurs propres éléments';
$txt['lgal_selfmod_lock_comment'] = 'Les utilisateurs peuvent empêcher leurs éléments d\'être commentés';
$txt['lgal_media_prefix'] = '[Media] %1$s';

// ACP Media notification Settings
$txt['setting_lglike'] = 'Médiathèque J\'aime';
$txt['setting_lgcomment'] = 'Médiathèque Commentaires';
$txt['setting_lgnew'] = 'Médiathèque Nouveaux éléments';

// Quotas; the rest is in ManageLevGal-Quotas.language.php
$txt['levgal_quotas'] = 'Quotas et types de fichiers de la médiathèque';

// Custom Fields; the rest is in ManageLevGal-CFields.language.php
$txt['levgal_cfields'] = 'Champs personnels de la médiathèque';

// Maintenance; the rest is in ManageLevGal-Maint.language.php
$txt['levgal_maint'] = 'Maintenance de la médiathèque';
// Notifications
$txt['levgal_notify'] = "Media Notifications";

// Moderation Log
$txt['levgal_modlog'] = 'Mediathèque journal de modération';
$txt['levgal_modlog_desc'] = 'D\'ici vous pouvez observer toutes les actions de modération menées dans la médiathèque.';
$txt['levgal_modlog_empty'] = 'Il n\'y a aucun événement dans le journal de modération.';
$txt['levgal_modlog_action'] = 'Action réalisées';
$txt['levgal_modlog_time'] = 'Date/Heure';
$txt['levgal_modlog_member'] = 'Membre';
$txt['levgal_modlog_position'] = 'Position';
$txt['levgal_modlog_ip'] = 'Addr. IP';
$txt['levgal_modlog_remove'] = 'Supprimer';
$txt['levgal_modlog_removeall'] = 'Tout supprimer';

// Credits
$txt['levgal_credits'] = 'Contributeurs';
$txt['levgal_credits_title'] = 'Contributeurs à Levertine Gallery';
$txt['levgal_credits_desc'] = 'Toutes les personnes adorables qui ont contribué au développement de la médiathèque Levertine Gallery.';
$txt['levgal_credits_developers_title'] = 'Developeurs';
$txt['levgal_credits_developers_desc'] = 'Les gens qui ont construit la médiathèque Levertine Gallery :';
$txt['levgal_credits_components_title'] = 'Composants';
$txt['levgal_credits_components_desc'] = 'Composants et logiciels additionels utilisés par la médiathèque Levertine Gallery :';
$txt['levgal_credits_images_title'] = 'Images';
$txt['levgal_credits_images_desc'] = 'Images et icônes utilisées par la médiathèque Levertine Gallery :';
$txt['levgal_credits_translators_title'] = 'Traducteurs';
$txt['levgal_credits_translators_desc'] = 'Les personnes qui ont contribué à la préparer au monde :';
$txt['levgal_credits_people_title'] = 'Remerciements';
$txt['levgal_credits_people_desc'] = 'Autres personnes que l\'auteur souhaite remercier :';

// Importers; the rest is in ManageLevGal-Importer.language.php
$txt['levgal_importers'] = 'Importer d\'autres médiathèques';
