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

$current_user = wp_get_current_user();

// Filtri
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
$categorie = MM_Database::get_tipi_evento(true);
?>

<div class="mm-dashboard-container">
    <style>
        .mm-dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .mm-dashboard-header {
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
        }

        .mm-dashboard-header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .mm-dashboard-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .mm-dashboard-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .mm-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .mm-filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mm-filter-group select,
        .mm-filter-group input {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
            min-width: 180px;
        }

        .mm-filter-group select:focus,
        .mm-filter-group input:focus {
            outline: none;
            border-color: #e91e63;
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
        }

        .mm-filter-btn {
            background: #e91e63;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: auto;
        }

        .mm-filter-btn:hover {
            background: #c2185b;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(233, 30, 99, 0.3);
        }

        .mm-preventivi-table-wrapper {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mm-preventivi-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mm-preventivi-table thead {
            background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%);
            color: white;
        }

        .mm-preventivi-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .mm-preventivi-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
        }

        .mm-preventivi-table tbody tr:hover {
            background: #fafafa;
        }

        .mm-preventivi-table td {
            padding: 14px 16px;
            font-size: 14px;
            color: #333;
        }

        .mm-stato-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .mm-stato-attivo {
            background: #e3f2fd;
            color: #1976d2;
        }

        .mm-stato-confermato,
        .mm-stato-accettato {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .mm-stato-scaduto {
            background: #fff3e0;
            color: #f57c00;
        }

        .mm-stato-annullato {
            background: #ffebee;
            color: #c62828;
        }

        .mm-categoria-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(233, 30, 99, 0.1) 0%, rgba(156, 39, 176, 0.1) 100%);
            border-left: 3px solid #e91e63;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #e91e63;
        }

        .mm-tipo-evento-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            color: #666;
        }

        .mm-action-btn {
            padding: 6px 14px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .mm-btn-email {
            background: #2196f3;
            color: white;
        }

        .mm-btn-email:hover {
            background: #1976d2;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(33, 150, 243, 0.3);
        }

        .mm-btn-view {
            background: #4caf50;
            color: white;
            margin-right: 5px;
        }

        .mm-btn-view:hover {
            background: #388e3c;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }

        .mm-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .mm-empty-state-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .mm-empty-state h3 {
            font-size: 20px;
            color: #666;
            margin: 0 0 10px 0;
        }

        .mm-empty-state p {
            font-size: 14px;
            margin: 0;
        }

        .mm-loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: mm-spin 0.6s linear infinite;
        }

        @keyframes mm-spin {
            to { transform: rotate(360deg); }
        }

        .mm-totale-badge {
            font-weight: 700;
            color: #e91e63;
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .mm-dashboard-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .mm-filter-group select,
            .mm-filter-group input {
                min-width: 100%;
            }

            .mm-preventivi-table {
                font-size: 12px;
            }

            .mm-preventivi-table th,
            .mm-preventivi-table td {
                padding: 10px 8px;
            }

            .mm-action-btn {
                padding: 5px 10px;
                font-size: 11px;
            }
        }
    </style>

    <div class="mm-dashboard-header">
        <h1>📋 Dashboard Preventivi</h1>
        <p>Gestisci e monitora tutti i tuoi preventivi</p>
    </div>

    <!-- Navigation Links -->
    <div style="margin: 20px 0; text-align: center;">
        <a href="<?php echo home_url('/dashboard-preventivi/'); ?>" style="display: inline-block; padding: 10px 20px; background: #e91e63; color: white; text-decoration: none; border-radius: 6px; margin: 0 5px; font-weight: 600;">📋 Dashboard</a>
        <a href="<?php echo home_url('/lista-preventivi/'); ?>" style="display: inline-block; padding: 10px 20px; background: #757575; color: white; text-decoration: none; border-radius: 6px; margin: 0 5px; font-weight: 600;">📊 Tutti i Preventivi</a>
        <a href="<?php echo home_url('/statistiche-preventivi/'); ?>" style="display: inline-block; padding: 10px 20px; background: #757575; color: white; text-decoration: none; border-radius: 6px; margin: 0 5px; font-weight: 600;">📈 Statistiche</a>
        <a href="<?php echo home_url('/nuovo-preventivo/'); ?>" style="display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin: 0 5px; font-weight: 600;">➕ Nuovo Preventivo</a>
    </div>

    <!-- Filtri -->
    <form method="get" class="mm-dashboard-filters">
        <div class="mm-filter-group">
            <label>Cerca</label>
            <input type="text" name="search" placeholder="Nome cliente, location..." value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
        </div>

        <div class="mm-filter-group">
            <label>Stato</label>
            <select name="stato">
                <option value="">Tutti gli stati</option>
                <option value="attivo" <?php selected($_GET['stato'] ?? '', 'attivo'); ?>>Attivo</option>
                <option value="confermato" <?php selected($_GET['stato'] ?? '', 'confermato'); ?>>Confermato</option>
                <option value="accettato" <?php selected($_GET['stato'] ?? '', 'accettato'); ?>>Accettato</option>
                <option value="scaduto" <?php selected($_GET['stato'] ?? '', 'scaduto'); ?>>Scaduto</option>
                <option value="annullato" <?php selected($_GET['stato'] ?? '', 'annullato'); ?>>Annullato</option>
            </select>
        </div>

        <div class="mm-filter-group">
            <label>Categoria</label>
            <select name="categoria_id">
                <option value="">Tutte le categorie</option>
                <?php foreach ($categorie as $cat) : ?>
                    <option value="<?php echo $cat['id']; ?>" <?php selected($_GET['categoria_id'] ?? '', $cat['id']); ?>>
                        <?php echo esc_html($cat['icona'] . ' ' . $cat['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mm-filter-group">
            <label>Ordina per</label>
            <select name="order_by">
                <option value="data_evento_desc" <?php selected($_GET['order_by'] ?? '', 'data_evento_desc'); ?>>Data Evento ↓</option>
                <option value="data_evento_asc" <?php selected($_GET['order_by'] ?? '', 'data_evento_asc'); ?>>Data Evento ↑</option>
                <option value="data_preventivo_desc" <?php selected($_GET['order_by'] ?? '', 'data_preventivo_desc'); ?>>Data Preventivo ↓</option>
                <option value="data_preventivo_asc" <?php selected($_GET['order_by'] ?? '', 'data_preventivo_asc'); ?>>Data Preventivo ↑</option>
                <option value="numero_preventivo_desc" <?php selected($_GET['order_by'] ?? '', 'numero_preventivo_desc'); ?>>Numero ↓</option>
                <option value="numero_preventivo_asc" <?php selected($_GET['order_by'] ?? '', 'numero_preventivo_asc'); ?>>Numero ↑</option>
                <option value="totale_desc" <?php selected($_GET['order_by'] ?? '', 'totale_desc'); ?>>Importo ↓</option>
                <option value="totale_asc" <?php selected($_GET['order_by'] ?? '', 'totale_asc'); ?>>Importo ↑</option>
            </select>
        </div>

        <button type="submit" class="mm-filter-btn">🔍 Filtra</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['stato']) || !empty($_GET['categoria_id']) || !empty($_GET['order_by'])) : ?>
            <a href="?" class="mm-filter-btn" style="background: #757575; text-decoration: none;">✕ Pulisci</a>
        <?php endif; ?>
    </form>

    <!-- Tabella Preventivi -->
    <div class="mm-preventivi-table-wrapper">
        <?php if (empty($preventivi)) : ?>
            <div class="mm-empty-state">
                <div class="mm-empty-state-icon">📭</div>
                <h3>Nessun preventivo trovato</h3>
                <p>Non ci sono preventivi che corrispondono ai criteri di ricerca.</p>
            </div>
        <?php else : ?>
            <table class="mm-preventivi-table">
                <thead>
                    <tr>
                        <th>N. Preventivo</th>
                        <th>Cliente</th>
                        <th>Categoria</th>
                        <th>Data Preventivo</th>
                        <th>Data Evento</th>
                        <th>Momento</th>
                        <th>Location</th>
                        <th>Imponibile</th>
                        <th>Totale</th>
                        <th>Acconti</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preventivi as $preventivo) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($preventivo['numero_preventivo']); ?></strong></td>
                            <td><?php echo esc_html($preventivo['sposi']); ?></td>
                            <td>
                                <?php if (!empty($preventivo['categoria_nome'])) : ?>
                                    <span class="mm-categoria-badge">
                                        <?php echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($preventivo['data_preventivo'])); ?></td>
                            <td><strong><?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?></strong></td>
                            <td>
                                <?php if (!empty($preventivo['tipo_evento'])) : ?>
                                    <span class="mm-tipo-evento-badge"><?php echo esc_html($preventivo['tipo_evento']); ?></span>
                                <?php else : ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($preventivo['location']); ?></td>
                            <?php
                            // Calcola subtotale (totale servizi - sconti)
                            $totale_servizi = floatval($preventivo['totale_servizi']);
                            $sconto_fisso = floatval($preventivo['sconto']);
                            $sconto_percentuale = floatval($preventivo['sconto_percentuale']);
                            $sconto_perc_importo = $totale_servizi * ($sconto_percentuale / 100);
                            $imponibile = $totale_servizi - $sconto_fisso - $sconto_perc_importo;
                            $totale_acconti = isset($preventivo['totale_acconti']) ? floatval($preventivo['totale_acconti']) : 0;
                            ?>
                            <td><span class="mm-imponibile-badge" style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #666;"><?php echo number_format($imponibile, 2, ',', '.'); ?> €</span></td>
                            <td><span class="mm-totale-badge"><?php echo number_format($preventivo['totale'], 2, ',', '.'); ?> €</span></td>
                            <td>
                                <?php if ($totale_acconti > 0) : ?>
                                    <span class="mm-acconto-badge" style="background: #e8f5e9; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #2e7d32;">💰 <?php echo number_format($totale_acconti, 2, ',', '.'); ?> €</span>
                                <?php else : ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="mm-stato-badge mm-stato-<?php echo esc_attr($preventivo['stato']); ?>">
                                    <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                                </span>
                            </td>
                            <td>
                                <button class="mm-action-btn mm-btn-view" onclick="window.open('?page=mm-preventivi&action=view&id=<?php echo $preventivo['id']; ?>', '_blank')">
                                    👁 Vedi
                                </button>
                                <button class="mm-action-btn mm-btn-email mm-send-email-btn"
                                        data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                        data-cliente="<?php echo esc_attr($preventivo['sposi']); ?>"
                                        data-email="<?php echo esc_attr($preventivo['email']); ?>">
                                    📧 Email
                                </button>
                                <button class="mm-action-btn mm-btn-whatsapp mm-send-whatsapp-btn"
                                        data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                        data-telefono="<?php echo esc_attr($preventivo['telefono']); ?>">
                                    💬 WhatsApp
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Mobile Cards (visibili solo su mobile) -->
            <div class="mm-dashboard-mobile-cards" style="display: none;">
                <?php foreach ($preventivi as $preventivo) :
                    // Calcola imponibile
                    $totale_servizi = floatval($preventivo['totale_servizi']);
                    $sconto_fisso = floatval($preventivo['sconto']);
                    $sconto_percentuale = floatval($preventivo['sconto_percentuale']);
                    $sconto_perc_importo = $totale_servizi * ($sconto_percentuale / 100);
                    $imponibile = $totale_servizi - $sconto_fisso - $sconto_perc_importo;
                    $totale_acconti = isset($preventivo['totale_acconti']) ? floatval($preventivo['totale_acconti']) : 0;

                    $stato_class = '';
                    switch($preventivo['stato']) {
                        case 'attivo': $stato_class = 'stato-attivo'; break;
                        case 'accettato': $stato_class = 'stato-accettato'; break;
                        case 'confermato': $stato_class = 'stato-confermato'; break;
                        case 'scaduto': $stato_class = 'stato-scaduto'; break;
                        case 'annullato': $stato_class = 'stato-annullato'; break;
                    }
                ?>
                <div class="mm-preventivo-card" style="margin-bottom: 15px; background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e0e0e0;">
                        <div>
                            <div style="font-weight: 700; font-size: 16px; color: #e91e63;">
                                <?php echo esc_html($preventivo['numero_preventivo']); ?>
                            </div>
                            <div style="font-size: 14px; color: #333; margin-top: 4px;">
                                <?php echo esc_html($preventivo['sposi']); ?>
                            </div>
                        </div>
                        <span class="<?php echo $stato_class; ?>" style="padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                            <?php echo esc_html(ucfirst($preventivo['stato'])); ?>
                        </span>
                    </div>

                    <!-- Info -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; font-size: 13px;">
                        <?php if (!empty($preventivo['categoria_nome'])) : ?>
                        <div>
                            <div style="color: #666; font-size: 11px; text-transform: uppercase; margin-bottom: 2px;">Categoria</div>
                            <div style="color: #333;"><?php echo esc_html($preventivo['categoria_icona'] . ' ' . $preventivo['categoria_nome']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div>
                            <div style="color: #666; font-size: 11px; text-transform: uppercase; margin-bottom: 2px;">Data Evento</div>
                            <div style="color: #333;"><?php echo esc_html(date('d/m/Y', strtotime($preventivo['data_evento']))); ?></div>
                        </div>

                        <?php if (!empty($preventivo['location'])) : ?>
                        <div style="grid-column: 1 / -1;">
                            <div style="color: #666; font-size: 11px; text-transform: uppercase; margin-bottom: 2px;">Location</div>
                            <div style="color: #333;"><?php echo esc_html($preventivo['location']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Totali -->
                    <div style="background: #f5f5f5; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                            <span style="color: #666;">Imponibile:</span>
                            <span style="color: #333; font-weight: 600;">€ <?php echo number_format($imponibile, 2, ',', '.'); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px solid #ddd; font-size: 14px;">
                            <span style="color: #333; font-weight: 600;">Totale:</span>
                            <span style="color: #e91e63; font-weight: 700;">€ <?php echo number_format($preventivo['totale'], 2, ',', '.'); ?></span>
                        </div>
                        <?php if ($totale_acconti > 0) : ?>
                        <div style="display: flex; justify-content: space-between; margin-top: 6px; padding-top: 6px; border-top: 1px solid #ddd; font-size: 13px;">
                            <span style="color: #2e7d32; font-weight: 600;">💰 Acconti:</span>
                            <span style="color: #2e7d32; font-weight: 700;">€ <?php echo number_format($totale_acconti, 2, ',', '.'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Azioni -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button class="mm-action-btn mm-btn-view"
                                style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); color: white; padding: 10px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 44px;"
                                onclick="alert('Dettagli preventivo - da implementare')">
                            👁 Vedi
                        </button>
                        <button class="mm-send-email-btn"
                                style="background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%); color: white; padding: 10px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 44px;"
                                data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                data-cliente="<?php echo esc_attr($preventivo['sposi']); ?>"
                                data-email="<?php echo esc_attr($preventivo['email']); ?>">
                            📧 Email
                        </button>
                        <button class="mm-send-whatsapp-btn"
                                style="background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); color: white; padding: 10px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 44px;"
                                data-preventivo-id="<?php echo $preventivo['id']; ?>"
                                data-telefono="<?php echo esc_attr($preventivo['telefono']); ?>">
                            💬 WhatsApp
                        </button>
                        <button class="mm-action-btn"
                                style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; padding: 10px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 44px;"
                                onclick="window.location.href='<?php echo add_query_arg('id', $preventivo['id'], home_url('/modifica-preventivo/')); ?>'">
                            ✏️ Modifica
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Mostra card mobile solo su schermi piccoli */
@media only screen and (max-width: 768px) {
    .mm-preventivi-table {
        display: none !important;
    }

    .mm-dashboard-mobile-cards {
        display: block !important;
    }

    .stato-attivo {
        background: #fff3e0;
        color: #e65100;
    }

    .stato-accettato {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .stato-confermato {
        background: #e3f2fd;
        color: #1565c0;
    }

    .stato-scaduto {
        background: #ffebee;
        color: #c62828;
    }

    .stato-annullato {
        background: #f5f5f5;
        color: #616161;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('Dashboard preventivi caricata');

    // Gestione invio email
    $('.mm-send-email-btn').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const preventivoId = $btn.data('preventivo-id');
        const cliente = $btn.data('cliente');
        const email = $btn.data('email');

        console.log('Invio email per preventivo:', preventivoId, cliente, email);

        if (!email) {
            alert('Questo preventivo non ha un indirizzo email associato.');
            return;
        }

        if (!confirm('Vuoi inviare il PDF del preventivo a ' + cliente + ' (' + email + ')?')) {
            return;
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="mm-loading"></span> Invio...');

        const ajaxData = {
            action: 'mm_send_preventivo_email',
            preventivo_id: preventivoId,
            nonce: '<?php echo wp_create_nonce('mm_send_email'); ?>'
        };

        console.log('Invio AJAX:', ajaxData);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                console.log('Risposta AJAX:', response);
                if (response && response.success) {
                    alert('✅ Email inviata con successo!');
                } else {
                    const errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Impossibile inviare l\'email';
                    alert('❌ Errore: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error completo:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });

                let errorMessage = 'Errore di connessione';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch(e) {
                    errorMessage = xhr.responseText || error || 'Errore sconosciuto';
                }

                alert('❌ Errore: ' + errorMessage);
            },
            complete: function() {
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
        $btn.prop('disabled', true).html('<span class="mm-loading"></span> Generazione...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mm_get_whatsapp_link',
                nonce: '<?php echo wp_create_nonce('mm_send_email'); ?>',
                preventivo_id: preventivoId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Risposta WhatsApp:', response);
                if (response && response.success && response.data.link) {
                    // Apri WhatsApp in una nuova finestra
                    window.open(response.data.link, '_blank');
                    alert('✅ Link WhatsApp aperto!');
                } else {
                    const errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Impossibile generare il link WhatsApp';
                    alert('❌ Errore: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                const errorMessage = xhr.responseText || error || 'Errore di connessione';
                alert('❌ Errore: ' + errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
