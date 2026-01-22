<?php
/**
 * Template: Form Preventivo Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica autenticazione
if (!MM_Auth::is_logged_in()) {
    echo MM_Auth::show_login_form();
    return;
}

$current_user = wp_get_current_user();

// Carica tipi evento attivi
$categorie = MM_Database::get_tipi_evento(true);
?>

<div class="mm-preventivi-container">>

    <!-- Navigation Bar -->
    <div class="mm-nav-bar">
        <div class="mm-nav-left">
            <span class="mm-user-info">
                👤 <strong><?php echo esc_html($current_user->display_name); ?></strong>
            </span>
        </div>
        <div class="mm-nav-center">
            <a href="<?php echo home_url('/dashboard-preventivi/'); ?>" class="mm-nav-btn">
                📋 Dashboard
            </a>
            <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-nav-btn">
                📊 Tutti i Preventivi
            </a>
            <a href="<?php echo home_url('/assegnazioni-collaboratori/'); ?>" class="mm-nav-btn">
                🎵 Assegnazioni
            </a>
            <a href="<?php echo home_url('/statistiche-preventivi/'); ?>" class="mm-nav-btn">
                📈 Statistiche
            </a>
            <a href="<?php echo get_permalink(); ?>" class="mm-nav-btn mm-nav-btn-active">
                ➕ Nuovo Preventivo
            </a>
        </div>
        <div class="mm-nav-right">
            <a href="<?php echo MM_Auth::get_logout_url(); ?>" class="mm-nav-btn mm-nav-btn-logout">
                🚪 Esci
            </a>
        </div>
    </div>

    <form id="mm-preventivo-form" class="mm-preventivi-form" method="post">

        <!-- Header -->
        <div class="mm-form-header">
            <h1>✨ Nuovo Preventivo</h1>
            <p>DJ • Animazione • Scenografie • Photo Booth</p>
        </div>
        
        <!-- Form Body -->
        <div class="mm-form-body">
            
            <!-- Dati Cliente -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📋 Dati Cliente</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Preventivo <span class="required">*</span></label>
                        <input type="date" id="data_preventivo" name="data_preventivo" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Categoria Evento <span class="required">*</span></label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">-- Seleziona categoria --</option>
                            <?php foreach ($categorie as $categoria) : ?>
                                <option value="<?php echo $categoria['id']; ?>">
                                    <?php echo esc_html($categoria['icona'] . ' ' . $categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Sposi / Cliente <span class="required">*</span></label>
                        <input type="text" id="sposi" name="sposi" placeholder="Nome e cognome" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" placeholder="email@esempio.it">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Telefono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="333-7512343">
                    </div>
                </div>
            </div>
            
            <!-- Dati Evento -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📅 Dati Evento</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Evento <span class="required">*</span></label>
                        <input type="date" id="data_evento" name="data_evento" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Location</label>
                        <input type="text" id="location" name="location" placeholder="Nome location">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Tipo Evento</label>
                        <div class="mm-radio-group">
                            <div class="mm-radio-item">
                                <input type="radio" id="pranzo" name="tipo_evento" value="Pranzo">
                                <label for="pranzo">Pranzo</label>
                            </div>
                            <div class="mm-radio-item">
                                <input type="radio" id="cena" name="tipo_evento" value="Cena" checked>
                                <label for="cena">Cena</label>
                            </div>
                        </div>
                    </div>
                    <div class="mm-form-group">
                        <label>Cerimonia</label>

                        <!-- Rito Si/No -->
                        <div class="mm-rito-selector" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; margin-bottom: 8px; display: block;">Rito:</label>
                            <div style="display: flex; gap: 20px;">
                                <div class="mm-radio-item">
                                    <input type="radio" id="rito_si" name="rito" value="Si">
                                    <label for="rito_si">Si</label>
                                </div>
                                <div class="mm-radio-item">
                                    <input type="radio" id="rito_no" name="rito" value="No" checked>
                                    <label for="rito_no">No</label>
                                </div>
                            </div>
                        </div>

                        <!-- Strumenti Rito -->
                        <div class="mm-checkbox-group">
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="violino" name="cerimonia[]" value="Violino">
                                <label for="violino">Violino</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="arpa" name="cerimonia[]" value="Arpa">
                                <label for="arpa">Arpa</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="piano" name="cerimonia[]" value="Piano">
                                <label for="piano">Piano</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="altro_cerimonia" name="cerimonia[]" value="Altro">
                                <label for="altro_cerimonia">Altro</label>
                            </div>
                        </div>

                        <!-- Campo Altro Rito (mostrato solo se selezionato) -->
                        <div id="altro_rito_container" style="margin-top: 10px; display: none;">
                            <label for="altro_rito_testo" style="font-weight: 600; display: block; margin-bottom: 8px;">Specifica altro:</label>
                            <input type="text" id="altro_rito_testo" name="altro_rito_testo" placeholder="Inserisci strumento o servizio" style="width: 100%; max-width: 400px; padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px;">
                        </div>

                        <!-- Prezzo Rito -->
                        <div style="margin-top: 15px;">
                            <label for="prezzo_cerimonia" style="font-weight: 600; display: block; margin-bottom: 8px;">Prezzo Rito (€):</label>
                            <input type="number" id="prezzo_cerimonia" name="prezzo_cerimonia" placeholder="0,00" min="0" step="0.01" value="0" style="width: 100%; max-width: 200px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Servizi -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">🎉 Servizi Richiesti</h2>
                <div class="mm-services-list">
                    <!-- Header Colonne -->
                    <div class="mm-services-header">
                        <div class="header-checkbox"></div>
                        <div class="header-label">Servizio</div>
                        <div class="header-pricing">
                            <span class="price-label">Prezzo (€)</span>
                            <span class="discount-label">Sconto (€)</span>
                        </div>
                    </div>

                    <?php
                    // Carica servizi dal catalogo backend
                    $servizi = MM_Database::get_catalogo_servizi();

                    if (empty($servizi)) {
                        echo '<p style="color: #999; text-align: center; padding: 20px;">Nessun servizio disponibile nel catalogo. Vai su <a href="' . admin_url('admin.php?page=mm-preventivi-settings') . '">Impostazioni</a> per aggiungere servizi.</p>';
                    } else {
                        foreach ($servizi as $servizio) :
                            // Genera un ID univoco dal nome del servizio
                            $service_id = sanitize_title($servizio['nome_servizio']);
                        ?>
                        <div class="mm-service-item">
                            <input type="checkbox" id="srv_<?php echo esc_attr($service_id); ?>">
                            <label for="srv_<?php echo esc_attr($service_id); ?>">
                                <?php echo esc_html($servizio['nome_servizio']); ?>
                                <?php if (!empty($servizio['descrizione'])): ?>
                                    <small style="display: block; color: #999; font-size: 11px; font-weight: 300; margin-top: 2px;"><?php echo esc_html($servizio['descrizione']); ?></small>
                                <?php endif; ?>
                            </label>
                            <div class="mm-service-pricing">
                                <input type="number" id="price_<?php echo esc_attr($service_id); ?>" placeholder="€" value="<?php echo esc_attr($servizio['prezzo_default']); ?>" min="0" step="0.01" class="mm-price-input">
                                <input type="number" id="discount_<?php echo esc_attr($service_id); ?>" placeholder="Sconto €" value="0" min="0" step="0.01" class="mm-discount-input" title="Sconto fisso per questo servizio">
                            </div>
                        </div>
                        <?php
                        endforeach;
                    }
                    ?>
                </div>
            </div>
            
            <!-- Servizi Extra -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">✨ Servizi</h2>
                <div class="mm-checkbox-group">
                    <?php
                    $extras = array('Rito', 'Accoglienza', 'Antipasti', 'Sala', 'Torta', 'Buffet f/d', 'After Party');
                    $extras_checked = array('Accoglienza', 'Antipasti', 'Sala', 'Torta', 'Buffet f/d'); // Default checked (Rito non selezionato)
                    foreach ($extras as $extra) :
                        $is_checked = in_array($extra, $extras_checked) ? 'checked' : '';
                    ?>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="extra_<?php echo sanitize_title($extra); ?>" name="servizi_extra[]" value="<?php echo esc_attr($extra); ?>" <?php echo $is_checked; ?>>
                        <label for="extra_<?php echo sanitize_title($extra); ?>"><?php echo esc_html($extra); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Note -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📝 Note</h2>
                <div class="mm-form-row full">
                    <div class="mm-form-group">
                        <textarea id="note" name="note" placeholder="Inserisci eventuali note o richieste particolari..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Sconti e Opzioni -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">💰 Sconti e Opzioni</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Sconto Fisso (€)</label>
                        <input type="number" id="sconto" name="sconto" placeholder="0,00" min="0" step="0.01" value="0">
                    </div>
                    <div class="mm-form-group">
                        <label>Sconto Percentuale (%)</label>
                        <input type="number" id="sconto_percentuale" name="sconto_percentuale" placeholder="0" min="0" max="100" step="0.1" value="0">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <div class="mm-checkbox-group">
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="applica_enpals" name="applica_enpals" value="1" checked>
                                <label for="applica_enpals">Applica Enpals (<?php echo esc_html(get_option('mm_preventivi_enpals_percentage', '33')); ?>%)</label>
                            </div>
                            <div class="mm-checkbox-item">
                                <input type="checkbox" id="applica_iva" name="applica_iva" value="1" checked>
                                <label for="applica_iva">Applica IVA (<?php echo esc_html(get_option('mm_preventivi_iva_percentage', '22')); ?>%)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calcolo Prezzi -->
            <div class="mm-price-summary">
                <div class="mm-price-row">
                    <span class="label">Totale Servizi:</span>
                    <span class="value" id="subtotal">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="sconto-row" style="display: none; color: #4caf50;">
                    <span class="label">- Sconto:</span>
                    <span class="value" id="sconto-display">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="subtotal-sconto-row" style="display: none;">
                    <span class="label">Subtotale:</span>
                    <span class="value" id="subtotal-sconto">€ 0,00</span>
                </div>
                <div class="mm-price-row" id="enpals-row">
  <span class="label">Ex Enpals (<?php echo esc_html(get_option('mm_preventivi_enpals_percentage', '33')); ?>%):</span>
  <span class="value" id="enpals">€ 0,00</span>
</div>

                <div class="mm-price-row" id="iva-row">
                    <span class="label">IVA (<?php echo esc_html(get_option('mm_preventivi_iva_percentage', '22')); ?>%):</span>
                    <span class="value" id="iva">€ 0,00</span>
                </div>
                <div class="mm-price-row total">
                    <span class="label">TOTALE:</span>
                    <span class="value" id="total">€ 0,00</span>
                </div>
            </div>
            
            <!-- Acconto -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">💰 Acconto (opzionale)</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Acconto</label>
                        <input type="date" id="data_acconto" name="data_acconto">
                    </div>
                    <div class="mm-form-group">
                        <label>Importo Acconto (€)</label>
                        <input type="number" id="importo_acconto" name="importo_acconto" placeholder="0,00" min="0" step="0.01">
                    </div>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="mm-form-actions">
                <button type="button" class="mm-btn mm-btn-preview">
                    👁️ Anteprima Preventivo
                </button>
                <button type="submit" class="mm-btn mm-btn-primary">
                    💾 Salva Preventivo
                </button>
                <button type="button" class="mm-btn mm-btn-secondary mm-btn-reset">
                    🔄 Reset Form
                </button>
            </div>
            
        </div>
    </form>

    <!-- Preview Modal -->
    <div id="mm-preview-modal" class="mm-modal" style="display: none;">
        <div class="mm-modal-overlay"></div>
        <div class="mm-modal-content">
            <div class="mm-modal-header">
                <h2>📄 Anteprima Preventivo</h2>
                <button type="button" class="mm-modal-close">&times;</button>
            </div>
            <div class="mm-modal-body" id="mm-preview-content">
                <!-- Content will be injected here -->
            </div>
            <div class="mm-modal-footer">
                <button type="button" class="mm-btn mm-btn-secondary mm-modal-close-btn">
                    Chiudi
                </button>
                <button type="button" class="mm-btn mm-btn-primary mm-preview-save-btn">
                    💾 Salva Preventivo
                </button>
                <button type="button" class="mm-btn mm-btn-secondary" onclick="window.print()">
                    🖨️ Stampa
                </button>
            </div>
        </div>
    </div>
</div>