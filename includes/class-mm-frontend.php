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
        add_action('wp_ajax_mm_send_preventivo_email', array($this, 'ajax_send_preventivo_email'));
        add_action('wp_ajax_nopriv_mm_send_preventivo_email', array($this, 'ajax_send_preventivo_email'));
        add_action('wp_ajax_mm_get_whatsapp_link', array($this, 'ajax_get_whatsapp_link'));
        add_action('wp_ajax_nopriv_mm_get_whatsapp_link', array($this, 'ajax_get_whatsapp_link'));

        // Handler per visualizzazione pubblica preventivi
        add_action('wp_ajax_mm_view_public_preventivo', array($this, 'ajax_view_public_preventivo'));
        add_action('wp_ajax_nopriv_mm_view_public_preventivo', array($this, 'ajax_view_public_preventivo'));

        // Handler per download PDF
        add_action('wp_ajax_mm_download_pdf', array($this, 'ajax_download_pdf'));
        add_action('wp_ajax_nopriv_mm_download_pdf', array($this, 'ajax_download_pdf'));

        // Shortcodes per le pagine frontend
        add_shortcode('mm_preventivi_list', array($this, 'render_preventivi_list'));
        add_shortcode('mm_preventivi_stats', array($this, 'render_statistics'));
        add_shortcode('mm_edit_preventivo', array($this, 'render_edit_preventivo'));
        add_shortcode('mm_assegnazioni_collaboratori', array($this, 'render_assegnazioni_collaboratori'));

        // AJAX handlers per assegnazioni collaboratori frontend
        add_action('wp_ajax_mm_frontend_assegna_collaboratore', array($this, 'ajax_frontend_assegna_collaboratore'));
        add_action('wp_ajax_mm_frontend_rimuovi_assegnazione', array($this, 'ajax_frontend_rimuovi_assegnazione'));
        add_action('wp_ajax_mm_frontend_get_assegnazioni', array($this, 'ajax_frontend_get_assegnazioni'));
        add_action('wp_ajax_mm_frontend_invia_whatsapp_collaboratore', array($this, 'ajax_invia_whatsapp_collaboratore'));

        // Handler per export PDF assegnazioni
        add_action('wp_ajax_mm_export_assegnazioni_pdf', array($this, 'ajax_export_assegnazioni_pdf'));
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
            'cerimonia' => isset($_POST['cerimonia']) && is_array($_POST['cerimonia']) ? array_map('sanitize_text_field', $_POST['cerimonia']) : array(),
            'servizi_extra' => isset($_POST['servizi_extra']) && is_array($_POST['servizi_extra']) ? array_map('sanitize_text_field', $_POST['servizi_extra']) : array(),
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
                    'nome_servizio' => sanitize_text_field($servizio['nome_servizio']),
                    'prezzo' => floatval($servizio['prezzo']),
                    'sconto' => isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0
                );
            }
        }

        // Processa acconti multipli
        $data['acconti'] = array();
        if (isset($_POST['acconti_data']) && is_array($_POST['acconti_data']) &&
            isset($_POST['acconti_importo']) && is_array($_POST['acconti_importo'])) {

            error_log('MM Preventivi - Acconti POST ricevuti: data=' . print_r($_POST['acconti_data'], true) . ', importo=' . print_r($_POST['acconti_importo'], true));

            $count = min(count($_POST['acconti_data']), count($_POST['acconti_importo']));
            for ($i = 0; $i < $count; $i++) {
                if (!empty($_POST['acconti_data'][$i]) && !empty($_POST['acconti_importo'][$i])) {
                    $data['acconti'][] = array(
                        'data_acconto' => sanitize_text_field($_POST['acconti_data'][$i]),
                        'importo_acconto' => floatval($_POST['acconti_importo'][$i])
                    );
                }
            }
        }

        error_log('MM Preventivi - Acconti processati: ' . print_r($data['acconti'], true));

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

        // Carica assegnazioni collaboratori
        $assegnazioni = MM_Database::get_assegnazioni_preventivo($preventivo_id);

        // Decodifica campi JSON per il frontend
        $preventivo['cerimonia_array'] = !empty($preventivo['cerimonia'])
            ? (is_array($preventivo['cerimonia']) ? $preventivo['cerimonia'] : json_decode($preventivo['cerimonia'], true))
            : array();
        $preventivo['servizi_extra_array'] = !empty($preventivo['servizi_extra'])
            ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : json_decode($preventivo['servizi_extra'], true))
            : array();

        // Carica acconti se disponibili
        $preventivo['acconti'] = isset($preventivo['acconti']) ? $preventivo['acconti'] : array();

        // Log evento
        MM_Security::log_security_event('preventivo_viewed', array(
            'preventivo_id' => $preventivo_id
        ));

        // Restituisci dati strutturati
        wp_send_json_success(array(
            'preventivo' => $preventivo,
            'servizi' => isset($preventivo['servizi']) ? $preventivo['servizi'] : array(),
            'assegnazioni' => $assegnazioni
        ));
    }

    /**
     * AJAX: Invia preventivo via email
     */
    public function ajax_send_preventivo_email() {
        // Verifica nonce (supporta sia mm_preventivi_nonce che mm_send_email)
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'mm_preventivi_nonce') ||
                          wp_verify_nonce($_POST['nonce'], 'mm_send_email');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => __('Richiesta non valida.', 'mm-preventivi')
            ));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Devi essere autenticato.', 'mm-preventivi')
            ));
        }

        // Ottieni ID preventivo
        $preventivo_id = isset($_POST['preventivo_id']) ? intval($_POST['preventivo_id']) : 0;

        if (!$preventivo_id) {
            wp_send_json_error(array(
                'message' => __('ID preventivo mancante.', 'mm-preventivi')
            ));
        }

        // Invia email
        $result = MM_Email::send_preventivo($preventivo_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Email inviata con successo!', 'mm-preventivi')
        ));
    }

    /**
     * AJAX: Genera link WhatsApp per preventivo
     */
    public function ajax_get_whatsapp_link() {
        // Verifica nonce (supporta sia mm_preventivi_nonce che mm_send_email)
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'mm_preventivi_nonce') ||
                          wp_verify_nonce($_POST['nonce'], 'mm_send_email');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => __('Richiesta non valida.', 'mm-preventivi')
            ));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Devi essere autenticato.', 'mm-preventivi')
            ));
        }

        // Ottieni ID preventivo
        $preventivo_id = isset($_POST['preventivo_id']) ? intval($_POST['preventivo_id']) : 0;

        if (!$preventivo_id) {
            wp_send_json_error(array(
                'message' => __('ID preventivo mancante.', 'mm-preventivi')
            ));
        }

        // Genera link WhatsApp
        $link = MM_Email::generate_whatsapp_link($preventivo_id);

        if (is_wp_error($link)) {
            wp_send_json_error(array(
                'message' => $link->get_error_message()
            ));
        }

        // Log evento
        MM_Security::log_security_event('whatsapp_link_generated', array(
            'preventivo_id' => $preventivo_id
        ));

        wp_send_json_success(array(
            'link' => $link,
            'message' => __('Link WhatsApp generato!', 'mm-preventivi')
        ));
    }

    /**
     * Genera token di accesso pubblico per un preventivo
     */
    public static function generate_public_token($preventivo_id) {
        // Genera token basato su ID preventivo + salt + timestamp
        $salt = wp_salt('auth');
        $token = hash('sha256', $preventivo_id . $salt . get_option('mm_preventivi_secret_key', AUTH_KEY));

        // Salva token nel database con scadenza (45 giorni)
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_preventivi';

        $wpdb->update(
            $table_name,
            array(
                'public_token' => $token,
                'token_expires' => date('Y-m-d H:i:s', strtotime('+45 days'))
            ),
            array('id' => $preventivo_id),
            array('%s', '%s'),
            array('%d')
        );

        return $token;
    }

    /**
     * Valida token di accesso pubblico
     */
    public static function validate_public_token($preventivo_id, $token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_preventivi';

        $preventivo = $wpdb->get_row($wpdb->prepare(
            "SELECT public_token, token_expires FROM $table_name WHERE id = %d",
            $preventivo_id
        ), ARRAY_A);

        if (!$preventivo) {
            return false;
        }

        // Verifica token
        if ($preventivo['public_token'] !== $token) {
            return false;
        }

        // Verifica scadenza
        if (!empty($preventivo['token_expires'])) {
            $expires = strtotime($preventivo['token_expires']);
            if ($expires < time()) {
                return false; // Token scaduto
            }
        }

        return true;
    }

    /**
     * AJAX handler per visualizzazione pubblica preventivo
     */
    public function ajax_view_public_preventivo() {
        // Ottieni parametri
        $preventivo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (!$preventivo_id || !$token) {
            wp_die('Parametri mancanti', 'Errore', array('response' => 400));
        }

        // Valida token
        if (!self::validate_public_token($preventivo_id, $token)) {
            wp_die('Link non valido o scaduto. Contatta il fornitore per ricevere un nuovo link.', 'Accesso negato', array('response' => 403));
        }

        // Carica preventivo
        $preventivo = MM_Database::get_preventivo($preventivo_id);

        if (!$preventivo) {
            wp_die('Preventivo non trovato', 'Errore', array('response' => 404));
        }

        // Log accesso pubblico
        MM_Security::log_security_event('public_preventivo_view', array(
            'preventivo_id' => $preventivo_id,
            'ip' => $_SERVER['REMOTE_ADDR']
        ));

        // Genera HTML del preventivo (usa lo stesso metodo del PDF)
        MM_PDF_Generator::generate_pdf($preventivo);
        exit;
    }

    /**
     * AJAX handler per download PDF reale
     */
    public function ajax_download_pdf() {
        // Ottieni parametri
        $preventivo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (!$preventivo_id) {
            wp_die('ID preventivo mancante', 'Errore', array('response' => 400));
        }

        // Se c'è un token, valida accesso pubblico
        if (!empty($token)) {
            if (!self::validate_public_token($preventivo_id, $token)) {
                wp_die('Token non valido o scaduto', 'Accesso negato', array('response' => 403));
            }
        } else {
            // Altrimenti richiedi autenticazione
            if (!MM_Auth::is_logged_in()) {
                wp_die('Autenticazione richiesta', 'Accesso negato', array('response' => 401));
            }
        }

        // Carica preventivo
        $preventivo = MM_Database::get_preventivo($preventivo_id);

        if (!$preventivo) {
            wp_die('Preventivo non trovato', 'Errore', array('response' => 404));
        }

        // Log download PDF
        MM_Security::log_security_event('pdf_download', array(
            'preventivo_id' => $preventivo_id,
            'ip' => $_SERVER['REMOTE_ADDR']
        ));

        // Genera e scarica PDF reale
        MM_PDF_Generator::download_real_pdf($preventivo);
        exit;
    }

    /**
     * Genera link pubblico per un preventivo
     */
    public static function get_public_link($preventivo_id) {
        // Genera o recupera token
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_preventivi';

        $preventivo = $wpdb->get_row($wpdb->prepare(
            "SELECT public_token, token_expires FROM $table_name WHERE id = %d",
            $preventivo_id
        ), ARRAY_A);

        // Se non esiste token o è scaduto, genera nuovo
        if (empty($preventivo['public_token']) ||
            (!empty($preventivo['token_expires']) && strtotime($preventivo['token_expires']) < time())) {
            $token = self::generate_public_token($preventivo_id);
        } else {
            $token = $preventivo['public_token'];
        }

        // Genera URL pulito: /preventivo/{id}/{token}
        $url = home_url('/preventivo/' . $preventivo_id . '/' . $token . '/');

        return $url;
    }

    /**
     * Render assegnazioni collaboratori frontend
     */
    public function render_assegnazioni_collaboratori() {
        ob_start();
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/assegnazioni-collaboratori.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Assegna collaboratore da frontend
     */
    public function ajax_frontend_assegna_collaboratore() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array('message' => __('Devi essere autenticato.', 'mm-preventivi')));
        }

        $preventivo_id = intval($_POST['preventivo_id']);
        $collaboratore_id = intval($_POST['collaboratore_id']);

        $data = array(
            'ruolo_evento' => isset($_POST['ruolo_evento']) ? sanitize_text_field($_POST['ruolo_evento']) : '',
            'compenso' => isset($_POST['compenso']) ? floatval($_POST['compenso']) : null,
            'note' => isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : ''
        );

        $result = MM_Database::assegna_collaboratore($preventivo_id, $collaboratore_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Log evento
        MM_Security::log_security_event('collaboratore_assegnato', array(
            'preventivo_id' => $preventivo_id,
            'collaboratore_id' => $collaboratore_id
        ));

        wp_send_json_success(array('message' => __('Collaboratore assegnato con successo!', 'mm-preventivi'), 'id' => $result));
    }

    /**
     * AJAX: Rimuovi assegnazione da frontend
     */
    public function ajax_frontend_rimuovi_assegnazione() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array('message' => __('Devi essere autenticato.', 'mm-preventivi')));
        }

        $id = intval($_POST['id']);
        $result = MM_Database::rimuovi_assegnazione($id);

        if ($result) {
            MM_Security::log_security_event('assegnazione_rimossa', array('assegnazione_id' => $id));
            wp_send_json_success(array('message' => __('Assegnazione rimossa con successo!', 'mm-preventivi')));
        } else {
            wp_send_json_error(array('message' => __('Errore nella rimozione.', 'mm-preventivi')));
        }
    }

    /**
     * AJAX: Ottieni assegnazioni preventivo da frontend
     */
    public function ajax_frontend_get_assegnazioni() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array('message' => __('Devi essere autenticato.', 'mm-preventivi')));
        }

        $preventivo_id = intval($_POST['preventivo_id']);
        $assegnazioni = MM_Database::get_assegnazioni_preventivo($preventivo_id);

        wp_send_json_success(array('assegnazioni' => $assegnazioni));
    }

    /**
     * AJAX: Invia messaggio WhatsApp a collaboratore
     */
    public function ajax_invia_whatsapp_collaboratore() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !MM_Security::verify_nonce($_POST['nonce'])) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita.', 'mm-preventivi')));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_send_json_error(array('message' => __('Devi essere autenticato.', 'mm-preventivi')));
        }

        $assegnazione_id = intval($_POST['assegnazione_id']);
        $preventivo_id = intval($_POST['preventivo_id']);
        $collaboratore_id = intval($_POST['collaboratore_id']);

        // Carica dati
        $preventivo = MM_Database::get_preventivo($preventivo_id);
        $collaboratore = MM_Database::get_collaboratore($collaboratore_id);

        if (!$preventivo || !$collaboratore) {
            wp_send_json_error(array('message' => __('Dati non trovati.', 'mm-preventivi')));
        }

        if (empty($collaboratore['whatsapp'])) {
            wp_send_json_error(array('message' => __('Il collaboratore non ha un numero WhatsApp.', 'mm-preventivi')));
        }

        // Formatta numero WhatsApp
        $whatsapp_number = preg_replace('/[^0-9]/', '', $collaboratore['whatsapp']);

        // Formatta data evento
        $data_evento = date('d/m/Y', strtotime($preventivo['data_evento']));

        // Crea messaggio
        $company_name = get_option('mm_preventivi_company_name', 'La nostra azienda');
        $message = "Ciao " . $collaboratore['nome'] . "!\n\n";
        $message .= "Ti ricordo l'evento del *" . $data_evento . "*";
        if (!empty($preventivo['tipo_evento'])) {
            $message .= " (" . $preventivo['tipo_evento'] . ")";
        }
        $message .= ":\n";
        $message .= "Cliente: " . $preventivo['sposi'] . "\n";
        if (!empty($preventivo['location'])) {
            $message .= "Location: " . $preventivo['location'] . "\n";
        }
        $message .= "\nGrazie!\n" . $company_name;

        // Genera link WhatsApp
        $whatsapp_link = 'https://wa.me/' . $whatsapp_number . '?text=' . rawurlencode($message);

        // Segna notifica come inviata
        MM_Database::segna_notifica_inviata($assegnazione_id);

        // Log evento
        MM_Security::log_security_event('whatsapp_collaboratore', array(
            'assegnazione_id' => $assegnazione_id,
            'preventivo_id' => $preventivo_id,
            'collaboratore_id' => $collaboratore_id
        ));

        wp_send_json_success(array(
            'link' => $whatsapp_link,
            'message' => __('Link WhatsApp generato!', 'mm-preventivi')
        ));
    }

    /**
     * AJAX: Export PDF assegnazioni
     */
    public function ajax_export_assegnazioni_pdf() {
        // Verifica nonce
        if (!isset($_GET['nonce']) || !MM_Security::verify_nonce($_GET['nonce'])) {
            wp_die(__('Verifica di sicurezza fallita.', 'mm-preventivi'));
        }

        // Verifica autenticazione
        if (!MM_Auth::is_logged_in()) {
            wp_die(__('Devi essere autenticato.', 'mm-preventivi'));
        }

        // Recupera filtri
        $filters = array();
        if (isset($_GET['stato']) && !empty($_GET['stato'])) {
            $filters['stato'] = sanitize_text_field($_GET['stato']);
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        if (isset($_GET['collaboratore_id']) && !empty($_GET['collaboratore_id'])) {
            $filters['collaboratore_id'] = intval($_GET['collaboratore_id']);
        }
        if (isset($_GET['mostra_passati']) && $_GET['mostra_passati'] == '1') {
            $filters['mostra_passati'] = true;
        }

        // Nessuna paginazione per PDF - ottieni tutti i risultati
        $filters['per_page'] = 9999;

        // Ottieni dati
        $preventivi = MM_Database::get_preventivi_con_assegnazioni($filters);

        // Informazioni collaboratore se filtrato
        $collaboratore_info = null;
        if (!empty($filters['collaboratore_id'])) {
            $collaboratore_info = MM_Database::get_collaboratore($filters['collaboratore_id']);
        }

        // Genera PDF
        $this->generate_assegnazioni_pdf($preventivi, $collaboratore_info, $filters);
        exit;
    }

    /**
     * Genera PDF delle assegnazioni
     */
    private function generate_assegnazioni_pdf($preventivi, $collaboratore_info = null, $filters = array()) {
        $company_name = get_option('mm_preventivi_company_name', 'Wedding in Salento');

        // Titolo
        $title = 'Lista Assegnazioni';
        if ($collaboratore_info) {
            $title = 'Assegnazioni - ' . $collaboratore_info['nome'] . ' ' . $collaboratore_info['cognome'];
        }

        // Genera HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($title) . '</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 15mm;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e91e63;
        }
        .header h1 {
            color: #e91e63;
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .header .subtitle {
            color: #666;
            font-size: 12px;
        }
        .header .company {
            color: #9c27b0;
            font-weight: bold;
            margin-top: 5px;
        }
        .filters-info {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 10px;
            color: #666;
        }
        .evento {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .evento-header {
            background: linear-gradient(135deg, #fce4ec 0%, #f3e5f5 100%);
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .evento-data {
            font-size: 14px;
            font-weight: bold;
            color: #e91e63;
        }
        .evento-tipo {
            font-size: 10px;
            color: #9c27b0;
            margin-left: 10px;
        }
        .evento-cliente {
            font-size: 13px;
            color: #333;
            margin-top: 5px;
        }
        .evento-location {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
        .evento-body {
            padding: 10px 15px;
        }
        .collaboratore {
            display: inline-block;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 3px 5px 3px 0;
            font-size: 10px;
        }
        .collaboratore-nome {
            font-weight: bold;
            color: #333;
        }
        .collaboratore-ruolo {
            color: #9c27b0;
            font-style: italic;
        }
        .no-collaboratori {
            color: #f44336;
            font-style: italic;
            font-size: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            font-size: 9px;
            color: #999;
        }
        .summary {
            background: #e8f5e9;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .summary-title {
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        .past-event {
            opacity: 0.6;
        }
        .past-event .evento-header {
            background: #f5f5f5;
        }
    </style>
</head>
<body>';

        // Header
        $html .= '<div class="header">';
        $html .= '<h1>' . esc_html($title) . '</h1>';
        $html .= '<div class="subtitle">Generato il ' . date('d/m/Y H:i') . '</div>';
        $html .= '<div class="company">' . esc_html($company_name) . '</div>';
        $html .= '</div>';

        // Info filtri applicati
        $filter_texts = array();
        if (!empty($filters['stato'])) {
            $filter_texts[] = 'Stato: ' . ucfirst($filters['stato']);
        }
        if (!empty($filters['search'])) {
            $filter_texts[] = 'Ricerca: ' . $filters['search'];
        }
        if (!empty($filters['mostra_passati'])) {
            $filter_texts[] = 'Include eventi passati';
        }
        if (!empty($filter_texts)) {
            $html .= '<div class="filters-info">Filtri applicati: ' . implode(' | ', $filter_texts) . '</div>';
        }

        // Riepilogo
        $total_eventi = count($preventivi);
        $total_assegnazioni = 0;
        foreach ($preventivi as $p) {
            $total_assegnazioni += intval($p['num_collaboratori']);
        }
        $html .= '<div class="summary">';
        $html .= '<div class="summary-title">Riepilogo</div>';
        $html .= 'Totale eventi: <strong>' . $total_eventi . '</strong> | ';
        $html .= 'Totale assegnazioni: <strong>' . $total_assegnazioni . '</strong>';
        $html .= '</div>';

        // Lista eventi
        if (!empty($preventivi)) {
            foreach ($preventivi as $preventivo) {
                $data_evento = strtotime($preventivo['data_evento']);
                $is_past = $data_evento < strtotime('today');

                $html .= '<div class="evento' . ($is_past ? ' past-event' : '') . '">';
                $html .= '<div class="evento-header">';
                $html .= '<span class="evento-data">' . date('d/m/Y', $data_evento) . '</span>';
                if (!empty($preventivo['tipo_evento'])) {
                    $html .= '<span class="evento-tipo">(' . esc_html($preventivo['tipo_evento']) . ')</span>';
                }
                $html .= '<div class="evento-cliente">' . esc_html($preventivo['sposi']) . '</div>';
                if (!empty($preventivo['location'])) {
                    $html .= '<div class="evento-location">📍 ' . esc_html($preventivo['location']) . '</div>';
                }
                $html .= '</div>';

                $html .= '<div class="evento-body">';
                if (!empty($preventivo['collaboratori_assegnati'])) {
                    // Carica assegnazioni complete
                    $assegnazioni = MM_Database::get_assegnazioni_preventivo($preventivo['id']);
                    foreach ($assegnazioni as $ass) {
                        $html .= '<span class="collaboratore">';
                        $html .= '<span class="collaboratore-nome">' . esc_html($ass['nome'] . ' ' . $ass['cognome']) . '</span>';
                        $html .= ' (' . esc_html($ass['mansione']) . ')';
                        if (!empty($ass['ruolo_evento'])) {
                            $html .= ' - <span class="collaboratore-ruolo">' . esc_html($ass['ruolo_evento']) . '</span>';
                        }
                        $html .= '</span>';
                    }
                } else {
                    $html .= '<span class="no-collaboratori">⚠️ Nessun collaboratore assegnato</span>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $html .= '<p style="text-align: center; color: #666;">Nessun evento trovato con i filtri applicati.</p>';
        }

        // Footer
        $html .= '<div class="footer">';
        $html .= 'Documento generato automaticamente da ' . esc_html($company_name);
        $html .= '</div>';

        $html .= '</body></html>';

        // Genera PDF con dompdf
        $dompdf_autoload = MM_PREVENTIVI_PLUGIN_DIR . 'includes/dompdf/autoload.php';
        if (file_exists($dompdf_autoload)) {
            require_once $dompdf_autoload;

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Nome file
            $filename = 'Assegnazioni';
            if ($collaboratore_info) {
                $filename .= '_' . sanitize_file_name($collaboratore_info['cognome'] . '_' . $collaboratore_info['nome']);
            }
            $filename .= '_' . date('Y-m-d') . '.pdf';

            // Output
            $dompdf->stream($filename, array('Attachment' => false));
        } else {
            // Fallback: mostra HTML
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
        }
    }
}

// Inizializza
new MM_Frontend();
