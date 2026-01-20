<?php
/**
 * Gestione Sicurezza
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Security {
    
    /**
     * Verifica nonce
     */
    public static function verify_nonce($nonce, $action = 'mm_preventivi_nonce') {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Verifica permessi amministratore
     */
    public static function check_admin_permission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'mm-preventivi'));
        }
    }
    
    /**
     * Valida dati preventivo con controlli avanzati
     */
    public static function validate_preventivo_data($data) {
        $errors = array();

        // Campi obbligatori
        $required_fields = array(
            'data_preventivo' => 'Data preventivo',
            'sposi' => 'Nome sposi/cliente',
            'data_evento' => 'Data evento',
            'totale_servizi' => 'Totale servizi',
            'totale' => 'Totale'
        );

        foreach ($required_fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('Il campo %s è obbligatorio.', 'mm-preventivi'), $label);
            }
        }

        // Valida date
        if (!empty($data['data_preventivo']) && !self::validate_date($data['data_preventivo'])) {
            $errors[] = __('Data preventivo non valida.', 'mm-preventivi');
        }

        if (!empty($data['data_evento']) && !self::validate_date($data['data_evento'])) {
            $errors[] = __('Data evento non valida.', 'mm-preventivi');
        }

        if (!empty($data['data_acconto']) && !self::validate_date($data['data_acconto'])) {
            $errors[] = __('Data acconto non valida.', 'mm-preventivi');
        }

        // Valida che la data evento sia futura (almeno oggi)
        if (!empty($data['data_evento'])) {
            $data_evento = strtotime($data['data_evento']);
            $oggi = strtotime(date('Y-m-d'));
            if ($data_evento < $oggi) {
                $errors[] = __('La data evento non può essere nel passato.', 'mm-preventivi');
            }
        }

        // Valida email
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = __('Email non valida.', 'mm-preventivi');
        }

        // Valida lunghezza stringhe
        $string_limits = array(
            'sposi' => 255,
            'email' => 100,
            'telefono' => 50,
            'location' => 255,
            'tipo_evento' => 50,
            'note' => 5000
        );

        foreach ($string_limits as $field => $max_length) {
            if (!empty($data[$field]) && mb_strlen($data[$field]) > $max_length) {
                $errors[] = sprintf(__('Il campo %s supera la lunghezza massima di %d caratteri.', 'mm-preventivi'), $field, $max_length);
            }
        }

        // Valida numeri con range
        $numeric_fields = array(
            'totale_servizi' => array('min' => 0, 'max' => 999999.99),
            'enpals_committente' => array('min' => 0, 'max' => 999999.99),
            'enpals_lavoratore' => array('min' => 0, 'max' => 999999.99),
            'iva' => array('min' => 0, 'max' => 999999.99),
            'totale' => array('min' => 0, 'max' => 999999.99)
        );

        foreach ($numeric_fields as $field => $limits) {
            if (!empty($data[$field])) {
                if (!is_numeric($data[$field])) {
                    $errors[] = sprintf(__('Il campo %s deve essere un numero.', 'mm-preventivi'), $field);
                } else {
                    $value = floatval($data[$field]);
                    if ($value < $limits['min'] || $value > $limits['max']) {
                        $errors[] = sprintf(__('Il campo %s deve essere tra %s e %s.', 'mm-preventivi'), $field, number_format($limits['min'], 2), number_format($limits['max'], 2));
                    }
                }
            }
        }

        // Valida numeri opzionali con range
        $optional_numeric_fields = array(
            'importo_acconto' => array('min' => 0, 'max' => 999999.99),
            'sconto' => array('min' => 0, 'max' => 999999.99),
            'sconto_percentuale' => array('min' => 0, 'max' => 100)
        );

        foreach ($optional_numeric_fields as $field => $limits) {
            if (isset($data[$field]) && !empty($data[$field])) {
                if (!is_numeric($data[$field])) {
                    $errors[] = sprintf(__('Il campo %s deve essere un numero.', 'mm-preventivi'), $field);
                } else {
                    $value = floatval($data[$field]);
                    if ($value < $limits['min'] || $value > $limits['max']) {
                        $errors[] = sprintf(__('Il campo %s deve essere tra %s e %s.', 'mm-preventivi'), $field, $limits['min'], $limits['max']);
                    }
                }
            }
        }

        // Valida telefono (formato internazionale opzionale)
        if (!empty($data['telefono'])) {
            $telefono = preg_replace('/[^0-9+\s\-\(\)]/', '', $data['telefono']);
            if (strlen($telefono) < 8 || strlen($telefono) > 20) {
                $errors[] = __('Il numero di telefono non è valido.', 'mm-preventivi');
            }
        }

        // Valida servizi
        if (isset($data['servizi']) && !is_array($data['servizi'])) {
            $errors[] = __('Formato servizi non valido.', 'mm-preventivi');
        } elseif (isset($data['servizi']) && is_array($data['servizi'])) {
            // Valida ogni servizio
            foreach ($data['servizi'] as $index => $servizio) {
                if (empty($servizio['nome'])) {
                    $errors[] = sprintf(__('Il nome del servizio #%d è obbligatorio.', 'mm-preventivi'), $index + 1);
                }
                if (!isset($servizio['prezzo']) || !is_numeric($servizio['prezzo'])) {
                    $errors[] = sprintf(__('Il prezzo del servizio #%d deve essere un numero.', 'mm-preventivi'), $index + 1);
                } elseif (floatval($servizio['prezzo']) < 0 || floatval($servizio['prezzo']) > 999999.99) {
                    $errors[] = sprintf(__('Il prezzo del servizio #%d deve essere tra 0 e 999999.99.', 'mm-preventivi'), $index + 1);
                }
            }

            // Limita numero massimo di servizi
            if (count($data['servizi']) > 100) {
                $errors[] = __('Numero massimo di servizi superato (max 100).', 'mm-preventivi');
            }
        }

        // Valida coerenza tra sconto fisso e percentuale
        if (!empty($data['sconto']) && !empty($data['sconto_percentuale'])) {
            $errors[] = __('Non è possibile applicare sia uno sconto fisso che percentuale contemporaneamente.', 'mm-preventivi');
        }

        // Valida che l\'acconto non superi il totale
        if (!empty($data['importo_acconto']) && !empty($data['totale'])) {
            if (floatval($data['importo_acconto']) > floatval($data['totale'])) {
                $errors[] = __('L\'importo dell\'acconto non può superare il totale.', 'mm-preventivi');
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }
    
    /**
     * Sanitizza dati preventivo
     */
    public static function sanitize_preventivo_data($data) {
        $sanitized = array();

        // ID categoria (integer nullable)
        $sanitized['categoria_id'] = isset($data['categoria_id']) && !empty($data['categoria_id']) ? intval($data['categoria_id']) : null;

        // Stato (string)
        $sanitized['stato'] = isset($data['stato']) ? sanitize_text_field($data['stato']) : 'attivo';

        // Testo semplice
        $text_fields = array('sposi', 'location', 'tipo_evento', 'telefono');
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }

        // Email
        $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';

        // Date
        $date_fields = array('data_preventivo', 'data_evento', 'data_acconto');
        foreach ($date_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }

        // Textarea
        $sanitized['note'] = isset($data['note']) ? sanitize_textarea_field($data['note']) : '';

        // Numeri
        $numeric_fields = array('totale_servizi', 'enpals_committente', 'enpals_lavoratore', 'iva', 'totale', 'importo_acconto', 'sconto', 'sconto_percentuale');
        foreach ($numeric_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? floatval($data[$field]) : 0;
        }

        // Boolean fields - gestione unificata e robusta
        $sanitized['applica_enpals'] = self::sanitize_boolean($data['applica_enpals'] ?? true);
        $sanitized['applica_iva'] = self::sanitize_boolean($data['applica_iva'] ?? true);
        
        // Array
        if (isset($data['cerimonia']) && is_array($data['cerimonia'])) {
            $sanitized['cerimonia'] = array_map('sanitize_text_field', $data['cerimonia']);
        }
        
        if (isset($data['servizi_extra']) && is_array($data['servizi_extra'])) {
            $sanitized['servizi_extra'] = array_map('sanitize_text_field', $data['servizi_extra']);
        }
        
        // Servizi
        if (isset($data['servizi']) && is_array($data['servizi'])) {
            $sanitized['servizi'] = array();
            foreach ($data['servizi'] as $servizio) {
                // Supporta sia 'nome' che 'nome_servizio' per retrocompatibilità
                $nome_servizio = isset($servizio['nome_servizio']) ? $servizio['nome_servizio'] : (isset($servizio['nome']) ? $servizio['nome'] : '');
                $sanitized['servizi'][] = array(
                    'nome_servizio' => sanitize_text_field($nome_servizio),
                    'nome' => sanitize_text_field($nome_servizio), // Per retrocompatibilità
                    'prezzo' => floatval($servizio['prezzo']),
                    'sconto' => isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0
                );
            }
        }

        // Acconti multipli
        if (isset($data['acconti']) && is_array($data['acconti'])) {
            $sanitized['acconti'] = array();
            foreach ($data['acconti'] as $acconto) {
                if (!empty($acconto['data_acconto']) && !empty($acconto['importo_acconto'])) {
                    $sanitized['acconti'][] = array(
                        'data_acconto' => sanitize_text_field($acconto['data_acconto']),
                        'importo_acconto' => floatval($acconto['importo_acconto'])
                    );
                }
            }
        }

        return $sanitized;
    }
    
    /**
     * Valida data
     */
    private static function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Previeni SQL Injection
     */
    public static function escape_sql($value) {
        global $wpdb;
        return $wpdb->_real_escape($value);
    }
    
    /**
     * Previeni XSS
     */
    public static function escape_output($text) {
        return esc_html($text);
    }
    
    /**
     * Previeni XSS in attributi
     */
    public static function escape_attr($text) {
        return esc_attr($text);
    }
    
    /**
     * Previeni XSS in URL
     */
    public static function escape_url($url) {
        return esc_url($url);
    }
    
    /**
     * Sanitizza filename
     */
    public static function sanitize_filename($filename) {
        return sanitize_file_name($filename);
    }
    
    /**
     * Rate limiting per prevenire spam
     */
    public static function check_rate_limit($identifier, $max_requests = 10, $time_window = 3600) {
        $transient_key = 'mm_rate_limit_' . md5($identifier);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            set_transient($transient_key, 1, $time_window);
            return true;
        }
        
        if ($requests >= $max_requests) {
            return false;
        }
        
        set_transient($transient_key, $requests + 1, $time_window);
        return true;
    }
    
    /**
     * Genera token sicuro
     */
    public static function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Log attività per sicurezza (solo se debug attivo)
     */
    public static function log_security_event($event, $details = array()) {
        // Log solo in modalità debug
        if (!WP_DEBUG && !defined('MM_PREVENTIVI_DEBUG')) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_ip' => self::get_client_ip(),
            'event' => $event,
            'details' => $details
        );

        // Usa error_log per sviluppo, ma permette hook personalizzati
        do_action('mm_preventivi_security_log', $log_entry);

        error_log('[MM_PREVENTIVI_SECURITY] ' . wp_json_encode($log_entry, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Ottieni IP cliente in modo sicuro
     * Previene IP spoofing verificando header affidabili
     */
    public static function get_client_ip() {
        $ip = '';

        // Lista di header da verificare in ordine di priorità
        // Solo REMOTE_ADDR è affidabile per default
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        // Se il sito usa un proxy/load balancer affidabile (es. Cloudflare, AWS),
        // può essere necessario abilitare questa opzione nelle impostazioni
        $use_proxy_headers = get_option('mm_preventivi_trust_proxy', false);

        if ($use_proxy_headers) {
            // Verifica X-Forwarded-For solo se esplicitamente abilitato
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                // Prendi solo il primo IP (client originale)
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($ips[0]);
            } elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                // Cloudflare
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }

        // Valida e sanitizza IP
        $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        // Fallback a REMOTE_ADDR se la validazione fallisce
        if (!$ip && isset($_SERVER['REMOTE_ADDR'])) {
            $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        }

        return $ip ? $ip : '0.0.0.0';
    }
    
    /**
     * Verifica CSRF token
     */
    public static function verify_csrf_token($token, $action = 'mm_preventivi_csrf') {
        $stored_token = get_transient($action . '_' . get_current_user_id());
        
        if ($stored_token === false || $token !== $stored_token) {
            return false;
        }
        
        delete_transient($action . '_' . get_current_user_id());
        return true;
    }
    
    /**
     * Genera CSRF token
     */
    public static function generate_csrf_token($action = 'mm_preventivi_csrf') {
        $token = self::generate_secure_token();
        set_transient($action . '_' . get_current_user_id(), $token, 3600);
        return $token;
    }
    
    /**
     * Sanitizza valore boolean in modo consistente
     */
    public static function sanitize_boolean($value) {
        // Gestisce tutti i possibili formati di boolean
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, array('1', 'true', 'yes', 'on'), true);
        }

        // Default false per valori null o non riconosciuti
        return false;
    }

    /**
     * Sanitizza input ricorsivamente
     */
    public static function sanitize_recursive($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'sanitize_recursive'), $data);
        }

        return sanitize_text_field($data);
    }

    /**
     * Valida permessi upload file
     */
    public static function validate_file_upload($file) {
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'application/pdf');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!isset($file['error']) || is_array($file['error'])) {
            return new WP_Error('invalid_file', __('Errore nel caricamento del file.', 'mm-preventivi'));
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Errore durante l\'upload.', 'mm-preventivi'));
        }
        
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('Il file è troppo grande. Massimo 5MB.', 'mm-preventivi'));
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $allowed_types)) {
            return new WP_Error('invalid_type', __('Tipo di file non consentito.', 'mm-preventivi'));
        }
        
        return true;
    }
}
