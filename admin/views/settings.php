<?php
/**
 * Admin View: Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Salva impostazioni
$settings_saved = false;
if (isset($_POST['mm_save_settings'])) {
    check_admin_referer('mm_preventivi_settings');

    // Debug: verifica che i dati arrivino
    error_log('MM Preventivi - Salvataggio impostazioni: ' . print_r($_POST, true));

    update_option('mm_preventivi_company_name', sanitize_text_field($_POST['company_name']));
    update_option('mm_preventivi_company_address', sanitize_text_field($_POST['company_address']));
    update_option('mm_preventivi_company_phone', sanitize_text_field($_POST['company_phone']));
    update_option('mm_preventivi_company_email', sanitize_email($_POST['company_email']));
    update_option('mm_preventivi_company_piva', sanitize_text_field($_POST['company_piva']));
    update_option('mm_preventivi_company_cf', sanitize_text_field($_POST['company_cf']));
    update_option('mm_preventivi_logo', esc_url_raw($_POST['company_logo']));
    update_option('mm_preventivi_enpals_percentage', floatval($_POST['enpals_percentage']));
    update_option('mm_preventivi_iva_percentage', floatval($_POST['iva_percentage']));

    $settings_saved = true;
}

// Carica impostazioni correnti
$company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
$company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
$company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
$company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
$company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
$company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
$company_logo = get_option('mm_preventivi_logo', '');
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>⚙️ Impostazioni</h1>
        <p>Configura le informazioni aziendali per i preventivi e i PDF</p>
    </div>

    <?php if ($settings_saved): ?>
        <div class="mm-notice mm-notice-success" style="margin: 20px 0;">
            ✅ <strong>Impostazioni salvate con successo!</strong> Le modifiche sono state applicate.
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('mm_preventivi_settings'); ?>
        
        <!-- Company Info -->
        <div class="mm-settings-section">
            <h3>🏢 Informazioni Aziendali</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Queste informazioni verranno utilizzate nei PDF dei preventivi.
            </p>
            
            <div class="mm-setting-row">
                <label for="company_name">Nome Azienda</label>
                <input type="text" 
                       id="company_name" 
                       name="company_name" 
                       value="<?php echo esc_attr($company_name); ?>" 
                       required>
            </div>
            
            <div class="mm-setting-row">
                <label for="company_address">Indirizzo</label>
                <input type="text" 
                       id="company_address" 
                       name="company_address" 
                       value="<?php echo esc_attr($company_address); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_phone">Telefono</label>
                <input type="text" 
                       id="company_phone" 
                       name="company_phone" 
                       value="<?php echo esc_attr($company_phone); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_email">Email</label>
                <input type="email" 
                       id="company_email" 
                       name="company_email" 
                       value="<?php echo esc_attr($company_email); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_piva">Partita IVA</label>
                <input type="text" 
                       id="company_piva" 
                       name="company_piva" 
                       value="<?php echo esc_attr($company_piva); ?>">
            </div>
            
            <div class="mm-setting-row">
                <label for="company_cf">Codice Fiscale</label>
                <input type="text"
                       id="company_cf"
                       name="company_cf"
                       value="<?php echo esc_attr($company_cf); ?>">
            </div>

            <div class="mm-setting-row">
                <label for="company_logo">Logo Aziendale</label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="url"
                           id="company_logo"
                           name="company_logo"
                           value="<?php echo esc_url($company_logo); ?>"
                           placeholder="https://esempio.it/logo.png"
                           style="flex: 1;">
                    <button type="button" class="button mm-upload-logo-btn">
                        🖼️ Carica Immagine
                    </button>
                </div>
                <?php if (!empty($company_logo)): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo esc_url($company_logo); ?>"
                         alt="Logo Preview"
                         style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: white;">
                </div>
                <?php endif; ?>
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Il logo verrà mostrato nell'header dei PDF dei preventivi. Dimensione consigliata: 200x60px.
                </p>
            </div>

            <div class="mm-setting-row">
                <label for="enpals_percentage">Aliquota ENPALS (%)</label>
                <input type="number"
                       id="enpals_percentage"
                       name="enpals_percentage"
                       value="<?php echo esc_attr(get_option('mm_preventivi_enpals_percentage', '33')); ?>"
                       step="0.1"
                       min="0"
                       max="100"
                       placeholder="33">
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Percentuale ENPALS da applicare ai preventivi (default: 33%)
                </p>
            </div>

            <div class="mm-setting-row">
                <label for="iva_percentage">Aliquota IVA (%)</label>
                <input type="number"
                       id="iva_percentage"
                       name="iva_percentage"
                       value="<?php echo esc_attr(get_option('mm_preventivi_iva_percentage', '22')); ?>"
                       step="0.1"
                       min="0"
                       max="100"
                       placeholder="22">
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Percentuale IVA da applicare ai preventivi (default: 22%)
                </p>
            </div>
        </div>

        <!-- Save Button -->
        <div style="margin-top: 30px;">
            <button type="submit" name="mm_save_settings" class="mm-btn-admin-primary" style="font-size: 16px; padding: 12px 24px;">
                💾 Salva Impostazioni
            </button>
        </div>

    </form>

    <!-- Gestione Servizi Offerti (FUORI DAL FORM) -->
    <div class="mm-settings-section" style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">🎵 Gestione Servizi Offerti</h3>
            <button type="button" id="mm-add-service-btn" class="mm-btn-admin-primary">
                ➕ Aggiungi Servizio
            </button>
        </div>

        <div id="mm-services-list">
            <?php
            $servizi = MM_Database::get_catalogo_servizi();
            if (empty($servizi)) {
                echo '<div class="mm-empty-state">
                    <div class="mm-empty-state-icon">📋</div>
                    <h3>Nessun servizio nel catalogo</h3>
                    <p>Clicca su "Aggiungi Servizio" per iniziare</p>
                </div>';
            } else {
                echo '<table class="mm-table">
                    <thead>
                        <tr>
                            <th>Servizio</th>
                            <th>Categoria</th>
                            <th style="width: 120px;">Prezzo Default</th>
                            <th style="width: 100px;">Stato</th>
                            <th style="width: 120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($servizi as $servizio) {
                    $attivo_class = $servizio['attivo'] ? 'attivo' : 'bozza';
                    $attivo_text = $servizio['attivo'] ? 'Attivo' : 'Disattivo';
                    echo '<tr>
                        <td>
                            <strong>' . esc_html($servizio['nome_servizio']) . '</strong>';
                    if (!empty($servizio['descrizione'])) {
                        echo '<br><small style="color: #999;">' . esc_html($servizio['descrizione']) . '</small>';
                    }
                    echo '</td>
                        <td>' . esc_html($servizio['categoria'] ?: '—') . '</td>
                        <td class="number">€ ' . number_format($servizio['prezzo_default'], 2, ',', '.') . '</td>
                        <td><span class="mm-status-badge ' . $attivo_class . '">' . $attivo_text . '</span></td>
                        <td class="mm-actions">
                            <button type="button" class="mm-btn-icon edit mm-edit-service" data-id="' . $servizio['id'] . '" title="Modifica">
                                ✏️
                            </button>
                            <button type="button" class="mm-btn-icon delete mm-delete-service" data-id="' . $servizio['id'] . '" title="Elimina">
                                🗑️
                            </button>
                        </td>
                    </tr>';
                }

                echo '</tbody></table>';
            }
            ?>
        </div>
    </div>

    <!-- Modal Aggiungi/Modifica Servizio -->
    <div id="mm-service-modal" class="mm-modal" style="display: none;">
        <div class="mm-modal-content" style="max-width: 600px;">
            <div class="mm-modal-header">
                <h2 id="mm-service-modal-title">Aggiungi Servizio</h2>
                <button type="button" class="mm-modal-close">&times;</button>
            </div>
            <div class="mm-modal-body">
                <form id="mm-service-form">
                    <input type="hidden" id="service-id" name="service_id">

                    <div class="mm-setting-row">
                        <label for="service-nome">Nome Servizio *</label>
                        <input type="text" id="service-nome" name="nome_servizio" required>
                    </div>

                    <div class="mm-setting-row">
                        <label for="service-descrizione">Descrizione</label>
                        <textarea id="service-descrizione" name="descrizione" rows="3" style="resize: vertical;"></textarea>
                    </div>

                    <div class="mm-setting-row">
                        <label for="service-categoria">Categoria</label>
                        <input type="text" id="service-categoria" name="categoria" placeholder="Es: DJ, Musicisti, Luci, ecc.">
                    </div>

                    <div class="mm-setting-row">
                        <label for="service-prezzo">Prezzo Default (€)</label>
                        <input type="number" id="service-prezzo" name="prezzo_default" step="0.01" min="0" value="0">
                    </div>

                    <div class="mm-setting-row">
                        <label for="service-ordinamento">Ordinamento</label>
                        <input type="number" id="service-ordinamento" name="ordinamento" min="0" value="0">
                        <p style="color: #999; font-size: 12px; margin-top: 5px;">
                            Numero più basso = appare prima nella lista
                        </p>
                    </div>

                    <div class="mm-setting-row">
                        <label>
                            <input type="checkbox" id="service-attivo" name="attivo" value="1" checked>
                            Servizio attivo
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                        <button type="submit" class="mm-btn-admin-primary" style="flex: 1;">
                            💾 Salva Servizio
                        </button>
                        <button type="button" class="mm-btn-admin-secondary mm-modal-close">
                            Annulla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Shortcode Info -->
        <div class="mm-settings-section">
            <h3>📝 Shortcodes Disponibili</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Usa questi shortcode per integrare il plugin nelle tue pagine:
            </p>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #e91e63;">Form Preventivo</h4>
                <p style="color: #666; margin-bottom: 10px; font-size: 13px;">
                    Mostra il form per creare un nuovo preventivo:
                </p>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px;">
                    [mm_preventivo_form]
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #e91e63;">Lista Preventivi (Richiede Login)</h4>
                <p style="color: #666; margin-bottom: 10px; font-size: 13px;">
                    Mostra la lista di tutti i preventivi con filtri (solo per amministratori):
                </p>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px;">
                    [mm_preventivi_list]
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #e91e63;">Statistiche (Richiede Login)</h4>
                <p style="color: #666; margin-bottom: 10px; font-size: 13px;">
                    Mostra le statistiche dei preventivi (solo per amministratori):
                </p>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px;">
                    [mm_preventivi_stats]
                </div>
            </div>

            <p style="color: #999; margin-top: 15px; font-size: 13px; background: #fff3cd; padding: 12px; border-left: 4px solid #ff9800; border-radius: 5px;">
                <strong>⚠️ Nota Sicurezza:</strong> Gli shortcode per lista e statistiche richiedono che l'utente sia loggato e abbia i permessi di amministratore. Gli utenti non autorizzati vedranno un messaggio di errore.
            </p>
        </div>
        
        <!-- Database Info -->
        <div class="mm-settings-section">
            <h3>💾 Informazioni Database</h3>
            <?php
            global $wpdb;
            $table_preventivi = $wpdb->prefix . 'mm_preventivi';
            $table_servizi = $wpdb->prefix . 'mm_preventivi_servizi';

            $count_preventivi = $wpdb->get_var("SELECT COUNT(*) FROM $table_preventivi");
            $count_servizi = $wpdb->get_var("SELECT COUNT(*) FROM $table_servizi");
            ?>
            <table class="mm-services-table">
                <tr>
                    <td><strong>Tabella Preventivi:</strong></td>
                    <td><?php echo esc_html($table_preventivi); ?></td>
                    <td><?php echo esc_html($count_preventivi); ?> record</td>
                </tr>
                <tr>
                    <td><strong>Tabella Servizi:</strong></td>
                    <td><?php echo esc_html($table_servizi); ?></td>
                    <td><?php echo esc_html($count_servizi); ?> record</td>
                </tr>
                <tr>
                    <td><strong>Versione Plugin:</strong></td>
                    <td colspan="2"><?php echo MM_PREVENTIVI_VERSION; ?></td>
                </tr>
            </table>

            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ff9800; border-radius: 5px;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ Manutenzione Database</h4>
                <p style="margin: 0 0 15px 0; color: #856404;">
                    Se riscontri errori nel salvataggio dei preventivi, potrebbe essere necessario eseguire le migrazioni del database per aggiungere colonne mancanti (sconto, applica_enpals, applica_iva, ecc.).
                </p>
                <button type="button" id="mm-run-migrations" class="button button-secondary">
                    🔧 Esegui Migrazioni Database
                </button>
                <div id="mm-migration-result" style="margin-top: 10px;"></div>
            </div>
        </div>

</div>
