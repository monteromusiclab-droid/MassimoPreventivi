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

        <button type="submit" class="mm-filter-btn">🔍 Filtra</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['stato']) || !empty($_GET['categoria_id'])) : ?>
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
                        <th>Totale</th>
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
                            <td><span class="mm-totale-badge"><?php echo number_format($preventivo['totale'], 2, ',', '.'); ?> €</span></td>
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
                                    📧 Invia PDF
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

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
});
</script>
