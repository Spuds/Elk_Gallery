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
// Version: 1.0; ManageLevGal custom fields

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['levgal_cfields_desc'] = 'Ici, vous pouvez définir des informations supplémentaires qui doivent être enregistrées pour les éléments multimédias dans la médiathèque.';
$txt['levgal_cfields_none'] = 'Aucun champ n\'a été créé.';
$txt['levgal_cfields_add'] = 'Ajouter un champ';
$txt['levgal_cfields_modify'] = 'Modifier le champ';
$txt['levgal_cfields_field_active'] = 'Actif';
$txt['levgal_cfields_field_inactive'] = 'Inactif';

$txt['lgal_cfields_save'] = 'Enregistrer la nouvelle organisation';

$txt['levgal_cfields_general'] = 'Options générales';
$txt['levgal_cfields_field_name'] = 'Nom du champ :';
$txt['levgal_cfields_field_name_desc'] = 'Le nom du champ personnalisé. Toujours affiché aux utilisateurs lors de l\'affichage de ce champ.';
$txt['levgal_cfields_field_desc'] = 'Description du champ :';
$txt['levgal_cfields_field_desc_desc'] = 'Ceci est montré aux utilisateurs lors de la saisie des informations. Vous pouvez utiliser le bbcode du forum ici.';
$txt['levgal_cfields_field_placement'] = 'Emplacement du champ :';
$txt['levgal_cfields_field_placement_desc'] = 'Cela contrôle où les informations supplémentaires seront affichées lorsque les gens regarderont un élément.';
$txt['levgal_cfields_placement'] = 'Emplacement : %1$s';
$txt['levgal_cfields_placement_0'] = 'Boîte d\'information supplémentaire';
$txt['levgal_cfields_placement_1'] = 'Zone d\'informations sur l\'élément';
$txt['levgal_cfields_placement_2'] = 'Sous la description de l\'élément';
$txt['levgal_cfields_field_is_active'] = 'Le champ est actif ?';
$txt['levgal_cfields_field_is_active_desc'] = 'Si ce champ n\'est pas actif, il ne sera pas affiché, ou ne pourra être saisi, sur aucun élément.';
$txt['levgal_cfields_field_is_searchable'] = 'Le champ est recherchable ?';
$txt['levgal_cfields_field_is_searchable_desc'] = 'Indique si les utilisateurs peuvent rechercher le contenu de ce champ via la zone de recherche.';

$txt['levgal_cfields_input'] = 'Options de saisie';
$txt['levgal_cfields_field_type'] = 'Type de champ :';
$txt['levgal_cfields_field_type_integer'] = 'Nombres entiers (zone de texte)';
$txt['levgal_cfields_field_type_float'] = 'N\'importe quel nombre (zone de texte)';
$txt['levgal_cfields_field_type_text'] = 'Zone de texte';
$txt['levgal_cfields_field_type_largetext'] = 'Grande zone de texte';
$txt['levgal_cfields_field_type_select'] = 'Sélectionnez dans la liste déroulante';
$txt['levgal_cfields_field_type_radio'] = 'Sélectionnez parmi les boutons radio';
$txt['levgal_cfields_field_type_multiselect'] = 'Sélection multiple dans la liste';
$txt['levgal_cfields_field_type_checkbox'] = 'Case à cocher';
$txt['levgal_cfields_field_bbc'] = 'Utiliser le bbcode ?';
$txt['levgal_cfields_field_is_required'] = 'Champ obligatoire ?';
$txt['levgal_cfields_field_is_required_desc'] = 'Indique si les utilisateurs doivent remplir ce champ lors de l\'ajout d\'un nouvel élément à la médiathèque (ou lors de la modification d\'un élément)';
$txt['levgal_cfields_field_num_limits'] = 'Nombres minimum et maximum :';
$txt['levgal_cfields_field_num_limits_size'] = 'Spécifie les valeurs numériques minimales et maximales autorisées dans ce champ.';
$txt['levgal_cfields_field_num_limits_form'] = 'Minimum : %1$s &nbsp; Maximum : %2$s';
$txt['levgal_cfields_field_text_length'] = 'Quelle est la longueur maximale du contenu saisi dans ce champ ?';
$txt['levgal_cfields_field_text_length_desc'] = 'Nombre de caractères, utilisez 0 pour illimité.';
$txt['levgal_cfields_field_largetext_size'] = 'Quelle doit être la taille de cette grande zone de texte ?';
$txt['levgal_cfields_field_largetext_size_desc'] = 'Cela indique la taille en colonnes et en lignes qui sera affichée à l\'utilisateur. Plus le texte que vous attendez de l\'utilisateur est grand, plus la zone devrait être grande.';
$txt['levgal_cfields_field_largetext_size_form'] = '%1$s colonnes sur %2$s lignes';
$txt['levgal_cfields_field_default_val'] = 'Valeur par défaut pour ce champ ?';
$txt['levgal_cfields_field_default_val_desc'] = 'Vous pouvez spécifier une valeur par défaut pour ce champ que les utilisateurs peuvent modifier.';
$txt['levgal_cfields_field_validation'] = 'Règles de contenu valides :';
$txt['levgal_cfields_field_validation_desc'] = 'Cela permet de configurer quelles règles sont utilisées pour vérifier ce que quelqu\'un entre dans ce champ.';
$txt['levgal_cfields_field_validation_nohtml'] = 'Format libre (pas de HTML, mais autorise tout autre contenu)';
$txt['levgal_cfields_field_validation_email'] = 'Le champ doit contenir une adresse de courriel valide';
$txt['levgal_cfields_field_validation_numbers'] = 'Le champ ne doit contenir que les chiffres 0-9';
$txt['levgal_cfields_field_validation_regex'] = 'Le champ doit être conforme à l\'expression régulière suivante :';

$txt['levgal_cfields_field_options'] = 'Options du champ :';
$txt['levgal_cfields_field_options_desc'] = 'Ici, vous pouvez définir les choix pour ce champ personnalisé. Laissez un espace vide pour le supprimer en tant qu\'option et utilisez le bouton radio pour indiquer une valeur par défaut.';
$txt['levgal_cfields_field_options_multi_desc'] = 'Ici, vous pouvez définir les choix pour ce champ personnalisé. Laissez un espace vide pour le supprimer en tant qu\'option et utilisez les cases à cocher pour en indiquer une ou plusieurs comme sélection par défaut.';
$txt['levgal_cfields_field_options_add'] = 'ajout d\'une option';
$txt['levgal_cfields_field_options_no_default'] = 'Aucune valeur par défaut sélectionnée';

$txt['levgal_cfields_albums'] = 'Albums';
$txt['levgal_cfields_applies_to_album'] = 'À quel(s) album(s) ce champ s\'applique-t-il ?';
$txt['levgal_cfields_applies_to_album_desc'] = 'Certains champs ne devraient vraiment s\'appliquer qu\'à certains albums. C\'est ici que vous pouvez le configurer.';
$txt['levgal_cfields_applies_to_albums_all'] = 'S\'applique à tous les albums';
$txt['levgal_cfields_applies_to_albums_some'] = 'S\'applique uniquement à certains albums:';

$txt['levgal_cfield_could_not_be_saved'] = 'Le champ personnalisé n\'a pas pu être enregistré:';
$txt['levgal_cfields_empty_field'] = 'Le champ personnalisé a besoin d\'un nom.';
$txt['levgal_cfields_empty_options'] = 'Aucune option n\'a été fournie.';
