<?php
/**
 * Admin View: Statistics
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">
    
    <div class="mm-admin-header">
        <h1>📈 Statistiche</h1>
        <p>Panoramica generale dei preventivi e fatturato</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="mm-stats-grid">
        <div class="mm-stat-card">
            <div class="mm-stat-label">Totale Preventivi</div>
            <div class="mm-stat-value"><?php echo esc_html($stats['totale_preventivi']); ?></div>
            <div class="mm-stat-icon">📋</div>
        </div>
        
        <div class="mm-stat-card info">
            <div class="mm-stat-label">Preventivi Attivi</div>
            <div class="mm-stat-value"><?php echo esc_html($stats['preventivi_attivi']); ?></div>
            <div class="mm-stat-icon">⏳</div>
        </div>
        
        <div class="mm-stat-card success">
            <div class="mm-stat-label">Preventivi Accettati</div>
            <div class="mm-stat-value"><?php echo esc_html($stats['preventivi_accettati']); ?></div>
            <div class="mm-stat-icon">✅</div>
        </div>

        <div class="mm-stat-card" style="border-left-color: #f44336;">
            <div class="mm-stat-label">Preventivi Rifiutati</div>
            <div class="mm-stat-value"><?php echo esc_html($stats['preventivi_rifiutati']); ?></div>
            <div class="mm-stat-icon">❌</div>
        </div>

        <div class="mm-stat-card warning">
            <div class="mm-stat-label">Totale Fatturato</div>
            <div class="mm-stat-value">€ <?php echo number_format($stats['totale_fatturato'], 0, ',', '.'); ?></div>
            <div class="mm-stat-icon">💰</div>
        </div>
        
        <div class="mm-stat-card info">
            <div class="mm-stat-label">Fatturato Mese Corrente</div>
            <div class="mm-stat-value">€ <?php echo number_format($stats['fatturato_mese'], 0, ',', '.'); ?></div>
            <div class="mm-stat-icon">📊</div>
        </div>
        
        <div class="mm-stat-card">
            <div class="mm-stat-label">Tasso Conversione</div>
            <div class="mm-stat-value">
                <?php 
                $conversion = $stats['totale_preventivi'] > 0 
                    ? round(($stats['preventivi_accettati'] / $stats['totale_preventivi']) * 100, 1) 
                    : 0;
                echo esc_html($conversion); 
                ?>%
            </div>
            <div class="mm-stat-icon">🎯</div>
        </div>
    </div>
    
    <!-- Info Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">🎉 Preventivi per Tipo Evento</h3>
            <?php
            $stats_by_type = MM_Database::get_statistics_by_event_type();

            if (!empty($stats_by_type)) :
            ?>
            <table class="mm-services-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Tipo Evento</th>
                        <th style="text-align: center;">Quantità</th>
                        <th style="text-align: right;">Totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_by_type as $type_stat) : ?>
                    <tr>
                        <td>
                            <strong>
                                <?php
                                if (!empty($type_stat['categoria_icona'])) {
                                    echo esc_html($type_stat['categoria_icona'] . ' ');
                                }
                                echo esc_html($type_stat['tipo_evento']);
                                ?>
                            </strong>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #e91e63; color: white; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                <?php echo esc_html($type_stat['count']); ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #e91e63;">
                            € <?php echo number_format($type_stat['totale'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p style="color: #999; text-align: center; padding: 20px;">Nessun dato disponibile</p>
            <?php endif; ?>
        </div>

        <div class="mm-detail-card">
            <h3 style="color: #e91e63; margin-top: 0;">💡 Suggerimento</h3>
            <p style="color: #666; margin-bottom: 15px;">
                Usa i filtri nella dashboard per trovare rapidamente i preventivi per stato, data o cliente.
                Esporta i preventivi in PDF per inviarli ai clienti.
            </p>
            <h3 style="color: #e91e63; margin-top: 20px;">📅 Prossimi Eventi</h3>
            <p style="color: #666;">
                Visualizza i prossimi eventi pianificati nella sezione "Tutti i Preventivi" filtrando per data.
            </p>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="mm-detail-card" style="margin-top: 20px;">
        <h3 style="color: #e91e63; margin-top: 0;">🔔 Attività Recente</h3>
        <?php
        $recent = MM_Database::get_all_preventivi(array());
        $recent = array_slice($recent, 0, 5);
        
        if (!empty($recent)) :
        ?>
        <table class="mm-services-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Evento</th>
                    <th>Tipo</th>
                    <th>Stato</th>
                    <th style="text-align: right;">Importo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $item) : ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                    <td><?php echo esc_html($item['sposi']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($item['data_evento'])); ?></td>
                    <td>
                        <small style="background: #f0f0f0; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                            🎉 <?php echo esc_html($item['tipo_evento']); ?>
                        </small>
                    </td>
                    <td>
                        <span class="mm-status-badge <?php echo esc_attr($item['stato']); ?>">
                            <?php echo esc_html(ucfirst($item['stato'])); ?>
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #e91e63;">
                        € <?php echo number_format($item['totale'], 2, ',', '.'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p style="color: #999; text-align: center; padding: 40px;">Nessuna attività recente</p>
        <?php endif; ?>
    </div>
    
</div>
