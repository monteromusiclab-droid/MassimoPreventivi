<?php
/**
 * Template: Lista Preventivi Frontend
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
if (isset($_GET['categoria_id']) && !empty($_GET['categoria_id'])) {
    $filters['categoria_id'] = intval($_GET['categoria_id']);
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize_text_field($_GET['search']);
}
if (isset($_GET['order_by']) && !empty($_GET['order_by'])) {
    $filters['order_by'] = sanitize_text_field($_GET['order_by']);
}

$preventivi = MM_Database::get_all_preventivi($filters);
$stats = MM_Database::get_statistics();
$categorie = MM_Database::get_tipi_evento(true); // Usa tipi evento invece di categorie
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
            <a href="<?php echo home_url('/dashboard-preventivi/'); ?>" class="mm-nav-btn">
                📋 Dashboard
            </a>
            <a href="<?php echo get_permalink(); ?>" class="mm-nav-btn mm-nav-btn-active">
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

    <!-- Header -->
    <div class="mm-page-header">
        <h1>📊 Gestione Preventivi</h1>
        <p>Visualizza e gestisci tutti i tuoi preventivi</p>
    </div>

    <!-- Quick Stats (Cliccabili) -->
    <div class="mm-quick-stats">
        <a href="<?php echo remove_query_arg('stato'); ?>" class="mm-quick-stat mm-quick-stat-link" style="text-decoration: none; color: inherit;">
            <div class="mm-quick-stat-value"><?php echo number_format($stats['totale_preventivi']); ?></div>
            <div class="mm-quick-stat-label">Totali</div>
        </a>
        <a href="<?php echo add_query_arg('stato', 'attivo'); ?>" class="mm-quick-stat mm-quick-stat-pending mm-quick-stat-link" style="text-decoration: none; color: inherit;">
            <div class="mm-quick-stat-value"><?php echo number_format($stats['preventivi_attivi']); ?></div>
            <div class="mm-quick-stat-label">Attivi</div>
        </a>
        <a href="<?php echo add_query_arg('stato', 'accettato'); ?>" class="mm-quick-stat mm-quick-stat-success mm-quick-stat-link" style="text-decoration: none; color: inherit;">
            <div class="mm-quick-stat-value"><?php echo number_format($stats['preventivi_accettati']); ?></div>
            <div class="mm-quick-stat-label">Accettati</div>
        </a>
        <div class="mm-quick-stat mm-quick-stat-warning">
            <div class="mm-quick-stat-value">€ <?php echo number_format($stats['totale_fatturato'], 0, ',', '.'); ?></div>
            <div class="mm-quick-stat-label">Fatturato</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mm-filters-card">
        <form method="get" class="mm-filters-form">
            <div class="mm-filter-group">
                <label for="search">🔍 Cerca</label>
                <input type="text"
                       id="search"
                       name="search"
                       placeholder="Nome cliente, email, numero..."
                       value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
            </div>

            <div class="mm-filter-group">
                <label for="categoria_id">🏷️ Categoria</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Tutte</option>
                    <?php foreach ($categorie as $categoria) : ?>
                        <option value="<?php echo $categoria['id']; ?>"
                            <?php selected(isset($_GET['categoria_id']) && $_GET['categoria_id'] == $categoria['id']); ?>>
                            <?php echo esc_html($categoria['icona'] . ' ' . $categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mm-filter-group">
                <label for="stato">📌 Stato</label>
                <select id="stato" name="stato">
                    <option value="">Tutti</option>
                    <option value="bozza" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'bozza'); ?>>Bozza</option>
                    <option value="attivo" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'attivo'); ?>>Attivo</option>
                    <option value="accettato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'accettato'); ?>>Accettato</option>
                    <option value="rifiutato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'rifiutato'); ?>>Rifiutato</option>
                    <option value="completato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'completato'); ?>>Completato</option>
                </select>
            </div>

            <div class="mm-filter-group">
                <label for="order_by">🔃 Ordina per</label>
                <select id="order_by" name="order_by">
                    <option value="data_evento_desc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'data_evento_desc'); ?>>Data Evento (recente)</option>
                    <option value="data_evento_asc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'data_evento_asc'); ?>>Data Evento (vecchio)</option>
                    <option value="data_preventivo_desc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'data_preventivo_desc'); ?>>Data Preventivo (recente)</option>
                    <option value="data_preventivo_asc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'data_preventivo_asc'); ?>>Data Preventivo (vecchio)</option>
                    <option value="numero_preventivo_desc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'numero_preventivo_desc'); ?>>Numero Preventivo (Z-A)</option>
                    <option value="numero_preventivo_asc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'numero_preventivo_asc'); ?>>Numero Preventivo (A-Z)</option>
                    <option value="totale_desc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'totale_desc'); ?>>Importo (alto)</option>
                    <option value="totale_asc" <?php selected(isset($_GET['order_by']) && $_GET['order_by'] === 'totale_asc'); ?>>Importo (basso)</option>
                </select>
            </div>

            <div class="mm-filter-actions">
                <button type="submit" class="mm-filter-btn mm-filter-btn-primary">Filtra</button>
                <a href="<?php echo get_permalink(); ?>" class="mm-filter-btn mm-filter-btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Preventivi List -->
    <div class="mm-preventivi-grid">
        <?php if (!empty($preventivi)) : ?>
            <?php foreach ($preventivi as $preventivo) : ?>
                <div class="mm-preventivo-card">
                    <div class="mm-preventivo-card-header">
                        <div class="mm-preventivo-number">
                            <span class="mm-preventivo-hash">#</span><?php echo esc_html($preventivo['numero_preventivo']); ?>
                        </div>
                        <span class="mm-status-badge mm-status-<?php echo esc_attr($preventivo['stato']); ?>">
                            <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                        </span>
                    </div>

                    <div class="mm-preventivo-card-body">
                        <div class="mm-preventivo-client">
                            <strong>👥 <?php echo esc_html($preventivo['sposi']); ?></strong>
                        </div>
                        <?php if (!empty($preventivo['categoria_nome'])) : ?>
                            <div class="mm-preventivo-categoria" style="margin: 8px 0; padding: 6px 12px; background: linear-gradient(135deg, rgba(233, 30, 99, 0.1) 0%, rgba(156, 39, 176, 0.1) 100%); border-left: 3px solid #e91e63; border-radius: 6px; font-size: 13px; font-weight: 600; color: #e91e63;">
                                <?php echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="mm-preventivo-info">
                            <span>📅 <?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?></span>
                            <span>📍 <?php echo esc_html($preventivo['location']); ?></span>
                        </div>
                        <?php
                        // Calcola imponibile corretto (subtotale = totale servizi - sconti)
                        $totale_servizi = floatval($preventivo['totale_servizi']);
                        $sconto_fisso = floatval($preventivo['sconto']);
                        $sconto_percentuale = floatval($preventivo['sconto_percentuale']);
                        $sconto_perc_importo = $totale_servizi * ($sconto_percentuale / 100);
                        $imponibile = $totale_servizi - $sconto_fisso - $sconto_perc_importo;

                        $totale_acconti = isset($preventivo['totale_acconti']) ? floatval($preventivo['totale_acconti']) : 0;
                        $restante = $preventivo['totale'] - $totale_acconti;
                        ?>

                        <div class="mm-preventivo-totali" style="margin-top: 10px; padding: 8px 12px; background: #f5f5f5; border-radius: 6px; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="color: #666; font-weight: 500;">Subtotale:</span>
                                <span style="color: #666; font-weight: 600;">€ <?php echo number_format($imponibile, 2, ',', '.'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-top: 4px; border-top: 1px solid #ddd;">
                                <span style="color: #333; font-weight: 600;">Totale:</span>
                                <span style="color: #e91e63; font-weight: bold; font-size: 14px;">€ <?php echo number_format($preventivo['totale'], 2, ',', '.'); ?></span>
                            </div>
                        </div>

                        <?php
                        // Debug temporaneo
                        error_log('Card Preventivo #' . $preventivo['numero_preventivo'] . ' - totale_acconti: ' . $totale_acconti);
                        ?>
                        <?php if ($totale_acconti > 0) : ?>
                        <div class="mm-preventivo-acconto" style="margin-top: 10px; padding: 8px 12px; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-left: 3px solid #4caf50; border-radius: 6px; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="color: #2e7d32; font-weight: 600;">💰 Acconti:</span>
                                <span style="color: #2e7d32; font-weight: bold;">€ <?php echo number_format($totale_acconti, 2, ',', '.'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #555; font-weight: 500;">Restante:</span>
                                <span style="color: #555; font-weight: 600;">€ <?php echo number_format($restante, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mm-preventivo-card-footer">
                        <button class="mm-card-btn mm-card-btn-view mm-view-details-btn"
                                data-preventivo-id="<?php echo $preventivo['id']; ?>">
                            👁️ Visualizza
                        </button>
                        <a href="<?php echo admin_url('admin-ajax.php?action=mm_export_pdf&id=' . $preventivo['id']); ?>"
                           class="mm-card-btn mm-card-btn-pdf"
                           target="_blank">
                            📄 PDF
                        </a>
                        <button class="mm-card-btn mm-card-btn-email mm-send-email-btn"
                                data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                data-email="<?php echo esc_attr($preventivo['email']); ?>">
                            📧 Email
                        </button>
                        <button class="mm-card-btn mm-card-btn-whatsapp mm-send-whatsapp-btn"
                                data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                data-telefono="<?php echo esc_attr($preventivo['telefono']); ?>">
                            💬 WhatsApp
                        </button>
                        <a href="<?php echo add_query_arg('id', $preventivo['id'], home_url('/modifica-preventivo/')); ?>"
                           class="mm-card-btn mm-card-btn-edit">
                            ✏️ Modifica
                        </a>

                        <?php if ($preventivo['stato'] === 'attivo') : ?>
                            <button class="mm-card-btn mm-card-btn-accept mm-status-btn"
                                    data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                    data-nuovo-stato="accettato">
                                ✅ Accettato
                            </button>
                            <button class="mm-card-btn mm-card-btn-reject mm-status-btn"
                                    data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                    data-nuovo-stato="rifiutato">
                                ❌ Rifiutato
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">📭</div>
                <h3>Nessun preventivo trovato</h3>
                <p>Non ci sono preventivi che corrispondono ai criteri di ricerca.</p>
                <?php if (!empty($_GET)) : ?>
                    <a href="<?php echo get_permalink(); ?>" class="mm-btn mm-btn-primary">Mostra tutti</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Dettagli Preventivo -->
<div id="mm-preventivo-modal" class="mm-modal" style="display: none;">
    <div class="mm-modal-overlay"></div>
    <div class="mm-modal-content">
        <div class="mm-modal-header">
            <h2>📋 Dettagli Preventivo</h2>
            <button class="mm-modal-close">&times;</button>
        </div>
        <div class="mm-modal-body">
            <div class="mm-loading">
                <div class="mm-spinner"></div>
                <p>Caricamento...</p>
            </div>
            <div id="mm-preventivo-details" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.mm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.mm-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.mm-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.mm-modal-header {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    padding: 25px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mm-modal-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.mm-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 32px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    line-height: 1;
}

.mm-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.mm-modal-body {
    padding: 30px;
    max-height: calc(90vh - 95px);
    overflow-y: auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.mm-loading {
    text-align: center;
    padding: 60px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.mm-loading p {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.mm-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #e91e63;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.mm-detail-section {
    background: #fafafa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border: 2px solid #e0e0e0;
}

.mm-detail-section h3 {
    color: #e91e63;
    font-size: 17px;
    margin: 0 0 20px 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.3px;
}

.mm-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.mm-detail-item {
    background: white;
    padding: 14px 16px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.2s ease;
}

.mm-detail-item:hover {
    border-color: #d0d0d0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.mm-detail-label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
    font-weight: 600;
}

.mm-detail-value {
    font-size: 15px;
    color: #222;
    font-weight: 400;
    line-height: 1.5;
}

.mm-detail-value.highlight {
    color: #e91e63;
    font-size: 18px;
    font-weight: 700;
}

.mm-services-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.mm-services-table th {
    background: #e91e63;
    color: white;
    padding: 12px 14px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.3px;
}

.mm-services-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 14px;
    color: #333;
    line-height: 1.6;
}

.mm-services-table tr:last-child td {
    border-bottom: none;
}

.mm-services-table tr:hover {
    background: #f5f5f5;
}

.mm-price-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 15px;
    color: #444;
    line-height: 1.5;
}

.mm-price-row.total {
    border-top: 2px solid #e91e63;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 18px;
    font-weight: 700;
    color: #e91e63;
}

.mm-status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.3px;
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

@media (max-width: 768px) {
    .mm-modal {
        padding: 10px;
    }

    .mm-modal-content {
        max-height: 95vh;
    }

    .mm-modal-header {
        padding: 20px;
    }

    .mm-modal-header h2 {
        font-size: 20px;
    }

    .mm-modal-body {
        padding: 20px;
    }

    .mm-detail-grid {
        grid-template-columns: 1fr;
    }

    .mm-services-table {
        font-size: 12px;
    }

    .mm-services-table th,
    .mm-services-table td {
        padding: 8px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Verifica che mmPreventivi sia definito
    if (typeof mmPreventivi === 'undefined') {
        console.error('mmPreventivi non è definito!');
        return;
    }

    // Apri modal dettagli preventivo
    $('.mm-view-details-btn').on('click', function(e) {
        e.preventDefault();

        const preventivoId = $(this).data('preventivo-id');
        console.log('Opening modal for preventivo ID:', preventivoId);

        const $modal = $('#mm-preventivo-modal');
        const $loading = $modal.find('.mm-loading');
        const $details = $('#mm-preventivo-details');

        // Mostra modal
        $modal.fadeIn(300);
        $loading.show();
        $details.hide();

        // Carica dati preventivo
        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_get_preventivo_details',
                nonce: mmPreventivi.nonce,
                preventivo_id: preventivoId
            },
            success: function(response) {
                console.log('AJAX Response:', response);

                if (response.success && response.data) {
                    try {
                        renderPreventivoDetails(response.data);
                        $loading.hide();
                        $details.show();
                    } catch (error) {
                        console.error('Errore rendering:', error);
                        alert('Errore nel rendering dei dati: ' + error.message);
                        $modal.fadeOut(300);
                    }
                } else {
                    console.error('Response non valida:', response);
                    alert('Errore: ' + (response.data && response.data.message ? response.data.message : 'Impossibile caricare i dettagli'));
                    $modal.fadeOut(300);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                alert('Errore di connessione: ' + error + '. Riprova.');
                $modal.fadeOut(300);
            }
        });
    });

    // Chiudi modal
    $('.mm-modal-close, .mm-modal-overlay').on('click', function() {
        $('#mm-preventivo-modal').fadeOut(300);
    });

    // Chiudi con ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#mm-preventivo-modal').fadeOut(300);
        }
    });

    // Render dettagli preventivo
    function renderPreventivoDetails(preventivo) {
        console.log('Rendering preventivo:', preventivo);

        // Parse cerimonia e servizi_extra (possono essere già array o stringhe JSON)
        let cerimonia = [];
        let serviziExtra = [];

        try {
            if (preventivo.cerimonia) {
                cerimonia = typeof preventivo.cerimonia === 'string'
                    ? JSON.parse(preventivo.cerimonia)
                    : preventivo.cerimonia;
            }
        } catch (e) {
            console.error('Errore parsing cerimonia:', e);
            cerimonia = [];
        }

        try {
            if (preventivo.servizi_extra) {
                serviziExtra = typeof preventivo.servizi_extra === 'string'
                    ? JSON.parse(preventivo.servizi_extra)
                    : preventivo.servizi_extra;
            }
        } catch (e) {
            console.error('Errore parsing servizi_extra:', e);
            serviziExtra = [];
        }

        let html = `
            <!-- Informazioni Cliente -->
            <div class="mm-detail-section">
                <h3>👥 Informazioni Cliente</h3>
                <div class="mm-detail-grid">
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Numero Preventivo</div>
                        <div class="mm-detail-value highlight">#${preventivo.numero_preventivo}</div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Stato</div>
                        <div class="mm-detail-value">
                            <span class="mm-status-badge mm-status-${preventivo.stato}">
                                ${preventivo.stato.charAt(0).toUpperCase() + preventivo.stato.slice(1)}
                            </span>
                        </div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Sposi</div>
                        <div class="mm-detail-value">${preventivo.sposi}</div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Email</div>
                        <div class="mm-detail-value">${preventivo.email}</div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Telefono</div>
                        <div class="mm-detail-value">${preventivo.telefono}</div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Data Preventivo</div>
                        <div class="mm-detail-value">${formatDate(preventivo.data_preventivo)}</div>
                    </div>
                </div>
            </div>

            <!-- Dettagli Evento -->
            <div class="mm-detail-section">
                <h3>🎉 Dettagli Evento</h3>
                <div class="mm-detail-grid">
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Data Evento</div>
                        <div class="mm-detail-value highlight">${formatDate(preventivo.data_evento)}</div>
                    </div>
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Location</div>
                        <div class="mm-detail-value">${preventivo.location}</div>
                    </div>
                    ${preventivo.categoria_nome ? `
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Categoria Evento</div>
                        <div class="mm-detail-value">${preventivo.categoria_icona} ${preventivo.categoria_nome}</div>
                    </div>
                    ` : ''}
                    ${preventivo.tipo_evento ? `
                    <div class="mm-detail-item">
                        <div class="mm-detail-label">Momento Evento</div>
                        <div class="mm-detail-value">${preventivo.tipo_evento}</div>
                    </div>
                    ` : ''}
                </div>
                ${cerimonia.length > 0 ? `
                    <div style="margin-top: 15px;">
                        <div class="mm-detail-label">Cerimonia</div>
                        <div class="mm-detail-value">${cerimonia.join(', ')}</div>
                    </div>
                ` : ''}
                ${serviziExtra.length > 0 ? `
                    <div style="margin-top: 15px;">
                        <div class="mm-detail-label">Servizi Extra</div>
                        <div class="mm-detail-value">${serviziExtra.join(', ')}</div>
                    </div>
                ` : ''}
                ${preventivo.note ? `
                    <div style="margin-top: 15px;">
                        <div class="mm-detail-label">Note</div>
                        <div class="mm-detail-value">${preventivo.note}</div>
                    </div>
                ` : ''}
            </div>

            <!-- Servizi -->
            ${preventivo.servizi && preventivo.servizi.length > 0 ? `
                <div class="mm-detail-section">
                    <h3>📦 Servizi Inclusi</h3>
                    <table class="mm-services-table">
                        <thead>
                            <tr>
                                <th>Servizio</th>
                                <th style="text-align: right;">Prezzo</th>
                                <th style="text-align: right;">Sconto</th>
                                <th style="text-align: right;">Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${preventivo.servizi.map(s => `
                                <tr>
                                    <td>${s.nome_servizio}</td>
                                    <td style="text-align: right;">€ ${formatNumber(s.prezzo)}</td>
                                    <td style="text-align: right;">${s.sconto > 0 ? '€ ' + formatNumber(s.sconto) : '-'}</td>
                                    <td style="text-align: right;"><strong>€ ${formatNumber(s.prezzo - (s.sconto || 0))}</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            ` : ''}

            <!-- Riepilogo Prezzi -->
            <div class="mm-detail-section">
                <h3>💰 Riepilogo Prezzi</h3>
                <div class="mm-price-row">
                    <span>Totale Servizi:</span>
                    <strong>€ ${formatNumber(preventivo.totale_servizi)}</strong>
                </div>
                ${preventivo.sconto > 0 || preventivo.sconto_percentuale > 0 ? `
                    <div class="mm-price-row">
                        <span>Sconto${preventivo.sconto_percentuale > 0 ? ' (' + preventivo.sconto_percentuale + '%)' : ''}:</span>
                        <strong style="color: #2e7d32;">- € ${formatNumber(preventivo.sconto)}</strong>
                    </div>
                ` : ''}
                ${preventivo.applica_enpals && preventivo.enpals > 0 ? `
                    <div class="mm-price-row">
                        <span>ENPALS (33%):</span>
                        <strong>€ ${formatNumber(preventivo.enpals)}</strong>
                    </div>
                ` : ''}
                ${preventivo.applica_iva && preventivo.iva > 0 ? `
                    <div class="mm-price-row">
                        <span>IVA (22%):</span>
                        <strong>€ ${formatNumber(preventivo.iva)}</strong>
                    </div>
                ` : ''}
                <div class="mm-price-row total">
                    <span>TOTALE:</span>
                    <span>€ ${formatNumber(preventivo.totale)}</span>
                </div>
                ${preventivo.importo_acconto > 0 ? `
                    <div class="mm-price-row" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <span>Acconto${preventivo.data_acconto ? ' (' + formatDate(preventivo.data_acconto) + ')' : ''}:</span>
                        <strong style="color: #1976d2;">€ ${formatNumber(preventivo.importo_acconto)}</strong>
                    </div>
                    <div class="mm-price-row">
                        <span>Saldo Rimanente:</span>
                        <strong style="color: #e91e63;">€ ${formatNumber(preventivo.totale - preventivo.importo_acconto)}</strong>
                    </div>
                ` : ''}
            </div>
        `;

        $('#mm-preventivo-details').html(html);
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Gestione pulsanti Accettato/Rifiutato
    $('.mm-status-btn').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const preventivoId = $btn.data('preventivo-id');
        const nuovoStato = $btn.data('nuovo-stato');
        const originalText = $btn.html();

        // Conferma azione
        const confermaMsg = nuovoStato === 'accettato'
            ? 'Confermi di voler accettare questo preventivo?'
            : 'Confermi di voler rifiutare questo preventivo?';

        if (!confirm(confermaMsg)) {
            return;
        }

        console.log('Aggiornamento stato preventivo:', preventivoId, nuovoStato);

        // Disabilita pulsante durante l'elaborazione
        $btn.prop('disabled', true).html('⏳ Elaborazione...');

        // Chiamata AJAX per aggiornare lo stato
        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_update_preventivo_status',
                nonce: mmPreventivi.nonce,
                preventivo_id: preventivoId,
                stato: nuovoStato
            },
            success: function(response) {
                console.log('Risposta aggiornamento stato:', response);

                if (response.success) {
                    alert(response.data.message || 'Stato aggiornato con successo!');
                    // Ricarica la pagina per mostrare lo stato aggiornato
                    location.reload();
                } else {
                    console.error('Errore aggiornamento stato:', response);
                    alert('Errore: ' + (response.data && response.data.message ? response.data.message : 'Impossibile aggiornare lo stato'));
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                alert('Errore di connessione: ' + error + '. Riprova.');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Gestione pulsante Invia Email
    $('.mm-send-email-btn').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const preventivoId = $btn.data('preventivo-id');
        const email = $btn.data('email');

        if (!email) {
            alert('❌ Questo preventivo non ha un indirizzo email associato.');
            return;
        }

        if (!confirm('📧 Vuoi inviare il preventivo via email a ' + email + '?')) {
            return;
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('⏳ Invio...');

        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_send_preventivo_email',
                nonce: mmPreventivi.nonce,
                preventivo_id: preventivoId
            },
            success: function(response) {
                console.log('Risposta invio email:', response);

                if (response.success) {
                    alert('✅ ' + (response.data.message || 'Email inviata con successo!'));
                    $btn.prop('disabled', false).html(originalHtml);
                } else {
                    console.error('Errore invio email:', response);
                    alert('❌ Errore: ' + (response.data && response.data.message ? response.data.message : 'Impossibile inviare l\'email'));
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                alert('❌ Errore di connessione: ' + error + '. Riprova.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Gestione pulsante WhatsApp
    $('.mm-send-whatsapp-btn').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const preventivoId = $btn.data('preventivo-id');
        const telefono = $btn.data('telefono');

        if (!telefono) {
            alert('❌ Questo preventivo non ha un numero di telefono associato.');
            return;
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('⏳ Generazione...');

        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_get_whatsapp_link',
                nonce: mmPreventivi.nonce,
                preventivo_id: preventivoId
            },
            success: function(response) {
                console.log('Risposta WhatsApp link:', response);

                if (response.success && response.data.link) {
                    // Apri WhatsApp in una nuova finestra
                    window.open(response.data.link, '_blank');
                    $btn.prop('disabled', false).html(originalHtml);
                } else {
                    console.error('Errore generazione link WhatsApp:', response);
                    alert('❌ Errore: ' + (response.data && response.data.message ? response.data.message : 'Impossibile generare il link WhatsApp'));
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                alert('❌ Errore di connessione: ' + error + '. Riprova.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
