<?php
/**
 * Admin View: Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mm-admin-page">
    
    <div class="mm-admin-header">
        <h1>📊 Gestione Preventivi</h1>
        <p>Visualizza, modifica ed esporta i preventivi creati dai clienti</p>
    </div>
    
    <!-- Filters -->
    <div class="mm-filters-bar">
        <div class="mm-filter-group">
            <label for="filter-search">Cerca</label>
            <input type="text" id="filter-search" placeholder="Nome, email, numero..." value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
        </div>
        
        <div class="mm-filter-group">
            <label for="filter-stato">Stato</label>
            <select id="filter-stato">
                <option value="">Tutti</option>
                <option value="bozza" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'bozza'); ?>>Bozza</option>
                <option value="attivo" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'attivo'); ?>>Attivo</option>
                <option value="accettato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'accettato'); ?>>Accettato</option>
                <option value="rifiutato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'rifiutato'); ?>>Rifiutato</option>
                <option value="completato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'completato'); ?>>Completato</option>
            </select>
        </div>
        
        <div class="mm-filter-actions">
            <button type="button" class="mm-btn-admin-primary mm-filter-btn">🔍 Filtra</button>
            <button type="button" class="mm-btn-admin-secondary mm-reset-filter">✕ Reset</button>
        </div>
    </div>
    
    <?php if (empty($preventivi)) : ?>
        
        <div class="mm-table-container">
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">📋</div>
                <h3>Nessun preventivo trovato</h3>
                <p>I preventivi creati dai clienti appariranno qui.</p>
            </div>
        </div>
        
    <?php else : ?>
        
        <div class="mm-table-container">
            <table class="mm-table">
                <thead>
                    <tr>
                        <th>N. Preventivo</th>
                        <th>Cliente</th>
                        <th>Data Evento</th>
                        <th>Location</th>
                        <th class="number">Totale</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preventivi as $preventivo) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($preventivo['numero_preventivo']); ?></strong>
                            <br>
                            <small style="color: #999;">
                                <?php echo date('d/m/Y', strtotime($preventivo['data_preventivo'])); ?>
                            </small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($preventivo['sposi']); ?></strong>
                            <?php if (!empty($preventivo['email'])) : ?>
                                <br><small><?php echo esc_html($preventivo['email']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($preventivo['telefono'])) : ?>
                                <br><small>📞 <?php echo esc_html($preventivo['telefono']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?>
                            <br>
                            <small>
                                <?php
                                if (!empty($preventivo['categoria_nome'])) {
                                    echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']);
                                } else {
                                    echo 'Non specificato';
                                }
                                ?>
                            </small>
                        </td>
                        <td><?php echo esc_html($preventivo['location']); ?></td>
                        <td class="number">€ <?php echo number_format($preventivo['totale'], 2, ',', '.'); ?></td>
                        <td>
                            <span class="mm-status-badge <?php echo esc_attr($preventivo['stato']); ?>">
                                <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="mm-actions">
                                <a href="?page=mm-preventivi&action=view&id=<?php echo esc_attr($preventivo['id']); ?>"
                                   class="mm-btn-icon view"
                                   title="Visualizza">
                                    👁️
                                </a>

                                <a href="?page=mm-preventivi&action=edit&id=<?php echo esc_attr($preventivo['id']); ?>"
                                   class="mm-btn-icon edit"
                                   title="Modifica">
                                    ✏️
                                </a>

                                <?php if ($preventivo['stato'] === 'attivo') : ?>
                                    <button type="button"
                                            class="mm-btn-icon mm-btn-quick-status"
                                            data-id="<?php echo esc_attr($preventivo['id']); ?>"
                                            data-status="accettato"
                                            title="Segna come Accettato"
                                            style="background: #4caf50; color: white;">
                                        ✓
                                    </button>
                                    <button type="button"
                                            class="mm-btn-icon mm-btn-quick-status"
                                            data-id="<?php echo esc_attr($preventivo['id']); ?>"
                                            data-status="rifiutato"
                                            title="Segna come Rifiutato"
                                            style="background: #f44336; color: white;">
                                        ✗
                                    </button>
                                <?php endif; ?>

                                <button type="button"
                                        class="mm-btn-icon pdf mm-btn-pdf"
                                        data-id="<?php echo esc_attr($preventivo['id']); ?>"
                                        title="Esporta PDF">
                                    📄
                                </button>
                                <button type="button"
                                        class="mm-btn-icon delete mm-btn-delete"
                                        data-id="<?php echo esc_attr($preventivo['id']); ?>"
                                        data-numero="<?php echo esc_attr($preventivo['numero_preventivo']); ?>"
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
        
        <div style="margin-top: 20px; text-align: center; color: #666;">
            <p>Totale: <strong><?php echo count($preventivi); ?></strong> preventivi trovati</p>
        </div>
        
    <?php endif; ?>
    
</div>
