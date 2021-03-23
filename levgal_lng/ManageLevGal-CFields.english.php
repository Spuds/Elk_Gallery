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

$txt['levgal_cfields_desc'] = 'From here you can define additional information that should be recorded for media items in the gallery.';
$txt['levgal_cfields_none'] = 'No fields have been created.';
$txt['levgal_cfields_add'] = 'Add Field';
$txt['levgal_cfields_modify'] = 'Modify Field';
$txt['levgal_cfields_field_active'] = 'Active';
$txt['levgal_cfields_field_inactive'] = 'Inactive';

$txt['lgal_cfields_save'] = 'Save the new order';

$txt['levgal_cfields_general'] = 'General Options';
$txt['levgal_cfields_field_name'] = 'Field Name:';
$txt['levgal_cfields_field_name_desc'] = 'The name of the custom field. Always shown to users when showing this field.';
$txt['levgal_cfields_field_desc'] = 'Field Description:';
$txt['levgal_cfields_field_desc_desc'] = 'This is shown to users when entering this information. You may use forum bbcode here.';
$txt['levgal_cfields_field_placement'] = 'Field Placement:';
$txt['levgal_cfields_field_placement_desc'] = 'This controls where the extra information will be shown when people look at an item.';
$txt['levgal_cfields_placement'] = 'Placement: %1$s';
$txt['levgal_cfields_placement_0'] = 'Additional Info box';
$txt['levgal_cfields_placement_1'] = 'Item Information box';
$txt['levgal_cfields_placement_2'] = 'Under the item description';
$txt['levgal_cfields_field_is_active'] = 'Field is active?';
$txt['levgal_cfields_field_is_active_desc'] = 'If this field is not active, it will not be shown, or able to be entered, on any items.';
$txt['levgal_cfields_field_is_searchable'] = 'Field is searchable?';
$txt['levgal_cfields_field_is_searchable_desc'] = 'Indicates whether users can search for the contents of this field through the search area.';

$txt['levgal_cfields_input'] = 'Input Options';
$txt['levgal_cfields_field_type'] = 'Field Type:';
$txt['levgal_cfields_field_type_integer'] = 'Whole numbers (textbox)';
$txt['levgal_cfields_field_type_float'] = 'Any numbers (textbox)';
$txt['levgal_cfields_field_type_text'] = 'Text box';
$txt['levgal_cfields_field_type_largetext'] = 'Large text box';
$txt['levgal_cfields_field_type_select'] = 'Select from dropdown';
$txt['levgal_cfields_field_type_radio'] = 'Select from radio buttons';
$txt['levgal_cfields_field_type_multiselect'] = 'Select multiple from list';
$txt['levgal_cfields_field_type_checkbox'] = 'Checkbox';
$txt['levgal_cfields_field_bbc'] = 'Use bbcode?';
$txt['levgal_cfields_field_is_required'] = 'Field is required?';
$txt['levgal_cfields_field_is_required_desc'] = 'Indicates whether users must fill this in when adding a new item to the gallery (or when editing an item)';
$txt['levgal_cfields_field_num_limits'] = 'Minimum and maximum numbers:';
$txt['levgal_cfields_field_num_limits_size'] = 'Specifies the minimum and maximum numeric values allowed in this field.';
$txt['levgal_cfields_field_num_limits_form'] = 'Minimum: %1$s &nbsp; Maximum: %2$s';
$txt['levgal_cfields_field_text_length'] = 'What is the maximum length for content entered into this field?';
$txt['levgal_cfields_field_text_length_desc'] = 'Counted in characters, use 0 for no maximum length.';
$txt['levgal_cfields_field_largetext_size'] = 'How big should this large text box be?';
$txt['levgal_cfields_field_largetext_size_desc'] = 'This indicates the size in columns and rows that will be shown to the user. The larger the text you expect the user to add, the larger the box should probably be.';
$txt['levgal_cfields_field_largetext_size_form'] = '%1$s columns by %2$s rows';
$txt['levgal_cfields_field_default_val'] = 'Default value for this field?';
$txt['levgal_cfields_field_default_val_desc'] = 'You can specify a standard value for this field that users can change.';
$txt['levgal_cfields_field_validation'] = 'Valid Content Rules:';
$txt['levgal_cfields_field_validation_desc'] = 'This allows to configure what rules are used to check what someone enters into this field.';
$txt['levgal_cfields_field_validation_nohtml'] = 'Free-format (no HTML, but allows any other content)';
$txt['levgal_cfields_field_validation_email'] = 'Field must contain a valid email address';
$txt['levgal_cfields_field_validation_numbers'] = 'Field must contain only digits 0-9';
$txt['levgal_cfields_field_validation_regex'] = 'Field must conform to the following regular expression:';

$txt['levgal_cfields_field_options'] = 'Field Options:';
$txt['levgal_cfields_field_options_desc'] = 'Here you can set the choices for this custom field. Leave one blank to remove it as an option, and use the radio button to indicate a default.';
$txt['levgal_cfields_field_options_multi_desc'] = 'Here you can set the choices for this custom field. Leave one blank to remove it as an option, and use the checkboxes to indicate one or more as the default selection.';
$txt['levgal_cfields_field_options_add'] = 'add option';
$txt['levgal_cfields_field_options_no_default'] = 'No selected default';

$txt['levgal_cfields_albums'] = 'Albums';
$txt['levgal_cfields_applies_to_album'] = 'Which album or albums does this field apply to?';
$txt['levgal_cfields_applies_to_album_desc'] = 'Some fields should really only apply to some albums. This is where you can configure it.';
$txt['levgal_cfields_applies_to_albums_all'] = 'Applies to all albums';
$txt['levgal_cfields_applies_to_albums_some'] = 'Applies only to some albums:';

$txt['levgal_cfield_could_not_be_saved'] = 'The custom field could not be saved:';
$txt['levgal_cfields_empty_field'] = 'The custom field needs a name.';
$txt['levgal_cfields_empty_options'] = 'No options were provided.';
