<?php
/**
 * Admin View: View Preventivo
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">
    
    <div class="mm-admin-header">
        <h1>📄 Preventivo <?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
        <p>
            <a href="?page=mm-preventivi" style="color: white; opacity: 0.9;">← Torna all'elenco</a>
        </p>
    </div>
    
    <!-- Cliente Info -->
    <div class="mm-detail-card">
        <div class="mm-detail-header">
            <h2>👤 Informazioni Cliente</h2>
            <div>
                <button type="button" class="mm-btn-admin-primary mm-btn-pdf" data-id="<?php echo esc_attr($preventivo['id']); ?>">
                    📥 Scarica PDF
                </button>
            </div>
        </div>
        <div class="mm-detail-grid">
            <div class="mm-detail-item">
                <div class="mm-detail-label">Sposi / Cliente</div>
                <div class="mm-detail-value"><?php echo esc_html($preventivo['sposi']); ?></div>
            </div>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Email</div>
                <div class="mm-detail-value">
                    <?php if (!empty($preventivo['email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($preventivo['email']); ?>"><?php echo esc_html($preventivo['email']); ?></a>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Telefono</div>
                <div class="mm-detail-value">
                    <?php if (!empty($preventivo['telefono'])) : ?>
                        <a href="tel:<?php echo esc_attr($preventivo['telefono']); ?>"><?php echo esc_html($preventivo['telefono']); ?></a>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Data Preventivo</div>
                <div class="mm-detail-value"><?php echo date('d/m/Y', strtotime($preventivo['data_preventivo'])); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Evento Info -->
    <div class="mm-detail-card">
        <div class="mm-detail-header">
            <h2>📅 Dettagli Evento</h2>
            <div>
                <span class="mm-status-badge <?php echo esc_attr($preventivo['stato']); ?>">
                    <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                </span>
            </div>
        </div>
        <div class="mm-detail-grid">
            <div class="mm-detail-item">
                <div class="mm-detail-label">Data Evento</div>
                <div class="mm-detail-value"><?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?></div>
            </div>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Location</div>
                <div class="mm-detail-value"><?php echo esc_html($preventivo['location']); ?></div>
            </div>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Categoria Evento</div>
                <div class="mm-detail-value">
                    <?php
                    if (!empty($preventivo['categoria_nome'])) {
                        echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']);
                    } else {
                        echo 'Non specificato';
                    }
                    ?>
                </div>
            </div>
            <?php if (!empty($preventivo['tipo_evento'])) : ?>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Momento Evento</div>
                <div class="mm-detail-value"><?php echo esc_html($preventivo['tipo_evento']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($preventivo['cerimonia'])) : ?>
            <div class="mm-detail-item">
                <div class="mm-detail-label">Cerimonia</div>
                <div class="mm-detail-value"><?php echo esc_html(implode(', ', $preventivo['cerimonia'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Servizi -->
    <div class="mm-detail-card">
        <div class="mm-detail-header">
            <h2>🎉 Servizi Richiesti</h2>
        </div>
        <table class="mm-services-table">
            <thead>
                <tr>
                    <th>Servizio</th>
                    <th style="text-align: right;">Prezzo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preventivo['servizi'] as $servizio) : ?>
                <tr>
                    <td><?php echo esc_html($servizio['nome_servizio']); ?></td>
                    <td class="price">€ <?php echo number_format($servizio['prezzo'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (!empty($preventivo['servizi_extra'])) : ?>
        <div style="margin-top: 20px;">
            <strong>Servizi Aggiuntivi:</strong><br>
            <?php echo esc_html(implode(', ', $preventivo['servizi_extra'])); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($preventivo['note'])) : ?>
        <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
            <strong>Note:</strong><br>
            <?php echo nl2br(esc_html($preventivo['note'])); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Totali -->
    <div class="mm-detail-card">
        <div class="mm-detail-header">
            <h2>💰 Riepilogo Costi</h2>
        </div>
        <div class="mm-totals-summary">
            <div class="mm-total-row">
                <span>Totale Servizi:</span>
                <span>€ <?php echo number_format($preventivo['totale_servizi'], 2, ',', '.'); ?></span>
            </div>
            <div class="mm-total-row">
                <span>Ex Enpals (33%):</span>
                <span>€ <?php echo number_format($preventivo['enpals'], 2, ',', '.'); ?></span>
            </div>
            <div class="mm-total-row">
                <span>IVA (22%):</span>
                <span>€ <?php echo number_format($preventivo['iva'], 2, ',', '.'); ?></span>
            </div>
            <div class="mm-total-row grand-total">
                <span>TOTALE:</span>
                <span>€ <?php echo number_format($preventivo['totale'], 2, ',', '.'); ?></span>
            </div>
            
            <?php if (!empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto'])) : ?>
            <div class="mm-total-row" style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #e0e0e0;">
                <span>Acconto (<?php echo date('d/m/Y', strtotime($preventivo['data_acconto'])); ?>):</span>
                <span>€ <?php echo number_format($preventivo['importo_acconto'], 2, ',', '.'); ?></span>
            </div>
            <div class="mm-total-row" style="font-weight: bold; color: #f57c00;">
                <span>Saldo Residuo:</span>
                <span>€ <?php echo number_format($preventivo['totale'] - $preventivo['importo_acconto'], 2, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Azioni -->
    <div style="margin-top: 30px; display: flex; gap: 15px;">
        <button type="button" class="mm-btn-admin-primary mm-btn-pdf" data-id="<?php echo esc_attr($preventivo['id']); ?>">
            📥 Scarica PDF
        </button>
        <button type="button" class="mm-btn-admin-secondary mm-btn-delete" 
                data-id="<?php echo esc_attr($preventivo['id']); ?>" 
                data-numero="<?php echo esc_attr($preventivo['numero_preventivo']); ?>">
            🗑️ Elimina Preventivo
        </button>
        <a href="?page=mm-preventivi" class="mm-btn-admin-secondary">
            ← Torna all'elenco
        </a>
    </div>
    
</div>
