<?php
/**
 * Pannello Amministratore
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_mm_delete_preventivo', array($this, 'ajax_delete_preventivo'));
        add_action('wp_ajax_mm_update_stato', array($this, 'ajax_update_stato'));
        add_action('wp_ajax_mm_export_pdf', array($this, 'ajax_export_pdf'));
        add_action('wp_ajax_mm_run_migrations', array($this, 'ajax_run_migrations'));

        // AJAX handlers per catalogo servizi
        add_action('wp_ajax_mm_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mm_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_mm_delete_service', array($this, 'ajax_delete_service'));

        // AJAX handlers per tipi evento
        add_action('wp_ajax_mm_save_tipo_evento', array($this, 'ajax_save_tipo_evento'));
        add_action('wp_ajax_mm_delete_tipo_evento', array($this, 'ajax_delete_tipo_evento'));
    }
    
    /**
     * Aggiungi menu amministratore
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Preventivi', 'mm-preventivi'),
            __('Preventivi', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi',
            array($this, 'render_dashboard'),
            'dashicons-media-spreadsheet',
            30
        );
        
        add_submenu_page(
            'mm-preventivi',
            __('Tutti i Preventivi', 'mm-preventivi'),
            __('Tutti i Preventivi', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Nuovo Preventivo', 'mm-preventivi'),
            __('Nuovo Preventivo', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-new',
            array($this, 'render_new_preventivo')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Statistiche', 'mm-preventivi'),
            __('Statistiche', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-stats',
            array($this, 'render_statistics')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Tipi Evento', 'mm-preventivi'),
            __('Tipi Evento', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-tipi-evento',
            array($this, 'render_tipi_evento')
        );

        add_submenu_page(
            'mm-preventivi',
            __('Impostazioni', 'mm-preventivi'),
            __('Impostazioni', 'mm-preventivi'),
            'manage_options',
            'mm-preventivi-settings',
            array($this, 'render_settings')
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('mm_preventivi_settings', 'mm_preventivi_logo');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_name');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_address');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_phone');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_email');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_piva');
        register_setting('mm_preventivi_settings', 'mm_preventivi_company_cf');
        register_setting('mm_preventivi_settings', 'mm_preventivi_enpals_committente_percentage');
        register_setting('mm_preventivi_settings', 'mm_preventivi_enpals_lavoratore_percentage');
        register_setting('mm_preventivi_settings', 'mm_preventivi_iva_percentage');
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        MM_Security::check_admin_permission();
        
        // Gestisci azioni
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            if ($_GET['action'] === 'view') {
                $this->render_view_preventivo($id);
                return;
            }

            if ($_GET['action'] === 'edit') {
                $this->render_edit_preventivo($id);
                return;
            }
        }
        
        // Ottieni preventivi
        $filters = array();
        
        if (isset($_GET['stato']) && !empty($_GET['stato'])) {
            $filters['stato'] = sanitize_text_field($_GET['stato']);
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        
        $preventivi = MM_Database::get_all_preventivi($filters);
        
        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render dettaglio preventivo
     */
    private function render_view_preventivo($id) {
        MM_Security::check_admin_permission();
        
        $preventivo = MM_Database::get_preventivo($id);
        
        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }
        
        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/view-preventivo.php';
    }

    /**
     * Render modifica preventivo
     */
    private function render_edit_preventivo($id) {
        MM_Security::check_admin_permission();

        $preventivo = MM_Database::get_preventivo($id);

        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Gestisci salvataggio
        if (isset($_POST['mm_update_preventivo'])) {
            check_admin_referer('mm_update_preventivo_' . $id);

            $data = array(
                'categoria_id' => isset($_POST['categoria_id']) && !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null,
                'data_preventivo' => sanitize_text_field($_POST['data_preventivo']),
                'sposi' => sanitize_text_field($_POST['sposi']),
                'email' => sanitize_email($_POST['email']),
                'telefono' => sanitize_text_field($_POST['telefono']),
                'data_evento' => sanitize_text_field($_POST['data_evento']),
                'location' => sanitize_text_field($_POST['location']),
                'tipo_evento' => sanitize_text_field($_POST['tipo_evento']),
                'cerimonia' => isset($_POST['cerimonia']) ? $_POST['cerimonia'] : array(),
                'servizi_extra' => isset($_POST['servizi_extra']) ? $_POST['servizi_extra'] : array(),
                'note' => sanitize_textarea_field($_POST['note']),
                'totale_servizi' => floatval($_POST['totale_servizi']),
                'sconto' => floatval($_POST['sconto']),
                'sconto_percentuale' => floatval($_POST['sconto_percentuale']),
                'applica_enpals' => isset($_POST['applica_enpals']) ? 1 : 0,
                'applica_iva' => isset($_POST['applica_iva']) ? 1 : 0,
                'enpals' => floatval($_POST['enpals']),
                'iva' => floatval($_POST['iva']),
                'totale' => floatval($_POST['totale']),
                'data_acconto' => !empty($_POST['data_acconto']) ? sanitize_text_field($_POST['data_acconto']) : null,
                'importo_acconto' => !empty($_POST['importo_acconto']) ? floatval($_POST['importo_acconto']) : null,
                'servizi' => isset($_POST['servizi']) ? $_POST['servizi'] : array()
            );

            $result = MM_Database::update_preventivo($id, $data);

            if (is_wp_error($result)) {
                echo '<div class="mm-notice mm-notice-error">' . $result->get_error_message() . '</div>';
            } else {
                echo '<div class="mm-notice mm-notice-success">Preventivo aggiornato con successo!</div>';
                // Ricarica i dati aggiornati
                $preventivo = MM_Database::get_preventivo($id);
            }
        }

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/edit-preventivo.php';
    }

    /**
     * Render nuovo preventivo
     */
    public function render_new_preventivo() {
        MM_Security::check_admin_permission();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/new-preventivo.php';
    }

    /**
     * Render statistiche
     */
    public function render_statistics() {
        MM_Security::check_admin_permission();

        $stats = MM_Database::get_statistics();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/statistics.php';
    }
    
    /**
     * Render impostazioni
     */
    public function render_settings() {
        MM_Security::check_admin_permission();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render tipi evento
     */
    public function render_tipi_evento() {
        MM_Security::check_admin_permission();

        $tipi_evento = MM_Database::get_tipi_evento();

        include MM_PREVENTIVI_PLUGIN_DIR . 'admin/views/tipi-evento.php';
    }

    /**
     * AJAX: Elimina preventivo
     */
    public function ajax_delete_preventivo() {
        MM_Security::check_admin_permission();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }
        
        $id = intval($_POST['id']);
        
        $result = MM_Database::delete_preventivo($id);
        
        if ($result) {
            MM_Security::log_security_event('preventivo_deleted', array('id' => $id));
            wp_send_json_success(array('message' => __('Preventivo eliminato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'eliminazione del preventivo.', 'mm-preventivi')));
        }
    }
    
    /**
     * AJAX: Aggiorna stato
     */
    public function ajax_update_stato() {
        MM_Security::check_admin_permission();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }
        
        $id = intval($_POST['id']);
        $stato = sanitize_text_field($_POST['stato']);
        
        $result = MM_Database::update_stato($id, $stato);
        
        if ($result) {
            MM_Security::log_security_event('stato_updated', array('id' => $id, 'stato' => $stato));
            wp_send_json_success(array('message' => __('Stato aggiornato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'aggiornamento dello stato.', 'mm-preventivi')));
        }
    }
    
    /**
     * AJAX: Esporta PDF
     */
    public function ajax_export_pdf() {
        MM_Security::check_admin_permission();
        
        if (!isset($_GET['id'])) {
            wp_die(__('ID preventivo mancante.', 'mm-preventivi'));
        }
        
        $id = intval($_GET['id']);
        $preventivo = MM_Database::get_preventivo($id);
        
        if (!$preventivo) {
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }
        
        MM_PDF_Generator::generate_pdf($preventivo);
        exit;
    }

    /**
     * AJAX: Esegui migrazioni database
     */
    public function ajax_run_migrations() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        // Esegui migrazioni
        MM_Database::create_tables();

        wp_send_json_success(array('message' => __('Migrazioni database eseguite con successo!', 'mm-preventivi')));
    }

    /**
     * AJAX: Salva servizio (nuovo o aggiorna)
     */
    public function ajax_save_service() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

        $data = array(
            'nome_servizio' => sanitize_text_field($_POST['nome_servizio']),
            'descrizione' => isset($_POST['descrizione']) ? sanitize_textarea_field($_POST['descrizione']) : '',
            'prezzo_default' => isset($_POST['prezzo_default']) ? floatval($_POST['prezzo_default']) : 0,
            'categoria' => isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '',
            'attivo' => isset($_POST['attivo']) ? 1 : 0,
            'ordinamento' => isset($_POST['ordinamento']) ? intval($_POST['ordinamento']) : 0
        );

        if ($service_id > 0) {
            // Aggiorna servizio esistente
            $result = MM_Database::update_catalogo_servizio($service_id, $data);
            $message = __('Servizio aggiornato con successo.', 'mm-preventivi');
        } else {
            // Crea nuovo servizio
            $result = MM_Database::save_catalogo_servizio($data);
            $message = __('Servizio creato con successo.', 'mm-preventivi');
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => $message));
        }
    }

    /**
     * AJAX: Ottieni servizio
     */
    public function ajax_get_service() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        $id = intval($_POST['id']);
        $servizio = MM_Database::get_catalogo_servizio($id);

        if ($servizio) {
            wp_send_json_success(array('servizio' => $servizio));
        } else {
            wp_send_json_error(array('message' => __('Servizio non trovato.', 'mm-preventivi')));
        }
    }

    /**
     * AJAX: Elimina servizio
     */
    public function ajax_delete_service() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        $id = intval($_POST['id']);
        $result = MM_Database::delete_catalogo_servizio($id);

        if ($result) {
            wp_send_json_success(array('message' => __('Servizio eliminato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'eliminazione del servizio.', 'mm-preventivi')));
        }
    }

    /**
     * AJAX: Salva tipo evento
     */
    public function ajax_save_tipo_evento() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        $tipo_id = isset($_POST['tipo_id']) ? intval($_POST['tipo_id']) : 0;

        $data = array(
            'nome' => sanitize_text_field($_POST['nome']),
            'icona' => isset($_POST['icona']) ? sanitize_text_field($_POST['icona']) : '🎉',
            'ordinamento' => isset($_POST['ordinamento']) ? intval($_POST['ordinamento']) : 0,
            'attivo' => isset($_POST['attivo']) ? intval($_POST['attivo']) : 1
        );

        if ($tipo_id > 0) {
            // Aggiorna tipo evento esistente
            $result = MM_Database::update_tipo_evento($tipo_id, $data);
            $message = __('Tipo evento aggiornato con successo.', 'mm-preventivi');
        } else {
            // Crea nuovo tipo evento
            $result = MM_Database::save_tipo_evento($data);
            $message = __('Tipo evento creato con successo.', 'mm-preventivi');
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => $message));
        }
    }

    /**
     * AJAX: Elimina tipo evento
     */
    public function ajax_delete_tipo_evento() {
        MM_Security::check_admin_permission();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_preventivi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        $id = intval($_POST['id']);
        $result = MM_Database::delete_tipo_evento($id);

        if ($result) {
            wp_send_json_success(array('message' => __('Tipo evento eliminato con successo.', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nell\'eliminazione del tipo evento.', 'mm-preventivi')));
        }
    }
}

// Inizializza
new MM_Admin();
