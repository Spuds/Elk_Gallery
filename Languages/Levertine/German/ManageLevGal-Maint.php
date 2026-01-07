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

$txt['levgal_maint_desc'] = 'Von hier aus können Sie verschiedene Funktionen im Falle eines unerwarteten Verhaltens der Galerie ausführen.';
$txt['levgal_run_task'] = 'Diese Aufgabe jetzt ausführen.';
$txt['levgal_maint_success'] = 'Wartungsaufgabe "%1$s" erfolgreich abgeschlossen.';
$txt['levgal_recovered_album'] = 'Wiederhergestelltes Album';
$txt['levgal_task_recount'] = 'Wiederherstellungsstatistik';
$txt['levgal_task_desc_recount'] = 'Erzwingt eine Nachzählung von Statistiken und anderen internen Zahlen. Nützlich, wenn die Statistiken aus irgendeinem Grund falsch zu sein scheinen.';
$txt['levgal_task_findfix'] = 'Fehler finden und beheben.';
$txt['levgal_task_desc_findfix'] = 'Untersuche die Datenbank auf Inkonsistenzen und versuche sie zu beheben. Dies kann ein langer Prozess sein. Sie sollten wahrscheinlich das Forum in den Wartungsmodus versetzen, bevor Sie dies ausführen.';
$txt['levgal_findfix_substep_fixOrphanAlbumHierarchy'] = 'Überprüfen auf Alben ohne richtige Besitzer ...';
$txt['levgal_findfix_substep_fixOrphanItems'] = 'Überprüfung auf Elemente in nicht existierenden Alben ...';
$txt['levgal_findfix_substep_fixOrphanComments'] = 'Auf Kommentare zu nicht existierenden Elementen prüfen ...';
$txt['levgal_findfix_substep_fixOrphanBookmarks'] = 'Suche nach Lesezeichen für nicht existierende Elemente oder Benutzer ...';
$txt['levgal_findfix_substep_fixOrphanLikes'] = 'Suche nach Likes für nicht existierende Elemente oder Benutzer ...';
$txt['levgal_findfix_substep_fixOrphanTags'] = 'Auf nicht existierende Tags oder Tags bei nicht existierenden Elementen prüfen ...';
$txt['levgal_findfix_substep_fixOrphanNotify'] = 'Überprüfung auf Benachrichtigungen über nicht existierende Alben, Elemente oder Benutzer ...';
$txt['levgal_findfix_substep_fixOrphanUnseen'] = 'Überprüfung auf ungesehene Protokolle für nicht existierende Elemente oder Benutzer ...';
$txt['levgal_findfix_substep_fixOrphanReports'] = 'Überprüfung auf Moderationsberichte mit fehlenden oder nicht existierenden Details ...';
$txt['levgal_findfix_substep_fixOrphanCustomFields'] = 'Überprüfung auf benutzerdefinierte Felder mit nicht existierenden Feldern oder Elementen ...';
$txt['levgal_findfix_substep_checkMissingFiles'] = 'Auf fehlende Dateien prüfen ...';
$txt['levgal_findfix_substep_checkExtraFiles'] = 'Auf fremde Dateien prüfen ...';
$txt['levgal_findfix_substep_checkAlbumFiles'] = 'Auf fremde albumbezogene Dateien prüfen ...';

$txt['levgal_task_rebuildsearch'] = 'Suchindex neu aufbauen';
$txt['levgal_task_desc_rebuildsearch'] = 'Ein Suchindex ermöglicht ein schnelles Durchsuchen der Medienelemente. Wenn es Probleme mit der Suchfunktion gibt, kann das bedeuten, dass der Index beschädigt wurde; ein Neuaufbau sollte dies beheben.';
$txt['levgal_task_rebuildsearch_album_subtitle'] = 'Album-Index neu aufbauen ...';
$txt['levgal_task_rebuildsearch_item_subtitle'] = 'Artikelindex neu aufbauen ...';
$txt['levgal_task_rebuildthumbs'] = 'Element-Thumbnails neu erstellen';
$txt['levgal_task_desc_rebuildthumbs'] = 'Wenn es Probleme mit Miniatur- oder Vorschaubildern gibt, werden sie mit dieser Wartungsoption nach Möglichkeit aus dem Artikel neu erstellt, sofern sie nicht manuell von einem Benutzer hochgeladen wurden.';
