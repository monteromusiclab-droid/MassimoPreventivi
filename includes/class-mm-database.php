<?php
/**
 * Gestione Database
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Database {
    
    /**
     * Nome tabella preventivi
     */
    private static $table_preventivi = 'mm_preventivi';
    
    /**
     * Nome tabella servizi
     */
    private static $table_servizi = 'mm_preventivi_servizi';

    /**
     * Nome tabella catalogo servizi
     */
    private static $table_catalogo_servizi = 'mm_catalogo_servizi';

    /**
     * Nome tabella tipi evento
     */
    private static $table_tipi_evento = 'mm_tipi_evento';

    /**
     * Nome tabella acconti
     */
    private static $table_acconti = 'mm_preventivi_acconti';

    /**
     * Crea tabelle database
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;
        $table_tipi_evento = $wpdb->prefix . self::$table_tipi_evento;
        $table_acconti = $wpdb->prefix . self::$table_acconti;

        // Tabella tipi evento
        $sql_tipi_evento = "CREATE TABLE IF NOT EXISTS $table_tipi_evento (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            icona varchar(50) DEFAULT '🎉',
            ordinamento int DEFAULT 0,
            attivo tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nome (nome),
            KEY attivo (attivo)
        ) $charset_collate;";

        // Tabella preventivi
        $sql_preventivi = "CREATE TABLE IF NOT EXISTS $table_preventivi (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_preventivo varchar(50) NOT NULL,
            categoria_id bigint(20) UNSIGNED DEFAULT NULL,
            data_preventivo date NOT NULL,
            sposi varchar(255) NOT NULL,
            email varchar(100) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            data_evento date NOT NULL,
            location varchar(255) DEFAULT NULL,
            tipo_evento varchar(50) DEFAULT NULL,
            cerimonia text DEFAULT NULL,
            servizi_extra text DEFAULT NULL,
            note text DEFAULT NULL,
            totale_servizi decimal(10,2) NOT NULL DEFAULT 0,
            sconto decimal(10,2) DEFAULT 0,
            sconto_percentuale decimal(5,2) DEFAULT 0,
            applica_enpals tinyint(1) DEFAULT 1,
            applica_iva tinyint(1) DEFAULT 1,
            enpals_committente decimal(10,2) NOT NULL DEFAULT 0,
            enpals_lavoratore decimal(10,2) NOT NULL DEFAULT 0,
            iva decimal(10,2) NOT NULL DEFAULT 0,
            totale decimal(10,2) NOT NULL DEFAULT 0,
            data_acconto date DEFAULT NULL,
            importo_acconto decimal(10,2) DEFAULT NULL,
            stato varchar(50) DEFAULT 'bozza',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY numero_preventivo (numero_preventivo),
            KEY categoria_id (categoria_id),
            KEY data_evento (data_evento),
            KEY stato (stato),
            KEY created_by (created_by),
            CONSTRAINT fk_categoria FOREIGN KEY (categoria_id)
                REFERENCES $table_tipi_evento(id) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Tabella servizi
        $sql_servizi = "CREATE TABLE IF NOT EXISTS $table_servizi (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            preventivo_id bigint(20) UNSIGNED NOT NULL,
            nome_servizio varchar(255) NOT NULL,
            prezzo decimal(10,2) NOT NULL DEFAULT 0,
            sconto decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY preventivo_id (preventivo_id),
            CONSTRAINT fk_preventivo FOREIGN KEY (preventivo_id)
                REFERENCES $table_preventivi(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabella catalogo servizi
        $sql_catalogo = "CREATE TABLE IF NOT EXISTS $table_catalogo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nome_servizio varchar(255) NOT NULL,
            descrizione text DEFAULT NULL,
            prezzo_default decimal(10,2) DEFAULT 0,
            categoria varchar(100) DEFAULT NULL,
            attivo tinyint(1) DEFAULT 1,
            ordinamento int DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attivo (attivo),
            KEY categoria (categoria)
        ) $charset_collate;";

        // Tabella acconti
        $sql_acconti = "CREATE TABLE IF NOT EXISTS $table_acconti (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            preventivo_id bigint(20) UNSIGNED NOT NULL,
            data_acconto date NOT NULL,
            importo_acconto decimal(10,2) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY preventivo_id (preventivo_id),
            CONSTRAINT fk_preventivo_acconto FOREIGN KEY (preventivo_id)
                REFERENCES $table_preventivi(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tipi_evento);
        dbDelta($sql_preventivi);
        dbDelta($sql_servizi);
        dbDelta($sql_catalogo);
        dbDelta($sql_acconti);

        // Ottimizzazione: usa una singola query per verificare tutte le colonne
        $existing_columns_servizi = $wpdb->get_col("SHOW COLUMNS FROM $table_servizi");
        $existing_columns_preventivi = $wpdb->get_col("SHOW COLUMNS FROM $table_preventivi");

        // Array di migrazioni da eseguire
        $migrations = array();

        // Migrazioni tabella servizi
        if (!in_array('sconto', $existing_columns_servizi)) {
            $migrations[] = "ALTER TABLE $table_servizi ADD COLUMN sconto decimal(10,2) DEFAULT 0 AFTER prezzo";
        }

        // Migrazioni tabella preventivi
        if (!in_array('sconto', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN sconto decimal(10,2) DEFAULT 0 AFTER totale_servizi";
        }

        if (!in_array('sconto_percentuale', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN sconto_percentuale decimal(5,2) DEFAULT 0 AFTER sconto";
        }

        if (!in_array('applica_enpals', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN applica_enpals tinyint(1) DEFAULT 1 AFTER sconto_percentuale";
        }

        if (!in_array('applica_iva', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN applica_iva tinyint(1) DEFAULT 1 AFTER applica_enpals";
        }

        if (!in_array('categoria_id', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN categoria_id bigint(20) UNSIGNED DEFAULT NULL AFTER numero_preventivo";
            $migrations[] = "ALTER TABLE $table_preventivi ADD KEY categoria_id (categoria_id)";
        }

        // Migrazione split ENPALS (v1.2.0)
        if (!in_array('enpals_committente', $existing_columns_preventivi)) {
            // Se esiste la vecchia colonna 'enpals', copiala in enpals_committente e poi eliminala
            if (in_array('enpals', $existing_columns_preventivi)) {
                $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN enpals_committente decimal(10,2) NOT NULL DEFAULT 0 AFTER applica_iva";
                $migrations[] = "UPDATE $table_preventivi SET enpals_committente = enpals WHERE enpals IS NOT NULL";
                $migrations[] = "ALTER TABLE $table_preventivi DROP COLUMN enpals";
            } else {
                $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN enpals_committente decimal(10,2) NOT NULL DEFAULT 0 AFTER applica_iva";
            }
        }

        if (!in_array('enpals_lavoratore', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN enpals_lavoratore decimal(10,2) NOT NULL DEFAULT 0 AFTER enpals_committente";
        }

        // Migrazione public token per condivisione pubblica (v1.2.1)
        if (!in_array('public_token', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN public_token varchar(64) DEFAULT NULL AFTER stato";
            error_log('MM Preventivi - Aggiunta colonna public_token');
        }

        if (!in_array('token_expires', $existing_columns_preventivi)) {
            $migrations[] = "ALTER TABLE $table_preventivi ADD COLUMN token_expires datetime DEFAULT NULL AFTER public_token";
            error_log('MM Preventivi - Aggiunta colonna token_expires');
        }

        // Esegui tutte le migrazioni in modo sicuro
        foreach ($migrations as $migration_query) {
            $result = $wpdb->query($migration_query);
            if ($result === false) {
                error_log('MM Preventivi - Errore migrazione: ' . $wpdb->last_error);
            }
        }

        // Inserisci tipi evento predefiniti se la tabella è vuota
        $count_tipi_evento = $wpdb->get_var("SELECT COUNT(*) FROM $table_tipi_evento");
        if ($count_tipi_evento == 0) {
            $tipi_evento_default = array(
                array('nome' => 'Matrimonio', 'icona' => '💒', 'ordinamento' => 1),
                array('nome' => 'Compleanno', 'icona' => '🎂', 'ordinamento' => 2),
                array('nome' => 'Laurea', 'icona' => '🎓', 'ordinamento' => 3),
                array('nome' => 'Battesimo', 'icona' => '👶', 'ordinamento' => 4),
                array('nome' => 'Comunione', 'icona' => '🕊️', 'ordinamento' => 5),
                array('nome' => 'Cresima', 'icona' => '✝️', 'ordinamento' => 6),
                array('nome' => 'Anniversario', 'icona' => '💍', 'ordinamento' => 7),
                array('nome' => 'Festa Aziendale', 'icona' => '💼', 'ordinamento' => 8),
                array('nome' => 'Evento Sportivo', 'icona' => '⚽', 'ordinamento' => 9),
                array('nome' => 'Festa Privata', 'icona' => '🎉', 'ordinamento' => 10),
                array('nome' => 'Altro', 'icona' => '🎪', 'ordinamento' => 99)
            );

            foreach ($tipi_evento_default as $tipo) {
                $wpdb->insert($table_tipi_evento, array(
                    'nome' => $tipo['nome'],
                    'icona' => $tipo['icona'],
                    'ordinamento' => $tipo['ordinamento'],
                    'attivo' => 1
                ));
            }
        }

        // Salva versione database
        update_option('mm_preventivi_db_version', MM_PREVENTIVI_VERSION);
    }

    /**
     * Esegue solo le migrazioni necessarie (può essere chiamato ad ogni init)
     */
    public static function run_migrations() {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        // Verifica se la tabella esiste
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_preventivi'");
        if (!$table_exists) {
            return; // Tabella non esiste ancora, sarà creata all'attivazione
        }

        // Ottieni colonne esistenti
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_preventivi");

        // Migrazione public token per condivisione pubblica (v1.2.1)
        if (!in_array('public_token', $existing_columns)) {
            $result = $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN public_token varchar(64) DEFAULT NULL AFTER stato");
            if ($result !== false) {
                error_log('MM Preventivi - Migrazione: aggiunta colonna public_token');
            } else {
                error_log('MM Preventivi - Errore migrazione public_token: ' . $wpdb->last_error);
            }
        }

        if (!in_array('token_expires', $existing_columns)) {
            $result = $wpdb->query("ALTER TABLE $table_preventivi ADD COLUMN token_expires datetime DEFAULT NULL AFTER public_token");
            if ($result !== false) {
                error_log('MM Preventivi - Migrazione: aggiunta colonna token_expires');
            } else {
                error_log('MM Preventivi - Errore migrazione token_expires: ' . $wpdb->last_error);
            }
        }
    }

    /**
     * Salva preventivo con transazione atomica
     */
    public static function save_preventivo($data) {
        global $wpdb;

        // Validazione dati
        $validation = MM_Security::validate_preventivo_data($data);
        if ($validation !== true) {
            // Log per debug
            error_log('MM Preventivi - Validazione fallita: ' . print_r($validation, true));
            $error_message = is_array($validation) ? implode(' ', $validation) : __('Dati non validi. Controlla i campi obbligatori.', 'mm-preventivi');
            return new WP_Error('invalid_data', $error_message);
        }

        // Sanitizzazione
        $data = MM_Security::sanitize_preventivo_data($data);

        // DEBUG: Log categoria_id dopo sanitizzazione
        error_log('MM Preventivi - save_preventivo categoria_id AFTER sanitize: ' . print_r($data['categoria_id'] ?? 'NOT SET', true));

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;

        // Inizio transazione per operazione atomica
        $wpdb->query('START TRANSACTION');

        try {
            // Genera numero preventivo
            $numero_preventivo = self::generate_numero_preventivo();

            // Prepara dati preventivo
            $preventivo_data = array(
                'numero_preventivo' => $numero_preventivo,
                'categoria_id' => isset($data['categoria_id']) && !empty($data['categoria_id']) ? intval($data['categoria_id']) : null,
                'data_preventivo' => $data['data_preventivo'],
                'sposi' => $data['sposi'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'data_evento' => $data['data_evento'],
                'location' => $data['location'],
                'tipo_evento' => $data['tipo_evento'],
                'cerimonia' => isset($data['cerimonia']) ? json_encode($data['cerimonia']) : null,
                'servizi_extra' => isset($data['servizi_extra']) ? json_encode($data['servizi_extra']) : null,
                'note' => $data['note'],
                'totale_servizi' => $data['totale_servizi'],
                'sconto' => isset($data['sconto']) ? $data['sconto'] : 0,
                'sconto_percentuale' => isset($data['sconto_percentuale']) ? $data['sconto_percentuale'] : 0,
                'applica_enpals' => isset($data['applica_enpals']) ? (empty($data['applica_enpals']) ? 0 : 1) : 1,
                'applica_iva' => isset($data['applica_iva']) ? (empty($data['applica_iva']) ? 0 : 1) : 1,
                'enpals_committente' => isset($data['enpals_committente']) ? $data['enpals_committente'] : 0,
                'enpals_lavoratore' => isset($data['enpals_lavoratore']) ? $data['enpals_lavoratore'] : 0,
                'iva' => $data['iva'],
                'totale' => $data['totale'],
                'data_acconto' => $data['data_acconto'],
                'importo_acconto' => $data['importo_acconto'],
                'stato' => isset($data['stato']) ? $data['stato'] : 'attivo',
                'created_by' => get_current_user_id()
            );

            // DEBUG: Log array prima dell'inserimento
            error_log('MM Preventivi - preventivo_data array categoria_id: ' . print_r($preventivo_data['categoria_id'] ?? 'NOT IN ARRAY', true));
            error_log('MM Preventivi - preventivo_data array stato: ' . print_r($preventivo_data['stato'] ?? 'NOT IN ARRAY', true));

            // Inserisci preventivo
            $result = $wpdb->insert(
                $table_preventivi,
                $preventivo_data,
                array(
                    '%s',  // numero_preventivo
                    '%d',  // categoria_id
                    '%s',  // data_preventivo
                    '%s',  // sposi
                    '%s',  // email
                    '%s',  // telefono
                    '%s',  // data_evento
                    '%s',  // location
                    '%s',  // tipo_evento
                    '%s',  // cerimonia
                    '%s',  // servizi_extra
                    '%s',  // note
                    '%f',  // totale_servizi
                    '%f',  // sconto
                    '%f',  // sconto_percentuale
                    '%d',  // applica_enpals
                    '%d',  // applica_iva
                    '%f',  // enpals_committente
                    '%f',  // enpals_lavoratore
                    '%f',  // iva
                    '%f',  // totale
                    '%s',  // data_acconto
                    '%f',  // importo_acconto
                    '%s',  // stato
                    '%d'   // created_by
                )
            );

            if ($result === false) {
                throw new Exception(sprintf(
                    __('Errore database nel salvataggio del preventivo: %s', 'mm-preventivi'),
                    $wpdb->last_error
                ));
            }

            $preventivo_id = $wpdb->insert_id;

            if (!$preventivo_id) {
                throw new Exception(__('Errore nel salvataggio del preventivo: ID non generato', 'mm-preventivi'));
            }

            // Inserisci servizi
            if (isset($data['servizi']) && is_array($data['servizi'])) {
                foreach ($data['servizi'] as $servizio) {
                    // Supporta sia 'nome' che 'nome_servizio' per retrocompatibilità
                    $nome_servizio = isset($servizio['nome_servizio']) ? $servizio['nome_servizio'] : (isset($servizio['nome']) ? $servizio['nome'] : '');

                    // Salta servizi senza nome
                    if (empty($nome_servizio)) {
                        continue;
                    }

                    $servizio_result = $wpdb->insert(
                        $table_servizi,
                        array(
                            'preventivo_id' => $preventivo_id,
                            'nome_servizio' => $nome_servizio,
                            'prezzo' => $servizio['prezzo'],
                            'sconto' => isset($servizio['sconto']) ? $servizio['sconto'] : 0
                        ),
                        array('%d', '%s', '%f', '%f')
                    );

                    if ($servizio_result === false) {
                        throw new Exception(sprintf(
                            __('Errore nell\'inserimento del servizio: %s', 'mm-preventivi'),
                            $wpdb->last_error
                        ));
                    }
                }
            }

            // Inserisci acconti multipli
            $table_acconti = $wpdb->prefix . self::$table_acconti;
            if (isset($data['acconti']) && is_array($data['acconti'])) {
                foreach ($data['acconti'] as $acconto) {
                    if (!empty($acconto['data_acconto']) && !empty($acconto['importo_acconto'])) {
                        $acconto_result = $wpdb->insert(
                            $table_acconti,
                            array(
                                'preventivo_id' => $preventivo_id,
                                'data_acconto' => $acconto['data_acconto'],
                                'importo_acconto' => floatval($acconto['importo_acconto'])
                            ),
                            array('%d', '%s', '%f')
                        );

                        if ($acconto_result === false) {
                            throw new Exception(sprintf(
                                __('Errore nell\'inserimento dell\'acconto: %s', 'mm-preventivi'),
                                $wpdb->last_error
                            ));
                        }
                    }
                }
            }

            // Commit transazione
            $wpdb->query('COMMIT');

            // Invalida cache
            self::clear_preventivi_cache();

            return $preventivo_id;

        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            error_log('MM Preventivi - Errore transazione: ' . $e->getMessage());
            return new WP_Error('db_error', $e->getMessage());
        }
    }
    
    /**
     * Ottieni preventivo con cache
     */
    public static function get_preventivo($id) {
        global $wpdb;

        // Verifica cache
        $cache_key = self::get_cache_key('preventivo', array('id' => $id));
        $cached = self::get_from_cache($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;
        $table_tipi_evento = $wpdb->prefix . self::$table_tipi_evento;

        $preventivo = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, te.nome AS categoria_nome, te.icona AS categoria_icona
             FROM $table_preventivi p
             LEFT JOIN $table_tipi_evento te ON p.categoria_id = te.id
             WHERE p.id = %d",
            $id
        ), ARRAY_A);

        if (!$preventivo) {
            return null;
        }

        // Decodifica JSON
        if ($preventivo['cerimonia']) {
            $preventivo['cerimonia'] = json_decode($preventivo['cerimonia'], true);
        }
        if ($preventivo['servizi_extra']) {
            $preventivo['servizi_extra'] = json_decode($preventivo['servizi_extra'], true);
        }

        // Ottieni servizi
        $servizi = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_servizi WHERE preventivo_id = %d",
            $id
        ), ARRAY_A);

        $preventivo['servizi'] = $servizi;

        // Ottieni acconti
        $table_acconti = $wpdb->prefix . self::$table_acconti;
        $acconti = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_acconti WHERE preventivo_id = %d ORDER BY data_acconto ASC",
            $id
        ), ARRAY_A);

        $preventivo['acconti'] = $acconti;

        // Salva in cache (1 ora)
        self::set_to_cache($cache_key, $preventivo, 3600);

        return $preventivo;
    }
    
    /**
     * Ottieni tutti i preventivi con cache e paginazione
     */
    public static function get_all_preventivi($filters = array()) {
        global $wpdb;

        // Verifica cache solo se non ci sono filtri dinamici
        $cache_enabled = empty($filters['page']) || $filters['page'] == 1;
        if ($cache_enabled && empty($filters['search'])) {
            $cache_key = self::get_cache_key('all_preventivi', $filters);
            $cached = self::get_from_cache($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_tipi_evento = $wpdb->prefix . self::$table_tipi_evento;
        $table_acconti = $wpdb->prefix . self::$table_acconti;

        $where = array('1=1');
        $where_values = array();

        // Filtri
        if (isset($filters['stato']) && !empty($filters['stato'])) {
            $where[] = 'p.stato = %s';
            $where_values[] = $filters['stato'];
        }

        if (isset($filters['categoria_id']) && !empty($filters['categoria_id'])) {
            $where[] = 'p.categoria_id = %d';
            $where_values[] = intval($filters['categoria_id']);
        }

        if (isset($filters['data_da']) && !empty($filters['data_da'])) {
            $where[] = 'p.data_evento >= %s';
            $where_values[] = $filters['data_da'];
        }

        if (isset($filters['data_a']) && !empty($filters['data_a'])) {
            $where[] = 'p.data_evento <= %s';
            $where_values[] = $filters['data_a'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(p.sposi LIKE %s OR p.email LIKE %s OR p.numero_preventivo LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        // Ordinamento
        $order_by = 'p.data_evento DESC'; // Default: ordina per data evento (più recente prima)
        if (isset($filters['order_by'])) {
            switch ($filters['order_by']) {
                case 'data_evento_asc':
                    $order_by = 'p.data_evento ASC';
                    break;
                case 'data_evento_desc':
                    $order_by = 'p.data_evento DESC';
                    break;
                case 'data_preventivo_asc':
                    $order_by = 'p.data_preventivo ASC';
                    break;
                case 'data_preventivo_desc':
                    $order_by = 'p.data_preventivo DESC';
                    break;
                case 'numero_preventivo_asc':
                    $order_by = 'p.numero_preventivo ASC';
                    break;
                case 'numero_preventivo_desc':
                    $order_by = 'p.numero_preventivo DESC';
                    break;
                case 'totale_asc':
                    $order_by = 'p.totale ASC';
                    break;
                case 'totale_desc':
                    $order_by = 'p.totale DESC';
                    break;
                default:
                    $order_by = 'p.data_evento DESC';
            }
        }

        // Paginazione
        $per_page = isset($filters['per_page']) ? intval($filters['per_page']) : 50;
        $page = isset($filters['page']) ? intval($filters['page']) : 1;
        $offset = ($page - 1) * $per_page;

        $query = "SELECT p.*,
                         te.nome as categoria_nome,
                         te.icona as categoria_icona,
                         COALESCE((SELECT SUM(importo_acconto) FROM $table_acconti WHERE preventivo_id = p.id), 0) as totale_acconti
                  FROM $table_preventivi p
                  LEFT JOIN $table_tipi_evento te ON p.categoria_id = te.id
                  WHERE $where_clause
                  ORDER BY $order_by
                  LIMIT %d OFFSET %d";

        // Aggiungi parametri paginazione
        $where_values[] = $per_page;
        $where_values[] = $offset;

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        // Salva in cache se appropriato
        if ($cache_enabled && empty($filters['search'])) {
            self::set_to_cache($cache_key, $results, 1800); // 30 minuti
        }

        return $results;
    }

    /**
     * Conta totale preventivi con filtri
     */
    public static function count_all_preventivi($filters = array()) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        $where = array('1=1');
        $where_values = array();

        // Applica stessi filtri di get_all_preventivi
        if (isset($filters['stato']) && !empty($filters['stato'])) {
            $where[] = 'stato = %s';
            $where_values[] = $filters['stato'];
        }

        if (isset($filters['categoria_id']) && !empty($filters['categoria_id'])) {
            $where[] = 'categoria_id = %d';
            $where_values[] = intval($filters['categoria_id']);
        }

        if (isset($filters['data_da']) && !empty($filters['data_da'])) {
            $where[] = 'data_evento >= %s';
            $where_values[] = $filters['data_da'];
        }

        if (isset($filters['data_a']) && !empty($filters['data_a'])) {
            $where[] = 'data_evento <= %s';
            $where_values[] = $filters['data_a'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(sposi LIKE %s OR email LIKE %s OR numero_preventivo LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table_preventivi WHERE $where_clause";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return intval($wpdb->get_var($query));
    }
    
    /**
     * Elimina preventivo
     */
    public static function delete_preventivo($id) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        // I servizi verranno eliminati automaticamente per CASCADE
        $result = $wpdb->delete(
            $table_preventivi,
            array('id' => $id),
            array('%d')
        );

        if ($result) {
            // Invalida cache
            self::clear_preventivo_cache($id);
        }

        return $result;
    }
    
    /**
     * Aggiorna stato preventivo
     */
    public static function update_stato($id, $stato) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        $stati_validi = array('bozza', 'attivo', 'accettato', 'rifiutato', 'completato');

        if (!in_array($stato, $stati_validi)) {
            return new WP_Error('invalid_stato', __('Stato non valido.', 'mm-preventivi'));
        }

        $result = $wpdb->update(
            $table_preventivi,
            array('stato' => $stato),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nell\'aggiornamento dello stato.', 'mm-preventivi'));
        }

        // Invalida cache
        self::clear_preventivo_cache($id);

        return true;
    }
    
    /**
     * Genera numero preventivo
     */
    private static function generate_numero_preventivo() {
        $anno = date('Y');
        $mese = date('m');
        
        global $wpdb;
        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        
        // Conta preventivi del mese
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_preventivi 
             WHERE numero_preventivo LIKE %s",
            $anno . $mese . '%'
        ));
        
        $progressivo = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return $anno . $mese . $progressivo;
    }
    
    /**
     * Ottieni statistiche con cache
     */
    public static function get_statistics() {
        global $wpdb;

        // Verifica cache
        $cache_key = 'mm_preventivi_stats';
        $cached = self::get_from_cache($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        $stats = array(
            'totale_preventivi' => 0,
            'preventivi_attivi' => 0,
            'preventivi_accettati' => 0,
            'preventivi_rifiutati' => 0,
            'totale_fatturato' => 0,
            'fatturato_mese' => 0,
            'valore_medio_preventivo' => 0,
            'tasso_conversione' => 0
        );

        // Totale preventivi
        $stats['totale_preventivi'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi"
        ));

        // Preventivi attivi
        $stats['preventivi_attivi'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'attivo'"
        ));

        // Preventivi accettati
        $stats['preventivi_accettati'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'accettato'"
        ));

        // Preventivi rifiutati
        $stats['preventivi_rifiutati'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $table_preventivi WHERE stato = 'rifiutato'"
        ));

        // Totale fatturato
        $stats['totale_fatturato'] = floatval($wpdb->get_var(
            "SELECT COALESCE(SUM(totale), 0) FROM $table_preventivi WHERE stato IN ('accettato', 'completato')"
        ));

        // Fatturato mese corrente
        $stats['fatturato_mese'] = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(totale), 0) FROM $table_preventivi
             WHERE stato IN ('accettato', 'completato')
             AND MONTH(data_evento) = %d
             AND YEAR(data_evento) = %d",
            date('m'),
            date('Y')
        )));

        // Valore medio preventivo
        $stats['valore_medio_preventivo'] = floatval($wpdb->get_var(
            "SELECT COALESCE(AVG(totale), 0) FROM $table_preventivi WHERE stato IN ('accettato', 'completato')"
        ));

        // Tasso di conversione (preventivi accettati / totali * 100)
        if ($stats['totale_preventivi'] > 0) {
            $stats['tasso_conversione'] = round(($stats['preventivi_accettati'] / $stats['totale_preventivi']) * 100, 2);
        }

        // Salva in cache (15 minuti)
        self::set_to_cache($cache_key, $stats, 900);

        return $stats;
    }

    /**
     * Ottieni statistiche per tipo evento
     */
    public static function get_statistics_by_event_type() {
        global $wpdb;

        // Verifica cache
        $cache_key = 'mm_preventivi_stats_by_type';
        $cached = self::get_from_cache($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_tipi_evento = $wpdb->prefix . self::$table_tipi_evento;

        // Query per ottenere conteggio per categoria evento
        $results = $wpdb->get_results(
            "SELECT
                COALESCE(te.nome, 'Non specificato') as tipo_evento,
                te.icona as categoria_icona,
                COUNT(*) as count,
                SUM(p.totale) as totale
             FROM $table_preventivi p
             LEFT JOIN $table_tipi_evento te ON p.categoria_id = te.id
             GROUP BY p.categoria_id, te.nome, te.icona
             ORDER BY count DESC",
            ARRAY_A
        );

        // Salva in cache (15 minuti)
        self::set_to_cache($cache_key, $results, 900);

        return $results;
    }

    /**
     * Ottieni attività recenti
     */
    public static function get_recent_activity($limit = 10) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        $query = $wpdb->prepare(
            "SELECT * FROM $table_preventivi
             ORDER BY id DESC
             LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if (!$results) {
            return array();
        }

        return $results;
    }

    /**
     * Aggiorna stato preventivo
     */
    public static function update_preventivo_status($id, $nuovo_stato) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;

        // Verifica che il preventivo esista
        $preventivo = self::get_preventivo($id);
        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Aggiorna lo stato (updated_at si aggiorna automaticamente)
        $result = $wpdb->update(
            $table_preventivi,
            array(
                'stato' => $nuovo_stato
            ),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', __('Errore durante l\'aggiornamento dello stato.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Aggiorna preventivo completo con transazione
     */
    public static function update_preventivo($id, $data) {
        global $wpdb;

        $table_preventivi = $wpdb->prefix . self::$table_preventivi;
        $table_servizi = $wpdb->prefix . self::$table_servizi;

        // Verifica che il preventivo esista
        $preventivo = self::get_preventivo($id);
        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato.', 'mm-preventivi'));
        }

        // Sanitizzazione
        $data = MM_Security::sanitize_preventivo_data($data);

        // Inizio transazione
        $wpdb->query('START TRANSACTION');

        try {
            // Prepara dati preventivo
            $preventivo_data = array(
                'categoria_id' => isset($data['categoria_id']) && !empty($data['categoria_id']) ? intval($data['categoria_id']) : null,
                'data_preventivo' => $data['data_preventivo'],
                'sposi' => $data['sposi'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'data_evento' => $data['data_evento'],
                'location' => $data['location'],
                'tipo_evento' => $data['tipo_evento'],
                'cerimonia' => isset($data['cerimonia']) ? json_encode($data['cerimonia']) : null,
                'servizi_extra' => isset($data['servizi_extra']) ? json_encode($data['servizi_extra']) : null,
                'note' => $data['note'],
                'totale_servizi' => $data['totale_servizi'],
                'sconto' => isset($data['sconto']) ? $data['sconto'] : 0,
                'sconto_percentuale' => isset($data['sconto_percentuale']) ? $data['sconto_percentuale'] : 0,
                'applica_enpals' => isset($data['applica_enpals']) ? (empty($data['applica_enpals']) ? 0 : 1) : 1,
                'applica_iva' => isset($data['applica_iva']) ? (empty($data['applica_iva']) ? 0 : 1) : 1,
                'enpals_committente' => isset($data['enpals_committente']) ? $data['enpals_committente'] : 0,
                'enpals_lavoratore' => isset($data['enpals_lavoratore']) ? $data['enpals_lavoratore'] : 0,
                'iva' => $data['iva'],
                'totale' => $data['totale'],
                'data_acconto' => $data['data_acconto'],
                'importo_acconto' => $data['importo_acconto'],
                'stato' => isset($data['stato']) ? $data['stato'] : $preventivo['stato']
            );

            // Aggiorna preventivo
            $result = $wpdb->update(
                $table_preventivi,
                $preventivo_data,
                array('id' => $id),
                array(
                    '%d',  // categoria_id
                    '%s',  // data_preventivo
                    '%s',  // sposi
                    '%s',  // email
                    '%s',  // telefono
                    '%s',  // data_evento
                    '%s',  // location
                    '%s',  // tipo_evento
                    '%s',  // cerimonia
                    '%s',  // servizi_extra
                    '%s',  // note
                    '%f',  // totale_servizi
                    '%f',  // sconto
                    '%f',  // sconto_percentuale
                    '%d',  // applica_enpals
                    '%d',  // applica_iva
                    '%f',  // enpals_committente
                    '%f',  // enpals_lavoratore
                    '%f',  // iva
                    '%f',  // totale
                    '%s',  // data_acconto
                    '%f',  // importo_acconto
                    '%s'   // stato
                ),
                array('%d')
            );

            if ($result === false) {
                throw new Exception(__('Errore nell\'aggiornamento del preventivo.', 'mm-preventivi'));
            }

            // Elimina servizi esistenti
            $delete_result = $wpdb->delete($table_servizi, array('preventivo_id' => $id), array('%d'));
            if ($delete_result === false) {
                throw new Exception(__('Errore nella rimozione dei servizi esistenti.', 'mm-preventivi'));
            }

            // Inserisci servizi aggiornati
            if (isset($data['servizi']) && is_array($data['servizi'])) {
                foreach ($data['servizi'] as $servizio) {
                    // Salta servizi senza nome
                    if (empty($servizio['nome_servizio'])) {
                        continue;
                    }

                    $insert_result = $wpdb->insert(
                        $table_servizi,
                        array(
                            'preventivo_id' => $id,
                            'nome_servizio' => $servizio['nome_servizio'],
                            'prezzo' => $servizio['prezzo'],
                            'sconto' => isset($servizio['sconto']) ? $servizio['sconto'] : 0
                        ),
                        array('%d', '%s', '%f', '%f')
                    );

                    if ($insert_result === false) {
                        throw new Exception(__('Errore nell\'inserimento dei servizi aggiornati.', 'mm-preventivi'));
                    }
                }
            }

            // Gestione acconti multipli
            $table_acconti = $wpdb->prefix . self::$table_acconti;

            // Verifica esistenza tabella acconti
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_acconti'");
            if (!$table_exists) {
                error_log('MM Preventivi - ATTENZIONE: La tabella acconti non esiste! Creazione tabelle...');
                self::create_tables();
            }

            // Elimina acconti esistenti
            $delete_acconti_result = $wpdb->delete($table_acconti, array('preventivo_id' => $id), array('%d'));
            if ($delete_acconti_result === false) {
                throw new Exception(__('Errore nella rimozione degli acconti esistenti: ', 'mm-preventivi') . $wpdb->last_error);
            }

            // Inserisci acconti aggiornati
            if (isset($data['acconti']) && is_array($data['acconti'])) {
                foreach ($data['acconti'] as $acconto) {
                    if (!empty($acconto['data_acconto']) && !empty($acconto['importo_acconto'])) {
                        $insert_acconto_result = $wpdb->insert(
                            $table_acconti,
                            array(
                                'preventivo_id' => $id,
                                'data_acconto' => $acconto['data_acconto'],
                                'importo_acconto' => floatval($acconto['importo_acconto'])
                            ),
                            array('%d', '%s', '%f')
                        );

                        if ($insert_acconto_result === false) {
                            throw new Exception(__('Errore nell\'inserimento degli acconti aggiornati: ', 'mm-preventivi') . $wpdb->last_error);
                        }
                    }
                }
            }

            // Commit transazione
            $wpdb->query('COMMIT');

            // Invalida cache in modo aggressivo
            self::clear_preventivo_cache($id);
            // Invalida TUTTA la cache delle liste (non solo il singolo preventivo)
            wp_cache_delete('all_preventivi', 'mm_preventivi');
            wp_cache_flush(); // Svuota tutta la cache

            return true;

        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            error_log('MM Preventivi - Errore UPDATE transazione: ' . $e->getMessage());
            return new WP_Error('db_error', $e->getMessage());
        }
    }

    /**
     * CRUD Catalogo Servizi
     */

    /**
     * Ottieni tutti i servizi dal catalogo
     */
    public static function get_catalogo_servizi($filters = array()) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $where = array('1=1');
        $where_values = array();

        if (isset($filters['attivo'])) {
            $where[] = 'attivo = %d';
            $where_values[] = $filters['attivo'];
        }

        if (isset($filters['categoria']) && !empty($filters['categoria'])) {
            $where[] = 'categoria = %s';
            $where_values[] = $filters['categoria'];
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM $table_catalogo WHERE $where_clause ORDER BY ordinamento ASC, nome_servizio ASC";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Ottieni singolo servizio dal catalogo
     */
    public static function get_catalogo_servizio($id) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_catalogo WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    /**
     * Salva servizio nel catalogo (nuovo)
     */
    public static function save_catalogo_servizio($data) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $servizio_data = array(
            'nome_servizio' => sanitize_text_field($data['nome_servizio']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'prezzo_default' => isset($data['prezzo_default']) ? floatval($data['prezzo_default']) : 0,
            'categoria' => isset($data['categoria']) ? sanitize_text_field($data['categoria']) : '',
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1,
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0
        );

        $result = $wpdb->insert(
            $table_catalogo,
            $servizio_data,
            array('%s', '%s', '%f', '%s', '%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nel salvataggio del servizio.', 'mm-preventivi'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Aggiorna servizio nel catalogo
     */
    public static function update_catalogo_servizio($id, $data) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        $servizio_data = array(
            'nome_servizio' => sanitize_text_field($data['nome_servizio']),
            'descrizione' => isset($data['descrizione']) ? sanitize_textarea_field($data['descrizione']) : '',
            'prezzo_default' => isset($data['prezzo_default']) ? floatval($data['prezzo_default']) : 0,
            'categoria' => isset($data['categoria']) ? sanitize_text_field($data['categoria']) : '',
            'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1,
            'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0
        );

        $result = $wpdb->update(
            $table_catalogo,
            $servizio_data,
            array('id' => $id),
            array('%s', '%s', '%f', '%s', '%d', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nell\'aggiornamento del servizio.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Elimina servizio dal catalogo
     */
    public static function delete_catalogo_servizio($id) {
        global $wpdb;

        $table_catalogo = $wpdb->prefix . self::$table_catalogo_servizi;

        return $wpdb->delete(
            $table_catalogo,
            array('id' => $id),
            array('%d')
        );
    }

    // ===================================
    // SISTEMA DI CACHING
    // ===================================

    /**
     * Ottieni cache key per preventivi
     */
    private static function get_cache_key($type, $params = array()) {
        $key_parts = array('mm_preventivi', $type);
        if (!empty($params)) {
            $key_parts[] = md5(serialize($params));
        }
        return implode('_', $key_parts);
    }

    /**
     * Ottieni dati dalla cache
     */
    private static function get_from_cache($key) {
        return wp_cache_get($key, 'mm_preventivi');
    }

    /**
     * Salva dati nella cache
     */
    private static function set_to_cache($key, $data, $expiration = 3600) {
        return wp_cache_set($key, $data, 'mm_preventivi', $expiration);
    }

    /**
     * Cancella cache preventivi
     */
    public static function clear_preventivi_cache() {
        wp_cache_delete('mm_preventivi_all', 'mm_preventivi');
        wp_cache_delete('mm_preventivi_stats', 'mm_preventivi');
        wp_cache_delete('mm_preventivi_count', 'mm_preventivi');
        // Cancella anche transient per rate limiting se necessario
        return true;
    }

    /**
     * Cancella cache per singolo preventivo
     */
    private static function clear_preventivo_cache($id) {
        $cache_key = self::get_cache_key('preventivo', array('id' => $id));
        wp_cache_delete($cache_key, 'mm_preventivi');
        self::clear_preventivi_cache();
    }

    // ===================================
    // GESTIONE TIPI EVENTO
    // ===================================

    /**
     * Ottieni tutti i tipi evento
     */
    public static function get_tipi_evento($attivi_only = false) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table_tipi_evento;

        $where = $attivi_only ? "WHERE attivo = 1" : "";

        $results = $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY ordinamento ASC, nome ASC",
            ARRAY_A
        );

        return $results;
    }

    /**
     * Salva tipo evento
     */
    public static function save_tipo_evento($data) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table_tipi_evento;

        $result = $wpdb->insert(
            $table,
            array(
                'nome' => $data['nome'],
                'icona' => isset($data['icona']) ? $data['icona'] : '🎉',
                'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0,
                'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1
            ),
            array('%s', '%s', '%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nel salvataggio del tipo evento.', 'mm-preventivi'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Aggiorna tipo evento
     */
    public static function update_tipo_evento($id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table_tipi_evento;

        $result = $wpdb->update(
            $table,
            array(
                'nome' => $data['nome'],
                'icona' => isset($data['icona']) ? $data['icona'] : '🎉',
                'ordinamento' => isset($data['ordinamento']) ? intval($data['ordinamento']) : 0,
                'attivo' => isset($data['attivo']) ? intval($data['attivo']) : 1
            ),
            array('id' => $id),
            array('%s', '%s', '%d', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore nell\'aggiornamento del tipo evento.', 'mm-preventivi'));
        }

        return true;
    }

    /**
     * Elimina tipo evento
     */
    public static function delete_tipo_evento($id) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table_tipi_evento;

        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return $result !== false;
    }
}
