<?php
/**
 * Admin View: Nuovo Preventivo
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>➕ Nuovo Preventivo</h1>
        <p>Crea un nuovo preventivo dal pannello di amministrazione</p>
    </div>

    <!-- Include il form del frontend in stile admin -->
    <div class="mm-preventivi-container" style="max-width: 100%; margin: 0; padding: 0;">
        <?php
        // Usa il template del frontend
        include MM_PREVENTIVI_PLUGIN_DIR . 'templates/form-preventivo.php';
        ?>
    </div>

</div>

<style>
/* Adatta lo stile del form per l'admin - Migliorato */
.mm-admin-page .mm-preventivi-form {
    background: white;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
}

.mm-admin-page .mm-form-header {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    padding: 30px 40px;
}

.mm-admin-page .mm-form-header h1 {
    font-size: 28px;
    margin-bottom: 5px;
}

.mm-admin-page .mm-preventivi-container {
    margin-top: 20px;
}

.mm-admin-page .mm-form-body {
    padding: 35px 40px;
    background: #ffffff;
}

.mm-admin-page .mm-form-section {
    background: #fafafa;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #e0e0e0;
}

.mm-admin-page .mm-section-title {
    font-size: 18px;
    margin-bottom: 20px;
    color: #e91e63;
    font-weight: 700;
}

.mm-admin-page .mm-form-row {
    gap: 25px;
}

.mm-admin-page .mm-form-group label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.mm-admin-page .mm-form-group input,
.mm-admin-page .mm-form-group textarea,
.mm-admin-page .mm-form-group select {
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 14px;
}

.mm-admin-page .mm-form-group input:focus,
.mm-admin-page .mm-form-group textarea:focus,
.mm-admin-page .mm-form-group select:focus {
    border-color: #e91e63;
    box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
}

.mm-admin-page .mm-checkbox-group,
.mm-admin-page .mm-radio-group {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.mm-admin-page .mm-services-list {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.mm-admin-page .mm-service-item {
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 10px;
    background: #fafafa;
}

.mm-admin-page .mm-service-item:hover {
    background: #fff;
    border-color: #e91e63;
}

.mm-admin-page .mm-price-summary {
    background: linear-gradient(135deg, #f8bbd0 0%, #fce4ec 100%);
    border: 2px solid #e91e63;
    border-radius: 8px;
    padding: 25px;
}

.mm-admin-page .mm-btn {
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 6px;
}

.mm-admin-page .mm-btn-primary {
    background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
    box-shadow: 0 3px 10px rgba(233, 30, 99, 0.3);
}

.mm-admin-page .mm-btn-primary:hover {
    box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
}

.mm-admin-page .mm-btn-secondary {
    background: #f5f5f5;
    color: #333;
}

.mm-admin-page .mm-btn-secondary:hover {
    background: #e0e0e0;
}

.mm-admin-page .mm-btn-preview {
    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
}

.mm-admin-page .mm-form-actions {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #e0e0e0;
}

/* Radio buttons styling */
.mm-admin-page .mm-rito-selector {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.mm-admin-page .mm-radio-item {
    background: #fafafa;
    padding: 8px 16px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.mm-admin-page .mm-radio-item:has(input:checked) {
    background: #e3f2fd;
    border-color: #2196f3;
}

/* Services header */
.mm-admin-page .mm-services-header {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Override del successo per admin
    if (typeof MMPreventivi !== 'undefined') {
        const originalHandleSubmit = MMPreventivi.handleSubmit;

        MMPreventivi.handleSubmit = function(e) {
            e.preventDefault();

            // Remove previous messages
            $('.mm-message').remove();

            // Validate
            const errors = this.validateForm();
            if (errors.length > 0) {
                this.showMessage(errors.join('<br>'), 'error');
                return;
            }

            // Get totals
            const totals = $('#mm-preventivo-form').data('totals');

            // Prepare data
            const formData = {
                action: 'mm_save_preventivo',
                nonce: mmPreventivi.nonce,
                categoria_id: $('#categoria_id').val(),
                data_preventivo: $('#data_preventivo').val(),
                sposi: $('#sposi').val(),
                email: $('#email').val(),
                telefono: $('#telefono').val(),
                data_evento: $('#data_evento').val(),
                location: $('#location').val(),
                tipo_evento: $('input[name="tipo_evento"]:checked').val() || '',
                cerimonia: this.getSelectedCerimonie(),
                servizi_extra: this.getSelectedExtras(),
                note: $('#note').val(),
                totale_servizi: totals.totaleServizi,
                sconto: totals.sconto,
                sconto_percentuale: totals.scontoPercentuale,
                applica_enpals: totals.applicaEnpals,
                applica_iva: totals.applicaIva,
                enpals: totals.enpals,
                iva: totals.iva,
                totale: totals.totale,
                data_acconto: $('#data_acconto').val(),
                importo_acconto: $('#importo_acconto').val() || 0,
                servizi: this.getSelectedServices()
            };

            // Disable submit button
            const $submitBtn = $('.mm-btn-primary');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true)
                     .html('<span class="mm-loading"></span> Salvataggio...');

            // Send AJAX request
            $.ajax({
                url: mmPreventivi.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        // Redirect to view preventivo
                        window.location.href = 'admin.php?page=mm-preventivi&action=view&id=' + response.data.preventivo_id;
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Errore di connessione. Riprova.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        };
    }
});
</script>
