<?php
/**
 * Admin View: Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">

    <div class="mm-dash-header">
        <h1>Preventivi</h1>
        <div class="mm-dash-filters">
            <input type="text" id="filter-search" placeholder="Cerca..." value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
            <select id="filter-stato">
                <option value="">Stato</option>
                <option value="bozza" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'bozza'); ?>>Bozza</option>
                <option value="attivo" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'attivo'); ?>>Attivo</option>
                <option value="accettato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'accettato'); ?>>Accettato</option>
                <option value="rifiutato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'rifiutato'); ?>>Rifiutato</option>
                <option value="completato" <?php selected(isset($_GET['stato']) && $_GET['stato'] === 'completato'); ?>>Completato</option>
            </select>
            <button type="button" id="mm-apply-filter">Filtra</button>
            <button type="button" id="mm-reset-filter">Reset</button>
        </div>
    </div>

    <?php if (empty($preventivi)) : ?>
        <p style="padding:20px;background:#fff;border:1px solid #ddd;text-align:center;color:#666;">Nessun preventivo trovato</p>
    <?php else : ?>

        <table class="mm-dash-table">
            <thead>
                <tr>
                    <th>N.</th>
                    <th>Cliente</th>
                    <th>Data</th>
                    <th>Totale</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preventivi as $p) : ?>
                <tr>
                    <td class="td-num"><?php echo esc_html($p['numero_preventivo']); ?></td>
                    <td class="td-cliente">
                        <?php echo esc_html($p['sposi']); ?>
                        <?php if (!empty($p['location'])) : ?>
                            <small><?php echo esc_html($p['location']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="td-data">
                        <?php echo date('d/m/Y', strtotime($p['data_evento'])); ?>
                        <?php if (!empty($p['categoria_icona'])) echo esc_html($p['categoria_icona']); ?>
                    </td>
                    <td class="td-totale"><?php echo number_format($p['totale'], 2, ',', '.'); ?>&euro;</td>
                    <td class="td-stato">
                        <span class="badge-<?php echo esc_attr($p['stato']); ?>"><?php echo ucfirst($p['stato']); ?></span>
                    </td>
                    <td class="td-azioni">
                        <a href="<?php echo admin_url('admin.php?page=mm-preventivi&action=edit&id=' . intval($p['id'])); ?>">Modifica</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="text-align:right;color:#888;font-size:11px;margin-top:8px;"><?php echo count($preventivi); ?> preventivi</p>

    <?php endif; ?>

</div>

<style>
.mm-dash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0 15px 0;
    padding: 10px 15px;
    background: #fff;
    border: 1px solid #ccc;
}
.mm-dash-header h1 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}
.mm-dash-filters {
    display: flex;
    gap: 6px;
}
.mm-dash-filters input,
.mm-dash-filters select {
    height: 28px;
    padding: 0 8px;
    font-size: 12px;
    border: 1px solid #ccc;
}
.mm-dash-filters input { width: 140px; }
.mm-dash-filters select { width: 100px; }
.mm-dash-filters button {
    height: 28px;
    padding: 0 10px;
    font-size: 11px;
    border: none;
    cursor: pointer;
    background: #e91e63;
    color: #fff;
}
.mm-dash-filters button:hover { background: #c2185b; }
#mm-reset-filter { background: #888; }
#mm-reset-filter:hover { background: #666; }

.mm-dash-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #ccc;
    font-size: 12px;
    table-layout: fixed;
}
.mm-dash-table th {
    background: #f5f5f5;
    padding: 8px 6px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    color: #555;
    border-bottom: 2px solid #ddd;
}
.mm-dash-table td {
    padding: 6px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mm-dash-table tbody tr:hover { background: #fafafa; }

/* Colonne fisse */
.mm-dash-table th:nth-child(1),
.mm-dash-table td:nth-child(1) { width: 70px; }
.mm-dash-table th:nth-child(2),
.mm-dash-table td:nth-child(2) { width: auto; }
.mm-dash-table th:nth-child(3),
.mm-dash-table td:nth-child(3) { width: 80px; }
.mm-dash-table th:nth-child(4),
.mm-dash-table td:nth-child(4) { width: 70px; text-align: right; }
.mm-dash-table th:nth-child(5),
.mm-dash-table td:nth-child(5) { width: 70px; text-align: center; }
.mm-dash-table th:nth-child(6),
.mm-dash-table td:nth-child(6) { width: 70px; text-align: center; }

.td-num { font-weight: 600; color: #333; }
.td-cliente small {
    display: block;
    font-size: 10px;
    color: #888;
    margin-top: 1px;
}
.td-data { font-size: 11px; }
.td-totale { font-weight: 600; }

/* Badge stato */
.mm-dash-table td span[class^="badge-"] {
    display: inline-block;
    padding: 2px 6px;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 2px;
}
.badge-bozza { background: #e0e0e0; color: #555; }
.badge-attivo { background: #e3f2fd; color: #1565c0; }
.badge-accettato { background: #e8f5e9; color: #2e7d32; }
.badge-rifiutato { background: #ffebee; color: #c62828; }
.badge-completato { background: #f3e5f5; color: #7b1fa2; }

/* Link modifica */
.td-azioni a {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 500;
    color: #fff;
    background: #e91e63;
    text-decoration: none;
    border-radius: 2px;
}
.td-azioni a:hover {
    background: #c2185b;
    color: #fff;
}

@media screen and (max-width: 900px) {
    .mm-dash-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .mm-dash-filters { flex-wrap: wrap; }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#mm-apply-filter').on('click', function() {
        var search = $('#filter-search').val();
        var stato = $('#filter-stato').val();
        var url = '<?php echo admin_url('admin.php?page=mm-preventivi'); ?>';
        if (search) url += '&search=' + encodeURIComponent(search);
        if (stato) url += '&stato=' + encodeURIComponent(stato);
        window.location.href = url;
    });
    $('#mm-reset-filter').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=mm-preventivi'); ?>';
    });
    $('#filter-search').on('keypress', function(e) {
        if (e.which === 13) $('#mm-apply-filter').click();
    });
});
</script>
