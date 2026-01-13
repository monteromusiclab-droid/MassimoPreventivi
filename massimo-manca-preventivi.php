<?php
/**
 * Plugin Name: Massimo Manca - Generatore Preventivi
 * Plugin URI: https://massimomanca.it
 * Description: Sistema professionale per la creazione e gestione di preventivi per eventi con DJ, animazione, scenografie e photo booth. Include database sicuro, caching avanzato, transazioni atomiche e pannello amministratore ottimizzato.
 * Version: 1.2.0
 * Author: Massimo Manca
 * Author URI: https://massimomanca.it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mm-preventivi
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Impedisci accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('MM_PREVENTIVI_VERSION', '1.2.0');
define('MM_PREVENTIVI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MM_PREVENTIVI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MM_PREVENTIVI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale del plugin
 */
class MM_Preventivi {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Ottieni istanza singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Carica dipendenze
     */
    private function load_dependencies() {
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-database.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-security.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-auth.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-frontend.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'admin/class-mm-admin.php';
        require_once MM_PREVENTIVI_PLUGIN_DIR . 'includes/class-mm-pdf-generator.php';
    }
    
    /**
     * Inizializza hooks
     */
    private function init_hooks() {
        // Attivazione/Disattivazione plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enqueue scripts e styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Noindex per pagine admin del plugin
        add_action('admin_head', array($this, 'add_noindex_meta'));

        // Shortcodes
        add_shortcode('mm_preventivo_form', array($this, 'render_form_shortcode'));
        add_shortcode('mm_preventivi_dashboard', array($this, 'render_dashboard_shortcode'));

        // AJAX actions per invio email
        add_action('wp_ajax_mm_send_preventivo_email', array($this, 'ajax_send_preventivo_email'));
        add_action('wp_ajax_nopriv_mm_send_preventivo_email', array($this, 'ajax_send_preventivo_email'));
    }
    
    /**
     * Attivazione plugin
     */
    public function activate() {
        MM_Database::create_tables();

        // Migrazione: Elimina la vecchia tabella mm_categorie_preventivi
        $this->migrate_remove_old_categories_table();

        // Crea le pagine necessarie per il frontend
        $this->create_frontend_pages();

        flush_rewrite_rules();
    }

    /**
     * Migrazione: Rimuove la vecchia tabella mm_categorie_preventivi
     */
    private function migrate_remove_old_categories_table() {
        global $wpdb;

        $table_categorie = $wpdb->prefix . 'mm_categorie_preventivi';
        $table_preventivi = $wpdb->prefix . 'mm_preventivi';

        // Verifica se la tabella categorie esiste ancora
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_categorie'");

        if ($table_exists) {
            // Prima rimuovi il constraint FK se esiste
            $wpdb->query("ALTER TABLE $table_preventivi DROP FOREIGN KEY IF EXISTS fk_categoria");

            // Poi elimina la tabella
            $wpdb->query("DROP TABLE IF EXISTS $table_categorie");

            error_log('MM Preventivi - Migrazione completata: tabella mm_categorie_preventivi rimossa');
        }
    }

    /**
     * Crea le pagine frontend necessarie
     */
    private function create_frontend_pages() {
        // Pagina: Nuovo Preventivo
        $nuovo_preventivo = get_page_by_path('nuovo-preventivo');
        if (!$nuovo_preventivo) {
            wp_insert_post(array(
                'post_title'    => 'Nuovo Preventivo',
                'post_name'     => 'nuovo-preventivo',
                'post_content'  => '[mm_preventivo_form]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ));
        }

        // Pagina: Lista Preventivi
        $lista_preventivi = get_page_by_path('lista-preventivi');
        if (!$lista_preventivi) {
            wp_insert_post(array(
                'post_title'    => 'Lista Preventivi',
                'post_name'     => 'lista-preventivi',
                'post_content'  => '[mm_preventivi_list]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ));
        }

        // Pagina: Dashboard Preventivi
        $dashboard = get_page_by_path('dashboard-preventivi');
        if (!$dashboard) {
            wp_insert_post(array(
                'post_title'    => 'Dashboard Preventivi',
                'post_name'     => 'dashboard-preventivi',
                'post_content'  => '[mm_preventivi_dashboard]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ));
        }

        // Pagina: Statistiche Preventivi
        $statistiche = get_page_by_path('statistiche-preventivi');
        if (!$statistiche) {
            wp_insert_post(array(
                'post_title'    => 'Statistiche Preventivi',
                'post_name'     => 'statistiche-preventivi',
                'post_content'  => '[mm_preventivi_stats]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ));
        }
    }
    
    /**
     * Disattivazione plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Inizializzazione
     */
    public function init() {
        load_plugin_textdomain('mm-preventivi', false, dirname(MM_PREVENTIVI_PLUGIN_BASENAME) . '/languages');

        // Le pagine vengono create solo all'attivazione, non ad ogni caricamento
        // Rimosso check ridondante per migliorare performance
    }
    
    /**
     * Inizializzazione admin
     */
    public function admin_init() {
        // Verifica permessi
        if (!current_user_can('manage_options')) {
            return;
        }
    }
    
    /**
     * Enqueue assets frontend
     */
    public function enqueue_frontend_assets() {
        // Carica sempre su frontend (CSS leggero, sempre disponibile)
        if (!is_admin()) {
            wp_enqueue_style(
                'mm-preventivi-frontend',
                MM_PREVENTIVI_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                MM_PREVENTIVI_VERSION
            );

            wp_enqueue_script(
                'mm-preventivi-frontend',
                MM_PREVENTIVI_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                MM_PREVENTIVI_VERSION,
                true
            );

            // Localizza script
            wp_localize_script('mm-preventivi-frontend', 'mmPreventivi', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mm_preventivi_nonce'),
                'pdfNonce' => wp_create_nonce('mm_preventivi_view_pdf'),
                'enpalsCommittentePercentage' => floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81)),
                'enpalsLavoratorePercentage' => floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19)),
                'ivaPercentage' => floatval(get_option('mm_preventivi_iva_percentage', 22)),
                'strings' => array(
                    'error' => __('Si è verificato un errore. Riprova.', 'mm-preventivi'),
                    'success' => __('Preventivo salvato con successo!', 'mm-preventivi'),
                )
            ));
        }
    }
    
    /**
     * Enqueue assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Carica solo nelle pagine del plugin
        if (strpos($hook, 'mm-preventivi') === false) {
            return;
        }

        // Enqueue WordPress Media Library
        wp_enqueue_media();

        wp_enqueue_style(
            'mm-preventivi-admin',
            MM_PREVENTIVI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MM_PREVENTIVI_VERSION
        );

        wp_enqueue_script(
            'mm-preventivi-admin',
            MM_PREVENTIVI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            MM_PREVENTIVI_VERSION,
            true
        );

        wp_localize_script('mm-preventivi-admin', 'mmPreventiviAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mm_preventivi_admin_nonce'),
        ));
    }
    
    /**
     * Aggiungi meta tag noindex alle pagine admin del plugin
     */
    public function add_noindex_meta() {
        $screen = get_current_screen();

        // Verifica se siamo in una pagina del plugin
        if ($screen && strpos($screen->id, 'mm-preventivi') !== false) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }

    /**
     * Render shortcode form
     */
    public function render_form_shortcode($atts) {
        ob_start();
        MM_Frontend::render_form();
        return ob_get_clean();
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard_shortcode($atts) {
        ob_start();
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/preventivi-dashboard.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Invia preventivo via email
     */
    public function ajax_send_preventivo_email() {
        try {
            error_log('MM Preventivi - Inizio invio email preventivo');

            // Verifica nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mm_send_email')) {
                error_log('MM Preventivi - Nonce fallito');
                wp_send_json_error(array('message' => 'Verifica di sicurezza fallita.'));
                return;
            }

            // Verifica autenticazione
            if (!MM_Auth::is_logged_in()) {
                error_log('MM Preventivi - Utente non autenticato');
                wp_send_json_error(array('message' => 'Devi essere autenticato.'));
                return;
            }

            $preventivo_id = isset($_POST['preventivo_id']) ? intval($_POST['preventivo_id']) : 0;
            error_log('MM Preventivi - Preventivo ID: ' . $preventivo_id);

            if (!$preventivo_id) {
                wp_send_json_error(array('message' => 'ID preventivo non valido.'));
                return;
            }

            // Carica il preventivo
            $preventivo = MM_Database::get_preventivo($preventivo_id);

            if (!$preventivo) {
                error_log('MM Preventivi - Preventivo non trovato: ' . $preventivo_id);
                wp_send_json_error(array('message' => 'Preventivo non trovato.'));
                return;
            }

            error_log('MM Preventivi - Preventivo caricato: ' . $preventivo['numero_preventivo']);

            // Verifica che ci sia un'email
            if (empty($preventivo['email'])) {
                error_log('MM Preventivi - Email mancante');
                wp_send_json_error(array('message' => 'Nessun indirizzo email associato al preventivo.'));
                return;
            }

            // Genera PDF come file temporaneo
            error_log('MM Preventivi - Generazione PDF...');
            $pdf_path = MM_PDF_Generator::generate_pdf_file($preventivo);
            error_log('MM Preventivi - PDF generato: ' . $pdf_path);

            if (!$pdf_path || !file_exists($pdf_path)) {
                error_log('MM Preventivi - PDF non trovato o non generato');
                wp_send_json_error(array('message' => 'Impossibile generare il PDF. Path: ' . ($pdf_path ? $pdf_path : 'null')));
                return;
            }
        } catch (Exception $e) {
            error_log('MM Preventivi - Errore durante preparazione email: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Errore: ' . $e->getMessage()));
            return;
        }

        // Prepara email
        $to = $preventivo['email'];
        $subject = 'Preventivo n. ' . $preventivo['numero_preventivo'] . ' - ' . get_bloginfo('name');

        $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $message .= '<div style="background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 30px;">';
        $message .= '<h1 style="margin: 0; font-size: 28px;">📋 Preventivo</h1>';
        $message .= '<p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.95;">n. ' . esc_html($preventivo['numero_preventivo']) . '</p>';
        $message .= '</div>';

        $message .= '<div style="background: #f9f9f9; padding: 25px; border-radius: 8px; margin-bottom: 20px;">';
        $message .= '<p style="margin: 0 0 15px 0; font-size: 16px;">Gentile <strong>' . esc_html($preventivo['sposi']) . '</strong>,</p>';
        $message .= '<p style="margin: 0 0 15px 0;">In allegato trovi il preventivo richiesto per il tuo evento.</p>';
        $message .= '<p style="margin: 0; font-size: 14px; color: #666;">Se hai domande o necessiti di modifiche, non esitare a contattarci.</p>';
        $message .= '</div>';

        $message .= '<div style="padding: 20px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px; margin-bottom: 20px;">';
        $message .= '<p style="margin: 0; font-size: 14px;"><strong>📅 Data Evento:</strong> ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</p>';
        $message .= '<p style="margin: 8px 0 0 0; font-size: 14px;"><strong>📍 Location:</strong> ' . esc_html($preventivo['location']) . '</p>';
        $message .= '<p style="margin: 8px 0 0 0; font-size: 14px;"><strong>💰 Totale:</strong> <span style="color: #e91e63; font-weight: bold;">' . number_format($preventivo['totale'], 2, ',', '.') . ' €</span></p>';
        $message .= '</div>';

        $message .= '<div style="text-align: center; padding: 20px; border-top: 2px solid #e0e0e0;">';
        $message .= '<p style="margin: 0 0 10px 0; font-size: 13px; color: #999;">Cordiali saluti,<br><strong>' . get_bloginfo('name') . '</strong></p>';
        $message .= '<p style="margin: 0; font-size: 12px; color: #aaa;">' . get_bloginfo('description') . '</p>';
        $message .= '</div>';

        $message .= '</div>';
        $message .= '</body></html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        );

        $attachments = array($pdf_path);

        // Invia email
        error_log('MM Preventivi - Invio email a: ' . $to);
        error_log('MM Preventivi - Allegato: ' . $pdf_path . ' (exists: ' . (file_exists($pdf_path) ? 'yes' : 'no') . ')');

        $sent = wp_mail($to, $subject, $message, $headers, $attachments);

        error_log('MM Preventivi - Email inviata: ' . ($sent ? 'SI' : 'NO'));

        // Elimina il file temporaneo
        if (file_exists($pdf_path)) {
            error_log('MM Preventivi - Eliminazione file temporaneo: ' . $pdf_path);
            @unlink($pdf_path);
        }

        if ($sent) {
            error_log('MM Preventivi - Successo invio email');
            wp_send_json_success(array('message' => 'Email inviata con successo!'));
        } else {
            error_log('MM Preventivi - Fallimento invio email');
            wp_send_json_error(array('message' => 'Impossibile inviare l\'email. Verifica la configurazione del server SMTP.'));
        }
    }

}

/**
 * Inizializza plugin
 */
function mm_preventivi_init() {
    return MM_Preventivi::get_instance();
}

// Avvia plugin
mm_preventivi_init();