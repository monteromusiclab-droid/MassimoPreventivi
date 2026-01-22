<?php
/**
 * Template: Assegnazioni Collaboratori Frontend
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

// Ottieni filtri
$filters = array();
if (isset($_GET['stato']) && !empty($_GET['stato'])) {
    $filters['stato'] = sanitize_text_field($_GET['stato']);
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize_text_field($_GET['search']);
}
if (isset($_GET['mostra_passati']) && $_GET['mostra_passati'] == '1') {
    $filters['mostra_passati'] = true;
}
// Filtro per collaboratore
$filtro_collaboratore_id = isset($_GET['collaboratore_id']) ? intval($_GET['collaboratore_id']) : 0;
if ($filtro_collaboratore_id > 0) {
    $filters['collaboratore_id'] = $filtro_collaboratore_id;
}

// Paginazione
$page = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$filters['page'] = $page;
$filters['per_page'] = 15;

$preventivi = MM_Database::get_preventivi_con_assegnazioni($filters);
$total_preventivi = MM_Database::count_preventivi_con_assegnazioni($filters);
$total_pages = ceil($total_preventivi / $filters['per_page']);

// Ottieni collaboratori attivi per la select
$collaboratori = MM_Database::get_collaboratori(array('attivo' => 1));
?>

<div class="mm-frontend-container">

    <!-- Navigation Bar -->
    <div class="mm-nav-bar">
        <div class="mm-nav-left">
            <span class="mm-user-info">
                <strong><?php echo esc_html($current_user->display_name); ?></strong>
            </span>
        </div>
        <div class="mm-nav-center">
            <a href="<?php echo home_url('/dashboard-preventivi/'); ?>" class="mm-nav-btn">
                Dashboard
            </a>
            <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-nav-btn">
                Tutti i Preventivi
            </a>
            <a href="<?php echo get_permalink(); ?>" class="mm-nav-btn mm-nav-btn-active">
                Assegnazioni
            </a>
            <a href="<?php echo home_url('/nuovo-preventivo/'); ?>" class="mm-nav-btn">
                + Nuovo Preventivo
            </a>
        </div>
        <div class="mm-nav-right">
            <a href="<?php echo MM_Auth::get_logout_url(); ?>" class="mm-nav-btn mm-nav-btn-logout">
                Esci
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="mm-page-header">
        <h1>Assegnazioni Collaboratori</h1>
        <p>Assegna musicisti e collaboratori agli eventi</p>
    </div>

    <!-- Filters -->
    <div class="mm-filters-card">
        <form method="get" class="mm-filters-form">
            <div class="mm-filter-group">
                <label for="search">Cerca</label>
                <input type="text"
                       id="search"
                       name="search"
                       placeholder="Cliente, numero, location..."
                       value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
            </div>

            <div class="mm-filter-group">
                <label for="stato">Stato</label>
                <select id="stato" name="stato">
                    <option value="">Tutti</option>
                    <option value="attivo" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'attivo'); ?>>Attivo</option>
                    <option value="accettato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'accettato'); ?>>Accettato</option>
                    <option value="completato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'completato'); ?>>Completato</option>
                </select>
            </div>

            <div class="mm-filter-group">
                <label for="collaboratore_id">Collaboratore</label>
                <select id="collaboratore_id" name="collaboratore_id">
                    <option value="">Tutti</option>
                    <?php foreach ($collaboratori as $coll) : ?>
                        <option value="<?php echo esc_attr($coll['id']); ?>"
                            <?php selected($filtro_collaboratore_id == $coll['id']); ?>>
                            <?php echo esc_html($coll['cognome'] . ' ' . $coll['nome'] . ' (' . $coll['mansione'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mm-filter-group">
                <label>
                    <input type="checkbox" name="mostra_passati" value="1"
                        <?php checked(isset($_GET['mostra_passati']) && $_GET['mostra_passati'] == '1'); ?>>
                    Mostra eventi passati
                </label>
            </div>

            <div class="mm-filter-actions">
                <button type="submit" class="mm-filter-btn mm-filter-btn-primary">Filtra</button>
                <a href="<?php echo get_permalink(); ?>" class="mm-filter-btn mm-filter-btn-secondary">Reset</a>
                <button type="button" id="mm-btn-export-pdf" class="mm-filter-btn mm-filter-btn-pdf">
                    📄 Esporta PDF
                </button>
            </div>
        </form>
    </div>

    <!-- Preventivi List -->
    <div class="mm-assegnazioni-list">
        <?php if (!empty($preventivi)) : ?>
            <?php foreach ($preventivi as $preventivo) :
                $data_evento = strtotime($preventivo['data_evento']);
                $is_past = $data_evento < strtotime('today');
                $is_soon = !$is_past && $data_evento <= strtotime('+7 days');
            ?>
                <div class="mm-assegnazione-card <?php echo $is_past ? 'mm-card-past' : ($is_soon ? 'mm-card-soon' : ''); ?>">
                    <div class="mm-assegnazione-header">
                        <div class="mm-assegnazione-info">
                            <div class="mm-assegnazione-numero">
                                #<?php echo esc_html($preventivo['numero_preventivo']); ?>
                                <span class="mm-status-badge mm-status-<?php echo esc_attr($preventivo['stato']); ?>">
                                    <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                                </span>
                            </div>
                            <div class="mm-assegnazione-cliente">
                                <strong><?php echo esc_html($preventivo['sposi']); ?></strong>
                            </div>
                            <div class="mm-assegnazione-details">
                                <span class="mm-detail-item mm-detail-date <?php echo $is_soon ? 'mm-highlight' : ''; ?>">
                                    <?php echo date('d/m/Y', $data_evento); ?>
                                    <?php if ($is_soon && !$is_past) : ?>
                                        <small>(tra <?php echo ceil(($data_evento - time()) / 86400); ?> giorni)</small>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($preventivo['location'])) : ?>
                                    <span class="mm-detail-item"><?php echo esc_html($preventivo['location']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($preventivo['categoria_nome'])) : ?>
                                    <span class="mm-detail-item mm-detail-categoria">
                                        <?php echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mm-assegnazione-actions">
                            <button type="button"
                                    class="mm-btn-assegna"
                                    data-preventivo-id="<?php echo esc_attr($preventivo['id']); ?>"
                                    data-preventivo-info="<?php echo esc_attr($preventivo['sposi'] . ' - ' . date('d/m/Y', $data_evento)); ?>">
                                + Assegna
                            </button>
                        </div>
                    </div>

                    <div class="mm-assegnazione-collaboratori" data-preventivo-id="<?php echo esc_attr($preventivo['id']); ?>">
                        <?php if (!empty($preventivo['collaboratori_assegnati'])) : ?>
                            <div class="mm-collaboratori-lista">
                                <?php
                                // Carica assegnazioni complete
                                $assegnazioni = MM_Database::get_assegnazioni_preventivo($preventivo['id']);
                                foreach ($assegnazioni as $ass) :
                                ?>
                                    <div class="mm-collaboratore-item" data-assegnazione-id="<?php echo esc_attr($ass['id']); ?>">
                                        <div class="mm-collaboratore-info">
                                            <span class="mm-collaboratore-nome">
                                                <?php echo esc_html($ass['cognome'] . ' ' . $ass['nome']); ?>
                                            </span>
                                            <span class="mm-collaboratore-mansione">
                                                <?php echo esc_html($ass['mansione']); ?>
                                            </span>
                                            <?php if (!empty($ass['ruolo_evento'])) : ?>
                                                <span class="mm-collaboratore-ruolo"><?php echo esc_html($ass['ruolo_evento']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mm-collaboratore-actions">
                                            <?php if (!empty($ass['email'])) :
                                                // Genera link Google Calendar
                                                $event_title = urlencode('Evento: ' . $preventivo['sposi']);
                                                $event_location = urlencode($preventivo['location'] ?? '');
                                                $event_details = urlencode(
                                                    "Cliente: " . $preventivo['sposi'] . "\n" .
                                                    "Ruolo: " . ($ass['ruolo_evento'] ?? $ass['mansione']) . "\n" .
                                                    ($ass['note'] ? "Note: " . $ass['note'] : '')
                                                );
                                                // Formato data per Google Calendar: YYYYMMDD
                                                $event_date = date('Ymd', strtotime($preventivo['data_evento']));
                                                $gcal_url = "https://calendar.google.com/calendar/render?action=TEMPLATE" .
                                                    "&text=" . $event_title .
                                                    "&dates=" . $event_date . "/" . $event_date .
                                                    "&details=" . $event_details .
                                                    "&location=" . $event_location .
                                                    "&add=" . urlencode($ass['email']);
                                            ?>
                                                <a href="<?php echo esc_url($gcal_url); ?>"
                                                   target="_blank"
                                                   class="mm-btn-icon mm-btn-calendar"
                                                   title="Aggiungi a Google Calendar">
                                                    GC
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($ass['whatsapp'])) : ?>
                                                <button type="button"
                                                        class="mm-btn-icon mm-btn-whatsapp"
                                                        data-assegnazione-id="<?php echo esc_attr($ass['id']); ?>"
                                                        data-preventivo-id="<?php echo esc_attr($preventivo['id']); ?>"
                                                        data-collaboratore-id="<?php echo esc_attr($ass['collaboratore_id']); ?>"
                                                        title="Invia WhatsApp">
                                                    WA
                                                </button>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="mm-btn-icon mm-btn-rimuovi"
                                                    data-assegnazione-id="<?php echo esc_attr($ass['id']); ?>"
                                                    title="Rimuovi">
                                                X
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="mm-no-collaboratori">
                                Nessun collaboratore assegnato
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Paginazione -->
            <?php if ($total_pages > 1) : ?>
                <div class="mm-pagination">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo add_query_arg('pag', $page - 1); ?>" class="mm-page-link">&laquo; Precedente</a>
                    <?php endif; ?>

                    <span class="mm-page-info">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>

                    <?php if ($page < $total_pages) : ?>
                        <a href="<?php echo add_query_arg('pag', $page + 1); ?>" class="mm-page-link">Successiva &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="mm-empty-state">
                <h3>Nessun evento trovato</h3>
                <p>Non ci sono eventi futuri da gestire.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal Assegna Collaboratore -->
<div id="mm-modal-assegna" class="mm-modal" style="display: none;">
    <div class="mm-modal-overlay"></div>
    <div class="mm-modal-container">
        <div class="mm-modal-header">
            <h2>Assegna Collaboratore</h2>
            <button type="button" class="mm-modal-close">X</button>
        </div>
        <div class="mm-modal-body">
            <p id="mm-modal-preventivo-info" style="margin-bottom: 20px; font-weight: 600; color: #e91e63;"></p>

            <form id="mm-form-assegna">
                <input type="hidden" id="assegna-preventivo-id" name="preventivo_id" value="">

                <div class="mm-form-group">
                    <label for="assegna-collaboratore">Collaboratore *</label>
                    <select id="assegna-collaboratore" name="collaboratore_id" required class="mm-form-control">
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($collaboratori as $coll) : ?>
                            <option value="<?php echo esc_attr($coll['id']); ?>">
                                <?php echo esc_html($coll['cognome'] . ' ' . $coll['nome'] . ' (' . $coll['mansione'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mm-form-group">
                    <label for="assegna-ruolo">Ruolo nell'evento</label>
                    <input type="text" id="assegna-ruolo" name="ruolo_evento" class="mm-form-control"
                           placeholder="Es: Cantante principale, DJ aperitivo...">
                </div>

                <div class="mm-form-group">
                    <label for="assegna-compenso">Compenso (EUR)</label>
                    <input type="number" id="assegna-compenso" name="compenso" class="mm-form-control"
                           step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="mm-form-group">
                    <label for="assegna-note">Note</label>
                    <textarea id="assegna-note" name="note" class="mm-form-control" rows="2"
                              placeholder="Note aggiuntive..."></textarea>
                </div>
            </form>
        </div>
        <div class="mm-modal-footer">
            <button type="button" class="mm-btn-secondary mm-modal-close">Annulla</button>
            <button type="button" class="mm-btn-primary" id="mm-btn-conferma-assegna">Assegna</button>
        </div>
    </div>
</div>

<style>
.mm-assegnazioni-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.mm-assegnazione-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.mm-assegnazione-card.mm-card-past {
    opacity: 0.7;
    background: #f9f9f9;
}

.mm-assegnazione-card.mm-card-soon {
    border-left: 4px solid #ff9800;
}

.mm-assegnazione-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 15px 20px;
    background: linear-gradient(135deg, rgba(233, 30, 99, 0.05) 0%, rgba(156, 39, 176, 0.05) 100%);
    border-bottom: 1px solid #eee;
}

.mm-assegnazione-numero {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mm-assegnazione-cliente {
    font-size: 18px;
    color: #333;
    margin-bottom: 8px;
}

.mm-assegnazione-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 13px;
    color: #666;
}

.mm-detail-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.mm-detail-date.mm-highlight {
    color: #ff9800;
    font-weight: 600;
}

.mm-detail-categoria {
    padding: 2px 8px;
    background: rgba(233, 30, 99, 0.1);
    border-radius: 10px;
    color: #e91e63;
    font-weight: 500;
}

.mm-btn-assegna {
    padding: 8px 16px;
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s;
}

.mm-btn-assegna:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3);
}

.mm-assegnazione-collaboratori {
    padding: 15px 20px;
}

.mm-collaboratori-lista {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.mm-collaboratore-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #f5f5f5;
    border-radius: 8px;
    font-size: 13px;
}

.mm-collaboratore-nome {
    font-weight: 600;
    color: #333;
}

.mm-collaboratore-mansione {
    color: #666;
    padding: 2px 8px;
    background: #e0e0e0;
    border-radius: 10px;
    font-size: 11px;
}

.mm-collaboratore-ruolo {
    color: #9c27b0;
    font-style: italic;
    font-size: 12px;
}

.mm-collaboratore-actions {
    display: flex;
    gap: 5px;
    margin-left: 5px;
}

.mm-btn-icon {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.mm-btn-calendar {
    background: #4285F4;
    color: white;
    text-decoration: none;
}

.mm-btn-calendar:hover {
    background: #3367D6;
    color: white;
}

.mm-btn-whatsapp {
    background: #25D366;
    color: white;
}

.mm-btn-whatsapp:hover {
    background: #128C7E;
}

.mm-btn-rimuovi {
    background: #f44336;
    color: white;
}

.mm-btn-rimuovi:hover {
    background: #d32f2f;
}

.mm-no-collaboratori {
    color: #999;
    font-style: italic;
    font-size: 13px;
}

/* Modal */
.mm-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 99999;
}

.mm-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
}

.mm-modal-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.mm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.mm-modal-header h2 {
    margin: 0;
    color: #e91e63;
    font-size: 20px;
}

.mm-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
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
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #eee;
}

.mm-form-group {
    margin-bottom: 15px;
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
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

.mm-form-control:focus {
    outline: none;
    border-color: #e91e63;
    box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
}

.mm-btn-primary {
    padding: 10px 20px;
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.mm-btn-primary:hover {
    opacity: 0.9;
}

.mm-btn-secondary {
    padding: 10px 20px;
    background: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.mm-btn-secondary:hover {
    background: #eee;
}

.mm-filter-btn-pdf {
    background: #4caf50 !important;
    color: white !important;
    border: none !important;
}

.mm-filter-btn-pdf:hover {
    background: #43a047 !important;
}

/* Pagination */
.mm-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
    padding: 20px;
}

.mm-page-link {
    padding: 10px 20px;
    background: #e91e63;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
}

.mm-page-link:hover {
    background: #c2185b;
}

.mm-page-info {
    color: #666;
}

/* Empty State */
.mm-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mm-empty-state h3 {
    color: #333;
    margin-bottom: 10px;
}

.mm-empty-state p {
    color: #666;
}

/* Status Badge */
.mm-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.mm-status-bozza { background: #e0e0e0; color: #666; }
.mm-status-attivo { background: #fff3e0; color: #f57c00; }
.mm-status-accettato { background: #e8f5e9; color: #388e3c; }
.mm-status-rifiutato { background: #ffebee; color: #d32f2f; }
.mm-status-completato { background: #e3f2fd; color: #1976d2; }

/* Responsive */
@media (max-width: 768px) {
    .mm-assegnazione-header {
        flex-direction: column;
        gap: 15px;
    }

    .mm-assegnazione-actions {
        width: 100%;
    }

    .mm-btn-assegna {
        width: 100%;
    }

    .mm-collaboratori-lista {
        flex-direction: column;
    }

    .mm-collaboratore-item {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('mm_preventivi_nonce'); ?>';
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    // Apri modal assegnazione
    $('.mm-btn-assegna').on('click', function() {
        const preventivoId = $(this).data('preventivo-id');
        const preventivoInfo = $(this).data('preventivo-info');

        $('#assegna-preventivo-id').val(preventivoId);
        $('#mm-modal-preventivo-info').text(preventivoInfo);
        $('#mm-form-assegna')[0].reset();
        $('#mm-modal-assegna').fadeIn(200);
    });

    // Chiudi modal
    $('.mm-modal-close, .mm-modal-overlay').on('click', function() {
        $('#mm-modal-assegna').fadeOut(200);
    });

    // ESC per chiudere
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#mm-modal-assegna').fadeOut(200);
        }
    });

    // Conferma assegnazione
    $('#mm-btn-conferma-assegna').on('click', function() {
        const btn = $(this);
        const preventivoId = $('#assegna-preventivo-id').val();
        const collaboratoreId = $('#assegna-collaboratore').val();

        if (!collaboratoreId) {
            alert('Seleziona un collaboratore');
            return;
        }

        btn.text('Assegnando...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'mm_frontend_assegna_collaboratore',
            nonce: nonce,
            preventivo_id: preventivoId,
            collaboratore_id: collaboratoreId,
            ruolo_evento: $('#assegna-ruolo').val(),
            compenso: $('#assegna-compenso').val(),
            note: $('#assegna-note').val()
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'Errore');
                btn.text('Assegna').prop('disabled', false);
            }
        }).fail(function() {
            alert('Errore di connessione');
            btn.text('Assegna').prop('disabled', false);
        });
    });

    // Rimuovi assegnazione
    $('.mm-btn-rimuovi').on('click', function() {
        if (!confirm('Rimuovere questo collaboratore dall\'evento?')) {
            return;
        }

        const assegnazioneId = $(this).data('assegnazione-id');
        const item = $(this).closest('.mm-collaboratore-item');

        $.post(ajaxurl, {
            action: 'mm_frontend_rimuovi_assegnazione',
            nonce: nonce,
            id: assegnazioneId
        }, function(response) {
            if (response.success) {
                item.fadeOut(300, function() {
                    $(this).remove();
                    // Se non ci sono piu' collaboratori, mostra messaggio
                    const container = item.closest('.mm-assegnazione-collaboratori');
                    if (container.find('.mm-collaboratore-item').length === 0) {
                        container.find('.mm-collaboratori-lista').remove();
                        container.html('<div class="mm-no-collaboratori">Nessun collaboratore assegnato</div>');
                    }
                });
            } else {
                alert(response.data.message || 'Errore');
            }
        });
    });

    // Invia WhatsApp
    $('.mm-btn-whatsapp').on('click', function() {
        const btn = $(this);
        const assegnazioneId = btn.data('assegnazione-id');
        const preventivoId = btn.data('preventivo-id');
        const collaboratoreId = btn.data('collaboratore-id');

        btn.text('...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'mm_frontend_invia_whatsapp_collaboratore',
            nonce: nonce,
            assegnazione_id: assegnazioneId,
            preventivo_id: preventivoId,
            collaboratore_id: collaboratoreId
        }, function(response) {
            btn.text('WA').prop('disabled', false);

            if (response.success) {
                window.open(response.data.link, '_blank');
            } else {
                alert(response.data.message || 'Errore');
            }
        }).fail(function() {
            btn.text('WA').prop('disabled', false);
            alert('Errore di connessione');
        });
    });

    // Esporta PDF assegnazioni
    $('#mm-btn-export-pdf').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();

        // Recupera i filtri attuali dalla URL
        const urlParams = new URLSearchParams(window.location.search);

        btn.html('⏳ Generazione...').prop('disabled', true);

        // Apri il PDF in una nuova finestra
        const pdfUrl = ajaxurl + '?action=mm_export_assegnazioni_pdf' +
            '&nonce=' + nonce +
            '&stato=' + (urlParams.get('stato') || '') +
            '&search=' + (urlParams.get('search') || '') +
            '&collaboratore_id=' + (urlParams.get('collaboratore_id') || '') +
            '&mostra_passati=' + (urlParams.get('mostra_passati') || '');

        window.open(pdfUrl, '_blank');

        setTimeout(function() {
            btn.html(originalText).prop('disabled', false);
        }, 1000);
    });
});
</script>
