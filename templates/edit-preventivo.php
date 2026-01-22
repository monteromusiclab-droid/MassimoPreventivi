<?php
/**
 * Template: Modifica Preventivo Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica autenticazione
if (!MM_Auth::is_logged_in()) {
    echo MM_Auth::show_login_form();
    return;
}

// Ottieni ID preventivo
$preventivo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug: mostra sempre un messaggio per verificare che il template venga caricato
error_log('MM Preventivi - Template edit-preventivo.php caricato, ID richiesto: ' . $preventivo_id);

if (!$preventivo_id) {
    ?>
    <div class="mm-frontend-container">
        <div class="mm-error-message">
            <h3>⚠️ ID Preventivo Mancante</h3>
            <p>Nessun ID preventivo specificato nell'URL.</p>
            <p><strong>URL atteso:</strong> /modifica-preventivo/?id=<em>numero</em></p>
            <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-btn mm-btn-primary" style="display: inline-block; margin-top: 15px; padding: 12px 24px; background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                ← Torna alla Lista Preventivi
            </a>
        </div>
    </div>
    <?php
    return;
}

// Carica preventivo
$preventivo = MM_Database::get_preventivo($preventivo_id);

error_log('MM Preventivi - Caricamento preventivo ID ' . $preventivo_id . ': ' . ($preventivo ? 'TROVATO' : 'NON TROVATO'));

if (!$preventivo) {
    ?>
    <div class="mm-frontend-container">
        <div class="mm-error-message">
            <h3>❌ Preventivo Non Trovato</h3>
            <p>Il preventivo con ID <strong><?php echo $preventivo_id; ?></strong> non esiste nel database.</p>
            <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-btn mm-btn-primary" style="display: inline-block; margin-top: 15px; padding: 12px 24px; background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                ← Torna alla Lista Preventivi
            </a>
        </div>
    </div>
    <?php
    return;
}

$current_user = wp_get_current_user();

// Imposta valori di default per campi mancanti
$preventivo = array_merge(array(
    'numero_preventivo' => '',
    'stato' => 'bozza',
    'data_preventivo' => '',
    'sposi' => '',
    'email' => '',
    'telefono' => '',
    'data_evento' => '',
    'location' => '',
    'tipo_evento' => '',
    'cerimonia' => '',
    'servizi_extra' => '',
    'note' => '',
    'servizi' => array(),
    'sconto' => 0,
    'sconto_percentuale' => 0,
    'applica_enpals' => 0,
    'applica_iva' => 0,
    'data_acconto' => '',
    'importo_acconto' => 0,
    'totale_servizi' => 0,
    'enpals' => 0,
    'iva' => 0,
    'totale' => 0
), $preventivo);

// Converti tutti i valori numerici a float per evitare null
$preventivo['sconto'] = floatval($preventivo['sconto']);
$preventivo['sconto_percentuale'] = floatval($preventivo['sconto_percentuale']);
$preventivo['importo_acconto'] = floatval($preventivo['importo_acconto']);
$preventivo['totale_servizi'] = floatval($preventivo['totale_servizi']);
$preventivo['enpals'] = floatval($preventivo['enpals']);
$preventivo['iva'] = floatval($preventivo['iva']);
$preventivo['totale'] = floatval($preventivo['totale']);
$preventivo['applica_enpals'] = intval($preventivo['applica_enpals']);
$preventivo['applica_iva'] = intval($preventivo['applica_iva']);

// I servizi sono già un array dal database (tabella separata)
// Escludiamo i servizi che iniziano con "Rito" perché gestiti separatamente
$servizi_db_completi = isset($preventivo['servizi']) && is_array($preventivo['servizi']) ? $preventivo['servizi'] : array();
$servizi_db = array_filter($servizi_db_completi, function($serv) {
    return strpos($serv['nome_servizio'], 'Rito') !== 0;
});

// Cerimonia e servizi_extra sono JSON encoded
$cerimonia_db = isset($preventivo['cerimonia']) ? (is_string($preventivo['cerimonia']) ? json_decode($preventivo['cerimonia'], true) : $preventivo['cerimonia']) : array();
$servizi_extra_db = isset($preventivo['servizi_extra']) ? (is_string($preventivo['servizi_extra']) ? json_decode($preventivo['servizi_extra'], true) : $preventivo['servizi_extra']) : array();

// Carica catalogo servizi
$catalogo_servizi = MM_Database::get_catalogo_servizi(array('attivo' => 1));

// Debug: log servizi del database vs catalogo
error_log('=== DEBUG EDIT PREVENTIVO #' . $preventivo_id . ' ===');
error_log('Servizi nel database:');
foreach ($servizi_db as $s) {
    error_log('  - ' . $s['nome_servizio'] . ' (prezzo: ' . $s['prezzo'] . ', sconto: ' . $s['sconto'] . ')');
}
error_log('Servizi nel catalogo:');
foreach ($catalogo_servizi as $c) {
    error_log('  - ' . $c['nome_servizio']);
}

// Carica tipi evento attivi
$categorie = MM_Database::get_tipi_evento(true);
?>

<div class="mm-frontend-container">

    <!-- Navigation Bar -->
    <div class="mm-nav-bar">
        <div class="mm-nav-left">
            <span class="mm-user-info">
                👤 <strong><?php echo esc_html($current_user->display_name); ?></strong>
            </span>
        </div>
        <div class="mm-nav-center">
            <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-nav-btn">
                📊 Tutti i Preventivi
            </a>
            <a href="<?php echo home_url('/assegnazioni-collaboratori/'); ?>" class="mm-nav-btn">
                🎵 Assegnazioni
            </a>
            <a href="<?php echo home_url('/statistiche-preventivi/'); ?>" class="mm-nav-btn">
                📈 Statistiche
            </a>
            <a href="<?php echo home_url('/nuovo-preventivo/'); ?>" class="mm-nav-btn">
                ➕ Nuovo Preventivo
            </a>
        </div>
        <div class="mm-nav-right">
            <a href="<?php echo MM_Auth::get_logout_url(); ?>" class="mm-nav-btn mm-nav-btn-logout">
                🚪 Esci
            </a>
        </div>
    </div>

    <form id="mm-edit-preventivo-form" class="mm-preventivi-form" method="post" data-preventivo-id="<?php echo $preventivo_id; ?>">

        <!-- Header -->
        <div class="mm-form-header">
            <h1>✏️ Modifica Preventivo #<?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
            <p>Modifica tutti i dettagli del preventivo</p>
            <div class="mm-status-badge-header mm-status-<?php echo esc_attr($preventivo['stato']); ?>">
                Stato: <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
            </div>
        </div>

        <!-- Form Body -->
        <div class="mm-form-body">

            <!-- Dati Cliente -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📋 Dati Cliente</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Preventivo <span class="required">*</span></label>
                        <input type="date" id="data_preventivo" name="data_preventivo"
                               value="<?php echo esc_attr($preventivo['data_preventivo']); ?>" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Categoria Evento <span class="required">*</span></label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">-- Seleziona categoria --</option>
                            <?php foreach ($categorie as $categoria) : ?>
                                <option value="<?php echo $categoria['id']; ?>"
                                    <?php selected($preventivo['categoria_id'], $categoria['id']); ?>>
                                    <?php echo esc_html($categoria['icona'] . ' ' . $categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Sposi / Cliente <span class="required">*</span></label>
                        <input type="text" id="sposi" name="sposi"
                               value="<?php echo esc_attr($preventivo['sposi']); ?>"
                               placeholder="Nome e cognome" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo esc_attr($preventivo['email']); ?>"
                               placeholder="email@esempio.it">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Telefono</label>
                        <input type="tel" id="telefono" name="telefono"
                               value="<?php echo esc_attr($preventivo['telefono']); ?>"
                               placeholder="333-7512343">
                    </div>
                </div>
            </div>

            <!-- Dati Evento -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📅 Dati Evento</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Evento <span class="required">*</span></label>
                        <input type="date" id="data_evento" name="data_evento"
                               value="<?php echo esc_attr($preventivo['data_evento']); ?>" required>
                    </div>
                    <div class="mm-form-group">
                        <label>Location</label>
                        <input type="text" id="location" name="location"
                               value="<?php echo esc_attr($preventivo['location']); ?>"
                               placeholder="Nome location">
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Tipo Evento</label>
                        <div class="mm-radio-group">
                            <div class="mm-radio-item">
                                <input type="radio" id="pranzo" name="tipo_evento" value="Pranzo"
                                       <?php checked($preventivo['tipo_evento'], 'Pranzo'); ?>>
                                <label for="pranzo">Pranzo</label>
                            </div>
                            <div class="mm-radio-item">
                                <input type="radio" id="cena" name="tipo_evento" value="Cena"
                                       <?php checked($preventivo['tipo_evento'], 'Cena'); ?>>
                                <label for="cena">Cena</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cerimonia (Rito) -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">💒 Rito</h2>
                <div class="mm-checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="violino" name="cerimonia[]" value="Violino"
                               <?php checked(is_array($cerimonia_db) && in_array('Violino', $cerimonia_db)); ?>>
                        <label for="violino">Violino</label>
                    </div>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="arpa" name="cerimonia[]" value="Arpa"
                               <?php checked(is_array($cerimonia_db) && in_array('Arpa', $cerimonia_db)); ?>>
                        <label for="arpa">Arpa</label>
                    </div>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="piano" name="cerimonia[]" value="Piano"
                               <?php checked(is_array($cerimonia_db) && in_array('Piano', $cerimonia_db)); ?>>
                        <label for="piano">Piano</label>
                    </div>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="altro_cerimonia" name="cerimonia[]" value="Altro"
                               <?php checked(is_array($cerimonia_db) && in_array('Altro', $cerimonia_db)); ?>>
                        <label for="altro_cerimonia">Altro</label>
                    </div>
                </div>
                <?php
                // Cerca il prezzo del rito nei servizi COMPLETI (non in quelli filtrati)
                $prezzo_rito = 0;
                foreach ($servizi_db_completi as $serv) {
                    // Cerca servizi che iniziano con "Rito"
                    if (strpos($serv['nome_servizio'], 'Rito') === 0) {
                        $prezzo_rito = floatval($serv['prezzo']);
                        break;
                    }
                }
                ?>
                <div class="mm-form-group" style="margin-top: 15px;">
                    <label for="prezzo_cerimonia">Prezzo Rito (€)</label>
                    <input type="number" id="prezzo_cerimonia" name="prezzo_cerimonia"
                           value="<?php echo $prezzo_rito; ?>"
                           placeholder="0.00" step="0.01" min="0"
                           style="max-width: 200px;">
                </div>
            </div>

            <!-- Servizi Extra -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">✨ Servizi Extra</h2>
                <div class="mm-checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <?php
                    $extras_disponibili = array('Rito', 'Accoglienza', 'Antipasti', 'Sala', 'Torta', 'Buffet f/d', 'After Party');
                    foreach ($extras_disponibili as $extra) :
                        $is_checked = is_array($servizi_extra_db) && in_array($extra, $servizi_extra_db);
                    ?>
                    <div class="mm-checkbox-item">
                        <input type="checkbox" id="extra_<?php echo sanitize_title($extra); ?>"
                               name="servizi_extra[]" value="<?php echo esc_attr($extra); ?>"
                               <?php checked($is_checked); ?>>
                        <label for="extra_<?php echo sanitize_title($extra); ?>"><?php echo esc_html($extra); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Servizi -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">🎵 Servizi Disponibili</h2>
                <p style="margin-bottom: 20px; color: #666;">Seleziona i servizi da includere nel preventivo</p>

                <div class="mm-services-list">
                    <div class="mm-services-header">
                        <span class="header-checkbox"></span>
                        <span>Servizio</span>
                        <div class="header-pricing">
                            <span class="price-label">Prezzo Base</span>
                            <span class="discount-label">Sconto (€)</span>
                        </div>
                    </div>

                    <?php foreach ($catalogo_servizi as $servizio) :
                        // Controlla se questo servizio è già selezionato
                        $is_selected = false;
                        $sconto_servizio = 0;
                        $prezzo_custom = 0;

                        if (is_array($servizi_db)) {
                            foreach ($servizi_db as $serv_db) {
                                // Confronto flessibile: il servizio del DB inizia con il nome del catalogo
                                // Es: "Strumento v Violino" inizia con "Strumento v"
                                if (strpos($serv_db['nome_servizio'], $servizio['nome_servizio']) === 0) {
                                    $is_selected = true;
                                    $sconto_servizio = isset($serv_db['sconto']) ? floatval($serv_db['sconto']) : 0;
                                    $prezzo_custom = floatval($serv_db['prezzo']);
                                    break;
                                }
                            }
                        }
                    ?>
                    <div class="mm-service-item">
                        <?php
                        // Usa il prezzo salvato se presente, altrimenti usa il prezzo di default
                        $prezzo_visualizzato = ($is_selected && $prezzo_custom > 0) ? $prezzo_custom : floatval($servizio['prezzo_default'] ?? 0);
                        ?>
                        <input type="checkbox"
                               class="service-checkbox"
                               data-service-id="<?php echo $servizio['id']; ?>"
                               data-service-name="<?php echo esc_attr($servizio['nome_servizio']); ?>"
                               data-service-price="<?php echo $prezzo_visualizzato; ?>"
                               <?php checked($is_selected); ?>>
                        <label><?php echo esc_html($servizio['nome_servizio']); ?></label>
                        <div class="mm-service-pricing">
                            <span class="service-price">€ <?php echo number_format($prezzo_visualizzato, 2, ',', '.'); ?></span>
                            <input type="number"
                                   class="service-discount"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0"
                                   value="<?php echo $sconto_servizio > 0 ? number_format($sconto_servizio, 2, '.', '') : ''; ?>"
                                   data-service-id="<?php echo $servizio['id']; ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Note e Acconti -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📝 Note e Acconti</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Note Aggiuntive</label>
                        <textarea id="note" name="note" rows="4"
                                  placeholder="Inserisci eventuali note o richieste speciali..."><?php echo esc_textarea($preventivo['note']); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h3 style="font-size: 16px; color: #e91e63; margin-bottom: 10px;">💳 Gestione Acconti</h3>
                    <div id="acconti-container">
                        <?php
                        // Carica acconti esistenti dal database
                        $acconti_esistenti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();

                        // Se non ci sono acconti nella nuova tabella, controlla i vecchi campi per retrocompatibilità
                        if (empty($acconti_esistenti) && !empty($preventivo['data_acconto']) && floatval($preventivo['importo_acconto']) > 0) {
                            $acconti_esistenti = array(array(
                                'data_acconto' => $preventivo['data_acconto'],
                                'importo_acconto' => $preventivo['importo_acconto']
                            ));
                        }

                        // Mostra tutti gli acconti esistenti
                        foreach ($acconti_esistenti as $acconto) :
                        ?>
                        <div class="acconto-item" style="display: flex; gap: 15px; margin-bottom: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #4caf50;">
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; color: #666;">Data Acconto</label>
                                <input type="date" name="acconti_data[]" class="acconto-data" value="<?php echo esc_attr($acconto['data_acconto']); ?>" style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px;">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; color: #666;">Importo (€)</label>
                                <input type="number" name="acconti_importo[]" class="acconto-importo" value="<?php echo floatval($acconto['importo_acconto']); ?>" placeholder="0.00" step="0.01" min="0" style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px;">
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <button type="button" class="rimuovi-acconto" style="padding: 8px 15px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">✕ Rimuovi</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="aggiungi-acconto" style="margin-top: 10px; padding: 10px 20px; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);">
                        ➕ Aggiungi Acconto
                    </button>
                    <div id="totale-acconti" style="margin-top: 15px; padding: 12px; background: #e8f5e9; border-radius: 8px; font-weight: 600; color: #2e7d32;">
                        Totale Acconti: € <span id="somma-acconti">0,00</span>
                    </div>
                </div>
            </div>

            <!-- Riepilogo Prezzi -->
            <div class="mm-form-section mm-price-summary">
                <h2 class="mm-section-title">💰 Riepilogo Prezzi</h2>

                <div class="mm-price-row">
                    <span>Totale Servizi:</span>
                    <strong id="totale-servizi">€ 0,00</strong>
                </div>

                <div class="mm-form-row" style="margin-top: 15px;">
                    <div class="mm-form-group">
                        <label>Sconto Fisso (€)</label>
                        <input type="number" id="sconto" name="sconto"
                               value="<?php echo floatval($preventivo['sconto']); ?>"
                               placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="mm-form-group">
                        <label>Sconto Percentuale (%)</label>
                        <input type="number" id="sconto_percentuale" name="sconto_percentuale"
                               value="<?php echo floatval($preventivo['sconto_percentuale']); ?>"
                               placeholder="0" step="0.1" min="0" max="100">
                    </div>
                </div>

                <div class="mm-price-row">
                    <span>Subtotale (dopo sconti):</span>
                    <strong id="subtotale">€ 0,00</strong>
                </div>

                <div class="mm-form-row" style="margin-top: 15px;">
                    <div class="mm-form-group">
                        <label>
                            <input type="checkbox" id="applica_enpals" name="applica_enpals" value="1"
                                   <?php checked($preventivo['applica_enpals'], 1); ?>>
                            Applica ENPALS (<?php echo esc_html(get_option('mm_preventivi_enpals_percentage', '33')); ?>%)
                        </label>
                    </div>
                    <div class="mm-form-group">
                        <label>
                            <input type="checkbox" id="applica_iva" name="applica_iva" value="1"
                                   <?php checked($preventivo['applica_iva'], 1); ?>>
                            Applica IVA (22%)
                        </label>
                    </div>
                </div>

                <div class="mm-price-row" id="row-enpals" style="display: none;">
                    <span>ENPALS (<?php echo esc_html(get_option('mm_preventivi_enpals_percentage', '33')); ?>%):</span>
                    <strong id="importo-enpals">€ 0,00</strong>
                </div>

                <div class="mm-price-row" id="row-iva" style="display: none;">
                    <span>IVA (22%):</span>
                    <strong id="importo-iva">€ 0,00</strong>
                </div>

                <div class="mm-price-row mm-total">
                    <span>TOTALE FINALE:</span>
                    <strong id="totale-finale">€ 0,00</strong>
                </div>
            </div>

            <!-- Cambio Stato -->
            <div class="mm-form-section" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <h2 class="mm-section-title">🎯 Stato Preventivo</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Cambia Stato</label>
                        <select id="nuovo_stato" name="stato">
                            <option value="bozza" <?php selected($preventivo['stato'], 'bozza'); ?>>Bozza</option>
                            <option value="attivo" <?php selected($preventivo['stato'], 'attivo'); ?>>Attivo</option>
                            <option value="accettato" <?php selected($preventivo['stato'], 'accettato'); ?>>Accettato</option>
                            <option value="rifiutato" <?php selected($preventivo['stato'], 'rifiutato'); ?>>Rifiutato</option>
                            <option value="completato" <?php selected($preventivo['stato'], 'completato'); ?>>Completato</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mm-form-actions">
                <button type="submit" class="mm-btn mm-btn-primary">
                    💾 Salva Modifiche
                </button>
                <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-btn mm-btn-secondary">
                    ← Annulla
                </a>
                <button type="button" id="btn-view-pdf" class="mm-btn mm-btn-preview">
                    📄 Visualizza PDF
                </button>
            </div>

        </div>

    </form>

</div>

<style>
.mm-status-badge-header {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    margin-top: 10px;
}

.mm-status-bozza {
    background: #f5f5f5;
    color: #666;
}

.mm-status-attivo {
    background: #fff3e0;
    color: #f57c00;
}

.mm-status-accettato {
    background: #e8f5e9;
    color: #2e7d32;
}

.mm-status-rifiutato {
    background: #ffebee;
    color: #c62828;
}

.mm-status-completato {
    background: #e3f2fd;
    color: #1565c0;
}

.mm-error-message {
    background: #ffebee;
    color: #c62828;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    margin: 40px 20px;
}

.mm-error-message h3 {
    margin: 0 0 15px 0;
    font-size: 24px;
}

.mm-error-message p {
    margin: 10px 0;
    font-size: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    const $form = $('#mm-edit-preventivo-form');
    const preventivoId = $form.data('preventivo-id');

    // Calcola totali all'avvio (con piccolo ritardo per assicurare che tutto sia caricato)
    setTimeout(function() {
        calculateTotals();
        calcolaTotaleAcconti();
    }, 100);

    // Eventi per ricalcolo
    $('.service-checkbox, .service-discount, #sconto, #sconto_percentuale, #applica_enpals, #applica_iva, #prezzo_cerimonia').on('change input', calculateTotals);

    // Funzione calcolo totali
    function calculateTotals() {
        let totaleServizi = 0;

        // Aggiungi prezzo rito se presente
        const prezzoRito = parseFloat($('#prezzo_cerimonia').val()) || 0;
        if (prezzoRito > 0) {
            totaleServizi += prezzoRito;
        }

        // Calcola totale servizi selezionati
        $('.service-checkbox:checked').each(function() {
            const $checkbox = $(this);
            const prezzo = parseFloat($checkbox.data('service-price')) || 0;
            const serviceId = $checkbox.data('service-id');
            const sconto = parseFloat($('.service-discount[data-service-id="' + serviceId + '"]').val()) || 0;
            totaleServizi += (prezzo - sconto);
        });

        $('#totale-servizi').text('€ ' + totaleServizi.toFixed(2).replace('.', ','));

        // Applica sconti
        const scontoFisso = parseFloat($('#sconto').val()) || 0;
        const scontoPercentuale = parseFloat($('#sconto_percentuale').val()) || 0;
        const scontoPerc = totaleServizi * (scontoPercentuale / 100);
        const subtotale = totaleServizi - scontoFisso - scontoPerc;

        $('#subtotale').text('€ ' + subtotale.toFixed(2).replace('.', ','));

        // ENPALS e IVA (calcolo corretto come nel backend)
        let enpalsCommittente = 0;
        let enpalsLavoratore = 0;
        let iva = 0;
        let imponibileIva = subtotale;

        if ($('#applica_enpals').is(':checked')) {
            // Calcola ENPALS committente (23.81%) e lavoratore (9.19%)
            const enpalsCommittentePerc = <?php echo floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81)); ?> / 100;
            const enpalsLavoratorePerc = <?php echo floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19)); ?> / 100;

            enpalsCommittente = subtotale * enpalsCommittentePerc;
            enpalsLavoratore = subtotale * enpalsLavoratorePerc;

            // Solo l'ENPALS committente viene aggiunto al totale
            imponibileIva = subtotale + enpalsCommittente;

            // Mostra il totale netto (committente - lavoratore) come display
            const enpalsNetto = enpalsCommittente;
            $('#row-enpals').show();
            $('#importo-enpals').text('€ ' + enpalsNetto.toFixed(2).replace('.', ','));
        } else {
            $('#row-enpals').hide();
        }

        if ($('#applica_iva').is(':checked')) {
            const ivaPerc = <?php echo floatval(get_option('mm_preventivi_iva_percentage', 22)); ?> / 100;
            iva = imponibileIva * ivaPerc;
            $('#row-iva').show();
            $('#importo-iva').text('€ ' + iva.toFixed(2).replace('.', ','));
        } else {
            $('#row-iva').hide();
        }

        const totale = imponibileIva + iva;
        $('#totale-finale').text('€ ' + totale.toFixed(2).replace('.', ','));
    }

    // Submit form
    $form.on('submit', function(e) {
        e.preventDefault();

        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('💾 Salvataggio...');

        // Raccogli servizi selezionati
        const servizi = [];

        // Aggiungi rito come servizio se ha un prezzo
        const prezzoRito = parseFloat($('#prezzo_cerimonia').val()) || 0;
        if (prezzoRito > 0) {
            servizi.push({
                nome_servizio: 'Rito',
                prezzo: prezzoRito,
                sconto: 0
            });
        }

        $('.service-checkbox:checked').each(function() {
            const $checkbox = $(this);
            const serviceId = $checkbox.data('service-id');
            const sconto = parseFloat($('.service-discount[data-service-id="' + serviceId + '"]').val()) || 0;

            servizi.push({
                nome_servizio: $checkbox.data('service-name'),
                prezzo: parseFloat($checkbox.data('service-price')),
                sconto: sconto
            });
        });

        // Raccogli cerimonia (rito)
        const cerimonia = [];
        $('input[name="cerimonia[]"]:checked').each(function() {
            cerimonia.push($(this).val());
        });

        // Raccogli servizi extra
        const serviziExtra = [];
        $('input[name="servizi_extra[]"]:checked').each(function() {
            serviziExtra.push($(this).val());
        });

        // Raccogli acconti multipli
        const accontiData = [];
        const accontiImporto = [];
        $('input[name="acconti_data[]"]').each(function(index) {
            const data = $(this).val();
            const importo = $('input[name="acconti_importo[]"]').eq(index).val();
            if (data && importo && parseFloat(importo) > 0) {
                accontiData.push(data);
                accontiImporto.push(parseFloat(importo));
            }
        });

        // Calcola ENPALS committente e lavoratore per il salvataggio
        const subtotale = parseFloat($('#subtotale').text().replace('€ ', '').replace(',', '.'));
        const enpalsCommittentePerc = <?php echo floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81)); ?> / 100;
        const enpalsLavoratorePerc = <?php echo floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19)); ?> / 100;
        const enpalsCommittente = $('#applica_enpals').is(':checked') ? (subtotale * enpalsCommittentePerc) : 0;
        const enpalsLavoratore = $('#applica_enpals').is(':checked') ? (subtotale * enpalsLavoratorePerc) : 0;

        // Prepara dati
        const formData = {
            action: 'mm_update_preventivo',
            nonce: mmPreventivi.nonce,
            preventivo_id: preventivoId,
            categoria_id: $('#categoria_id').val(),
            data_preventivo: $('#data_preventivo').val(),
            sposi: $('#sposi').val(),
            email: $('#email').val(),
            telefono: $('#telefono').val(),
            data_evento: $('#data_evento').val(),
            location: $('#location').val(),
            tipo_evento: $('input[name="tipo_evento"]:checked').val(),
            cerimonia: cerimonia,
            servizi_extra: serviziExtra,
            servizi: servizi,
            note: $('#note').val(),
            acconti_data: accontiData,
            acconti_importo: accontiImporto,
            sconto: parseFloat($('#sconto').val()) || 0,
            sconto_percentuale: parseFloat($('#sconto_percentuale').val()) || 0,
            applica_enpals: $('#applica_enpals').is(':checked') ? 1 : 0,
            applica_iva: $('#applica_iva').is(':checked') ? 1 : 0,
            enpals_committente: enpalsCommittente,
            enpals_lavoratore: enpalsLavoratore,
            stato: $('#nuovo_stato').val(),
            totale_servizi: parseFloat($('#totale-servizi').text().replace('€ ', '').replace(',', '.')),
            iva: parseFloat($('#importo-iva').text().replace('€ ', '').replace(',', '.')) || 0,
            totale: parseFloat($('#totale-finale').text().replace('€ ', '').replace(',', '.'))
        };

        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('✅ Preventivo aggiornato con successo!');
                    window.location.href = '<?php echo home_url('/lista-preventivi/'); ?>';
                } else {
                    alert('❌ Errore: ' + (response.data.message || 'Errore sconosciuto'));
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('❌ Errore di connessione. Riprova.');
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Visualizza PDF
    $('#btn-view-pdf').on('click', function(e) {
        e.preventDefault();
        const pdfNonce = '<?php echo wp_create_nonce("mm_preventivi_view_pdf"); ?>';
        const url = mmPreventivi.ajaxurl + '?action=mm_view_pdf&id=' + preventivoId + '&nonce=' + pdfNonce;
        window.open(url, '_blank');
    });

    // Gestione Acconti Multipli
    let accontoCounter = 0;

    // Aggiungi acconto
    $('#aggiungi-acconto').on('click', function() {
        const accontoHtml = `
            <div class="acconto-item" style="display: flex; gap: 15px; margin-bottom: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #4caf50;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; color: #666;">Data Acconto</label>
                    <input type="date" name="acconti_data[]" style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; color: #666;">Importo (€)</label>
                    <input type="number" name="acconti_importo[]" class="acconto-importo" placeholder="0.00" step="0.01" min="0" style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="button" class="rimuovi-acconto" style="padding: 8px 15px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">✕ Rimuovi</button>
                </div>
            </div>
        `;
        $('#acconti-container').append(accontoHtml);
        calcolaTotaleAcconti();
    });

    // Rimuovi acconto
    $(document).on('click', '.rimuovi-acconto', function() {
        $(this).closest('.acconto-item').remove();
        calcolaTotaleAcconti();
    });

    // Ricalcola totale acconti quando cambiano gli importi
    $(document).on('change input', '.acconto-importo, input[name="acconti_importo[]"]', function() {
        calcolaTotaleAcconti();
    });

    // Funzione per calcolare il totale degli acconti
    function calcolaTotaleAcconti() {
        let totale = 0;
        $('input[name="acconti_importo[]"]').each(function() {
            totale += parseFloat($(this).val()) || 0;
        });
        $('#somma-acconti').text(totale.toFixed(2).replace('.', ','));
    }

    // Calcola totale acconti all'avvio
    calcolaTotaleAcconti();
});
</script>
