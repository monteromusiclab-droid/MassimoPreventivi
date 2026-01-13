<?php
/**
 * Gestione Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Frontend {

    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_mm_save_preventivo', array($this, 'ajax_save_preventivo'));
        add_action('wp_ajax_nopriv_mm_save_preventivo', array($this, 'ajax_save_preventivo'));
        add_action('wp_ajax_mm_view_pdf', array($this, 'ajax_view_pdf'));
        add_action('wp_ajax_nopriv_mm_view_pdf', array($this, 'ajax_view_pdf'));
        add_action('wp_ajax_mm_update_preventivo_status', array($this, 'ajax_update_preventivo_status'));
        add_action('wp_ajax_nopriv_mm_update_preventivo_status', array($this, 'ajax_update_preventivo_status'));
        add_action('wp_ajax_mm_update_preventivo', array($this, 'ajax_update_preventivo'));
        add_action('wp_ajax_nopriv_mm_update_preventivo', array($this, 'ajax_update_preventivo'));
        add_action('wp_ajax_mm_get_preventivo_details', array($this, 'ajax_get_preventivo_details'));
        add_action('wp_ajax_nopriv_mm_get_preventivo_details', array($this, 'ajax_get_preventivo_details'));

        // Shortcodes per le pagine frontend
        add_shortcode('mm_preventivi_list', array($this, 'render_preventivi_list'));
        add_shortcode('mm_preventivi_stats', array($this, 'render_statistics'));
        add_shortcode('mm_edit_preventivo', array($this, 'render_edit_preventivo'));
    }
    
    /**
     * Render form preventivo
     */
    public static function render_form() {
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/form-preventivo.php';
    }

    /**
     * Render lista preventivi frontend
     */
    public function render_preventivi_list() {
        ob_start();
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/preventivi-list.php';
        return ob_get_clean();
    }

    /**
     * Render statistiche frontend
     */
    public function render_statistics() {
        ob_start();
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/statistics.php';
        return ob_get_clean();
    }

    /**
     * Render modifica preventivo frontend
     */
    public function render_edit_preventivo() {
        ob_start();
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/edit-preventivo.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Salva preventivo
     */
    public function ajax_save_preventivo() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')
            ));
        }
        
        // Rate limiting (20 richieste in 300 secondi = 5 minuti)
        $ip = MM_Security::get_client_ip();
        if (!MM_Security::check_rate_limit($ip, 20, 300)) {
            error_log('MM Preventivi - Rate limit superato per IP: ' . $ip);
            wp_send_json_error(array(
                'message' => __('Troppe richieste. Riprova tra qualche minuto.', 'mm-preventivi'),
                'debug' => 'rate_limit'
            ));
        }

        // Debug: Log punto di arrivo
        error_log('MM Preventivi - Inizio salvataggio preventivo');
        error_log('MM Preventivi - categoria_id POST: ' . print_r($_POST['categoria_id'] ?? 'NOT SET', true));
        error_log('MM Preventivi - applica_enpals POST: ' . print_r($_POST['applica_enpals'] ?? 'NOT SET', true));
        error_log('MM Preventivi - applica_iva POST: ' . print_r($_POST['applica_iva'] ?? 'NOT SET', true));

        // Prepara dati
        $data = array(
            'categoria_id' => isset($_POST['categoria_id']) && !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null,
            'data_preventivo' => sanitize_text_field($_POST['data_preventivo']),
            'sposi' => sanitize_text_field($_POST['sposi']),
            'email' => sanitize_email($_POST['email']),
            'telefono' => sanitize_text_field($_POST['telefono']),
            'data_evento' => sanitize_text_field($_POST['data_evento']),
            'location' => sanitize_text_field($_POST['location']),
            'tipo_evento' => sanitize_text_field($_POST['tipo_evento']),
            'cerimonia' => isset($_POST['cerimonia']) ? array_map('sanitize_text_field', $_POST['cerimonia']) : array(),
            'servizi_extra' => isset($_POST['servizi_extra']) ? array_map('sanitize_text_field', $_POST['servizi_extra']) : array(),
            'note' => sanitize_textarea_field($_POST['note']),
            'totale_servizi' => floatval($_POST['totale_servizi']),
            'sconto' => isset($_POST['sconto']) ? floatval($_POST['sconto']) : 0,
            'sconto_percentuale' => isset($_POST['sconto_percentuale']) ? floatval($_POST['sconto_percentuale']) : 0,
            'applica_enpals' => MM_Security::sanitize_boolean($_POST['applica_enpals'] ?? true),
            'applica_iva' => MM_Security::sanitize_boolean($_POST['applica_iva'] ?? true),
            'enpals_committente' => isset($_POST['enpals_committente']) ? floatval($_POST['enpals_committente']) : 0,
            'enpals_lavoratore' => isset($_POST['enpals_lavoratore']) ? floatval($_POST['enpals_lavoratore']) : 0,
            'iva' => floatval($_POST['iva']),
            'totale' => floatval($_POST['totale']),
            'data_acconto' => sanitize_text_field($_POST['data_acconto']),
            'importo_acconto' => floatval($_POST['importo_acconto']),
            'servizi' => array()
        );
        
        // Processa servizi
        if (isset($_POST['servizi']) && is_array($_POST['servizi'])) {
            foreach ($_POST['servizi'] as $servizio) {
                $data['servizi'][] = array(
                    'nome' => sanitize_text_field($servizio['nome']),
                    'prezzo' => floatval($servizio['prezzo']),
                    'sconto' => isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0
                );
            }
        }

        // Debug: Log valori processati
        error_log('MM Preventivi - applica_enpals dopo preparazione: ' . print_r($data['applica_enpals'], true));
        error_log('MM Preventivi - applica_iva dopo preparazione: ' . print_r($data['applica_iva'], true));

        // Salva nel database
        $preventivo_id = MM_Database::save_preventivo($data);

        if (is_wp_error($preventivo_id)) {
            MM_Security::log_security_event('preventivo_save_failed', array(
                'error' => $preventivo_id->get_error_message(),
                'data' => $data
            ));

            wp_send_json_error(array(
                'message' => $preventivo_id->get_error_message()
            ));
        }

        if (!$preventivo_id) {
            wp_send_json_error(array(
                'message' => __('Errore sconosciuto durante il salvataggio.', 'mm-preventivi')
            ));
        }
        
        MM_Security::log_security_event('preventivo_saved', array(
            'preventivo_id' => $preventivo_id
        ));
        
        wp_send_json_success(array(
            'message' => __('Preventivo salvato con successo!', 'mm-preventivi'),
            'preventivo_id' => $preventivo_id
        ));
    }

    /**
     * AJAX: Visualizza PDF
     */
    public function ajax_view_pdf() {
        // Log per debug
        error_log('MM Preventivi - ajax_view_pdf chiamato');
        error_log('MM Preventivi - GET params: ' . print_r($_GET, true));

        // Verifica nonce
        if (!isset($_GET['nonce']) || !MM_Security::verify_nonce($_GET['nonce'], 'mm_preventivi_view_pdf')) {
            error_log('MM Preventivi - Nonce verification failed');
            wp_die(__('Verifica di sicurezza fallita.', 'mm-preventivi'));
        }

        if (!isset($_GET['id'])) {
            error_log('MM Preventivi - ID mancante');
            wp_die(__('ID preventivo mancante.', 'mm-preventivi'));
        }

        $id = intval($_GET['id']);
        error_log('MM Preventivi - Caricamento preventivo ID: ' . $id);

        $preventivo = MM_Database::get_preventivo($id);

        if (!$preventivo) {
            error_log('MM Preventivi - Preventivo non trovato con ID: ' . $id);
            wp_die(__('Preventivo non trovato.', 'mm-preventivi'));
        }

        error_log('MM Preventivi - Preventivo caricato, generazione PDF...');
        error_log('MM Preventivi - Dati preventivo: ' . print_r($preventivo, true));

        // Genera PDF
        try {
            MM_PDF_Generator::generate_pdf($preventivo);
        } catch (Exception $e) {
            error_log('MM Preventivi - Errore generazione PDF: ' . $e->getMessage());
            error_log('MM Preventivi - Stack trace: ' . $e->getTraceAsString());
            wp_die('Errore nella generazione del PDF: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * AJAX: Aggiorna stato preventivo
     */
    public function ajax_update_preventivo_status() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')
            ));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Devi essere autenticato per modificare lo stato.', 'mm-preventivi')
            ));
        }

        // Verifica parametri
        if (!isset($_POST['preventivo_id']) || !isset($_POST['stato'])) {
            wp_send_json_error(array(
                'message' => __('Parametri mancanti.', 'mm-preventivi')
            ));
        }

        $preventivo_id = intval($_POST['preventivo_id']);
        $nuovo_stato = sanitize_text_field($_POST['stato']);

        // Valida stato
        $stati_validi = array('bozza', 'attivo', 'accettato', 'rifiutato', 'completato');
        if (!in_array($nuovo_stato, $stati_validi)) {
            wp_send_json_error(array(
                'message' => __('Stato non valido.', 'mm-preventivi')
            ));
        }

        // Aggiorna stato nel database
        $result = MM_Database::update_preventivo_status($preventivo_id, $nuovo_stato);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Errore durante l\'aggiornamento dello stato.', 'mm-preventivi')
            ));
        }

        // Log evento
        MM_Security::log_security_event('preventivo_status_updated', array(
            'preventivo_id' => $preventivo_id,
            'nuovo_stato' => $nuovo_stato
        ));

        wp_send_json_success(array(
            'message' => __('Stato aggiornato con successo!', 'mm-preventivi')
        ));
    }

    /**
     * AJAX: Aggiorna preventivo completo
     */
    public function ajax_update_preventivo() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')
            ));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Devi essere autenticato per modificare il preventivo.', 'mm-preventivi')
            ));
        }

        // Verifica ID preventivo
        if (!isset($_POST['preventivo_id']) || empty($_POST['preventivo_id'])) {
            wp_send_json_error(array(
                'message' => __('ID preventivo mancante.', 'mm-preventivi')
            ));
        }

        $preventivo_id = intval($_POST['preventivo_id']);

        // Prepara dati (stessa struttura di ajax_save_preventivo)
        $data = array(
            'categoria_id' => isset($_POST['categoria_id']) && !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null,
            'data_preventivo' => isset($_POST['data_preventivo']) ? sanitize_text_field($_POST['data_preventivo']) : '',
            'sposi' => isset($_POST['sposi']) ? sanitize_text_field($_POST['sposi']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'telefono' => isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '',
            'data_evento' => isset($_POST['data_evento']) ? sanitize_text_field($_POST['data_evento']) : '',
            'location' => isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '',
            'tipo_evento' => isset($_POST['tipo_evento']) ? sanitize_text_field($_POST['tipo_evento']) : '',
            'note' => isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '',
            'totale_servizi' => isset($_POST['totale_servizi']) ? floatval($_POST['totale_servizi']) : 0,
            'sconto' => isset($_POST['sconto']) ? floatval($_POST['sconto']) : 0,
            'sconto_percentuale' => isset($_POST['sconto_percentuale']) ? floatval($_POST['sconto_percentuale']) : 0,
            'applica_enpals' => MM_Security::sanitize_boolean($_POST['applica_enpals'] ?? true),
            'applica_iva' => MM_Security::sanitize_boolean($_POST['applica_iva'] ?? true),
            'enpals' => isset($_POST['enpals']) ? floatval($_POST['enpals']) : 0,
            'iva' => isset($_POST['iva']) ? floatval($_POST['iva']) : 0,
            'totale' => isset($_POST['totale']) ? floatval($_POST['totale']) : 0,
            'data_acconto' => isset($_POST['data_acconto']) ? sanitize_text_field($_POST['data_acconto']) : '',
            'importo_acconto' => isset($_POST['importo_acconto']) ? floatval($_POST['importo_acconto']) : 0,
            'stato' => isset($_POST['stato']) ? sanitize_text_field($_POST['stato']) : 'bozza',
            'servizi' => array()
        );

        // Processa servizi
        if (isset($_POST['servizi']) && is_array($_POST['servizi'])) {
            foreach ($_POST['servizi'] as $servizio) {
                $data['servizi'][] = array(
                    'nome_servizio' => sanitize_text_field($servizio['nome']),
                    'prezzo' => floatval($servizio['prezzo']),
                    'sconto' => isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0
                );
            }
        }

        // Aggiorna nel database
        $result = MM_Database::update_preventivo($preventivo_id, $data);

        if (is_wp_error($result)) {
            MM_Security::log_security_event('preventivo_update_failed', array(
                'error' => $result->get_error_message(),
                'preventivo_id' => $preventivo_id
            ));

            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Errore durante l\'aggiornamento del preventivo.', 'mm-preventivi')
            ));
        }

        MM_Security::log_security_event('preventivo_updated', array(
            'preventivo_id' => $preventivo_id
        ));

        wp_send_json_success(array(
            'message' => __('Preventivo aggiornato con successo!', 'mm-preventivi'),
            'preventivo_id' => $preventivo_id
        ));
    }

    /**
     * AJAX: Ottieni dettagli preventivo per modal
     */
    public function ajax_get_preventivo_details() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')
            ));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Devi essere autenticato per visualizzare i dettagli.', 'mm-preventivi')
            ));
        }

        // Verifica ID preventivo
        if (!isset($_POST['preventivo_id']) || empty($_POST['preventivo_id'])) {
            wp_send_json_error(array(
                'message' => __('ID preventivo mancante.', 'mm-preventivi')
            ));
        }

        $preventivo_id = intval($_POST['preventivo_id']);

        // Carica preventivo dal database
        $preventivo = MM_Database::get_preventivo($preventivo_id);

        if (!$preventivo) {
            wp_send_json_error(array(
                'message' => __('Preventivo non trovato.', 'mm-preventivi')
            ));
        }

        // Log evento
        MM_Security::log_security_event('preventivo_viewed', array(
            'preventivo_id' => $preventivo_id
        ));

        // Restituisci dati
        wp_send_json_success($preventivo);
    }
}

// Inizializza
new MM_Frontend();
