<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0
 * @package levgal
 * @since 1.0
 */
// Version: 1.0; ManageLevGal maintenance

// Important! Before editing these language files, please read the text at the top of index.english.php.

$txt['levgal_maint_desc'] = 'Da qui è possibile svolgere varie funzioni in caso di comportamento imprevisto della galleria.';
$txt['levgal_run_task'] = 'Esegui ora questa attività';
$txt['levgal_maint_success'] = 'Operazione di manutenzione "%1$s" completata.';
$txt['levgal_recovered_album'] = 'Album recuperato';
$txt['levgal_task_recount'] = 'Riconteggia statistiche';
$txt['levgal_task_desc_recount'] = 'Forza un riconteggio delle statistiche e di altri dati interni. Utile se le statistiche sembrano essere sbagliate per qualsiasi motivo.';
$txt['levgal_task_findfix'] = 'Trova e correggi gli errori';
$txt['levgal_task_desc_findfix'] = 'Esamina il database alla ricerca di incongruenze e prova a correggerle. Può richiedere molto tempo: è consigliabile mettere il forum in manutenzione prima di eseguirla.';
$txt['levgal_findfix_substep_fixOrphanAlbumHierarchy'] = 'Controllo di album senza proprietari adeguati...';
$txt['levgal_findfix_substep_fixOrphanItems'] = 'Controllo di elementi in album inesistenti...';
$txt['levgal_findfix_substep_fixOrphanComments'] = 'Controllo di commenti su elementi inesistenti...';
$txt['levgal_findfix_substep_fixOrphanBookmarks'] = 'Controllo di segnalibri su elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanLikes'] = 'Controllo dei "Mi piace" su elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanTags'] = 'Controllo di tag inesistenti o di tag su elementi inesistenti...';
$txt['levgal_findfix_substep_fixOrphanNotify'] = 'Controllo di notifiche su album, elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanUnseen'] = 'Controllo dei registri "non visti" di elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanReports'] = 'Controllo delle segnalazioni ai moderatori con dettagli mancanti o inesistenti...';
$txt['levgal_findfix_substep_fixOrphanCustomFields'] = 'Controllo di campi personalizzati con campi o elementi inesistenti...';
$txt['levgal_findfix_substep_checkMissingFiles'] = 'Controllo di file mancanti...';
$txt['levgal_findfix_substep_checkExtraFiles'] = 'Controllo di file estranei...';
$txt['levgal_findfix_substep_checkAlbumFiles'] = 'Controllo di file estranei collegati agli album...';
$txt['levgal_findfix_substep_fixWrongFilesize'] = 'Controllo e correzione delle dimensioni dei file errate...';

$txt['levgal_task_rebuildsearch'] = 'Ricostruisci l\'indice di ricerca';
$txt['levgal_task_desc_rebuildsearch'] = 'Un indice di ricerca consente di eseguire rapidamente la ricerca degli elementi multimediali. Se ci sono problemi con la funzione di ricerca, potrebbe significare che l\'indice è danneggiato; una ricostruzione dovrebbe risolvere questo problema.';
$txt['levgal_task_rebuildsearch_album_subtitle'] = 'Ricostruzione indice album...';
$txt['levgal_task_rebuildsearch_item_subtitle'] = 'Ricostruzione indice elementi...';
$txt['levgal_task_rebuildthumbs'] = 'Ricostruisci le miniature degli elementi';
$txt['levgal_task_desc_rebuildthumbs'] = 'Se ci sono problemi con miniature o anteprime, questa operazione le ricostruisce a partire dall\'elemento, quando possibile e se non sono state caricate a mano da un utente.';
