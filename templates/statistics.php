<?php
/**
 * Template: Statistiche Frontend
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
$stats = MM_Database::get_statistics();
$attivita_recenti = MM_Database::get_recent_activity(10);
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
            <a href="<?php echo get_permalink(); ?>" class="mm-nav-btn mm-nav-btn-active">
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
        <h1>📈 Statistiche & Analytics</h1>
        <p>Monitora le performance dei tuoi preventivi</p>
    </div>

    <!-- Main Stats Grid (Cliccabili) -->
    <div class="mm-stats-grid">
        <a href="<?php echo home_url('/lista-preventivi/'); ?>" class="mm-stat-card mm-stat-card-link" style="text-decoration: none; color: inherit;">
            <div class="mm-stat-icon">📋</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value"><?php echo number_format($stats['totale_preventivi']); ?></div>
                <div class="mm-stat-label">Preventivi Totali</div>
            </div>
        </a>

        <a href="<?php echo add_query_arg('stato', 'attivo', home_url('/lista-preventivi/')); ?>" class="mm-stat-card mm-stat-card-pending mm-stat-card-link" style="text-decoration: none; color: inherit;">
            <div class="mm-stat-icon">⏳</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value"><?php echo number_format($stats['preventivi_attivi']); ?></div>
                <div class="mm-stat-label">In Attesa</div>
            </div>
        </a>

        <a href="<?php echo add_query_arg('stato', 'accettato', home_url('/lista-preventivi/')); ?>" class="mm-stat-card mm-stat-card-success mm-stat-card-link" style="text-decoration: none; color: inherit;">
            <div class="mm-stat-icon">✅</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value"><?php echo number_format($stats['preventivi_accettati']); ?></div>
                <div class="mm-stat-label">Accettati</div>
            </div>
        </a>

        <a href="<?php echo add_query_arg('stato', 'rifiutato', home_url('/lista-preventivi/')); ?>" class="mm-stat-card mm-stat-card-rejected mm-stat-card-link" style="text-decoration: none; color: inherit;">
            <div class="mm-stat-icon">❌</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value"><?php echo number_format($stats['preventivi_rifiutati']); ?></div>
                <div class="mm-stat-label">Rifiutati</div>
            </div>
        </a>

        <div class="mm-stat-card mm-stat-card-money">
            <div class="mm-stat-icon">💰</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value">€ <?php echo number_format(floatval($stats['totale_fatturato'] ?? 0), 0, ',', '.'); ?></div>
                <div class="mm-stat-label">Fatturato Totale</div>
            </div>
        </div>

        <div class="mm-stat-card mm-stat-card-money">
            <div class="mm-stat-icon">📊</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value">€ <?php echo number_format(floatval($stats['fatturato_mese'] ?? 0), 0, ',', '.'); ?></div>
                <div class="mm-stat-label">Fatturato Mese</div>
            </div>
        </div>

        <div class="mm-stat-card mm-stat-card-average">
            <div class="mm-stat-icon">💵</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value">€ <?php echo number_format(floatval($stats['valore_medio_preventivo'] ?? 0), 0, ',', '.'); ?></div>
                <div class="mm-stat-label">Valore Medio</div>
            </div>
        </div>

        <div class="mm-stat-card mm-stat-card-rate">
            <div class="mm-stat-icon">📈</div>
            <div class="mm-stat-content">
                <div class="mm-stat-value"><?php echo number_format($stats['tasso_conversione'], 1); ?>%</div>
                <div class="mm-stat-label">Tasso Conversione</div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mm-activity-section">
        <h2 class="mm-section-title">
            <span class="mm-section-icon">🕒</span>
            Attività Recente
        </h2>

        <?php if (!empty($attivita_recenti)) : ?>
            <div class="mm-activity-list">
                <?php foreach ($attivita_recenti as $attivita) : ?>
                    <div class="mm-activity-item">
                        <div class="mm-activity-icon">
                            <?php
                            switch ($attivita['stato']) {
                                case 'bozza':
                                    echo '📝';
                                    break;
                                case 'attivo':
                                    echo '⏳';
                                    break;
                                case 'accettato':
                                    echo '✅';
                                    break;
                                case 'rifiutato':
                                    echo '❌';
                                    break;
                                case 'completato':
                                    echo '🎉';
                                    break;
                                default:
                                    echo '📋';
                            }
                            ?>
                        </div>
                        <div class="mm-activity-content">
                            <div class="mm-activity-title">
                                Preventivo <strong>#<?php echo esc_html($attivita['numero_preventivo']); ?></strong>
                                - <?php echo esc_html($attivita['sposi']); ?>
                            </div>
                            <div class="mm-activity-details">
                                <span class="mm-activity-date">
                                    <?php echo date('d/m/Y H:i', strtotime($attivita['data_creazione'])); ?>
                                </span>
                                <span class="mm-activity-status mm-status-<?php echo esc_attr($attivita['stato']); ?>">
                                    <?php echo esc_html(ucfirst($attivita['stato'])); ?>
                                </span>
                                <span class="mm-activity-amount">
                                    € <?php echo number_format($attivita['totale'], 2, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mm-activity-actions">
                            <button type="button" class="mm-activity-btn mm-btn-view-details"
                                    data-preventivo-id="<?php echo esc_attr($attivita['id']); ?>">
                                👁️ Vedi
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">📭</div>
                <h3>Nessuna attività recente</h3>
                <p>Non ci sono ancora preventivi nel sistema.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Dettagli Preventivo -->
    <div id="mm-modal-details" class="mm-modal-overlay" style="display: none;">
        <div class="mm-modal-container">
            <div class="mm-modal-header">
                <h2 id="mm-modal-title">Dettagli Preventivo</h2>
                <button type="button" class="mm-modal-close">&times;</button>
            </div>
            <div class="mm-modal-body" id="mm-modal-content">
                <div class="mm-loading"><div class="mm-loading-spinner"></div><span>Caricamento...</span></div>
            </div>
            <div class="mm-modal-footer">
                <a href="#" id="mm-modal-edit-link" class="mm-btn mm-btn-primary">✏️ Modifica</a>
                <button type="button" class="mm-btn mm-btn-secondary mm-modal-close-btn">Chiudi</button>
            </div>
        </div>
    </div>

</div>

<style>
/* Modal Styles */
.mm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 99999;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.mm-modal-container {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}

.mm-modal-header {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mm-modal-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.mm-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    opacity: 0.8;
}

.mm-modal-close:hover {
    opacity: 1;
}

.mm-modal-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
}

.mm-modal-footer {
    padding: 15px 25px;
    background: #f5f5f5;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e0e0e0;
}

.mm-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 40px;
    color: #666;
    font-size: 16px;
    gap: 20px;
}

.mm-loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f0f0f0;
    border-top-color: #e91e63;
    border-radius: 50%;
    animation: mm-spin 0.8s linear infinite;
}

@keyframes mm-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Detail Sections */
.mm-detail-section {
    margin-bottom: 25px;
}

.mm-detail-section:last-child {
    margin-bottom: 0;
}

.mm-detail-section h3 {
    color: #e91e63;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #f8bbd0;
}

.mm-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.mm-detail-item {
    display: flex;
    flex-direction: column;
}

.mm-detail-item.full-width {
    grid-column: 1 / -1;
}

.mm-detail-label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    margin-bottom: 3px;
}

.mm-detail-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.mm-detail-value.large {
    font-size: 18px;
    color: #e91e63;
    font-weight: 700;
}

/* Status Badge */
.mm-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.mm-status-badge.bozza { background: #f5f5f5; color: #666; }
.mm-status-badge.attivo { background: #fff3e0; color: #f57c00; }
.mm-status-badge.accettato { background: #e8f5e9; color: #2e7d32; }
.mm-status-badge.rifiutato { background: #ffebee; color: #c62828; }
.mm-status-badge.completato { background: #e3f2fd; color: #1565c0; }

/* Tags */
.mm-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.mm-tag {
    display: inline-block;
    padding: 4px 10px;
    background: #f0f0f0;
    border-radius: 15px;
    font-size: 12px;
    color: #555;
}

.mm-tag.active {
    background: #e8f5e9;
    color: #2e7d32;
}

/* Servizi List */
.mm-servizi-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.mm-servizio-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f8f8f8;
    border-radius: 6px;
    font-size: 13px;
}

.mm-servizio-item .nome {
    color: #333;
}

.mm-servizio-item .prezzo {
    color: #e91e63;
    font-weight: 600;
}

/* Assegnazioni */
.mm-assegnazioni-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.mm-assegnazione-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #9c27b0;
}

.mm-assegnazione-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.mm-assegnazione-info {
    flex: 1;
}

.mm-assegnazione-nome {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.mm-assegnazione-ruolo {
    font-size: 12px;
    color: #666;
}

.mm-assegnazione-compenso {
    font-weight: 600;
    color: #4caf50;
    font-size: 14px;
}

.mm-no-data {
    text-align: center;
    padding: 15px;
    color: #999;
    font-style: italic;
    font-size: 13px;
}

/* Totali */
.mm-totali-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 15px;
    border: 2px solid #e91e63;
}

.mm-totale-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
}

.mm-totale-row.finale {
    border-top: 2px solid #e91e63;
    margin-top: 8px;
    padding-top: 12px;
    font-size: 16px;
    font-weight: 700;
    color: #e91e63;
}

/* Button styles */
.mm-btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.mm-btn-primary {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
}

.mm-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
}

.mm-btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.mm-btn-secondary:hover {
    background: #e0e0e0;
}

.mm-activity-btn {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.mm-activity-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
}

/* Mobile */
@media (max-width: 600px) {
    .mm-modal-container {
        max-height: 95vh;
        margin: 10px;
    }

    .mm-modal-header {
        padding: 15px;
    }

    .mm-modal-body {
        padding: 15px;
    }

    .mm-detail-grid {
        grid-template-columns: 1fr;
    }

    .mm-modal-footer {
        flex-direction: column;
    }

    .mm-modal-footer .mm-btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Apri modal dettagli
    $('.mm-btn-view-details').on('click', function() {
        const preventivoId = $(this).data('preventivo-id');
        const $modal = $('#mm-modal-details');
        const $content = $('#mm-modal-content');

        // Mostra modal con loading
        $modal.show();
        $content.html('<div class="mm-loading"><div class="mm-loading-spinner"></div><span>Caricamento dettagli...</span></div>');

        // Aggiorna link modifica
        $('#mm-modal-edit-link').attr('href', '<?php echo home_url('/modifica-preventivo/'); ?>?id=' + preventivoId);

        // Carica dettagli via AJAX
        $.ajax({
            url: mmPreventivi.ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_get_preventivo_details',
                nonce: mmPreventivi.nonce,
                preventivo_id: preventivoId
            },
            success: function(response) {
                if (response.success) {
                    renderDetails(response.data);
                } else {
                    $content.html('<div class="mm-no-data">❌ ' + (response.data.message || 'Errore nel caricamento') + '</div>');
                }
            },
            error: function() {
                $content.html('<div class="mm-no-data">❌ Errore di connessione</div>');
            }
        });
    });

    // Chiudi modal
    $('.mm-modal-close, .mm-modal-close-btn').on('click', function() {
        $('#mm-modal-details').hide();
    });

    // Chiudi con click su overlay
    $('#mm-modal-details').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // Chiudi con ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#mm-modal-details').hide();
        }
    });

    // Render dettagli
    function renderDetails(data) {
        const p = data.preventivo;
        const servizi = data.servizi || [];
        const assegnazioni = data.assegnazioni || [];

        // Titolo modal
        $('#mm-modal-title').html('Preventivo #' + escapeHtml(p.numero_preventivo) + ' - ' + escapeHtml(p.sposi));

        let html = '';

        // Sezione Info Cliente & Evento
        html += '<div class="mm-detail-section">';
        html += '<h3>📋 Informazioni Generali</h3>';
        html += '<div class="mm-detail-grid">';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Cliente</span><span class="mm-detail-value">' + escapeHtml(p.sposi) + '</span></div>';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Stato</span><span class="mm-status-badge ' + p.stato + '">' + ucfirst(p.stato) + '</span></div>';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Email</span><span class="mm-detail-value">' + escapeHtml(p.email || '-') + '</span></div>';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Telefono</span><span class="mm-detail-value">' + escapeHtml(p.telefono || '-') + '</span></div>';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Data Evento</span><span class="mm-detail-value">' + formatDate(p.data_evento) + '</span></div>';
        html += '<div class="mm-detail-item"><span class="mm-detail-label">Tipo Evento</span><span class="mm-detail-value">' + escapeHtml(p.tipo_evento || '-') + '</span></div>';
        html += '<div class="mm-detail-item full-width"><span class="mm-detail-label">Location</span><span class="mm-detail-value">' + escapeHtml(p.location || '-') + '</span></div>';
        html += '</div></div>';

        // Sezione Servizi Extra
        if (p.servizi_extra_array && p.servizi_extra_array.length > 0) {
            html += '<div class="mm-detail-section">';
            html += '<h3>✨ Servizi Extra</h3>';
            html += '<div class="mm-tags">';
            p.servizi_extra_array.forEach(function(extra) {
                html += '<span class="mm-tag active">✓ ' + escapeHtml(extra) + '</span>';
            });
            html += '</div></div>';
        }

        // Sezione Cerimonia
        if (p.cerimonia_array && p.cerimonia_array.length > 0) {
            html += '<div class="mm-detail-section">';
            html += '<h3>💒 Cerimonia / Rito</h3>';
            html += '<div class="mm-tags">';
            p.cerimonia_array.forEach(function(item) {
                html += '<span class="mm-tag active">✓ ' + escapeHtml(item) + '</span>';
            });
            html += '</div></div>';
        }

        // Sezione Servizi
        if (servizi.length > 0) {
            html += '<div class="mm-detail-section">';
            html += '<h3>🎵 Servizi Inclusi</h3>';
            html += '<div class="mm-servizi-list">';
            servizi.forEach(function(s) {
                const prezzo = parseFloat(s.prezzo) || 0;
                const sconto = parseFloat(s.sconto) || 0;
                const prezzoFinale = prezzo - sconto;
                html += '<div class="mm-servizio-item">';
                html += '<span class="nome">' + escapeHtml(s.nome_servizio) + '</span>';
                html += '<span class="prezzo">€ ' + prezzoFinale.toFixed(2).replace('.', ',') + '</span>';
                html += '</div>';
            });
            html += '</div></div>';
        }

        // Sezione Assegnazioni
        html += '<div class="mm-detail-section">';
        html += '<h3>👥 Collaboratori Assegnati</h3>';
        if (assegnazioni.length > 0) {
            html += '<div class="mm-assegnazioni-list">';
            assegnazioni.forEach(function(a) {
                const initials = (a.nome ? a.nome.charAt(0) : '') + (a.cognome ? a.cognome.charAt(0) : '');
                html += '<div class="mm-assegnazione-item">';
                html += '<div class="mm-assegnazione-avatar">' + initials.toUpperCase() + '</div>';
                html += '<div class="mm-assegnazione-info">';
                html += '<div class="mm-assegnazione-nome">' + escapeHtml(a.nome + ' ' + a.cognome) + '</div>';
                html += '<div class="mm-assegnazione-ruolo">' + escapeHtml(a.mansione || '') + (a.ruolo_evento ? ' - ' + escapeHtml(a.ruolo_evento) : '') + '</div>';
                html += '</div>';
                if (a.compenso && parseFloat(a.compenso) > 0) {
                    html += '<div class="mm-assegnazione-compenso">€ ' + parseFloat(a.compenso).toFixed(2).replace('.', ',') + '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<div class="mm-no-data">Nessun collaboratore assegnato</div>';
        }
        html += '</div>';

        // Sezione Note
        if (p.note) {
            html += '<div class="mm-detail-section">';
            html += '<h3>📝 Note</h3>';
            html += '<div style="background: #fffaf0; padding: 12px; border-radius: 6px; border-left: 3px solid #ff9800; font-size: 13px; line-height: 1.6;">';
            html += escapeHtml(p.note);
            html += '</div></div>';
        }

        // Sezione Totali
        html += '<div class="mm-detail-section">';
        html += '<h3>💰 Riepilogo Economico</h3>';
        html += '<div class="mm-totali-box">';

        const totaleServizi = parseFloat(p.totale_servizi) || 0;
        const sconto = parseFloat(p.sconto) || 0;
        const scontoPerc = parseFloat(p.sconto_percentuale) || 0;
        const enpals = parseFloat(p.enpals) || 0;
        const iva = parseFloat(p.iva) || 0;
        const totale = parseFloat(p.totale) || 0;

        html += '<div class="mm-totale-row"><span>Totale Servizi</span><span>€ ' + totaleServizi.toFixed(2).replace('.', ',') + '</span></div>';

        if (sconto > 0) {
            html += '<div class="mm-totale-row" style="color: #4caf50;"><span>- Sconto</span><span>€ ' + sconto.toFixed(2).replace('.', ',') + '</span></div>';
        }
        if (scontoPerc > 0) {
            html += '<div class="mm-totale-row" style="color: #4caf50;"><span>- Sconto (' + scontoPerc + '%)</span><span>€ ' + (totaleServizi * scontoPerc / 100).toFixed(2).replace('.', ',') + '</span></div>';
        }
        if (p.applica_enpals == 1 && enpals > 0) {
            html += '<div class="mm-totale-row"><span>Ex ENPALS</span><span>€ ' + enpals.toFixed(2).replace('.', ',') + '</span></div>';
        }
        if (p.applica_iva == 1 && iva > 0) {
            html += '<div class="mm-totale-row"><span>IVA (22%)</span><span>€ ' + iva.toFixed(2).replace('.', ',') + '</span></div>';
        }

        html += '<div class="mm-totale-row finale"><span>TOTALE</span><span>€ ' + totale.toFixed(2).replace('.', ',') + '</span></div>';
        html += '</div></div>';

        // Acconti
        if (p.acconti && p.acconti.length > 0) {
            html += '<div class="mm-detail-section">';
            html += '<h3>💳 Acconti</h3>';
            let totaleAcconti = 0;
            p.acconti.forEach(function(acc) {
                const importo = parseFloat(acc.importo_acconto) || 0;
                totaleAcconti += importo;
                html += '<div class="mm-servizio-item">';
                html += '<span class="nome">' + formatDate(acc.data_acconto) + '</span>';
                html += '<span class="prezzo" style="color: #4caf50;">€ ' + importo.toFixed(2).replace('.', ',') + '</span>';
                html += '</div>';
            });
            const saldo = totale - totaleAcconti;
            html += '<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; font-weight: 600;">';
            html += '<span>Saldo Rimanente</span>';
            html += '<span style="color: ' + (saldo > 0 ? '#e91e63' : '#4caf50') + ';">€ ' + saldo.toFixed(2).replace('.', ',') + '</span>';
            html += '</div>';
            html += '</div>';
        }

        $('#mm-modal-content').html(html);
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('it-IT');
    }
});
</script>
