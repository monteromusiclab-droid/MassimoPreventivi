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
                            <a href="<?php echo admin_url('admin.php?page=mm-preventivi&action=view&id=' . $attivita['id']); ?>"
                               class="mm-activity-btn">
                                👁️ Vedi
                            </a>
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

</div>
