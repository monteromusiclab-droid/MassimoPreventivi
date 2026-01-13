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
$servizi_db = isset($preventivo['servizi']) && is_array($preventivo['servizi']) ? $preventivo['servizi'] : array();

// Cerimonia e servizi_extra sono JSON encoded
$cerimonia_db = isset($preventivo['cerimonia']) ? (is_string($preventivo['cerimonia']) ? json_decode($preventivo['cerimonia'], true) : $preventivo['cerimonia']) : array();
$servizi_extra_db = isset($preventivo['servizi_extra']) ? (is_string($preventivo['servizi_extra']) ? json_decode($preventivo['servizi_extra'], true) : $preventivo['servizi_extra']) : array();

// Carica catalogo servizi
$catalogo_servizi = MM_Database::get_catalogo_servizi(array('attivo' => 1));

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
                        if (is_array($servizi_db)) {
                            foreach ($servizi_db as $serv_db) {
                                if ($serv_db['nome_servizio'] == $servizio['nome_servizio']) {
                                    $is_selected = true;
                                    $sconto_servizio = isset($serv_db['sconto']) ? floatval($serv_db['sconto']) : 0;
                                    break;
                                }
                            }
                        }
                    ?>
                    <div class="mm-service-item">
                        <input type="checkbox"
                               class="service-checkbox"
                               data-service-id="<?php echo $servizio['id']; ?>"
                               data-service-name="<?php echo esc_attr($servizio['nome_servizio']); ?>"
                               data-service-price="<?php echo floatval($servizio['prezzo_default'] ?? 0); ?>"
                               <?php checked($is_selected); ?>>
                        <label><?php echo esc_html($servizio['nome_servizio']); ?></label>
                        <div class="mm-service-pricing">
                            <span class="service-price">€ <?php echo number_format(floatval($servizio['prezzo_default'] ?? 0), 2, ',', '.'); ?></span>
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

            <!-- Note e Acconto -->
            <div class="mm-form-section">
                <h2 class="mm-section-title">📝 Note e Acconto</h2>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Note Aggiuntive</label>
                        <textarea id="note" name="note" rows="4"
                                  placeholder="Inserisci eventuali note o richieste speciali..."><?php echo esc_textarea($preventivo['note']); ?></textarea>
                    </div>
                </div>
                <div class="mm-form-row">
                    <div class="mm-form-group">
                        <label>Data Acconto</label>
                        <input type="date" id="data_acconto" name="data_acconto"
                               value="<?php echo esc_attr($preventivo['data_acconto']); ?>">
                    </div>
                    <div class="mm-form-group">
                        <label>Importo Acconto (€)</label>
                        <input type="number" id="importo_acconto" name="importo_acconto"
                               value="<?php echo floatval($preventivo['importo_acconto']); ?>"
                               placeholder="0.00" step="0.01" min="0">
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

    // Calcola totali all'avvio
    calculateTotals();

    // Eventi per ricalcolo
    $('.service-checkbox, .service-discount, #sconto, #sconto_percentuale, #applica_enpals, #applica_iva').on('change input', calculateTotals);

    // Funzione calcolo totali
    function calculateTotals() {
        let totaleServizi = 0;

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

        // ENPALS e IVA
        let enpals = 0;
        let iva = 0;
        let totale = subtotale;

        if ($('#applica_enpals').is(':checked')) {
            const enpalsPercentage = <?php echo floatval(get_option('mm_preventivi_enpals_percentage', 33)); ?> / 100;
            enpals = subtotale * enpalsPercentage;
            totale += enpals;
            $('#row-enpals').show();
            $('#importo-enpals').text('€ ' + enpals.toFixed(2).replace('.', ','));
        } else {
            $('#row-enpals').hide();
        }

        if ($('#applica_iva').is(':checked')) {
            iva = subtotale * 0.22;
            totale += iva;
            $('#row-iva').show();
            $('#importo-iva').text('€ ' + iva.toFixed(2).replace('.', ','));
        } else {
            $('#row-iva').hide();
        }

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
        $('.service-checkbox:checked').each(function() {
            const $checkbox = $(this);
            const serviceId = $checkbox.data('service-id');
            const sconto = parseFloat($('.service-discount[data-service-id="' + serviceId + '"]').val()) || 0;

            servizi.push({
                nome: $checkbox.data('service-name'),
                prezzo: parseFloat($checkbox.data('service-price')),
                sconto: sconto
            });
        });

        // Prepara dati
        const formData = {
            action: 'mm_update_preventivo',
            nonce: mmPreventivi.nonce,
            preventivo_id: preventivoId,
            data_preventivo: $('#data_preventivo').val(),
            sposi: $('#sposi').val(),
            email: $('#email').val(),
            telefono: $('#telefono').val(),
            data_evento: $('#data_evento').val(),
            location: $('#location').val(),
            tipo_evento: $('input[name="tipo_evento"]:checked').val(),
            servizi: servizi,
            note: $('#note').val(),
            data_acconto: $('#data_acconto').val(),
            importo_acconto: parseFloat($('#importo_acconto').val()) || 0,
            sconto: parseFloat($('#sconto').val()) || 0,
            sconto_percentuale: parseFloat($('#sconto_percentuale').val()) || 0,
            applica_enpals: $('#applica_enpals').is(':checked') ? 1 : 0,
            applica_iva: $('#applica_iva').is(':checked') ? 1 : 0,
            stato: $('#nuovo_stato').val(),
            totale_servizi: parseFloat($('#totale-servizi').text().replace('€ ', '').replace(',', '.')),
            enpals: parseFloat($('#importo-enpals').text().replace('€ ', '').replace(',', '.')) || 0,
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
});
</script>
