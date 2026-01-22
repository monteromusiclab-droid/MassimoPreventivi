<?php
/**
 * Admin View: Collaboratori
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">

    <div class="mm-admin-header">
        <h1>Gestione Collaboratori</h1>
        <p>Gestisci i tuoi collaboratori (musicisti, cantanti, dj, ecc.) da assegnare agli eventi</p>
        <button type="button" class="mm-btn-admin-primary" id="mm-add-collaboratore">
            + Aggiungi Nuovo Collaboratore
        </button>
    </div>

    <?php if (empty($collaboratori)) : ?>

        <div class="mm-table-container">
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">🎵</div>
                <h3>Nessun collaboratore trovato</h3>
                <p>Aggiungi i collaboratori che potrai assegnare ai tuoi eventi.</p>
            </div>
        </div>

    <?php else : ?>

        <div class="mm-table-container">
            <table class="mm-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Mansione</th>
                        <th>Email (Google Calendar)</th>
                        <th>WhatsApp</th>
                        <th style="width: 80px;">Stato</th>
                        <th style="width: 150px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collaboratori as $collaboratore) : ?>
                    <tr data-id="<?php echo esc_attr($collaboratore['id']); ?>">
                        <td>
                            <strong><?php echo esc_html($collaboratore['cognome'] . ' ' . $collaboratore['nome']); ?></strong>
                        </td>
                        <td>
                            <span class="mm-mansione-badge"><?php echo esc_html($collaboratore['mansione']); ?></span>
                        </td>
                        <td>
                            <?php if (!empty($collaboratore['email'])) : ?>
                                <a href="mailto:<?php echo esc_attr($collaboratore['email']); ?>">
                                    <?php echo esc_html($collaboratore['email']); ?>
                                </a>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($collaboratore['whatsapp'])) : ?>
                                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $collaboratore['whatsapp'])); ?>" target="_blank" class="mm-whatsapp-link">
                                    <?php echo esc_html($collaboratore['whatsapp']); ?>
                                </a>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($collaboratore['attivo'] == 1) : ?>
                                <span class="mm-status-badge attivo">Attivo</span>
                            <?php else : ?>
                                <span class="mm-status-badge rifiutato">Disattivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="mm-actions">
                                <button type="button"
                                        class="mm-btn-icon edit mm-btn-edit-collaboratore"
                                        data-id="<?php echo esc_attr($collaboratore['id']); ?>"
                                        title="Modifica">
                                    ✏️
                                </button>
                                <button type="button"
                                        class="mm-btn-icon delete mm-btn-delete-collaboratore"
                                        data-id="<?php echo esc_attr($collaboratore['id']); ?>"
                                        data-nome="<?php echo esc_attr($collaboratore['cognome'] . ' ' . $collaboratore['nome']); ?>"
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
                <strong>Suggerimento:</strong> I collaboratori attivi potranno essere assegnati agli eventi dalla pagina "Assegnazioni Collaboratori".
                L'email viene utilizzata per inviti Google Calendar, il numero WhatsApp per inviare promemoria.
            </p>
        </div>

    <?php endif; ?>

</div>

<!-- Modal Aggiungi/Modifica Collaboratore -->
<div id="mm-collaboratore-modal" style="display: none;">
    <div class="mm-modal-overlay"></div>
    <div class="mm-modal-container">
        <div class="mm-modal-header">
            <h2 id="mm-modal-title">Aggiungi Collaboratore</h2>
            <button type="button" class="mm-modal-close">✕</button>
        </div>
        <div class="mm-modal-body">
            <form id="mm-collaboratore-form">
                <input type="hidden" id="collaboratore-id" name="collaboratore_id" value="">

                <div class="mm-form-row">
                    <div class="mm-form-group mm-form-half">
                        <label for="collaboratore-nome">Nome *</label>
                        <input type="text" id="collaboratore-nome" name="nome" required class="mm-form-control">
                    </div>
                    <div class="mm-form-group mm-form-half">
                        <label for="collaboratore-cognome">Cognome *</label>
                        <input type="text" id="collaboratore-cognome" name="cognome" required class="mm-form-control">
                    </div>
                </div>

                <div class="mm-form-group">
                    <label for="collaboratore-mansione">Mansione *</label>
                    <input type="text" id="collaboratore-mansione" name="mansione" required class="mm-form-control"
                           list="mansioni-list" placeholder="Es: Cantante, Violinista, Sassofonista, DJ...">
                    <datalist id="mansioni-list">
                        <option value="Cantante">
                        <option value="Violinista">
                        <option value="Sassofonista">
                        <option value="Pianista">
                        <option value="Chitarrista">
                        <option value="DJ">
                        <option value="Batterista">
                        <option value="Bassista">
                        <option value="Trombettista">
                        <option value="Flautista">
                        <option value="Fotografo">
                        <option value="Videomaker">
                        <?php foreach ($mansioni as $mansione) : ?>
                            <option value="<?php echo esc_attr($mansione); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small>Seleziona una mansione esistente o inserisci una nuova</small>
                </div>

                <div class="mm-form-group">
                    <label for="collaboratore-email">Email (Google Calendar)</label>
                    <input type="email" id="collaboratore-email" name="email" class="mm-form-control"
                           placeholder="email@esempio.com">
                    <small>L'email verra' utilizzata per inviare inviti Google Calendar</small>
                </div>

                <div class="mm-form-group">
                    <label for="collaboratore-whatsapp">Numero WhatsApp</label>
                    <input type="tel" id="collaboratore-whatsapp" name="whatsapp" class="mm-form-control"
                           placeholder="+39 333 1234567">
                    <small>Formato internazionale consigliato (es: +39 333 1234567)</small>
                </div>

                <div class="mm-form-group">
                    <label for="collaboratore-note">Note</label>
                    <textarea id="collaboratore-note" name="note" class="mm-form-control" rows="3"
                              placeholder="Note aggiuntive sul collaboratore..."></textarea>
                </div>

                <div class="mm-form-group">
                    <label>
                        <input type="checkbox" id="collaboratore-attivo" name="attivo" checked>
                        Collaboratore Attivo
                    </label>
                    <small>I collaboratori disattivati non appariranno nella lista di assegnazione</small>
                </div>
            </form>
        </div>
        <div class="mm-modal-footer">
            <button type="button" class="mm-btn-admin-secondary mm-modal-close">Annulla</button>
            <button type="button" class="mm-btn-admin-primary" id="mm-save-collaboratore">Salva</button>
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

.mm-form-row {
    display: flex;
    gap: 15px;
}

.mm-form-half {
    flex: 1;
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
    box-sizing: border-box;
}

.mm-form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.mm-mansione-badge {
    display: inline-block;
    padding: 4px 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.mm-whatsapp-link {
    color: #25D366;
    text-decoration: none;
    font-weight: 500;
}

.mm-whatsapp-link:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .mm-form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Open modal per nuovo collaboratore
    $('#mm-add-collaboratore').on('click', function() {
        $('#mm-modal-title').text('Aggiungi Collaboratore');
        $('#mm-collaboratore-form')[0].reset();
        $('#collaboratore-id').val('');
        $('#collaboratore-attivo').prop('checked', true);
        $('#mm-collaboratore-modal').fadeIn(200);
    });

    // Open modal per modifica
    $('.mm-btn-edit-collaboratore').on('click', function() {
        const id = $(this).data('id');
        const btn = $(this);

        btn.text('...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'mm_get_collaboratore',
            nonce: mmPreventiviAdmin.nonce,
            id: id
        }, function(response) {
            btn.text('✏️').prop('disabled', false);

            if (response.success) {
                const c = response.data.collaboratore;
                $('#mm-modal-title').text('Modifica Collaboratore');
                $('#collaboratore-id').val(c.id);
                $('#collaboratore-nome').val(c.nome);
                $('#collaboratore-cognome').val(c.cognome);
                $('#collaboratore-mansione').val(c.mansione);
                $('#collaboratore-email').val(c.email || '');
                $('#collaboratore-whatsapp').val(c.whatsapp || '');
                $('#collaboratore-note').val(c.note || '');
                $('#collaboratore-attivo').prop('checked', c.attivo == 1);
                $('#mm-collaboratore-modal').fadeIn(200);
            } else {
                alert(response.data.message || 'Errore nel caricamento');
            }
        });
    });

    // Close modal
    $('.mm-modal-close').on('click', function() {
        $('#mm-collaboratore-modal').fadeOut(200);
    });

    // Click overlay per chiudere
    $('.mm-modal-overlay').on('click', function() {
        $('#mm-collaboratore-modal').fadeOut(200);
    });

    // Salva collaboratore
    $('#mm-save-collaboratore').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.text('Salvataggio...').prop('disabled', true);

        const data = {
            action: 'mm_save_collaboratore',
            nonce: mmPreventiviAdmin.nonce,
            collaboratore_id: $('#collaboratore-id').val(),
            nome: $('#collaboratore-nome').val(),
            cognome: $('#collaboratore-cognome').val(),
            mansione: $('#collaboratore-mansione').val(),
            email: $('#collaboratore-email').val(),
            whatsapp: $('#collaboratore-whatsapp').val(),
            note: $('#collaboratore-note').val(),
            attivo: $('#collaboratore-attivo').is(':checked') ? 1 : 0
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

    // Elimina collaboratore
    $('.mm-btn-delete-collaboratore').on('click', function() {
        const id = $(this).data('id');
        const nome = $(this).data('nome');

        if (!confirm('Sei sicuro di voler eliminare il collaboratore "' + nome + '"?\nVerranno rimosse anche tutte le sue assegnazioni.')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'mm_delete_collaboratore',
            nonce: mmPreventiviAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'Errore nell\'eliminazione');
            }
        });
    });

    // Chiudi modal con ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#mm-collaboratore-modal').fadeOut(200);
        }
    });
});
</script>
