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

$txt['levgal_maint_desc'] = 'Da qui è possibile svolgere varie funzioni in caso di comportamento imprevisto della galleria.';
$txt['levgal_run_task'] = 'Esegui ora questa attività';
$txt['levgal_maint_success'] = 'Operazione di manutenzione "%1$s" competata con successo.';
$txt['levgal_recovered_album'] = 'Album recuperato';
$txt['levgal_task_recount'] = 'Riconteggia statistiche';
$txt['levgal_task_desc_recount'] = 'Forza un riconteggio delle statistiche e di altri dati interni. Utile se le statistiche sembrano essere sbagliate per qualsiasi motivo.';
$txt['levgal_task_findfix'] = 'Trova e ripara errori';
$txt['levgal_task_desc_findfix'] = 'Esamina il database per incoerenze e tentare di risolverle. Questo potrebbe essere un processo lungo. Probabilmente dovresti mettere il forum in modalità di manutenzione prima di eseguire questa attività.';
$txt['levgal_findfix_substep_fixOrphanAlbumHierarchy'] = "Controllo di album senza proprietari adeguati...';
$txt['levgal_findfix_substep_fixOrphanItems'] = 'Controllo di elementi in album inesistenti...';
$txt['levgal_findfix_substep_fixOrphanComments'] = 'Controllo di commenti su elementi inesistenti...';
$txt['levgal_findfix_substep_fixOrphanBookmarks'] = 'Controllo di segnalibri su elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanLikes'] = 'Controllo di Mi piace su elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanTags'] = 'Controllo di tag e tag inesistenti su elementi inesistenti...';
$txt['levgal_findfix_substep_fixOrphanNotify'] = 'Controllo di notifiche su album, elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanUnseen'] = 'Controllo di log non visti su elementi o utenti inesistenti...';
$txt['levgal_findfix_substep_fixOrphanReports'] = 'Controllo di rapporti di moderazione con dettagli mancanti o inesistenti...';
$txt['levgal_findfix_substep_fixOrphanCustomFields'] = 'Controllo di campi personalizzati con campi o elementi inesistenti...';
$txt['levgal_findfix_substep_checkMissingFiles'] = 'Controllo di file mancanti...';
$txt['levgal_findfix_substep_checkExtraFiles'] = 'Controllo di file estranei...';
$txt['levgal_findfix_substep_checkAlbumFiles'] = 'Controllo di file estranei all'interno degli album...';

$txt['levgal_task_rebuildsearch'] = 'Ricostruisci l\'indice di ricerca';
$txt['levgal_task_desc_rebuildsearch'] = 'Un indice di ricerca consente di eseguire rapidamente la ricerca degli elementi multimediali. Se ci sono problemi con la funzione di ricerca, potrebbe significare che l\'indice è danneggiato; una ricostruzione dovrebbe risolvere questo problema.';
$txt['levgal_task_rebuildsearch_album_subtitle'] = 'Ricostruzione indice album...';
$txt['levgal_task_rebuildsearch_item_subtitle'] = 'Ricostruzione indice elementi...';
$txt['levgal_task_rebuildthumbs'] = 'Ricostruzione anteprime elementi multimediali';
$txt['levgal_task_desc_rebuildthumbs'] = 'Se si verificano problemi con miniature o anteprime, questa opzione di manutenzione li ricostruirà dall\'elemento multimediale ove possibile, se non sono state caricate manualmente da un utente.';
