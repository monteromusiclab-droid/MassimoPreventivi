<?php
/**
 * Admin View: Tipi Evento
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>🎉 Gestione Tipi Evento</h1>
        <p>Gestisci i tipi di evento disponibili nel menu a tendina del form preventivi</p>
        <button type="button" class="mm-btn-admin-primary" id="mm-add-tipo-evento">
            ➕ Aggiungi Nuovo Tipo
        </button>
    </div>

    <?php if (empty($tipi_evento)) : ?>

        <div class="mm-table-container">
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">🎉</div>
                <h3>Nessun tipo evento trovato</h3>
                <p>Aggiungi i tipi di evento che appariranno nel menu a tendina del form.</p>
            </div>
        </div>

    <?php else : ?>

        <div class="mm-table-container">
            <table class="mm-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Icona</th>
                        <th>Nome Tipo Evento</th>
                        <th style="width: 100px;">Ordinamento</th>
                        <th style="width: 80px;">Attivo</th>
                        <th style="width: 150px;">Azioni</th>
                    </tr>
                </thead>
                <tbody id="mm-tipi-evento-sortable">
                    <?php foreach ($tipi_evento as $tipo) : ?>
                    <tr data-id="<?php echo esc_attr($tipo['id']); ?>">
                        <td style="text-align: center; font-size: 24px;">
                            <?php echo esc_html($tipo['icona']); ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($tipo['nome']); ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <?php echo esc_html($tipo['ordinamento']); ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($tipo['attivo'] == 1) : ?>
                                <span class="mm-status-badge attivo">Attivo</span>
                            <?php else : ?>
                                <span class="mm-status-badge rifiutato">Disattivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="mm-actions">
                                <button type="button"
                                        class="mm-btn-icon edit mm-btn-edit-tipo"
                                        data-id="<?php echo esc_attr($tipo['id']); ?>"
                                        data-nome="<?php echo esc_attr($tipo['nome']); ?>"
                                        data-icona="<?php echo esc_attr($tipo['icona']); ?>"
                                        data-ordinamento="<?php echo esc_attr($tipo['ordinamento']); ?>"
                                        data-attivo="<?php echo esc_attr($tipo['attivo']); ?>"
                                        title="Modifica">
                                    ✏️
                                </button>
                                <button type="button"
                                        class="mm-btn-icon delete mm-btn-delete-tipo"
                                        data-id="<?php echo esc_attr($tipo['id']); ?>"
                                        data-nome="<?php echo esc_attr($tipo['nome']); ?>"
                                        title="Elimina">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <p style="margin: 0; color: #666; font-size: 13px;">
                <strong>💡 Suggerimento:</strong> Questi tipi di evento appariranno nel menu a tendina del form di creazione preventivo e verranno visualizzati in evidenza nel PDF.
                Puoi cambiare l'ordine modificando il valore "Ordinamento".
            </p>
        </div>

    <?php endif; ?>

</div>

<!-- Modal Aggiungi/Modifica Tipo Evento -->
<div id="mm-tipo-evento-modal" style="display: none;">
    <div class="mm-modal-overlay"></div>
    <div class="mm-modal-container">
        <div class="mm-modal-header">
            <h2 id="mm-modal-title">Aggiungi Tipo Evento</h2>
            <button type="button" class="mm-modal-close">✕</button>
        </div>
        <div class="mm-modal-body">
            <form id="mm-tipo-evento-form">
                <input type="hidden" id="tipo-id" name="tipo_id" value="">

                <div class="mm-form-group">
                    <label for="tipo-nome">Nome Tipo Evento *</label>
                    <input type="text" id="tipo-nome" name="nome" required class="mm-form-control">
                    <small>Es: Matrimonio, Compleanno, Laurea, ecc.</small>
                </div>

                <div class="mm-form-group">
                    <label for="tipo-icona">Icona Emoji</label>
                    <input type="text" id="tipo-icona" name="icona" maxlength="10" class="mm-form-control" placeholder="🎉">
                    <small>Inserisci un'emoji rappresentativa (es: 💒 per Matrimonio, 🎂 per Compleanno)</small>
                </div>

                <div class="mm-form-group">
                    <label for="tipo-ordinamento">Ordinamento</label>
                    <input type="number" id="tipo-ordinamento" name="ordinamento" value="0" class="mm-form-control">
                    <small>Numero che definisce l'ordine di visualizzazione (più basso = prima posizione)</small>
                </div>

                <div class="mm-form-group">
                    <label>
                        <input type="checkbox" id="tipo-attivo" name="attivo" checked>
                        Tipo Evento Attivo
                    </label>
                    <small>I tipi disattivati non appariranno nel menu a tendina</small>
                </div>
            </form>
        </div>
        <div class="mm-modal-footer">
            <button type="button" class="mm-btn-admin-secondary mm-modal-close">Annulla</button>
            <button type="button" class="mm-btn-admin-primary" id="mm-save-tipo-evento">Salva</button>
        </div>
    </div>
</div>

<style>
.mm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
}

.mm-modal-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    z-index: 100001;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.mm-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mm-modal-header h2 {
    margin: 0;
    color: #e91e63;
}

.mm-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mm-modal-close:hover {
    color: #e91e63;
}

.mm-modal-body {
    padding: 20px;
}

.mm-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.mm-form-group {
    margin-bottom: 20px;
}

.mm-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.mm-form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.mm-form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Open modal per nuovo tipo evento
    $('#mm-add-tipo-evento').on('click', function() {
        $('#mm-modal-title').text('Aggiungi Tipo Evento');
        $('#mm-tipo-evento-form')[0].reset();
        $('#tipo-id').val('');
        $('#tipo-attivo').prop('checked', true);
        $('#mm-tipo-evento-modal').fadeIn(200);
    });

    // Open modal per modifica
    $('.mm-btn-edit-tipo').on('click', function() {
        const id = $(this).data('id');
        const nome = $(this).data('nome');
        const icona = $(this).data('icona');
        const ordinamento = $(this).data('ordinamento');
        const attivo = $(this).data('attivo');

        $('#mm-modal-title').text('Modifica Tipo Evento');
        $('#tipo-id').val(id);
        $('#tipo-nome').val(nome);
        $('#tipo-icona').val(icona);
        $('#tipo-ordinamento').val(ordinamento);
        $('#tipo-attivo').prop('checked', attivo == 1);
        $('#mm-tipo-evento-modal').fadeIn(200);
    });

    // Close modal
    $('.mm-modal-close').on('click', function() {
        $('#mm-tipo-evento-modal').fadeOut(200);
    });

    // Click overlay per chiudere
    $('.mm-modal-overlay').on('click', function() {
        $('#mm-tipo-evento-modal').fadeOut(200);
    });

    // Salva tipo evento
    $('#mm-save-tipo-evento').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.text('Salvataggio...').prop('disabled', true);

        const data = {
            action: 'mm_save_tipo_evento',
            nonce: mmPreventiviAdmin.nonce,
            tipo_id: $('#tipo-id').val(),
            nome: $('#tipo-nome').val(),
            icona: $('#tipo-icona').val() || '🎉',
            ordinamento: $('#tipo-ordinamento').val() || 0,
            attivo: $('#tipo-attivo').is(':checked') ? 1 : 0
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'Errore nel salvataggio');
                btn.text(originalText).prop('disabled', false);
            }
        }).fail(function() {
            alert('Errore di connessione');
            btn.text(originalText).prop('disabled', false);
        });
    });

    // Elimina tipo evento
    $('.mm-btn-delete-tipo').on('click', function() {
        const id = $(this).data('id');
        const nome = $(this).data('nome');

        if (!confirm(`Sei sicuro di voler eliminare il tipo evento "${nome}"?`)) {
            return;
        }

        const data = {
            action: 'mm_delete_tipo_evento',
            nonce: mmPreventiviAdmin.nonce,
            id: id
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'Errore nell\'eliminazione');
            }
        });
    });
});
</script>
