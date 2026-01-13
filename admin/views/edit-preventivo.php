<?php
/**
 * Admin View: Modifica Preventivo
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>✏️ Modifica Preventivo #<?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
        <p>Modifica i dati del preventivo esistente</p>
    </div>

    <form method="post" action="" class="mm-edit-preventivo-form">
        <?php wp_nonce_field('mm_update_preventivo_' . $preventivo['id']); ?>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">👥 Dati Cliente</h3>

            <div class="mm-detail-grid">
                <div class="mm-setting-row">
                    <label for="sposi">Sposi / Cliente *</label>
                    <input type="text" id="sposi" name="sposi" value="<?php echo esc_attr($preventivo['sposi']); ?>" required>
                </div>

                <div class="mm-setting-row">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($preventivo['email']); ?>">
                </div>

                <div class="mm-setting-row">
                    <label for="telefono">Telefono</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo esc_attr($preventivo['telefono']); ?>">
                </div>

                <div class="mm-setting-row">
                    <label for="data_preventivo">Data Preventivo *</label>
                    <input type="date" id="data_preventivo" name="data_preventivo" value="<?php echo esc_attr($preventivo['data_preventivo']); ?>" required>
                </div>
            </div>
        </div>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">📅 Dettagli Evento</h3>

            <div class="mm-detail-grid">
                <div class="mm-setting-row">
                    <label for="data_evento">Data Evento *</label>
                    <input type="date" id="data_evento" name="data_evento" value="<?php echo esc_attr($preventivo['data_evento']); ?>" required>
                </div>

                <div class="mm-setting-row">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo esc_attr($preventivo['location']); ?>">
                </div>

                <div class="mm-setting-row">
                    <label for="categoria_id">Categoria Evento *</label>
                    <select id="categoria_id" name="categoria_id" required>
                        <option value="">-- Seleziona categoria --</option>
                        <?php
                        $tipi_evento = MM_Database::get_tipi_evento(true); // Solo attivi
                        foreach ($tipi_evento as $tipo) :
                        ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php selected($preventivo['categoria_id'], $tipo['id']); ?>>
                                <?php echo esc_html($tipo['icona'] . ' ' . $tipo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mm-setting-row">
                    <label for="tipo_evento">Momento Evento</label>
                    <select id="tipo_evento" name="tipo_evento">
                        <option value="">-- Seleziona --</option>
                        <option value="Pranzo" <?php selected($preventivo['tipo_evento'], 'Pranzo'); ?>>Pranzo</option>
                        <option value="Cena" <?php selected($preventivo['tipo_evento'], 'Cena'); ?>>Cena</option>
                        <option value="Giorno" <?php selected($preventivo['tipo_evento'], 'Giorno'); ?>>Giorno</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">💰 Servizi e Prezzi</h3>

            <div id="mm-edit-servizi-container">
                <?php
                if (!empty($preventivo['servizi'])) {
                    foreach ($preventivo['servizi'] as $index => $servizio) {
                        ?>
                        <div class="mm-service-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; margin-bottom: 15px; align-items: end;">
                            <div class="mm-setting-row" style="margin: 0;">
                                <label>Nome Servizio</label>
                                <input type="text" name="servizi[<?php echo $index; ?>][nome]" value="<?php echo esc_attr($servizio['nome_servizio']); ?>" required>
                            </div>
                            <div class="mm-setting-row" style="margin: 0;">
                                <label>Prezzo (€)</label>
                                <input type="number" name="servizi[<?php echo $index; ?>][prezzo]" value="<?php echo esc_attr($servizio['prezzo']); ?>" step="0.01" min="0">
                            </div>
                            <div class="mm-setting-row" style="margin: 0;">
                                <label>Sconto (€)</label>
                                <input type="number" name="servizi[<?php echo $index; ?>][sconto]" value="<?php echo isset($servizio['sconto']) ? esc_attr($servizio['sconto']) : 0; ?>" step="0.01" min="0">
                            </div>
                            <button type="button" class="mm-btn-icon delete mm-remove-service" style="margin-bottom: 0;">🗑️</button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>

            <button type="button" id="mm-add-service-row" class="mm-btn-admin-secondary" style="margin-top: 15px;">
                ➕ Aggiungi Servizio
            </button>

            <div class="mm-detail-grid" style="margin-top: 25px;">
                <div class="mm-setting-row">
                    <label for="totale_servizi">Totale Servizi (€)</label>
                    <input type="number" id="totale_servizi" name="totale_servizi" value="<?php echo esc_attr($preventivo['totale_servizi']); ?>" step="0.01" min="0" required>
                </div>

                <div class="mm-setting-row">
                    <label for="sconto">Sconto (€)</label>
                    <input type="number" id="sconto" name="sconto" value="<?php echo esc_attr($preventivo['sconto']); ?>" step="0.01" min="0">
                </div>

                <div class="mm-setting-row">
                    <label for="sconto_percentuale">Sconto (%)</label>
                    <input type="number" id="sconto_percentuale" name="sconto_percentuale" value="<?php echo esc_attr($preventivo['sconto_percentuale']); ?>" step="0.01" min="0" max="100">
                </div>
            </div>

            <div style="margin-top: 20px;">
                <label>
                    <input type="checkbox" name="applica_enpals" value="1" <?php checked($preventivo['applica_enpals'], 1); ?>>
                    Applica ENPALS (<?php echo esc_html(get_option('mm_preventivi_enpals_percentage', '33')); ?>%)
                </label>
                <br>
                <label>
                    <input type="checkbox" name="applica_iva" value="1" <?php checked($preventivo['applica_iva'], 1); ?>>
                    Applica IVA (22%)
                </label>
            </div>

            <div class="mm-detail-grid" style="margin-top: 20px;">
                <div class="mm-setting-row">
                    <label for="enpals">ENPALS (€)</label>
                    <input type="number" id="enpals" name="enpals" value="<?php echo esc_attr($preventivo['enpals']); ?>" step="0.01" min="0">
                </div>

                <div class="mm-setting-row">
                    <label for="iva">IVA (€)</label>
                    <input type="number" id="iva" name="iva" value="<?php echo esc_attr($preventivo['iva']); ?>" step="0.01" min="0">
                </div>

                <div class="mm-setting-row">
                    <label for="totale">Totale Finale (€) *</label>
                    <input type="number" id="totale" name="totale" value="<?php echo esc_attr($preventivo['totale']); ?>" step="0.01" min="0" required>
                </div>
            </div>
        </div>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">💵 Acconto</h3>

            <div class="mm-detail-grid">
                <div class="mm-setting-row">
                    <label for="data_acconto">Data Acconto</label>
                    <input type="date" id="data_acconto" name="data_acconto" value="<?php echo !empty($preventivo['data_acconto']) ? esc_attr($preventivo['data_acconto']) : ''; ?>">
                </div>

                <div class="mm-setting-row">
                    <label for="importo_acconto">Importo Acconto (€)</label>
                    <input type="number" id="importo_acconto" name="importo_acconto" value="<?php echo !empty($preventivo['importo_acconto']) ? esc_attr($preventivo['importo_acconto']) : ''; ?>" step="0.01" min="0">
                </div>
            </div>
        </div>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">📝 Note</h3>

            <div class="mm-setting-row">
                <textarea id="note" name="note" rows="5" style="width: 100%; resize: vertical;"><?php echo esc_textarea($preventivo['note']); ?></textarea>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px;">
            <button type="submit" name="mm_update_preventivo" class="mm-btn-admin-primary" style="font-size: 16px; padding: 12px 24px;">
                💾 Salva Modifiche
            </button>
            <a href="?page=mm-preventivi" class="mm-btn-admin-secondary" style="font-size: 16px; padding: 12px 24px; text-decoration: none; display: inline-block;">
                ← Torna alla lista
            </a>
        </div>

    </form>

</div>

<style>
.mm-edit-preventivo-form .mm-setting-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.mm-edit-preventivo-form .mm-setting-row input,
.mm-edit-preventivo-form .mm-setting-row select,
.mm-edit-preventivo-form .mm-setting-row textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.mm-edit-preventivo-form .mm-setting-row input:focus,
.mm-edit-preventivo-form .mm-setting-row select:focus,
.mm-edit-preventivo-form .mm-setting-row textarea:focus {
    outline: none;
    border-color: #e91e63;
}

.mm-service-row {
    padding: 15px;
    background: #f8f8f8;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}
</style>

<script>
jQuery(document).ready(function($) {
    let serviceIndex = <?php echo count($preventivo['servizi']); ?>;

    // Aggiungi servizio
    $('#mm-add-service-row').on('click', function() {
        const html = `
            <div class="mm-service-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; margin-bottom: 15px; align-items: end;">
                <div class="mm-setting-row" style="margin: 0;">
                    <label>Nome Servizio</label>
                    <input type="text" name="servizi[${serviceIndex}][nome]" required>
                </div>
                <div class="mm-setting-row" style="margin: 0;">
                    <label>Prezzo (€)</label>
                    <input type="number" name="servizi[${serviceIndex}][prezzo]" value="0" step="0.01" min="0">
                </div>
                <div class="mm-setting-row" style="margin: 0;">
                    <label>Sconto (€)</label>
                    <input type="number" name="servizi[${serviceIndex}][sconto]" value="0" step="0.01" min="0">
                </div>
                <button type="button" class="mm-btn-icon delete mm-remove-service" style="margin-bottom: 0;">🗑️</button>
            </div>
        `;
        $('#mm-edit-servizi-container').append(html);
        serviceIndex++;
    });

    // Rimuovi servizio
    $(document).on('click', '.mm-remove-service', function() {
        if (confirm('Sei sicuro di voler rimuovere questo servizio?')) {
            $(this).closest('.mm-service-row').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
});
</script>
