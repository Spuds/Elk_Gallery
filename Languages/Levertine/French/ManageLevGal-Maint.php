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

$txt['levgal_maint_desc'] = 'De là, vous pouvez exécuter diverses fonctions en cas de comportement inattendu dans la médiathèque.';
$txt['levgal_run_task'] = 'Exécutez cette tâche maintenant';
$txt['levgal_maint_success'] = 'Tâche de maintenance "%1$s" terminée avec succès.';
$txt['levgal_recovered_album'] = 'Album(s) récupéré(s)';
$txt['levgal_task_recount'] = 'Recomptage des statistiques';
$txt['levgal_task_desc_recount'] = 'Forcer un recomptage des statistiques et autres chiffres internes. Utile si les statistiques semblent fausses pour une raison quelconque.';
$txt['levgal_task_findfix'] = 'Rechercher et corriger les erreurs';
$txt['levgal_task_desc_findfix'] = 'Examine la base de données à la recherche d\'incohérences et essaye de les corriger. Cela peut être un long processus. Vous devriez probablement mettre le forum en mode maintenance avant d\'exécuter ceci.';
$txt['levgal_findfix_substep_fixOrphanAlbumHierarchy'] = 'Recherche d\'albums sans propriétaires...';
$txt['levgal_findfix_substep_fixOrphanItems'] = 'Recherche d\'éléments dans des albums inexistants...';
$txt['levgal_findfix_substep_fixOrphanComments'] = 'Recherche de commentaires sur des éléments inexistants...';
$txt['levgal_findfix_substep_fixOrphanBookmarks'] = 'Recherche de signets sur des éléments ou des utilisateurs inexistants...';
$txt['levgal_findfix_substep_fixOrphanLikes'] = 'Recherche de J\'aime sur des éléments ou des utilisateurs inexistants...';
$txt['levgal_findfix_substep_fixOrphanTags'] = 'Vérification d\'étiquettes inexistantes ou des étiquettes sur des éléments inexistants...';
$txt['levgal_findfix_substep_fixOrphanNotify'] = 'Recherche de notifications sur des albums, éléments ou utilisateurs inexistants...';
$txt['levgal_findfix_substep_fixOrphanUnseen'] = 'Recherche de journaux non vus pour des éléments ou des utilisateurs inexistants...';
$txt['levgal_findfix_substep_fixOrphanReports'] = 'Vérification des signalements à la modération avec des détails manquants ou inexistants...';
$txt['levgal_findfix_substep_fixOrphanCustomFields'] = 'Recherche de champs personnalisés avec des champs ou des éléments inexistants...';
$txt['levgal_findfix_substep_checkMissingFiles'] = 'Recherche de fichiers manquants...';
$txt['levgal_findfix_substep_checkExtraFiles'] = 'Recherche de fichiers superflus...';
$txt['levgal_findfix_substep_checkAlbumFiles'] = 'Recherche de fichiers superflus liés à l\'album...';

$txt['levgal_task_rebuildsearch'] = 'Reconstruire l\'index de recherche';
$txt['levgal_task_desc_rebuildsearch'] = 'Un index de recherche permet de rechercher rapidement les éléments multimédias. S\'il y a des problèmes avec la fonction de recherche, cela peut signifier que l\'index est corrompu : une reconstruction devrait résoudre ce problème.';
$txt['levgal_task_rebuildsearch_album_subtitle'] = 'Reconstitution de l\'index des albums...';
$txt['levgal_task_rebuildsearch_item_subtitle'] = 'Reconstitution de l\'index des éléments...';
$txt['levgal_task_rebuildthumbs'] = 'Reconstruire les vignettes d\'éléments';
$txt['levgal_task_desc_rebuildthumbs'] = 'S\'il y a des problèmes avec les vignettes ou les aperçus, cette option de maintenance les reconstruira à partir de l\'élément dans la mesure du possible, et s\'ils n\'ont pas été téléversés manuellement par un utilisateur.';
