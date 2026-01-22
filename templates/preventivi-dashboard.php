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
                    <!-- Indicatore assegnazione -->
                    <div class="mm-assign-indicator <?php echo $ha_collaboratori ? 'assigned' : 'not-assigned'; ?>"
                         title="<?php echo $ha_collaboratori ? 'Musicisti assegnati: ' . esc_attr($p['collaboratori_assegnati']) : 'Nessun musicista assegnato'; ?>">
                    </div>

                    <!-- Numero preventivo -->
                    <div class="mm-col-numero">
                        <span class="mm-numero">#<?php echo esc_html($p['numero_preventivo']); ?></span>
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

                    <!-- Stato -->
                    <div class="mm-col-stato">
                        <span class="mm-stato stato-<?php echo esc_attr($p['stato']); ?>">
                            <?php echo ucfirst($p['stato']); ?>
                        </span>
                    </div>

                    <!-- Azioni -->
                    <div class="mm-col-azioni">
                        <a href="<?php echo add_query_arg('id', $p['id'], home_url('/modifica-preventivo/')); ?>" class="mm-btn-edit" title="Modifica">
                            ✏️
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mm-dash-count"><?php echo count($preventivi); ?> preventivi</div>
    <?php endif; ?>
</div>

<style>
.mm-dash {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 14px;
}

/* Header */
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

.mm-dash-nav a:hover {
    background: rgba(255,255,255,0.3);
}

.mm-dash-nav .btn-new {
    background: #4caf50;
}

.mm-dash-nav .btn-new:hover {
    background: #43a047;
}

/* Cards Statistiche */
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

.mm-stat-icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.mm-stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.mm-stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 5px;
}

.mm-stat-attivo { border-color: #2196f3; }
.mm-stat-attivo .mm-stat-number { color: #2196f3; }

.mm-stat-accettato { border-color: #4caf50; }
.mm-stat-accettato .mm-stat-number { color: #4caf50; }

.mm-stat-rifiutato { border-color: #f44336; }
.mm-stat-rifiutato .mm-stat-number { color: #f44336; }

/* Filtri */
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

.mm-filter-group {
    flex: 1;
    min-width: 150px;
}

.mm-dash-filters input,
.mm-dash-filters select {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    background: #fafafa;
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

.mm-dash-filters button:hover {
    background: #c2185b;
}

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

.mm-dash-filters .btn-reset:hover {
    background: #757575;
}

/* Lista Preventivi */
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

.mm-preventivo-row:last-child {
    border-bottom: none;
}

.mm-preventivo-row:hover {
    background: #fafafa;
}

.mm-preventivo-row.passato {
    background: #fafafa;
    opacity: 0.7;
}

/* Indicatore Assegnazione */
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

/* Colonne */
.mm-col-numero {
    width: 60px;
    flex-shrink: 0;
    margin-right: 10px;
}

.mm-numero {
    font-weight: 600;
    color: #999;
    font-size: 11px;
}

.mm-col-data {
    width: 120px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.mm-data {
    font-weight: 700;
    color: #333;
    font-size: 14px;
}

.mm-countdown {
    font-size: 11px;
    color: #666;
    font-weight: 500;
}

.mm-countdown.urgente {
    color: #f44336;
    font-weight: 700;
}

.mm-countdown.passato {
    color: #9e9e9e;
    font-style: italic;
}

.mm-col-cliente {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.mm-cliente {
    font-weight: 500;
    color: #333;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
}

.mm-location {
    font-size: 12px;
    color: #888;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
}

.mm-col-stato {
    width: 100px;
    flex-shrink: 0;
    text-align: center;
}

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

.mm-col-azioni {
    width: 50px;
    flex-shrink: 0;
    text-align: center;
}

.mm-btn-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #e91e63;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    transition: background 0.2s;
}

.mm-btn-edit:hover {
    background: #c2185b;
}

/* Empty State */
.mm-dash-empty {
    padding: 60px;
    text-align: center;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.mm-empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.mm-dash-empty p {
    color: #888;
    font-size: 16px;
    margin: 0;
}

/* Count */
.mm-dash-count {
    text-align: right;
    margin-top: 12px;
    font-size: 12px;
    color: #888;
}

/* Mobile */
@media (max-width: 768px) {
    .mm-dash {
        padding: 10px;
    }

    .mm-dash-head {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .mm-dash-nav {
        flex-wrap: wrap;
        justify-content: center;
    }

    .mm-stats-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .mm-stat-card {
        padding: 15px;
    }

    .mm-stat-number {
        font-size: 24px;
    }

    .mm-dash-filters {
        flex-direction: column;
    }

    .mm-filter-group {
        width: 100%;
    }

    .mm-preventivo-row {
        flex-wrap: wrap;
        padding: 15px;
        gap: 10px;
    }

    .mm-col-numero {
        width: auto;
        order: 1;
    }

    .mm-col-data {
        width: auto;
        order: 2;
        flex-direction: row;
        gap: 10px;
        align-items: center;
    }

    .mm-col-stato {
        width: auto;
        order: 3;
        margin-left: auto;
    }

    .mm-col-cliente {
        width: 100%;
        order: 4;
    }

    .mm-col-azioni {
        width: auto;
        order: 5;
        margin-left: auto;
    }

    .mm-assign-indicator {
        order: 0;
    }
}
</style>
