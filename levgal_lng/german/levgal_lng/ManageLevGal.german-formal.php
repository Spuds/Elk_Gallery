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
// Version: 1.2; ManageLevGal

// Important! Before editing these language files please read the text at the top of index.english.php.

// Generics
$txt['levgal_admin_js'] = 'Die Verwaltung der Levertine Gallery erfordert, dass Ihr Browser JavaScript unterstützt, um zu funktionieren.';

// Scheduled tasks.
$txt['scheduled_task_levgal_maintenance'] = 'Wartung der Levertine-Galerie';
$txt['scheduled_task_desc_levgal_maintenance'] = 'Hier werden täglich wichtige Wartungsarbeiten an der Galerie durchgeführt, die *nicht* deaktiviert werden sollten.';

// General admin stuff
$txt['levgal_admindash'] = 'Medienübersicht';
$txt['levgal_admindash_desc'] = 'In diesem Bereich können Sie sich einen Überblick über Ihre Medien verschaffen.';

// The rest of the dashboard stats are in LevGal-Stats.language.php
$txt['levgal_stats_installed_time'] = 'Installiert am:';
$txt['levgal_support_information'] = ' Kundendienstinformationen';
$txt['levgal_support'] = 'Wenn Sie Fragen zur Levertine Galerie haben, wenden Sie sich bitte an <a href="http://levertine.com/">Levertine.com</a>, die Ihnen helfen können.';
$txt['levgal_versions_elk'] = 'ElkArte-Version:';
$txt['levgal_versions_lgal'] = 'Levertine Galerie:';
$txt['levgal_versions_php'] = 'PHP:';
$txt['levgal_versions_GD'] = 'GD-Bibliothek:';
$txt['levgal_versions_Imagick'] = 'ImageMagick (Imagick):';
$txt['levgal_versions_webp'] = 'Webp support:';
$txt['levgal_support_notavailable'] = '(nicht verfügbar)';
$txt['levgal_support_available'] = '(verfügbar)';
$txt['levgal_support_warning'] = 'nicht korrekt konfiguriert)';
$txt['levgal_uploaded_items'] = 'Hochgeladene Elemente';
$txt['levgal_news_from_home'] = 'Nachrichten und Ankündigungen';
$txt['levgal_news_item'] = '%1$s von %2$s, %3$s';
$txt['levgal_news_not_available'] = 'Nachrichten sind derzeit nicht verfügbar.';
$txt['levgal_out_of_date'] = 'Ihre Version ist %1$s, die aktuelle Version ist %2$s, Sie sollten wahrscheinlich die Galerie aktualisieren.';

// Settings
$txt['levgal_settings'] = 'Medieneinstellungen';
$txt['levgal_settings_desc'] = 'Auf dieser Seite können Sie einige globale Optionen für den Galeriebereich konfigurieren.';
$txt['lgal_count_author_views'] = 'Zählung der Autorenaufrufe für Artikel';
$txt['lgal_enable_mature'] = 'Nicht jugendfreie Elemente aktivieren';
$txt['lgal_enable_mature_desc'] = 'Artikel können als nicht jugendfrei markiert werden; dies bedeutet, dass den Benutzern eine Warnung angezeigt wird, bevor sie solche Artikel ansehen.';
$txt['lgal_feed_enable_album'] = 'RSS-Feed für Einträge in einem Album aktivieren';
$txt['lgal_feed_items_album'] = 'Anzahl der neuesten Einträge, die im RSS-Feed eines Albums angezeigt werden';
$txt['lgal_feed_enable_item'] = 'RSS-Feed für Kommentare zu einem Element einschalten';
$txt['lgal_feed_items_item'] = 'Anzahl der neuesten Kommentare, die im RSS-Feed eines Elements angezeigt werden sollen';
$txt['lgal_feed_items_limits'] = '(1-50 Einträge)';
$txt['lgal_tag_items_list'] = 'Kommagetrennte Liste der zulässigen Tags';
$txt['lgal_tag_items_list_more'] = 'Erlauben Sie den Nutzern, ihre eigenen Tags zusätzlich zu den oben genannten zu verwenden';
$txt['lgal_items_per_page'] = 'Anzahl der Elemente, die auf einer Seitenansicht angezeigt werden';
$txt['lgal_comments_per_page'] = 'Anzahl der anzuzeigenden Kommentare pro Seite';
$txt['lgal_per_page_limits'] = '(10-50 Einträge)';
$txt['lgal_import_rendering'] = 'Aktivieren Sie die (teilweise) Wiedergabe von BBC-Codes anderer Galerien';
$txt['lgal_open_link_new_tab'] = '"Click to view" opens item in a new tab, otherwise the current one';
$txt['lgal_settings_social'] = 'Erlauben Sie den Benutzern, Elemente einfach in sozialen Netzwerken zu teilen';
$txt['lgal_settings_select_networks'] = 'Soziale Netzwerke auswählen';
$txt['lgal_settings_metadata'] = 'Zusätzliche Metadaten anzeigen';
$txt['lgal_settings_metadata_desc'] = 'Bilder, Audio- und Videodateien enthalten in der Regel zusätzliche Informationen, die interessant oder nützlich sein können, um sie den Nutzern zu zeigen.';
$txt['lgal_settings_metadata_types'] = 'Metadaten-Optionen';
$txt['lgal_settings_metadata_images'] = 'Bilder (Fotografien)';
$txt['lgal_settings_metadata_audio'] = 'Audiodateien';
$txt['lgal_settings_metadata_video'] = 'Videodateien';
$txt['lgal_opts_metadata_datetime'] = 'Aufgenommen';
$txt['lgal_opts_metadata_make'] = 'Kameramarke/-modell';
$txt['lgal_opts_metadata_flash'] = 'Blitzlicht-Einstellungen';$txt['lgal_opts_metadata_exposure_time'] = 'Belichtungszeit';
$txt['lgal_opts_metadata_exposure_time'] = 'Exposure time';
$txt['lgal_opts_metadata_fnumber'] = 'F-Nummer(e.g. <em>&#402;</em>/2.4, siehe <a href="https://en.wikipedia.org/wiki/F-number" class="new_win" target="_blank">Wikipedia</a> für mehr)';
$txt['lgal_opts_metadata_shutter_speed'] = 'Verschlusszeit';
$txt['lgal_opts_metadata_focal_length'] = 'Brennweite';
$txt['lgal_opts_metadata_digitalzoom'] = 'Digitale Vergrößerung';
$txt['lgal_opts_metadata_brightness'] = 'Helligkeit';
$txt['lgal_opts_metadata_contrast'] = 'Kontrast';
$txt['lgal_opts_metadata_sharpness'] = 'Schärfe';
$txt['lgal_opts_metadata_isospeed'] = 'ISO-Empfindlichkeitsstufe';
$txt['lgal_opts_metadata_lightsource'] = 'Lichtquelle';
$txt['lgal_opts_metadata_exposure_prog'] = 'Belichtungsprogramm';
$txt['lgal_opts_metadata_metering_mode'] = 'Belichtungsmessungsmodus';
$txt['lgal_opts_metadata_sensitivity'] = 'Empfindlichkeitsart';
$txt['lgal_opts_metadata_title'] = 'Titel';
$txt['lgal_opts_metadata_artist'] = 'Künstler';
$txt['lgal_opts_metadata_album_artist'] = 'Künstler des Albums (falls abweichend vom Künstler des Titels)';
$txt['lgal_opts_metadata_album'] = 'Albumname';
$txt['lgal_opts_metadata_track_number'] = 'Titelnummer';
$txt['lgal_opts_metadata_genre'] = 'Genre';
$txt['lgal_opts_metadata_playtime'] = ' Abspielzeit';
$txt['lgal_opts_metadata_bitrate'] = 'Bitrate';
$txt['lgal_opts_metadata_subject'] = 'Betreff';
$txt['lgal_opts_metadata_author'] = 'Autor';
$txt['lgal_opts_metadata_keywords'] = 'Schlüsselwörter';
$txt['lgal_opts_metadata_comment'] = 'Kommentar';

// Permissions
$txt['levgal_perms'] = 'Medienberechtigungen';
$txt['levgal_perms_general'] = 'Allgemeine Berechtigungen';
$txt['permissionname_lgal_view'] = 'Gruppen, die die Galerie sehen können';
$txt['permissionname_lgal_manage'] = 'Gruppen, die die Galerie verwalten können';
$txt['lgal_manage_note'] = 'Galerie-Manager haben volle Rechte innerhalb der Galerie und können alles tun.';
$txt['levgal_perms_album'] = 'Albumberechtigungen';
$txt['permissionname_lgal_adduseralbum'] = 'Gruppen, die neue persönliche Alben hinzufügen können';
$txt['permissionname_lgal_addgroupalbum'] = 'Gruppen, die neue Gruppenalben hinzufügen können';
$txt['permissionname_lgal_addalbum_approve'] = 'Gruppen, die Alben hinzufügen können (vorbehaltlich der oben genannten Bedingungen), ohne auf die Genehmigung warten zu müssen';
$txt['permissionname_lgal_approve_album'] = 'Gruppen, die neue Galeriealben genehmigen können';
$txt['permissionname_lgal_edit_album_own'] = 'Gruppen, die ihre eigenen Alben bearbeiten können';
$txt['permissionname_lgal_edit_album_any'] = 'Gruppen, die jedes Album bearbeiten können';
$txt['permissionname_lgal_delete_album_own'] = 'Gruppen, die ihre eigenen Alben löschen können';
$txt['permissionname_lgal_delete_album_any'] = 'Gruppen, die beliebige Alben löschen können';
$txt['levgal_perms_item'] = 'Item Permissions';
$txt['permissionname_lgal_additem_own'] = 'Gruppen, die Elemente zu ihren eigenen Alben hinzufügen können';
$txt['permissionname_lgal_additem_any'] = 'Gruppen, die Elemente zu beliebigen Alben hinzufügen können';
$txt['permissionname_lgal_addbulk'] = 'Gruppen, die Elemente in Massen zu Alben hinzufügen können (vorbehaltlich der oben genannten Bedingungen)';
$txt['permissionname_lgal_additem_approve'] = 'Gruppen, die Einträge hinzufügen können (vorbehaltlich der oben genannten Bedingungen), ohne auf die Genehmigung warten zu müssen';
$txt['permissionname_lgal_approve_item'] = 'Gruppen, die Galerieeinträge genehmigen können';
$txt['permissionname_lgal_edit_item_own'] = 'Gruppen, die ihre eigenen Elemente bearbeiten können';
$txt['permissionname_lgal_edit_item_any'] = 'Gruppen, die beliebige Elemente bearbeiten können';
$txt['permissionname_lgal_delete_item_own'] = 'Gruppen, die ihre eigenen Elemente löschen können';
$txt['permissionname_lgal_delete_item_any'] = 'Gruppen, die beliebige Elemente löschen können';
$txt['levgal_perms_comments'] = 'Kommentarerlaubnis';
$txt['permissionname_lgal_comment'] = 'Gruppen, die Kommentare hinzufügen können';
$txt['permissionname_lgal_comment_appr'] = 'Gruppen, die Kommentare abgeben können (vorbehaltlich der oben genannten Bedingungen), ohne auf die Genehmigung warten zu müssen';
$txt['permissionname_lgal_approve_comment'] = 'Gruppen, die Mitgliederkommentare genehmigen können';
$txt['permissionname_lgal_edit_comment_own'] = 'Gruppen, die ihre eigenen Kommentare bearbeiten können';
$txt['permissionname_lgal_edit_comment_any'] = 'Gruppen, die beliebige Kommentare bearbeiten können';
$txt['permissionname_lgal_delete_comment_own'] = 'Gruppen, die ihre eigenen Kommentare löschen können';
$txt['permissionname_lgal_delete_comment_any'] = 'Gruppen, die beliebige Kommentare löschen können';
$txt['levgal_perms_moderation'] = 'Moderationserlaubnis';
$txt['levgal_perms_moderation_desc'] = 'Die obigen Berechtigungen sind für sehr breite Gruppen; Sie möchten vielleicht, dass Benutzer die Dinge, die sie hochladen, moderieren können, ohne viele Berechtigungen zu vergeben';
$txt['lgal_selfmod_approve_item'] = 'Benutzer können die Beiträge anderer in ihren Alben genehmigen';
$txt['lgal_selfmod_approve_comment'] = 'Benutzer können die Kommentare anderer zu ihren Artikeln genehmigen';
$txt['lgal_selfmod_edit_comment'] = 'Benutzer können die Kommentare anderer zu ihren Artikeln bearbeiten';
$txt['lgal_selfmod_delete_comment'] = 'Benutzer können die Kommentare anderer zu ihren Artikeln löschen';
$txt['lgal_selfmod_lock_comment'] = 'Benutzer können ihre Artikel gegen Kommentare sperren';
$txt['lgal_media_prefix'] = '[Media] %1$s';

// ACP Media notification Settings
$txt['setting_lglike'] = 'Galerie-Likes';
$txt['setting_lgcomment'] = 'Galeriekommentare';
$txt['setting_lgnew'] = 'Neue Galerieelemente';

// Quotas; the rest is in ManageLevGal-Quotas.language.php
$txt['levgal_quotas'] = 'Medienkontingente und Dateitypen';

// Custom Fields; the rest is in ManageLevGal-CFields.language.php
$txt['levgal_cfields'] = 'Benutzerdefinierte Medienfelder';

// Maintenance; the rest is in ManageLevGal-Maint.language.php
$txt['levgal_maint'] = 'Wartung der Medien';
// Notifications
$txt['levgal_notify'] = "Media Notifications";

// Moderation Log
$txt['levgal_modlog'] = 'Medienmoderationsprotokoll';
$txt['levgal_modlog_desc'] = 'Hier können Sie alle im Medienbereich durchgeführten Moderationsaktionen beobachten.';
$txt['levgal_modlog_empty'] = 'Es gibt keine Ereignisse im Moderationsprotokoll.';
$txt['levgal_modlog_action'] = 'Aktion durchgeführt';
$txt['levgal_modlog_time'] = 'Datum/Uhrzeit';
$txt['levgal_modlog_member'] = 'Mitglied';
$txt['levgal_modlog_position'] = 'Position';
$txt['levgal_modlog_ip'] = 'IP-Adresse';
$txt['levgal_modlog_remove'] = 'Entfernen';
$txt['levgal_modlog_removeall'] = 'Alle entfernen';

// Credits
$txt['levgal_credits'] = 'Medien-Danksagungen';
$txt['levgal_credits_title'] = 'Levertine Galerie Danksagungen';
$txt['levgal_credits_desc'] = 'All die netten Leute, die zur Entwicklung der Levertine Galerie beigetragen haben.';
$txt['levgal_credits_developers_title'] = 'Entwickler';
$txt['levgal_credits_developers_desc'] = 'Die Leute, die Levertine Galerie gebaut haben:';
$txt['levgal_credits_components_title'] = 'Komponenten';
$txt['levgal_credits_components_desc'] = 'Von Levertine Galerie verwendete Komponenten und zusätzliche Software:';
$txt['levgal_credits_images_title'] = 'Bilder';
$txt['levgal_credits_images_desc'] = 'Von Levertine Galerie verwendete Bilder und Icons:';
$txt['levgal_credits_translators_title'] = 'Übersetzer';
$txt['levgal_credits_translators_desc'] = 'Die Leute, die geholfen haben, es für die Welt bereit zu machen:';
$txt['levgal_credits_people_title'] = 'Danke';
$txt['levgal_credits_people_desc'] = 'Andere Personen, denen der Autor danken möchte:';

// Importers; the rest is in ManageLevGal-Importer.language.php
$txt['levgal_importers'] = 'Medien-Importeure';
