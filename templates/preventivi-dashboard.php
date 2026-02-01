<?php
/**
 * Template: Dashboard Preventivi Frontend
 * Shortcode: [mm_preventivi_dashboard]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica autenticazione
if (!MM_Auth::is_logged_in()) {
    echo MM_Auth::show_login_form();
    return;
}

// Ottieni statistiche
$stats = MM_Database::get_statistics();

// Ottieni lista collaboratori per il filtro
$collaboratori = MM_Database::get_collaboratori();

// Filtri
$filters = array();
if (isset($_GET['stato']) && !empty($_GET['stato'])) {
    $filters['stato'] = sanitize_text_field($_GET['stato']);
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize_text_field($_GET['search']);
}
if (isset($_GET['collaboratore_id']) && !empty($_GET['collaboratore_id'])) {
    $filters['collaboratore_id'] = intval($_GET['collaboratore_id']);
}
if (isset($_GET['assegnazione']) && $_GET['assegnazione'] !== '') {
    $filters['assegnazione'] = sanitize_text_field($_GET['assegnazione']);
}

// Ordina sempre per data evento (prossimi prima)
$filters['order_by'] = 'data_evento_asc';

// Usa la funzione che include info sulle assegnazioni
$preventivi = MM_Database::get_preventivi_con_assegnazioni($filters);

// Filtra ulteriormente per assegnazione se richiesto
if (isset($filters['assegnazione']) && $filters['assegnazione'] !== '') {
    $preventivi = array_filter($preventivi, function($p) use ($filters) {
        $ha_assegnazioni = intval($p['num_collaboratori']) > 0;
        if ($filters['assegnazione'] === 'si') {
            return $ha_assegnazioni;
        } else {
            return !$ha_assegnazioni;
        }
    });
    $preventivi = array_values($preventivi);
}

// Funzione per calcolare giorni mancanti
function calcola_countdown($data_evento) {
    $oggi = new DateTime();
    $oggi->setTime(0, 0, 0);
    $evento = new DateTime($data_evento);
    $evento->setTime(0, 0, 0);
    $diff = $oggi->diff($evento);

    if ($evento < $oggi) {
        return array('giorni' => -$diff->days, 'passato' => true);
    }
    return array('giorni' => $diff->days, 'passato' => false);
}
?>

<div class="mm-dash">
    <!-- Header -->
    <div class="mm-dash-head">
        <h1>📋 Dashboard Preventivi</h1>
        <div class="mm-dash-nav">
            <a href="<?php echo home_url('/lista-preventivi/'); ?>">📊 Lista Completa</a>
            <a href="<?php echo home_url('/statistiche-preventivi/'); ?>">📈 Statistiche</a>
            <a href="<?php echo home_url('/assegnazioni-collaboratori/'); ?>">🎵 Assegnazioni</a>
            <a href="<?php echo home_url('/nuovo-preventivo/'); ?>" class="btn-new">+ Nuovo</a>
        </div>
    </div>

    <!-- Cards Statistiche -->
    <div class="mm-stats-cards">
        <a href="?stato=" class="mm-stat-card">
            <div class="mm-stat-icon">📋</div>
            <div class="mm-stat-number"><?php echo number_format($stats['totale_preventivi']); ?></div>
            <div class="mm-stat-label">Totali</div>
        </a>
        <a href="?stato=attivo" class="mm-stat-card mm-stat-attivo">
            <div class="mm-stat-icon">⏳</div>
            <div class="mm-stat-number"><?php echo number_format($stats['preventivi_attivi']); ?></div>
            <div class="mm-stat-label">Attivi</div>
        </a>
        <a href="?stato=accettato" class="mm-stat-card mm-stat-accettato">
            <div class="mm-stat-icon">✅</div>
            <div class="mm-stat-number"><?php echo number_format($stats['preventivi_accettati']); ?></div>
            <div class="mm-stat-label">Accettati</div>
        </a>
        <a href="?stato=rifiutato" class="mm-stat-card mm-stat-rifiutato">
            <div class="mm-stat-icon">❌</div>
            <div class="mm-stat-number"><?php echo number_format($stats['preventivi_rifiutati']); ?></div>
            <div class="mm-stat-label">Rifiutati</div>
        </a>
    </div>

    <!-- Filtri -->
    <form method="get" class="mm-dash-filters">
        <div class="mm-filter-group">
            <input type="text" name="search" placeholder="🔍 Cerca cliente, location..." value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
        </div>
        <div class="mm-filter-group">
            <select name="stato">
                <option value="">📌 Tutti gli stati</option>
                <option value="bozza" <?php selected($_GET['stato'] ?? '', 'bozza'); ?>>Bozza</option>
                <option value="attivo" <?php selected($_GET['stato'] ?? '', 'attivo'); ?>>Attivo</option>
                <option value="accettato" <?php selected($_GET['stato'] ?? '', 'accettato'); ?>>Accettato</option>
                <option value="completato" <?php selected($_GET['stato'] ?? '', 'completato'); ?>>Completato</option>
                <option value="rifiutato" <?php selected($_GET['stato'] ?? '', 'rifiutato'); ?>>Rifiutato</option>
            </select>
        </div>
        <div class="mm-filter-group">
            <select name="collaboratore_id">
                <option value="">👥 Tutti i collaboratori</option>
                <?php foreach ($collaboratori as $coll) : ?>
                    <option value="<?php echo $coll['id']; ?>" <?php selected($_GET['collaboratore_id'] ?? '', $coll['id']); ?>>
                        <?php echo esc_html($coll['nome'] . ' ' . $coll['cognome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mm-filter-group">
            <select name="assegnazione">
                <option value="">🎯 Assegnazione</option>
                <option value="si" <?php selected($_GET['assegnazione'] ?? '', 'si'); ?>>Con musicisti</option>
                <option value="no" <?php selected($_GET['assegnazione'] ?? '', 'no'); ?>>Senza musicisti</option>
            </select>
        </div>
        <button type="submit">Filtra</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['stato']) || !empty($_GET['collaboratore_id']) || isset($_GET['assegnazione']) && $_GET['assegnazione'] !== '') : ?>
            <a href="?" class="btn-reset">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Lista Preventivi -->
    <?php if (empty($preventivi)) : ?>
        <div class="mm-dash-empty">
            <div class="mm-empty-icon">📭</div>
            <p>Nessun preventivo trovato</p>
        </div>
    <?php else : ?>
        <div class="mm-preventivi-list">
            <?php foreach ($preventivi as $p) :
                $countdown = calcola_countdown($p['data_evento']);
                $ha_collaboratori = intval($p['num_collaboratori']) > 0;
            ?>
                <div class="mm-preventivo-row <?php echo $countdown['passato'] ? 'passato' : ''; ?>">
                    <!-- Riga header (visibile come riga su desktop, come blocco su mobile) -->
                    <div class="mm-row-header">
                        <!-- Indicatore assegnazione -->
                        <div class="mm-assign-indicator <?php echo $ha_collaboratori ? 'assigned' : 'not-assigned'; ?>"
                             title="<?php echo $ha_collaboratori ? 'Musicisti assegnati: ' . esc_attr($p['collaboratori_assegnati']) : 'Nessun musicista assegnato'; ?>">
                        </div>

                        <!-- Numero preventivo -->
                        <div class="mm-col-numero">
                            <span class="mm-numero">#<?php echo esc_html($p['numero_preventivo']); ?></span>
                        </div>

                        <!-- Stato -->
                        <div class="mm-col-stato">
                            <span class="mm-stato stato-<?php echo esc_attr($p['stato']); ?>">
                                <?php echo ucfirst($p['stato']); ?>
                            </span>
                        </div>

                        <!-- Azioni -->
                        <div class="mm-col-azioni">
                            <button type="button" class="mm-btn-view" data-id="<?php echo esc_attr($p['id']); ?>" title="Visualizza">
                                👁️
                            </button>
                            <a href="<?php echo add_query_arg('id', $p['id'], home_url('/modifica-preventivo/')); ?>" class="mm-btn-edit" title="Modifica">
                                ✏️
                            </a>
                        </div>
                    </div>

                    <!-- Data evento e countdown -->
                    <div class="mm-col-data">
                        <span class="mm-data"><?php echo date('d/m/Y', strtotime($p['data_evento'])); ?></span>
                        <span class="mm-countdown <?php echo $countdown['passato'] ? 'passato' : ($countdown['giorni'] <= 7 ? 'urgente' : ''); ?>">
                            <?php
                            if ($countdown['passato']) {
                                echo 'Passato';
                            } elseif ($countdown['giorni'] == 0) {
                                echo 'OGGI!';
                            } elseif ($countdown['giorni'] == 1) {
                                echo 'Domani';
                            } else {
                                echo $countdown['giorni'] . ' giorni';
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Cliente e Location -->
                    <div class="mm-col-cliente">
                        <span class="mm-cliente"><?php echo esc_html($p['sposi']); ?></span>
                        <?php if (!empty($p['location'])) : ?>
                            <span class="mm-location">📍 <?php echo esc_html($p['location']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mm-dash-count"><?php echo count($preventivi); ?> preventivi</div>
    <?php endif; ?>
</div>

<!-- Modal Dettagli Preventivo -->
<div id="mm-modal-dettagli" class="mm-modal-overlay" style="display: none;">
    <div class="mm-modal-container mm-modal-large">
        <div class="mm-modal-header">
            <h2>Dettagli Preventivo</h2>
            <button type="button" class="mm-modal-close">&times;</button>
        </div>
        <div class="mm-modal-body">
            <div class="mm-loading">
                <div class="mm-loading-spinner"></div>
                <span>Caricamento...</span>
            </div>
            <div class="mm-modal-content" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   DASHBOARD PREVENTIVI - CSS ORGANIZZATO
   ========================================================================== */

/* --------------------------------------------------------------------------
   1. BASE & CONTAINER
   -------------------------------------------------------------------------- */
.mm-dash {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 14px;
}

/* --------------------------------------------------------------------------
   2. HEADER & NAVIGATION
   -------------------------------------------------------------------------- */
.mm-dash-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: linear-gradient(135deg, #e91e63, #9c27b0);
    color: #fff;
    border-radius: 10px;
}

.mm-dash-head h1 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.mm-dash-nav {
    display: flex;
    gap: 10px;
}

.mm-dash-nav a {
    padding: 8px 14px;
    background: rgba(255,255,255,0.2);
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: background 0.2s;
}

.mm-dash-nav a:hover { background: rgba(255,255,255,0.3); }
.mm-dash-nav .btn-new { background: #4caf50; }
.mm-dash-nav .btn-new:hover { background: #43a047; }

/* --------------------------------------------------------------------------
   3. STATISTICHE CARDS
   -------------------------------------------------------------------------- */
.mm-stats-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.mm-stat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

.mm-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.mm-stat-icon { font-size: 28px; margin-bottom: 8px; }
.mm-stat-number { font-size: 32px; font-weight: 700; color: #333; }
.mm-stat-label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-top: 5px; }

/* Colori stat cards */
.mm-stat-attivo { border-color: #2196f3; }
.mm-stat-attivo .mm-stat-number { color: #2196f3; }
.mm-stat-accettato { border-color: #4caf50; }
.mm-stat-accettato .mm-stat-number { color: #4caf50; }
.mm-stat-rifiutato { border-color: #f44336; }
.mm-stat-rifiutato .mm-stat-number { color: #f44336; }

/* --------------------------------------------------------------------------
   4. FILTRI
   -------------------------------------------------------------------------- */
.mm-dash-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
    padding: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.mm-filter-group { flex: 1; min-width: 150px; }

.mm-dash-filters input,
.mm-dash-filters select {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    background: #fafafa;
    box-sizing: border-box;
}

.mm-dash-filters input:focus,
.mm-dash-filters select:focus {
    border-color: #e91e63;
    outline: none;
    background: #fff;
}

.mm-dash-filters button {
    height: 38px;
    padding: 0 20px;
    background: #e91e63;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.mm-dash-filters button:hover { background: #c2185b; }

.mm-dash-filters .btn-reset {
    height: 38px;
    line-height: 38px;
    padding: 0 15px;
    background: #9e9e9e;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.mm-dash-filters .btn-reset:hover { background: #757575; }

/* --------------------------------------------------------------------------
   5. LISTA PREVENTIVI (Desktop)
   -------------------------------------------------------------------------- */
.mm-preventivi-list {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.mm-preventivo-row {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
    gap: 15px;
}

.mm-row-header { display: contents; }
.mm-preventivo-row:last-child { border-bottom: none; }
.mm-preventivo-row:hover { background: #fafafa; }
.mm-preventivo-row.passato { background: #fafafa; opacity: 0.7; }

/* Indicatore assegnazione */
.mm-assign-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.mm-assign-indicator.assigned {
    background: #4caf50;
    box-shadow: 0 0 6px rgba(76, 175, 80, 0.5);
}

.mm-assign-indicator.not-assigned {
    background: #f44336;
    box-shadow: 0 0 6px rgba(244, 67, 54, 0.5);
}

/* Colonne lista */
.mm-col-numero { width: 60px; flex-shrink: 0; margin-right: 10px; }
.mm-numero { font-weight: 600; color: #999; font-size: 11px; }

.mm-col-data { width: 120px; flex-shrink: 0; display: flex; flex-direction: column; gap: 3px; }
.mm-data { font-weight: 700; color: #333; font-size: 14px; }
.mm-countdown { font-size: 11px; color: #666; font-weight: 500; }
.mm-countdown.urgente { color: #f44336; font-weight: 700; }
.mm-countdown.passato { color: #9e9e9e; font-style: italic; }

.mm-col-cliente { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: 3px; }
.mm-cliente { font-weight: 500; color: #333; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center; }
.mm-location { font-size: 12px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center; }

.mm-col-stato { width: 100px; flex-shrink: 0; text-align: center; }

/* --------------------------------------------------------------------------
   6. BADGE STATO
   -------------------------------------------------------------------------- */
.mm-stato {
    display: inline-block;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 15px;
}

.stato-bozza { background: #e0e0e0; color: #555; }
.stato-attivo { background: #e3f2fd; color: #1565c0; }
.stato-accettato { background: #e8f5e9; color: #2e7d32; }
.stato-completato { background: #f3e5f5; color: #7b1fa2; }
.stato-rifiutato { background: #ffebee; color: #c62828; }

/* --------------------------------------------------------------------------
   7. PULSANTI AZIONE
   -------------------------------------------------------------------------- */
.mm-col-azioni {
    width: 80px;
    flex-shrink: 0;
    display: flex;
    gap: 5px;
    justify-content: center;
}

.mm-btn-view,
.mm-btn-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.mm-btn-view { background: #2196f3; }
.mm-btn-view:hover { background: #1976d2; }
.mm-btn-edit { background: #e91e63; }
.mm-btn-edit:hover { background: #c2185b; }

/* --------------------------------------------------------------------------
   8. STATI VUOTI & CONTATORI
   -------------------------------------------------------------------------- */
.mm-dash-empty {
    padding: 60px;
    text-align: center;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.mm-empty-icon { font-size: 48px; margin-bottom: 15px; }
.mm-dash-empty p { color: #888; font-size: 16px; margin: 0; }
.mm-dash-count { text-align: right; margin-top: 12px; font-size: 12px; color: #888; }

/* --------------------------------------------------------------------------
   9. MODAL
   -------------------------------------------------------------------------- */
.mm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.mm-modal-container {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}

.mm-modal-container.mm-modal-large { max-width: 700px; }

.mm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, #e91e63, #9c27b0);
    color: #fff;
}

.mm-modal-header h2 { margin: 0; font-size: 18px; font-weight: 600; }

.mm-modal-close {
    background: rgba(255,255,255,0.2);
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: background 0.2s;
}

.mm-modal-close:hover { background: rgba(255,255,255,0.3); }

.mm-modal-body { padding: 20px; overflow-y: auto; flex: 1; }

.mm-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #eee;
    background: #fafafa;
}

/* Loading */
.mm-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    gap: 15px;
}

.mm-loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f0f0f0;
    border-top-color: #e91e63;
    border-radius: 50%;
    animation: mm-spin 0.8s linear infinite;
}

@keyframes mm-spin { to { transform: rotate(360deg); } }

/* --------------------------------------------------------------------------
   10. DETTAGLI PREVENTIVO (nel Modal) - DESIGN PROFESSIONALE
   -------------------------------------------------------------------------- */

/* Override font per il modal - più leggibile */
.mm-modal-body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
    font-size: 15px !important;
    line-height: 1.6 !important;
    color: #333 !important;
    padding: 28px !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.mm-modal-body * {
    font-family: inherit;
}

/* Header Card nel popup */
.mm-popup-header-card {
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: #fff;
    padding: 28px 32px;
    margin: -28px -28px 28px -28px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.mm-popup-header-left h3 {
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: -0.3px;
}

.mm-popup-header-left .mm-popup-subtitle {
    font-size: 15px;
    opacity: 0.95;
    font-weight: 400;
}

.mm-popup-header-right {
    text-align: right;
    flex-shrink: 0;
}

.mm-popup-numero {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 10px;
    font-weight: 500;
}

.mm-popup-stato {
    display: inline-block;
    padding: 7px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mm-popup-stato.stato-bozza { background: rgba(255,255,255,0.25); }
.mm-popup-stato.stato-attivo { background: #2196f3; }
.mm-popup-stato.stato-accettato { background: #4caf50; }
.mm-popup-stato.stato-completato { background: #673ab7; }
.mm-popup-stato.stato-rifiutato { background: #f44336; }

/* Info Cards Grid */
.mm-info-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.mm-info-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.mm-info-card-icon {
    font-size: 26px;
    margin-bottom: 10px;
    display: block;
}

.mm-info-card-value {
    font-size: 16px;
    font-weight: 600;
    color: #222;
    margin-bottom: 6px;
    display: block;
}

.mm-info-card-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    display: block;
}

/* Sezioni */
.mm-popup-section {
    margin-bottom: 32px;
}

.mm-popup-section:last-child {
    margin-bottom: 0;
}

.mm-popup-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 2px solid #f0f0f0;
}

.mm-popup-section-icon {
    width: 36px;
    height: 36px;
    background: #fce4ec;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.mm-popup-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #333;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Tabella Info */
.mm-popup-table {
    width: 100%;
    border-collapse: collapse;
}

.mm-popup-table tr {
    border-bottom: 1px solid #f0f0f0;
}

.mm-popup-table tr:last-child {
    border-bottom: none;
}

.mm-popup-table td {
    padding: 14px 12px;
    vertical-align: middle;
}

.mm-popup-table td:first-child {
    width: 110px;
    font-size: 13px;
    color: #777;
    font-weight: 600;
    padding-left: 0;
}

.mm-popup-table td:last-child {
    font-size: 15px;
    color: #222;
    font-weight: 500;
}

.mm-popup-table a {
    color: #e91e63;
    text-decoration: none;
    font-weight: 600;
}

.mm-popup-table a:hover {
    text-decoration: underline;
}

/* Lista Servizi */
.mm-popup-servizi {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.mm-popup-servizio {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.mm-popup-servizio:last-child {
    border-bottom: none;
}

.mm-popup-servizio-nome {
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.mm-popup-servizio-prezzo {
    font-size: 15px;
    font-weight: 600;
    color: #444;
}

.mm-popup-servizio.totale {
    background: #fafafa;
    border-top: 2px solid #e0e0e0;
    padding: 18px 20px;
}

.mm-popup-servizio.totale .mm-popup-servizio-nome {
    font-weight: 700;
    color: #222;
    font-size: 15px;
}

.mm-popup-servizio.totale .mm-popup-servizio-prezzo {
    font-size: 20px;
    color: #e91e63;
    font-weight: 700;
}

/* Lista Collaboratori */
.mm-popup-collaboratori {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mm-popup-collaboratore {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 18px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.mm-popup-collaboratore-avatar {
    width: 46px;
    height: 46px;
    background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.mm-popup-collaboratore-info {
    flex: 1;
}

.mm-popup-collaboratore-nome {
    font-size: 15px;
    font-weight: 600;
    color: #222;
    margin-bottom: 4px;
}

.mm-popup-collaboratore-ruolo {
    font-size: 13px;
    color: #666;
}

.mm-popup-collaboratore-badge {
    padding: 6px 12px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

/* Note */
.mm-popup-note {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 12px;
    padding: 18px 20px;
    font-size: 15px;
    color: #92400e;
    line-height: 1.7;
}

/* Empty State */
.mm-popup-empty {
    text-align: center;
    padding: 28px 20px;
    color: #777;
    font-size: 14px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed #e0e0e0;
}

/* Footer Azioni */
.mm-popup-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
}

.mm-popup-btn {
    flex: 1;
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.mm-popup-btn-primary {
    background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
    color: #fff;
}

.mm-popup-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(233, 30, 99, 0.35);
}

.mm-popup-btn-secondary {
    background: #f5f5f5;
    color: #555;
    border: 1px solid #ddd;
}

.mm-popup-btn-secondary:hover {
    background: #eee;
}

/* --------------------------------------------------------------------------
   11. RESPONSIVE - TABLET (max 768px)
   -------------------------------------------------------------------------- */
@media (max-width: 768px) {
    .mm-dash { padding: 10px; }

    /* Header */
    .mm-dash-head { flex-direction: column; gap: 12px; text-align: center; padding: 15px; }
    .mm-dash-head h1 { font-size: 18px; }
    .mm-dash-nav { flex-wrap: wrap; justify-content: center; gap: 8px; }
    .mm-dash-nav a { padding: 6px 10px; font-size: 11px; }

    /* Statistiche */
    .mm-stats-cards { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .mm-stat-card { padding: 12px 8px; }
    .mm-stat-icon { font-size: 22px; margin-bottom: 5px; }
    .mm-stat-number { font-size: 22px; }
    .mm-stat-label { font-size: 10px; }

    /* Filtri */
    .mm-dash-filters { flex-direction: column; padding: 12px; gap: 8px; }
    .mm-filter-group { width: 100%; min-width: unset; }
    .mm-dash-filters button,
    .mm-dash-filters .btn-reset { width: 100%; text-align: center; }

    /* Lista - Layout Card */
    .mm-preventivi-list {
        background: transparent;
        box-shadow: none;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .mm-preventivo-row {
        flex-direction: column;
        align-items: stretch;
        padding: 0;
        gap: 0;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-bottom: none;
        overflow: hidden;
    }

    .mm-preventivo-row:hover { background: #fff; }
    .mm-preventivo-row.passato { opacity: 0.7; }

    /* Card Header */
    .mm-row-header {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        background: linear-gradient(135deg, rgba(233, 30, 99, 0.05) 0%, rgba(156, 39, 176, 0.05) 100%);
        border-bottom: 1px solid #f0f0f0;
        gap: 8px;
    }

    .mm-assign-indicator { width: 10px; height: 10px; }
    .mm-col-numero { width: auto; margin-right: 0; }
    .mm-numero { font-size: 12px; }
    .mm-col-stato { width: auto; margin-left: auto; }
    .mm-stato { padding: 4px 10px; font-size: 10px; }
    .mm-col-azioni { width: auto; }
    .mm-btn-view, .mm-btn-edit { width: 28px; height: 28px; font-size: 12px; }

    /* Card Data */
    .mm-col-data {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: #fafafa;
        gap: 10px;
        box-sizing: border-box;
    }

    .mm-data { font-size: 15px; color: #e91e63; }

    .mm-countdown {
        font-size: 12px;
        padding: 3px 10px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #eee;
        white-space: nowrap;
    }

    .mm-countdown.urgente { background: #ffebee; border-color: #ffcdd2; }
    .mm-countdown.passato { background: #f5f5f5; border-color: #e0e0e0; }

    /* Card Cliente */
    .mm-col-cliente {
        width: 100%;
        align-items: flex-start;
        padding: 12px;
        gap: 4px;
        box-sizing: border-box;
    }

    .mm-cliente { font-size: 15px; white-space: normal; text-align: left; overflow: visible; }
    .mm-location { font-size: 13px; color: #666; white-space: normal; text-align: left; overflow: visible; }

    .mm-dash-count { text-align: center; margin-top: 15px; }
}

/* --------------------------------------------------------------------------
   12. RESPONSIVE - MOBILE (max 600px)
   -------------------------------------------------------------------------- */
@media (max-width: 600px) {
    .mm-modal-container { max-height: 95vh; }
    .mm-modal-body { padding: 20px !important; }

    /* Popup Header */
    .mm-popup-header-card {
        flex-direction: column;
        gap: 15px;
        margin: -20px -20px 20px -20px;
        padding: 20px;
    }

    .mm-popup-header-left h3 { font-size: 20px; }
    .mm-popup-header-left .mm-popup-subtitle { font-size: 14px; }
    .mm-popup-header-right { text-align: left; }

    /* Info Cards */
    .mm-info-cards {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 24px;
    }
    .mm-info-card { padding: 14px 10px; border-radius: 10px; }
    .mm-info-card-icon { font-size: 20px; margin-bottom: 6px; }
    .mm-info-card-value { font-size: 14px; }
    .mm-info-card-label { font-size: 10px; }

    /* Sezioni */
    .mm-popup-section { margin-bottom: 24px; }
    .mm-popup-section-header { margin-bottom: 14px; padding-bottom: 10px; }
    .mm-popup-section-icon { width: 32px; height: 32px; font-size: 16px; }
    .mm-popup-section-title { font-size: 13px; }

    /* Tabella */
    .mm-popup-table td { padding: 12px 8px; }
    .mm-popup-table td:first-child { width: 100px; font-size: 12px; }
    .mm-popup-table td:last-child { font-size: 14px; }

    /* Servizi */
    .mm-popup-servizio { padding: 14px 16px; }
    .mm-popup-servizio-nome { font-size: 14px; }
    .mm-popup-servizio-prezzo { font-size: 14px; }
    .mm-popup-servizio.totale .mm-popup-servizio-prezzo { font-size: 18px; }

    /* Collaboratori */
    .mm-popup-collaboratori { gap: 10px; }
    .mm-popup-collaboratore { padding: 14px; gap: 12px; }
    .mm-popup-collaboratore-avatar { width: 40px; height: 40px; font-size: 14px; }
    .mm-popup-collaboratore-nome { font-size: 14px; }
    .mm-popup-collaboratore-ruolo { font-size: 12px; }
    .mm-popup-collaboratore-badge { padding: 5px 10px; font-size: 11px; }

    /* Note */
    .mm-popup-note { padding: 16px; font-size: 14px; }

    /* Azioni */
    .mm-popup-actions { margin-top: 24px; padding-top: 20px; }
    .mm-popup-btn { padding: 14px 20px; font-size: 14px; }
}
</style>

<script>
jQuery(document).ready(function($) {
    const modal = $('#mm-modal-dettagli');
    const modalContent = modal.find('.mm-modal-content');
    const modalLoading = modal.find('.mm-loading');
    const nonce = '<?php echo wp_create_nonce('mm_preventivi_nonce'); ?>';

    // Apri modal al click su "Vedi"
    $('.mm-btn-view').on('click', function() {
        const preventivoId = $(this).data('id');
        openModal(preventivoId);
    });

    // Chiudi modal
    modal.on('click', '.mm-modal-close', function() {
        closeModal();
    });

    // Chiudi modal cliccando fuori
    modal.on('click', function(e) {
        if ($(e.target).is('.mm-modal-overlay')) {
            closeModal();
        }
    });

    // Chiudi con ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && modal.is(':visible')) {
            closeModal();
        }
    });

    function openModal(preventivoId) {
        modal.show();
        modalContent.hide();
        modalLoading.show();

        // Carica dettagli via AJAX
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mm_get_preventivo_details',
                preventivo_id: preventivoId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    renderDetails(response.data);
                } else {
                    modalContent.html('<div class="mm-no-data">Errore nel caricamento dei dati</div>');
                }
                modalLoading.hide();
                modalContent.show();
            },
            error: function() {
                modalContent.html('<div class="mm-no-data">Errore di connessione</div>');
                modalLoading.hide();
                modalContent.show();
            }
        });
    }

    function closeModal() {
        modal.hide();
        modalContent.empty();
    }

    function renderDetails(data) {
        const preventivo = data.preventivo || data;
        const servizi = data.servizi || [];
        const assegnazioni = data.assegnazioni || [];

        let html = '';

        // === HEADER CARD ===
        html += `<div class="mm-popup-header-card">
            <div class="mm-popup-header-left">
                <h3>${preventivo.sposi || 'Cliente'}</h3>
                <div class="mm-popup-subtitle">${preventivo.location ? '📍 ' + preventivo.location : ''}</div>
            </div>
            <div class="mm-popup-header-right">
                <div class="mm-popup-numero">#${preventivo.numero_preventivo || '-'}</div>
                <span class="mm-popup-stato stato-${preventivo.stato || 'bozza'}">${capitalizeFirst(preventivo.stato || 'bozza')}</span>
            </div>
        </div>`;

        // === INFO CARDS ===
        html += `<div class="mm-info-cards">
            <div class="mm-info-card">
                <div class="mm-info-card-icon">📅</div>
                <div class="mm-info-card-value">${formatDate(preventivo.data_evento)}</div>
                <div class="mm-info-card-label">Data Evento</div>
            </div>
            <div class="mm-info-card">
                <div class="mm-info-card-icon">${preventivo.tipo_evento === 'pranzo' ? '☀️' : '🌙'}</div>
                <div class="mm-info-card-value">${preventivo.tipo_evento ? capitalizeFirst(preventivo.tipo_evento) : '-'}</div>
                <div class="mm-info-card-label">Tipo</div>
            </div>
            <div class="mm-info-card">
                <div class="mm-info-card-icon">👥</div>
                <div class="mm-info-card-value">${assegnazioni.length}</div>
                <div class="mm-info-card-label">Musicisti</div>
            </div>
        </div>`;

        // === SEZIONE CONTATTI ===
        if (preventivo.telefono || preventivo.email) {
            html += `<div class="mm-popup-section">
                <div class="mm-popup-section-header">
                    <div class="mm-popup-section-icon">📞</div>
                    <div class="mm-popup-section-title">Contatti</div>
                </div>
                <table class="mm-popup-table">
                    ${preventivo.telefono ? `<tr><td>Telefono</td><td><a href="tel:${preventivo.telefono}">${preventivo.telefono}</a></td></tr>` : ''}
                    ${preventivo.email ? `<tr><td>Email</td><td><a href="mailto:${preventivo.email}">${preventivo.email}</a></td></tr>` : ''}
                </table>
            </div>`;
        }

        // === SEZIONE SERVIZI ===
        html += `<div class="mm-popup-section">
            <div class="mm-popup-section-header">
                <div class="mm-popup-section-icon">🎵</div>
                <div class="mm-popup-section-title">Servizi</div>
            </div>`;

        if (servizi.length > 0) {
            let totale = 0;
            html += '<div class="mm-popup-servizi">';
            servizi.forEach(function(s) {
                const prezzo = parseFloat(s.prezzo) || 0;
                totale += prezzo;
                html += `<div class="mm-popup-servizio">
                    <span class="mm-popup-servizio-nome">${s.nome || s.servizio || '-'}</span>
                    <span class="mm-popup-servizio-prezzo">€ ${prezzo.toFixed(2)}</span>
                </div>`;
            });
            html += `<div class="mm-popup-servizio totale">
                <span class="mm-popup-servizio-nome">Totale</span>
                <span class="mm-popup-servizio-prezzo">€ ${totale.toFixed(2)}</span>
            </div>`;
            html += '</div>';
        } else {
            html += '<div class="mm-popup-empty">Nessun servizio aggiunto</div>';
        }
        html += '</div>';

        // === SEZIONE COLLABORATORI ===
        html += `<div class="mm-popup-section">
            <div class="mm-popup-section-header">
                <div class="mm-popup-section-icon">🎤</div>
                <div class="mm-popup-section-title">Musicisti Assegnati</div>
            </div>`;

        if (assegnazioni.length > 0) {
            html += '<div class="mm-popup-collaboratori">';
            assegnazioni.forEach(function(a) {
                const iniziali = getInitials(a.nome, a.cognome);
                html += `<div class="mm-popup-collaboratore">
                    <div class="mm-popup-collaboratore-avatar">${iniziali}</div>
                    <div class="mm-popup-collaboratore-info">
                        <div class="mm-popup-collaboratore-nome">${a.nome || ''} ${a.cognome || ''}</div>
                        <div class="mm-popup-collaboratore-ruolo">${a.mansione || '-'}</div>
                    </div>
                    ${a.ruolo_evento ? `<div class="mm-popup-collaboratore-badge">${a.ruolo_evento}</div>` : ''}
                </div>`;
            });
            html += '</div>';
        } else {
            html += '<div class="mm-popup-empty">Nessun musicista assegnato</div>';
        }
        html += '</div>';

        // === SEZIONE NOTE ===
        if (preventivo.note) {
            html += `<div class="mm-popup-section">
                <div class="mm-popup-section-header">
                    <div class="mm-popup-section-icon">📝</div>
                    <div class="mm-popup-section-title">Note</div>
                </div>
                <div class="mm-popup-note">${preventivo.note.replace(/\n/g, '<br>')}</div>
            </div>`;
        }

        // === FOOTER AZIONI ===
        html += `<div class="mm-popup-actions">
            <a href="<?php echo home_url('/modifica-preventivo/'); ?>?id=${preventivo.id}" class="mm-popup-btn mm-popup-btn-primary">
                ✏️ Modifica Preventivo
            </a>
        </div>`;

        modalContent.html(html);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function getInitials(nome, cognome) {
        let initials = '';
        if (nome) initials += nome.charAt(0).toUpperCase();
        if (cognome) initials += cognome.charAt(0).toUpperCase();
        return initials || '?';
    }
});
</script>
