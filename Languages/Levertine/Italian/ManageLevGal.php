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
// Version: 1.2; ManageLevGal

// Important! Before editing these language files please read the text at the top of index.english.php.

// Generics
$txt['levgal_admin_js'] = 'L\'amministrazione di Levertine Gallery richiede che il browser supporti JavaScript.';

// Scheduled tasks.
$txt['scheduled_task_levgal_maintenance'] = 'Manutenzione di Levertine Gallery';
$txt['scheduled_task_desc_levgal_maintenance'] = 'Esegue la manutenzione giornaliera essenziale della galleria, e *non deve* essere disattivata.';

// General admin stuff
$txt['levgal_admindash'] = 'Pannello della galleria';
$txt['levgal_admindash_desc'] = 'Da questa area hai una panoramica della galleria.';

// The rest of the dashboard stats are in LevGal-Stats.language.php
$txt['levgal_stats_installed_time'] = 'Installata il:';
$txt['levgal_support_information'] = 'Informazioni di supporto';
$txt['levgal_support'] = 'Per qualsiasi domanda su Levertine Gallery per ElkArte, visita il nostro <a href="https://www.elkarte.net/community/index.php?topic=5969">topic di supporto</a>';
$txt['levgal_versions_elk'] = 'Versione di ElkArte:';
$txt['levgal_versions_lgal'] = 'Levertine Gallery:';
$txt['levgal_versions_php'] = 'PHP:';
$txt['levgal_versions_GD'] = 'Libreria GD:';
$txt['levgal_versions_Imagick'] = 'ImageMagick (Imagick):';
$txt['levgal_versions_webp'] = 'Supporto WebP:';
$txt['levgal_support_notavailable'] = '(non disponibile)';
$txt['levgal_support_available'] = '(disponibile)';
$txt['levgal_support_warning'] = '(non configurato correttamente)';
$txt['levgal_uploaded_items'] = 'Elementi caricati';
$txt['levgal_news_from_home'] = 'Notizie e annunci';
$txt['levgal_news_item'] = '%1$s di %2$s, %3$s';
$txt['levgal_news_not_available'] = 'Le notizie al momento non sono disponibili.';
$txt['levgal_out_of_date'] = 'La tua versione è %1$s, quella attuale è %2$s: è consigliabile aggiornare la galleria.';

// Settings
$txt['levgal_settings'] = 'Impostazioni della galleria';
$txt['levgal_settings_desc'] = 'Questa pagina ti permette di configurare alcune opzioni generali della galleria.';
$txt['lgal_count_author_views'] = 'Conta le visite dell\'autore ai propri elementi';
$txt['lgal_enable_mature'] = 'Attiva gli elementi per adulti';
$txt['lgal_enable_mature_desc'] = 'Gli elementi possono essere contrassegnati come per adulti: prima di vederli, agli utenti viene mostrato un avviso.';
$txt['lgal_feed_enable_album'] = 'Attiva il feed RSS degli elementi di un album';
$txt['lgal_feed_items_album'] = 'Numero di elementi più recenti nel feed RSS di un album';
$txt['lgal_feed_enable_item'] = 'Attiva il feed RSS dei commenti a un elemento';
$txt['lgal_feed_items_item'] = 'Numero di commenti più recenti nel feed RSS di un elemento';
$txt['lgal_feed_items_limits'] = '(1-50 elementi)';
$txt['lgal_tag_items_list'] = 'Elenco dei tag consentiti, separati da virgola';
$txt['lgal_tag_items_list_more'] = 'Consenti agli utenti di usare tag propri oltre a quelli indicati sopra';
$txt['lgal_items_per_page'] = 'Numero di elementi per pagina';
$txt['lgal_comments_per_page'] = 'Numero di commenti per pagina';
$txt['lgal_per_page_limits'] = '(10-50 elementi)';
$txt['lgal_import_rendering'] = 'Attiva la visualizzazione (parziale) dei codici BBC di altre gallerie';
$txt['lgal_open_link_new_tab'] = '"Clicca per vedere" apre l\'elemento in una nuova scheda, invece che in quella attuale';
$txt['lgal_dir'] = 'Cartella in cui salvare i file multimediali';
$txt['lgal_settings_social'] = 'Consenti agli utenti di condividere facilmente gli elementi sui social network';
$txt['lgal_settings_select_networks'] = 'Scegli i social network';
$txt['lgal_settings_metadata'] = 'Mostra i metadati aggiuntivi';
$txt['lgal_settings_metadata_desc'] = 'Immagini, audio e video contengono di solito informazioni aggiuntive che può essere interessante o utile mostrare agli utenti.';
$txt['lgal_settings_metadata_types'] = 'Opzioni dei metadati';
$txt['lgal_settings_metadata_images'] = 'Immagini (fotografie)';
$txt['lgal_settings_metadata_audio'] = 'File audio';
$txt['lgal_settings_metadata_video'] = 'File video';
$txt['lgal_opts_metadata_datetime'] = 'Data dello scatto';
$txt['lgal_opts_metadata_make'] = 'Marca e modello della fotocamera';
$txt['lgal_opts_metadata_flash'] = 'Impostazioni del flash';
$txt['lgal_opts_metadata_exposure_time'] = 'Tempo di esposizione';
$txt['lgal_opts_metadata_fnumber'] = 'Numero f (es. <em>&#402;</em>/2.4, vedi <a href="https://it.wikipedia.org/wiki/Numero_f" class="new_win" target="_blank">Wikipedia</a>)';
$txt['lgal_opts_metadata_shutter_speed'] = 'Velocità dell\'otturatore';
$txt['lgal_opts_metadata_focal_length'] = 'Lunghezza focale';
$txt['lgal_opts_metadata_digitalzoom'] = 'Zoom digitale';
$txt['lgal_opts_metadata_brightness'] = 'Luminosità';
$txt['lgal_opts_metadata_contrast'] = 'Contrasto';
$txt['lgal_opts_metadata_sharpness'] = 'Nitidezza';
$txt['lgal_opts_metadata_isospeed'] = 'Sensibilità ISO';
$txt['lgal_opts_metadata_lightsource'] = 'Sorgente luminosa';
$txt['lgal_opts_metadata_exposure_prog'] = 'Programma di esposizione';
$txt['lgal_opts_metadata_metering_mode'] = 'Modalità di misurazione';
$txt['lgal_opts_metadata_sensitivity'] = 'Tipo di sensibilità';
$txt['lgal_opts_metadata_title'] = 'Titolo';
$txt['lgal_opts_metadata_artist'] = 'Artista';
$txt['lgal_opts_metadata_album_artist'] = 'Artista dell\'album (se diverso da quello del brano)';
$txt['lgal_opts_metadata_album'] = 'Nome dell\'album';
$txt['lgal_opts_metadata_track_number'] = 'Numero della traccia';
$txt['lgal_opts_metadata_genre'] = 'Genere';
$txt['lgal_opts_metadata_playtime'] = 'Durata';
$txt['lgal_opts_metadata_bitrate'] = 'Bitrate';
$txt['lgal_opts_metadata_subject'] = 'Oggetto';
$txt['lgal_opts_metadata_author'] = 'Autore';
$txt['lgal_opts_metadata_keywords'] = 'Parole chiave';
$txt['lgal_opts_metadata_comment'] = 'Commento';

// Permissions
$txt['levgal_perms'] = 'Permessi della galleria';
$txt['levgal_perms_general'] = 'Permessi generali';
$txt['permissionname_lgal_view'] = 'Gruppi che possono vedere la galleria';
$txt['permissionname_lgal_manage'] = 'Gruppi che possono gestire la galleria';
$txt['lgal_manage_note'] = 'I gestori della galleria hanno tutti i permessi al suo interno e possono fare qualsiasi cosa.';
$txt['levgal_perms_album'] = 'Permessi sugli album';
$txt['permissionname_lgal_adduseralbum'] = 'Gruppi che possono creare album personali';
$txt['permissionname_lgal_addgroupalbum'] = 'Gruppi che possono creare album di gruppo';
$txt['permissionname_lgal_addalbum_approve'] = 'Gruppi che possono creare album (secondo i permessi sopra) senza attendere l\'approvazione';
$txt['permissionname_lgal_approve_album'] = 'Gruppi che possono approvare i nuovi album';
$txt['permissionname_lgal_edit_album_own'] = 'Gruppi che possono modificare i propri album';
$txt['permissionname_lgal_edit_album_any'] = 'Gruppi che possono modificare qualsiasi album';
$txt['permissionname_lgal_delete_album_own'] = 'Gruppi che possono eliminare i propri album';
$txt['permissionname_lgal_delete_album_any'] = 'Gruppi che possono eliminare qualsiasi album';
$txt['levgal_perms_item'] = 'Permessi sugli elementi';
$txt['permissionname_lgal_additem_own'] = 'Gruppi che possono aggiungere elementi ai propri album';
$txt['permissionname_lgal_additem_any'] = 'Gruppi che possono aggiungere elementi a qualsiasi album';
$txt['permissionname_lgal_addbulk'] = 'Gruppi che possono aggiungere elementi in blocco agli album (secondo i permessi sopra)';
$txt['permissionname_lgal_additem_approve'] = 'Gruppi che possono aggiungere elementi (secondo i permessi sopra) senza attendere l\'approvazione';
$txt['permissionname_lgal_approve_item'] = 'Gruppi che possono approvare gli elementi della galleria';
$txt['permissionname_lgal_edit_item_own'] = 'Gruppi che possono modificare i propri elementi';
$txt['permissionname_lgal_edit_item_any'] = 'Gruppi che possono modificare qualsiasi elemento';
$txt['permissionname_lgal_delete_item_own'] = 'Gruppi che possono eliminare i propri elementi';
$txt['permissionname_lgal_delete_item_any'] = 'Gruppi che possono eliminare qualsiasi elemento';
$txt['levgal_perms_comments'] = 'Permessi sui commenti';
$txt['permissionname_lgal_comment'] = 'Gruppi che possono aggiungere commenti';
$txt['permissionname_lgal_comment_appr'] = 'Gruppi che possono commentare (secondo i permessi sopra) senza attendere l\'approvazione';
$txt['permissionname_lgal_approve_comment'] = 'Gruppi che possono approvare i commenti degli utenti';
$txt['permissionname_lgal_edit_comment_own'] = 'Gruppi che possono modificare i propri commenti';
$txt['permissionname_lgal_edit_comment_any'] = 'Gruppi che possono modificare qualsiasi commento';
$txt['permissionname_lgal_delete_comment_own'] = 'Gruppi che possono eliminare i propri commenti';
$txt['permissionname_lgal_delete_comment_any'] = 'Gruppi che possono eliminare qualsiasi commento';
$txt['levgal_perms_moderation'] = 'Permessi di moderazione';
$txt['levgal_perms_moderation_desc'] = 'I permessi sopra valgono per gruppi molto ampi; può essere utile lasciare che gli utenti moderino ciò che caricano senza concedere troppi permessi.';
$txt['lgal_selfmod_approve_item'] = 'Gli utenti possono approvare gli elementi pubblicati da altri nei propri album';
$txt['lgal_selfmod_approve_comment'] = 'Gli utenti possono approvare i commenti di altri ai propri elementi';
$txt['lgal_selfmod_edit_comment'] = 'Gli utenti possono modificare i commenti di altri ai propri elementi';
$txt['lgal_selfmod_delete_comment'] = 'Gli utenti possono eliminare i commenti di altri ai propri elementi';
$txt['lgal_selfmod_lock_comment'] = 'Gli utenti possono bloccare i commenti ai propri elementi';
$txt['lgal_media_prefix'] = '[Galleria] %1$s';

// ACP Media notification Settings
$txt['setting_lglike'] = '"Mi piace" nella galleria';
$txt['setting_lgcomment'] = 'Commenti nella galleria';
$txt['setting_lgnew'] = 'Nuovi elementi nella galleria';

// Quotas; the rest is in ManageLevGal-Quotas.language.php
$txt['levgal_quotas'] = 'Quote e tipi di file della galleria';

// Custom Fields; the rest is in ManageLevGal-CFields.language.php
$txt['levgal_cfields'] = 'Campi personalizzati della galleria';

// Maintenance; the rest is in ManageLevGal-Maint.language.php
$txt['levgal_maint'] = 'Manutenzione della galleria';

// Notifications
$txt['levgal_notify'] = 'Notifiche della galleria';

// Moderation Log
$txt['levgal_modlog'] = 'Registro di moderazione della galleria';
$txt['levgal_modlog_desc'] = 'Da qui puoi vedere tutte le azioni di moderazione eseguite nella galleria.';
$txt['levgal_modlog_empty'] = 'Il registro di moderazione è vuoto.';
$txt['levgal_modlog_action'] = 'Azione eseguita';
$txt['levgal_modlog_time'] = 'Data e ora';
$txt['levgal_modlog_member'] = 'Utente';
$txt['levgal_modlog_position'] = 'Ruolo';
$txt['levgal_modlog_ip'] = 'Indirizzo IP';
$txt['levgal_modlog_remove'] = 'Rimuovi';
$txt['levgal_modlog_removeall'] = 'Rimuovi tutto';

// Credits
$txt['levgal_credits'] = 'Riconoscimenti della galleria';
$txt['levgal_credits_title'] = 'Riconoscimenti di Levertine Gallery';
$txt['levgal_credits_desc'] = 'Tutte le persone che hanno contribuito allo sviluppo di Levertine Gallery.';
$txt['levgal_credits_developers_title'] = 'Sviluppatori';
$txt['levgal_credits_developers_desc'] = 'Le persone che hanno realizzato Levertine Gallery:';
$txt['levgal_credits_components_title'] = 'Componenti';
$txt['levgal_credits_components_desc'] = 'Componenti e software aggiuntivi usati da Levertine Gallery:';
$txt['levgal_credits_images_title'] = 'Immagini';
$txt['levgal_credits_images_desc'] = 'Immagini e icone usate da Levertine Gallery:';
$txt['levgal_credits_translators_title'] = 'Traduttori';
$txt['levgal_credits_translators_desc'] = 'Le persone che hanno aiutato a portarla in tutto il mondo:';
$txt['levgal_credits_people_title'] = 'Ringraziamenti';
$txt['levgal_credits_people_desc'] = 'Altre persone che l\'autore vuole ringraziare:';

// Importers; the rest is in ManageLevGal-Importer.language.php
$txt['levgal_importers'] = 'Importazione nella galleria';
