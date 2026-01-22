<?php
/**
 * Generatore PDF - Massimo Manca Preventivi
 * Supporta DOMPDF (PHP 8+), TCPDF, con fallback HTML
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carica DOMPDF se disponibile
$dompdf_autoload = dirname(__FILE__) . '/dompdf/autoload.php';
if (file_exists($dompdf_autoload)) {
    require_once $dompdf_autoload;
}

use Dompdf\Dompdf;
use Dompdf\Options;

class MM_PDF_Generator {

    /**
     * Genera PDF preventivo (visualizzazione HTML con pulsante download PDF)
     */
    public static function generate_pdf($preventivo) {
        // Visualizza sempre HTML responsive con pulsante per scaricare PDF
        self::generate_html_pdf($preventivo);
    }

    /**
     * Genera e scarica PDF vero usando DOMPDF
     */
    public static function download_real_pdf($preventivo) {
        // Prova DOMPDF (compatibile con PHP 8+)
        if (class_exists('Dompdf\Dompdf')) {
            try {
                self::generate_dompdf($preventivo);
                return;
            } catch (Exception $e) {
                error_log('MM Preventivi - Errore DOMPDF: ' . $e->getMessage());
            }
        }

        // Fallback: TCPDF per PHP < 8
        if (!class_exists('TCPDF')) {
            $tcpdf_paths = array(
                ABSPATH . 'wp-content/plugins/tcpdf/tcpdf.php',
                dirname(__FILE__) . '/tcpdf/tcpdf.php',
            );

            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
        }

        if (class_exists('TCPDF')) {
            try {
                self::generate_tcpdf($preventivo);
                return;
            } catch (Exception $e) {
                error_log('MM Preventivi - Errore TCPDF: ' . $e->getMessage());
            }
        }

        // Ultimo fallback: genera HTML e suggerisci stampa come PDF
        wp_die('Impossibile generare il PDF. Usa la funzione "Stampa" del browser e seleziona "Salva come PDF".');
    }

    /**
     * Genera PDF con DOMPDF (compatibile PHP 8+)
     */
    private static function generate_dompdf($preventivo) {
        // Configura DOMPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // Genera HTML per il PDF
        $html = self::generate_pdf_html_content($preventivo);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output PDF
        $filename = 'Preventivo_' . $preventivo['numero_preventivo'] . '.pdf';
        $dompdf->stream($filename, array('Attachment' => true));
        exit;
    }

    /**
     * Genera HTML per PDF identico alla versione stampata
     * Replica esatto layout dell'anteprima HTML con stili @media print applicati
     * e conversione flex/grid in tabelle per compatibilità DOMPDF
     */
    private static function generate_pdf_html_content($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Variabili categoria e stato
        $categoria_nome = !empty($preventivo['categoria_nome']) ? strtolower($preventivo['categoria_nome']) : '';
        $label_cliente = ($categoria_nome === 'matrimonio') ? 'Sposi' : 'Cliente';
        $stato = !empty($preventivo['stato']) ? $preventivo['stato'] : 'attivo';
        $stato_label = ucfirst($stato);
        $categoria_display = !empty($preventivo['categoria_nome']) ? $preventivo['categoria_nome'] : 'Non specificato';
        $categoria_icona = !empty($preventivo['categoria_icona']) ? $preventivo['categoria_icona'] : '';

        // Colori stato
        $stato_colors = array(
            'bozza' => '#ff9800',
            'attivo' => '#4caf50',
            'inviato' => '#2196f3',
            'accettato' => '#8bc34a',
            'rifiutato' => '#f44336',
            'archiviato' => '#9e9e9e'
        );
        $stato_color = isset($stato_colors[$stato]) ? $stato_colors[$stato] : '#4caf50';

        // Calcoli finanziari (identici a generate_html_pdf)
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;
        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }
        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        $applica_enpals = isset($preventivo['applica_enpals']) && $preventivo['applica_enpals'] == 1;
        $applica_iva = isset($preventivo['applica_iva']) && $preventivo['applica_iva'] == 1;

        $enpals_committente_percentage = floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81));
        $enpals_lavoratore_percentage = floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19));
        $iva_percentage = floatval(get_option('mm_preventivi_iva_percentage', 22));

        $enpals_committente = $applica_enpals ? ($totale_dopo_sconto * ($enpals_committente_percentage / 100)) : 0;
        $enpals_lavoratore = $applica_enpals ? ($totale_dopo_sconto * ($enpals_lavoratore_percentage / 100)) : 0;
        $imponibile_iva = $totale_dopo_sconto + $enpals_committente;
        $iva = $applica_iva ? ($imponibile_iva * ($iva_percentage / 100)) : 0;
        $totale = $imponibile_iva + $iva;

        // Evidenzia totale servizi se non ci sono sconti
        $evidenzia_totale_servizi = ($importo_sconto == 0);
        $style_totale_servizi = $evidenzia_totale_servizi ? 'background-color: #fff9c4;' : '';

        // HTML con stili di stampa applicati direttamente (no flex/grid per DOMPDF)
        $html = '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Preventivo ' . esc_html($preventivo['numero_preventivo']) . '</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: white;
            padding: 20mm;
        }
        .container {
            width: 100%;
            padding: 0;
        }

        /* Header con tabella invece di flex */
        .header {
            border-bottom: 4px solid #e91e63;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; border: none; }
        .logo { max-height: 80px; }
        .company-name { color: #e91e63; font-size: 20px; font-weight: bold; }
        .subtitle { color: #666; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .preventivo-title { color: #e91e63; font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .preventivo-numero { color: #666; font-size: 13px; }

        /* Tipo evento e stato con tabella */
        .tipo-stato-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 10px 0;
        }
        .tipo-evento-cell {
            background-color: #e91e63;
            color: white;
            padding: 8px 15px;
            font-weight: 600;
            font-size: 11px;
        }
        .stato-cell {
            background-color: ' . $stato_color . ';
            color: white;
            padding: 8px 15px;
            font-weight: 600;
            font-size: 11px;
            text-align: center;
            width: 200px;
        }

        /* Info grid con tabella invece di grid */
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px 0;
            margin: 15px 0;
        }
        .info-table td { width: 50%; vertical-align: top; padding: 0; }
        .info-box {
            background: #f8f8f8;
            padding: 15px;
            border: 2px solid #e91e63;
        }
        .info-box-title {
            color: #e91e63;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e91e63;
        }
        .info-row { margin: 6px 0; font-size: 12px; line-height: 1.5; }

        /* Rito box */
        .rito-box {
            margin: 10px 0 15px 0;
            padding: 8px 12px;
            background: #fff3e0;
            border-left: 4px solid #ff9800;
        }
        .rito-label { font-size: 10px; color: #e65100; font-weight: 700; }
        .rito-value { font-size: 11px; color: #bf360c; font-weight: 600; margin-left: 8px; }

        /* Servizi extra */
        .extra-box {
            margin: 15px 0;
            padding: 12px 15px;
            background: #f5f5f5;
            border-left: 4px solid #9c27b0;
        }
        .extra-tag {
            display: inline-block;
            margin: 4px 6px;
            padding: 5px 10px;
            background: #ffffff;
            border: 1px solid #d0d0d0;
            font-size: 10px;
            font-weight: 500;
            color: #424242;
        }
        .checkbox-icon {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #9c27b0;
            color: white;
            text-align: center;
            line-height: 12px;
            font-size: 9px;
            margin-right: 5px;
        }

        /* Titoli sezione */
        h2 {
            color: #e91e63;
            font-size: 14px;
            margin: 20px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8bbd0;
        }

        /* Tabelle */
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #e91e63; color: white; font-weight: bold; font-size: 11px; }
        td:last-child { text-align: right; }

        /* Two column layout con tabella */
        .two-col-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px 0;
            margin: 20px 0;
        }
        .two-col-table > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        .column-box {
            background: #fafafa;
            padding: 15px;
            border: 2px solid #e91e63;
        }
        .column-box h3 {
            margin: 0 0 10px 0;
            color: #e91e63;
            font-size: 13px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8bbd0;
        }

        /* Tabella totali interna */
        .totali-table { width: 100%; border-collapse: collapse; }
        .totali-table td { border: none; padding: 5px 0; font-size: 11px; }
        .totali-table .right { text-align: right; font-weight: bold; }
        .totali-table .sconto { color: #4caf50; }
        .totali-table .highlight { background-color: #fff9c4; }
        .totali-table .enpals-detail { font-size: 9px; color: #666; background-color: #fffaf0; }
        .totali-table .total-row { border-top: 3px solid #e91e63; background: #f8bbd0; }
        .totali-table .total-row td { padding: 10px 0; font-size: 15px; font-weight: bold; color: #e91e63; }

        /* Validità */
        .validita-box {
            font-size: 10px;
            color: #666;
            font-style: italic;
            margin: 12px 0;
            padding: 8px 12px;
            background: #fffaf0;
            border-left: 3px solid #ff9800;
        }

        /* Acconti */
        .acconto {
            background: #e8f5e9;
            padding: 12px 15px;
            border-left: 4px solid #4caf50;
            margin: 15px 0;
            font-size: 11px;
        }
        .acconto p { margin: 4px 0; }

        /* Altri servizi */
        .altri-servizi-box {
            background: #f8f8f8;
            padding: 15px;
            border: 1px solid #e0e0e0;
            margin-top: 10px;
        }
        .altri-servizi-note {
            margin: 10px 0 0 0;
            font-size: 9px;
            color: #999;
            font-style: italic;
        }

        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid #e91e63;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .footer strong { color: #e91e63; }
    </style>
</head>
<body>
<div class="container">

    <!-- Header (tabella invece di flex) -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">';

        if (!empty($company_logo)) {
            $html .= '<img src="' . esc_url($company_logo) . '" class="logo"><br>';
        }

        $html .= '<div class="company-name">' . esc_html($company_name) . '</div>
                    <p class="subtitle">DJ - Animazione - Scenografie - Photo Booth</p>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="preventivo-title">PREVENTIVO</div>
                    <div class="preventivo-numero">N. ' . esc_html($preventivo['numero_preventivo']) . '</div>
                    <div class="preventivo-numero" style="margin-top: 5px;">del ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tipo Evento e Stato (tabella invece di flex) -->
    <table class="tipo-stato-table">
        <tr>
            <td class="tipo-evento-cell">TIPO DI EVENTO RICHIESTO: ' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</td>
            <td class="stato-cell">STATO: ' . strtoupper(esc_html($stato_label)) . '</td>
        </tr>
    </table>

    <!-- Info Grid (tabella invece di grid) -->
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-box-title">' . esc_html($label_cliente) . '</div>
                    <div class="info-row"><strong style="font-size: 16px;">' . esc_html($preventivo['sposi']) . '</strong></div>
                    <div class="info-row">' . esc_html($preventivo['email']) . '</div>
                    <div class="info-row">' . esc_html($preventivo['telefono']) . '</div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-box-title">Dettagli Evento</div>
                    <div class="info-row"><strong style="font-size: 16px;">' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</strong></div>
                    <div class="info-row">' . esc_html($preventivo['tipo_evento']) . '</div>
                    <div class="info-row">' . esc_html($preventivo['location']) . '</div>
                </div>
            </td>
        </tr>
    </table>';

        // Rito/Cerimonia
        $cerimonia_array = !empty($preventivo['cerimonia']) ? (is_array($preventivo['cerimonia']) ? $preventivo['cerimonia'] : explode(',', $preventivo['cerimonia'])) : array();
        if (!empty($cerimonia_array)) {
            $cerimonia_items = array();
            foreach ($cerimonia_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $cerimonia_items[] = esc_html($item);
                }
            }
            if (!empty($cerimonia_items)) {
                $html .= '<div class="rito-box">
                    <span class="rito-label">RITO:</span>
                    <span class="rito-value">' . implode(' - ', $cerimonia_items) . '</span>
                </div>';
            }
        }

        // Servizi Extra
        $extra_array = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array)) {
            $html .= '<div class="extra-box">';
            foreach ($extra_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $html .= '<span class="extra-tag"><span class="checkbox-icon">&#10003;</span>' . esc_html($item) . '</span>';
                }
            }
            $html .= '</div>';
        }

        // Verifica sconti servizi
        $ha_sconti = false;
        foreach ($preventivo['servizi'] as $servizio) {
            if (isset($servizio['sconto']) && floatval($servizio['sconto']) > 0) {
                $ha_sconti = true;
                break;
            }
        }

        // Tabella Servizi
        $html .= '<h2>Dettaglio Prezzi</h2>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Servizio</th>
                    <th style="width: 90px; text-align: right;">Prezzo</th>';
        if ($ha_sconti) {
            $html .= '<th style="width: 90px; text-align: right;">Sconto</th>';
        }
        $html .= '<th style="width: 90px; text-align: right;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto_serv = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_serv = $prezzo - $sconto_serv;

            $html .= '<tr>
                <td style="text-align: left;">' . esc_html($servizio['nome_servizio']) . '</td>
                <td style="text-align: right;">' . ($prezzo > 0 ? '&euro; ' . number_format($prezzo, 2, ',', '.') : '<span style="color: #999;">Incluso</span>') . '</td>';
            if ($ha_sconti) {
                $html .= '<td style="text-align: right;">' . ($sconto_serv > 0 ? '<span style="color: #4caf50; font-weight: bold;">-&euro; ' . number_format($sconto_serv, 2, ',', '.') . '</span>' : '—') . '</td>';
            }
            $html .= '<td style="text-align: right; font-weight: ' . ($prezzo > 0 ? 'bold' : 'normal') . ';">' . ($prezzo > 0 ? '&euro; ' . number_format($totale_serv, 2, ',', '.') : '—') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Layout a due colonne: Note (sinistra) e Totali (destra)
        $html .= '<table class="two-col-table">
            <tbody>
            <tr>
                <td>
                    <div class="column-box">
                        <h3>Note</h3>';

        if (!empty($preventivo['note'])) {
            $html .= '<div style="line-height: 1.6; font-size: 11px;">' . nl2br(esc_html($preventivo['note'])) . '</div>';
        } else {
            $html .= '<p style="color: #999; font-style: italic; font-size: 11px;">Nessuna nota aggiuntiva</p>';
        }

        $html .= '</div>
                </td>
                <td>
                    <div class="column-box">
                        <h3>Riepilogo Importi</h3>
                        <table class="totali-table">
                            <tr' . ($evidenzia_totale_servizi ? ' class="highlight"' : '') . '>
                                <td><strong>Totale Servizi</strong></td>
                                <td class="right">&euro; ' . number_format($totale_servizi, 2, ',', '.') . '</td>
                            </tr>';

        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            $html .= '<tr class="sconto">
                <td>- ' . $sconto_label . '</td>
                <td class="right">&euro; ' . number_format($importo_sconto, 2, ',', '.') . '</td>
            </tr>
            <tr class="highlight">
                <td><strong>Subtotale</strong></td>
                <td class="right">&euro; ' . number_format($totale_dopo_sconto, 2, ',', '.') . '</td>
            </tr>';
        }

        // ENPALS
        if ($applica_enpals) {
            $enpals_33_percent = $totale_dopo_sconto * 0.33;
            $enpals_netto = $enpals_33_percent - $enpals_lavoratore;

            $html .= '<tr>
                <td>Ex Enpals 33%</td>
                <td class="right">&euro; ' . number_format($enpals_33_percent, 2, ',', '.') . '</td>
            </tr>
            <tr class="enpals-detail">
                <td>- Ex Enpals Lavoratore ' . number_format($enpals_lavoratore_percentage, 2) . '%</td>
                <td class="right">- &euro; ' . number_format($enpals_lavoratore, 2, ',', '.') . '</td>
            </tr>
            <tr>
                <td><strong>Totale Ex Enpals</strong></td>
                <td class="right">&euro; ' . number_format($enpals_netto, 2, ',', '.') . '</td>
            </tr>';
        }

        // IVA
        $html .= '<tr>
            <td>IVA (' . number_format($iva_percentage, 1) . '%)</td>
            <td class="right">&euro; ' . number_format($iva, 2, ',', '.') . '</td>
        </tr>
        <tr class="total-row">
            <td><strong>TOTALE</strong></td>
            <td class="right"><strong>&euro; ' . number_format($totale, 2, ',', '.') . '</strong></td>
        </tr>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>';

        // Validità preventivo
        if ($stato !== 'accettato' && $stato !== 'confermato') {
            $html .= '<div class="validita-box">
                Il presente preventivo è valido 14 giorni dalla data indicata
            </div>';
        }

        // Acconti
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();
        if (empty($acconti) && !empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto']) && floatval($preventivo['importo_acconto']) > 0) {
            $acconti = array(array(
                'data_acconto' => $preventivo['data_acconto'],
                'importo_acconto' => $preventivo['importo_acconto']
            ));
        }

        if (!empty($acconti)) {
            $html .= '<div class="acconto">';
            $totale_acconti = 0;
            foreach ($acconti as $acconto) {
                $importo_acc = floatval($acconto['importo_acconto']);
                $totale_acconti += $importo_acc;
                $html .= '<p><strong>Acconto del ' . date('d/m/Y', strtotime($acconto['data_acconto'])) . ':</strong> &euro; ' . number_format($importo_acc, 2, ',', '.') . '</p>';
            }
            if (count($acconti) > 1) {
                $html .= '<p style="margin-top: 8px; padding-top: 8px; border-top: 2px solid #4caf50;"><strong>Totale Acconti:</strong> &euro; ' . number_format($totale_acconti, 2, ',', '.') . '</p>';
            }
            $restante = $totale - $totale_acconti;
            $html .= '<p><strong>Restante da saldare:</strong> &euro; ' . number_format($restante, 2, ',', '.') . '</p>
            </div>';
        }

        // Altri Servizi Disponibili
        $servizi_catalogo = MM_Database::get_catalogo_servizi();
        $servizi_selezionati_nomi = array_map(function($s) {
            return strtolower(trim($s['nome_servizio']));
        }, $preventivo['servizi']);

        $servizi_disponibili = array_filter($servizi_catalogo, function($servizio) use ($servizi_selezionati_nomi) {
            return $servizio['attivo'] == 1 &&
                   !in_array(strtolower(trim($servizio['nome_servizio'])), $servizi_selezionati_nomi);
        });

        if (!empty($servizi_disponibili)) {
            $html .= '<h2>Altri Servizi Disponibili</h2>
            <div class="altri-servizi-box">
                <table style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Servizio</th>
                            <th style="width: 130px; text-align: left;">Categoria</th>
                            <th style="width: 100px; text-align: right;">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>';

            usort($servizi_disponibili, function($a, $b) {
                if ($a['categoria'] == $b['categoria']) {
                    return $a['ordinamento'] - $b['ordinamento'];
                }
                return strcmp($a['categoria'], $b['categoria']);
            });

            foreach ($servizi_disponibili as $servizio) {
                $prezzo_display = $servizio['prezzo_default'] > 0
                    ? '&euro; ' . number_format($servizio['prezzo_default'], 2, ',', '.')
                    : '<span style="color: #999;">Su richiesta</span>';

                $html .= '<tr>
                    <td style="text-align: left;">' . esc_html($servizio['nome_servizio']);
                if (!empty($servizio['descrizione'])) {
                    $html .= '<br><small style="color: #666; font-size: 9px;">' . esc_html($servizio['descrizione']) . '</small>';
                }
                $html .= '</td>
                    <td style="text-align: left; color: #666;">' . esc_html($servizio['categoria'] ?: '—') . '</td>
                    <td style="text-align: right; font-weight: 600; color: #e91e63;">' . $prezzo_display . '</td>
                </tr>';
            }

            $html .= '</tbody>
                </table>
                <p class="altri-servizi-note">I servizi sopra elencati sono disponibili su richiesta. Contattaci per maggiori informazioni.</p>
            </div>';
        }

        // Footer
        $html .= '<div class="footer">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            Tel. ' . esc_html($company_phone) . ' - Email: ' . esc_html($company_email) . '
        </div>

</div>
</body>
</html>';

        return $html;
    }

    /**
     * Funzione DEPRECATA - mantenuta per retrocompatibilità
     * Genera HTML per PDF identico alla versione stampata
     */
    private static function generate_pdf_html_content_OLD($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Variabili categoria e stato
        $categoria_nome = !empty($preventivo['categoria_nome']) ? strtolower($preventivo['categoria_nome']) : '';
        $label_cliente = ($categoria_nome === 'matrimonio') ? 'Sposi' : 'Cliente';
        $stato = !empty($preventivo['stato']) ? $preventivo['stato'] : 'attivo';
        $stato_label = ucfirst($stato);
        $categoria_display = !empty($preventivo['categoria_nome']) ? $preventivo['categoria_nome'] : 'Non specificato';
        $categoria_icona = !empty($preventivo['categoria_icona']) ? $preventivo['categoria_icona'] : '';

        // Colori stato
        $stato_colors = array(
            'bozza' => '#ff9800',
            'attivo' => '#4caf50',
            'inviato' => '#2196f3',
            'accettato' => '#8bc34a',
            'rifiutato' => '#f44336',
            'archiviato' => '#9e9e9e'
        );
        $stato_color = isset($stato_colors[$stato]) ? $stato_colors[$stato] : '#4caf50';

        // Calcoli finanziari
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;
        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }
        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        $applica_enpals = isset($preventivo['applica_enpals']) && $preventivo['applica_enpals'] == 1;
        $applica_iva = isset($preventivo['applica_iva']) && $preventivo['applica_iva'] == 1;

        $enpals_committente_percentage = floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81));
        $enpals_lavoratore_percentage = floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19));
        $iva_percentage = floatval(get_option('mm_preventivi_iva_percentage', 22));

        $enpals_committente = $applica_enpals ? ($totale_dopo_sconto * ($enpals_committente_percentage / 100)) : 0;
        $enpals_lavoratore = $applica_enpals ? ($totale_dopo_sconto * ($enpals_lavoratore_percentage / 100)) : 0;
        $imponibile_iva = $totale_dopo_sconto + $enpals_committente;
        $iva = $applica_iva ? ($imponibile_iva * ($iva_percentage / 100)) : 0;
        $totale = $imponibile_iva + $iva;

        $html = '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Preventivo ' . esc_html($preventivo['numero_preventivo']) . '</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        .container {
            width: 100%;
            padding: 0;
        }

        /* ========== HEADER ========== */
        .header {
            border-bottom: 3px solid #e91e63;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .logo {
            max-height: 60px;
            max-width: 180px;
        }
        .company-name {
            color: #e91e63;
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-subtitle {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .preventivo-box {
            text-align: right;
        }
        .preventivo-title {
            color: #e91e63;
            font-size: 16pt;
            font-weight: bold;
        }
        .preventivo-numero {
            color: #555;
            font-size: 11pt;
            margin-top: 3px;
        }

        /* ========== TIPO EVENTO E STATO ========== */
        .tipo-stato-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .tipo-evento-cell {
            background-color: #e91e63;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 10pt;
        }
        .stato-cell {
            background-color: ' . $stato_color . ';
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            width: 180px;
        }

        /* ========== INFO BOXES ========== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .info-table > tbody > tr > td:last-child {
            padding: 0 0 0 8px;
        }
        .info-box {
            background-color: #fafafa;
            padding: 15px;
            border: 2px solid #e91e63;
            border-radius: 4px;
            height: 100%;
        }
        .info-box-title {
            color: #e91e63;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f8bbd0;
        }
        .info-row {
            margin: 6px 0;
            font-size: 10pt;
            line-height: 1.5;
        }
        .info-row-main {
            font-size: 13pt;
            font-weight: bold;
            color: #333;
            margin: 8px 0;
        }

        /* ========== RITO BOX ========== */
        .rito-box {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 8px 15px;
            margin: 12px 0;
        }
        .rito-label {
            font-size: 9pt;
            color: #e65100;
            font-weight: bold;
        }
        .rito-value {
            font-size: 10pt;
            color: #bf360c;
            font-weight: 600;
            margin-left: 10px;
        }

        /* ========== SERVIZI EXTRA ========== */
        .extra-box {
            background-color: #f5f5f5;
            border-left: 4px solid #9c27b0;
            padding: 12px 15px;
            margin: 15px 0;
        }
        .extra-tag {
            display: inline-block;
            background: #fff;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
            padding: 5px 12px;
            margin: 3px 5px 3px 0;
            font-size: 9pt;
        }
        .checkmark {
            display: inline-block;
            background: #9c27b0;
            color: white;
            width: 14px;
            height: 14px;
            text-align: center;
            line-height: 14px;
            font-size: 10px;
            border-radius: 2px;
            margin-right: 6px;
        }

        /* ========== SECTION TITLES ========== */
        h2 {
            color: #e91e63;
            font-size: 12pt;
            font-weight: bold;
            margin: 25px 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8bbd0;
        }

        /* ========== TABELLA SERVIZI ========== */
        .servizi-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .servizi-table th {
            background: #e91e63;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        .servizi-table th.right {
            text-align: right;
        }
        .servizi-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 10pt;
        }
        .servizi-table td.right {
            text-align: right;
        }
        .servizi-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .sconto-verde {
            color: #4caf50;
            font-weight: bold;
        }

        /* ========== LAYOUT DUE COLONNE ========== */
        .two-col-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .two-col-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .two-col-table > tbody > tr > td:last-child {
            padding: 0 0 0 8px;
        }
        .column-box {
            background: #fafafa;
            padding: 15px;
            border: 2px solid #e91e63;
            border-radius: 4px;
            height: 100%;
        }
        .column-box h3 {
            margin: 0 0 12px 0;
            color: #e91e63;
            font-size: 11pt;
            font-weight: bold;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8bbd0;
        }

        /* ========== TABELLA TOTALI ========== */
        .totali-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .totali-inner td {
            padding: 5px 0;
            border: none;
            font-size: 10pt;
        }
        .totali-inner td.right {
            text-align: right;
            font-weight: bold;
        }
        .totali-inner .highlight td {
            background-color: #fff9c4;
        }
        .totali-inner .sconto-row td {
            color: #4caf50;
        }
        .totali-inner .subtotale-row td {
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .totali-inner .enpals-detail td {
            font-size: 8pt;
            color: #666;
            background-color: #fffaf0;
            padding: 3px 0;
        }

        /* ========== BOX TOTALE FINALE ========== */
        .totale-finale {
            background: #e91e63;
            color: white;
            padding: 12px 15px;
            margin-top: 15px;
            border-radius: 4px;
        }
        .totale-finale-table {
            width: 100%;
        }
        .totale-finale-table td {
            border: none;
            padding: 0;
            font-size: 14pt;
            font-weight: bold;
            color: white;
        }
        .totale-finale-table td.right {
            text-align: right;
        }

        /* ========== ACCONTI ========== */
        .acconto-box {
            background: #e8f5e9;
            padding: 12px 15px;
            border-left: 4px solid #4caf50;
            border-radius: 0 4px 4px 0;
            margin: 15px 0;
        }
        .acconto-box p {
            margin: 4px 0;
            font-size: 10pt;
        }
        .acconto-box strong {
            color: #2e7d32;
        }
        .acconto-title {
            font-size: 11pt;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 8px;
        }
        .acconto-restante {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #4caf50;
            font-weight: bold;
        }

        /* ========== ALTRI SERVIZI ========== */
        .altri-servizi-box {
            background: #f8f8f8;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-top: 10px;
        }
        .altri-servizi-note {
            margin-top: 10px;
            font-size: 8pt;
            color: #999;
            font-style: italic;
        }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e91e63;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .footer strong {
            color: #e91e63;
            font-size: 10pt;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">';

        if (!empty($company_logo)) {
            $html .= '<img src="' . esc_url($company_logo) . '" class="logo" style="margin-bottom: 5px;"><br>';
        }

        $html .= '<div class="company-name">' . esc_html($company_name) . '</div>
                    <div class="company-subtitle">DJ - Animazione - Scenografie - Photo Booth</div>
                </td>
                <td class="preventivo-box">
                    <div class="preventivo-title">PREVENTIVO</div>
                    <div class="preventivo-numero">N. ' . esc_html($preventivo['numero_preventivo']) . '</div>
                    <div class="preventivo-numero">del ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tipo Evento e Stato -->
    <table class="tipo-stato-table">
        <tr>
            <td class="tipo-evento-cell">TIPO DI EVENTO: ' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</td>
            <td class="stato-cell">' . strtoupper(esc_html($stato_label)) . '</td>
        </tr>
    </table>

    <!-- Info Cliente e Evento -->
    <table class="info-table">
        <tbody>
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-box-title">' . esc_html($label_cliente) . '</div>
                    <div class="info-row-main">' . esc_html($preventivo['sposi']) . '</div>
                    <div class="info-row">&#9993; ' . esc_html($preventivo['email']) . '</div>
                    <div class="info-row">&#9742; ' . esc_html($preventivo['telefono']) . '</div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-box-title">Dettagli Evento</div>
                    <div class="info-row-main">&#128197; ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</div>
                    <div class="info-row">&#127881; ' . esc_html($preventivo['tipo_evento']) . '</div>
                    <div class="info-row">&#128205; ' . esc_html($preventivo['location']) . '</div>
                </div>
            </td>
        </tr>
        </tbody>
    </table>';

        // Rito/Cerimonia
        $cerimonia_array = !empty($preventivo['cerimonia']) ? (is_array($preventivo['cerimonia']) ? $preventivo['cerimonia'] : explode(',', $preventivo['cerimonia'])) : array();
        if (!empty($cerimonia_array)) {
            $cerimonia_items = array();
            foreach ($cerimonia_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $cerimonia_items[] = esc_html($item);
                }
            }
            if (!empty($cerimonia_items)) {
                $html .= '<div class="rito-box">
                    <span class="rito-label">RITO:</span>
                    <span class="rito-value">' . implode(' - ', $cerimonia_items) . '</span>
                </div>';
            }
        }

        // Servizi Extra
        $extra_array = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array)) {
            $html .= '<div class="extra-box">';
            foreach ($extra_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $html .= '<span class="extra-tag"><span class="checkmark">&#10003;</span>' . esc_html($item) . '</span>';
                }
            }
            $html .= '</div>';
        }

        // Verifica sconti sui servizi
        $ha_sconti = false;
        foreach ($preventivo['servizi'] as $servizio) {
            if (isset($servizio['sconto']) && floatval($servizio['sconto']) > 0) {
                $ha_sconti = true;
                break;
            }
        }

        // Tabella Servizi
        $html .= '<h2>Dettaglio Prezzi</h2>
        <table class="servizi-table">
            <thead>
                <tr>
                    <th>Servizio</th>
                    <th class="right" style="width: 80px;">Prezzo</th>';
        if ($ha_sconti) {
            $html .= '<th class="right" style="width: 80px;">Sconto</th>';
        }
        $html .= '<th class="right" style="width: 80px;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto_serv = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_serv = $prezzo - $sconto_serv;

            $html .= '<tr>
                <td>' . esc_html($servizio['nome_servizio']) . '</td>
                <td class="right">' . ($prezzo > 0 ? number_format($prezzo, 2, ',', '.') . ' &euro;' : '<span style="color:#999;">Incluso</span>') . '</td>';
            if ($ha_sconti) {
                $html .= '<td class="right">' . ($sconto_serv > 0 ? '<span class="sconto-verde">-' . number_format($sconto_serv, 2, ',', '.') . ' &euro;</span>' : '—') . '</td>';
            }
            $html .= '<td class="right"><strong>' . ($prezzo > 0 ? number_format($totale_serv, 2, ',', '.') . ' &euro;' : '—') . '</strong></td>
            </tr>';
        }

        $html .= '</tbody></table>';

        // Layout a due colonne: Note (sinistra) e Totali (destra)
        $html .= '<table class="two-col-table">
            <tbody>
            <tr>
                <!-- Colonna Note -->
                <td>
                    <div class="column-box">
                        <h3>&#128221; Note</h3>';

        if (!empty($preventivo['note'])) {
            $html .= '<div style="line-height: 1.6; font-size: 10pt;">' . nl2br(esc_html($preventivo['note'])) . '</div>';
        } else {
            $html .= '<p style="color: #999; font-style: italic; font-size: 10pt;">Nessuna nota aggiuntiva</p>';
        }

        $html .= '</div>
                </td>

                <!-- Colonna Totali -->
                <td>
                    <div class="column-box">
                        <h3>&#128176; Riepilogo Importi</h3>
                        <table class="totali-inner">';

        // Evidenzia totale servizi se non ci sono sconti
        $evidenzia = ($importo_sconto == 0) ? ' class="highlight"' : '';
        $html .= '<tr' . $evidenzia . '>
            <td><strong>Totale Servizi</strong></td>
            <td class="right">' . number_format($totale_servizi, 2, ',', '.') . ' &euro;</td>
        </tr>';

        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            $html .= '<tr class="sconto-row">
                <td>- ' . $sconto_label . '</td>
                <td class="right">' . number_format($importo_sconto, 2, ',', '.') . ' &euro;</td>
            </tr>
            <tr class="subtotale-row highlight">
                <td><strong>Subtotale</strong></td>
                <td class="right">' . number_format($totale_dopo_sconto, 2, ',', '.') . ' &euro;</td>
            </tr>';
        }

        // ENPALS dettaglio
        if ($applica_enpals) {
            $enpals_33_percent = $totale_dopo_sconto * 0.33;
            $html .= '<tr>
                <td>Ex Enpals 33%</td>
                <td class="right">' . number_format($enpals_33_percent, 2, ',', '.') . ' &euro;</td>
            </tr>
            <tr class="enpals-detail">
                <td>- Ex Enpals Lavoratore ' . number_format($enpals_lavoratore_percentage, 2) . '%</td>
                <td class="right">- ' . number_format($enpals_lavoratore, 2, ',', '.') . ' &euro;</td>
            </tr>
            <tr>
                <td>Ex Enpals Committente</td>
                <td class="right">' . number_format($enpals_committente, 2, ',', '.') . ' &euro;</td>
            </tr>';
        }

        // Imponibile
        $html .= '<tr>
            <td><strong>Imponibile</strong></td>
            <td class="right"><strong>' . number_format($imponibile_iva, 2, ',', '.') . ' &euro;</strong></td>
        </tr>';

        // IVA
        if ($applica_iva && $iva > 0) {
            $html .= '<tr>
                <td>IVA ' . number_format($iva_percentage, 0) . '%</td>
                <td class="right">' . number_format($iva, 2, ',', '.') . ' &euro;</td>
            </tr>';
        }

        $html .= '</table>

                        <!-- Box Totale Finale -->
                        <div class="totale-finale">
                            <table class="totale-finale-table">
                                <tr>
                                    <td>TOTALE</td>
                                    <td class="right">' . number_format($totale, 2, ',', '.') . ' &euro;</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>';

        // Acconti
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();
        if (empty($acconti) && !empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto']) && floatval($preventivo['importo_acconto']) > 0) {
            $acconti = array(array(
                'data_acconto' => $preventivo['data_acconto'],
                'importo_acconto' => $preventivo['importo_acconto']
            ));
        }

        if (!empty($acconti)) {
            $html .= '<div class="acconto-box">
                <p class="acconto-title">&#10004; Acconti Versati</p>';
            $totale_acconti = 0;
            foreach ($acconti as $acconto) {
                $importo_acc = floatval($acconto['importo_acconto']);
                $totale_acconti += $importo_acc;
                $html .= '<p>Acconto del ' . date('d/m/Y', strtotime($acconto['data_acconto'])) . ': <strong>' . number_format($importo_acc, 2, ',', '.') . ' &euro;</strong></p>';
            }
            if (count($acconti) > 1) {
                $html .= '<p style="margin-top: 5px;"><strong>Totale acconti: ' . number_format($totale_acconti, 2, ',', '.') . ' &euro;</strong></p>';
            }
            $restante = $totale - $totale_acconti;
            $html .= '<p class="acconto-restante">Restante da saldare: ' . number_format($restante, 2, ',', '.') . ' &euro;</p>
            </div>';
        }

        // Sezione Altri Servizi Disponibili (non selezionati)
        $servizi_catalogo = MM_Database::get_catalogo_servizi();
        $servizi_selezionati_nomi = array_map(function($s) {
            return strtolower(trim($s['nome_servizio']));
        }, $preventivo['servizi']);

        $servizi_disponibili = array_filter($servizi_catalogo, function($servizio) use ($servizi_selezionati_nomi) {
            return $servizio['attivo'] == 1 &&
                   !in_array(strtolower(trim($servizio['nome_servizio'])), $servizi_selezionati_nomi);
        });

        if (!empty($servizi_disponibili)) {
            $html .= '<h2>&#128203; Altri Servizi Disponibili</h2>
            <div class="altri-servizi-box">
                <table class="servizi-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Servizio</th>
                            <th style="width: 110px;">Categoria</th>
                            <th class="right" style="width: 100px;">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>';

            // Ordina per categoria e poi per ordinamento
            usort($servizi_disponibili, function($a, $b) {
                if ($a['categoria'] == $b['categoria']) {
                    return $a['ordinamento'] - $b['ordinamento'];
                }
                return strcmp($a['categoria'], $b['categoria']);
            });

            foreach ($servizi_disponibili as $servizio) {
                $prezzo_display = $servizio['prezzo_default'] > 0
                    ? number_format($servizio['prezzo_default'], 2, ',', '.') . ' &euro;'
                    : '<span style="color: #999; font-style: italic;">Su richiesta</span>';

                $html .= '<tr>
                    <td>' . esc_html($servizio['nome_servizio']);

                if (!empty($servizio['descrizione'])) {
                    $html .= '<br><span style="color: #888; font-size: 8pt;">' . esc_html($servizio['descrizione']) . '</span>';
                }

                $html .= '</td>
                    <td style="color: #666; font-size: 9pt;">' . esc_html($servizio['categoria'] ?: '—') . '</td>
                    <td class="right" style="font-weight: 600; color: #e91e63;">' . $prezzo_display . '</td>
                </tr>';
            }

            $html .= '</tbody>
                </table>
                <p class="altri-servizi-note">
                    I servizi sopra elencati sono disponibili su richiesta. Contattaci per maggiori informazioni.
                </p>
            </div>';
        }

        // Footer
        $html .= '<div class="footer">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            &#9742; ' . esc_html($company_phone) . ' &nbsp;&nbsp; &#9993; ' . esc_html($company_email) . '
        </div>

</div>
</body>
</html>';

        return $html;
    }

    /**
     * Genera PDF come file temporaneo e restituisce il path
     * Usato per allegati email - VERSIONE SEMPLIFICATA
     */
    public static function generate_pdf_file($preventivo) {
        try {
            error_log('MM PDF - Inizio generazione file');

            // Crea directory temporanea se non esiste
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/mm-preventivi-temp';

            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }

            error_log('MM PDF - Directory temp: ' . $temp_dir);

            // Nome file HTML (più sicuro e sempre funzionante)
            $filename = 'preventivo-' . sanitize_file_name($preventivo['numero_preventivo']) . '-' . time() . '.html';
            $filepath = $temp_dir . '/' . $filename;

            error_log('MM PDF - Path file: ' . $filepath);

            // Genera HTML semplice ma completo
            $html = self::generate_simple_html($preventivo);

            // Salva file
            $result = file_put_contents($filepath, $html);

            if ($result === false) {
                error_log('MM PDF - Errore scrittura file');
                return false;
            }

            error_log('MM PDF - File creato con successo: ' . $filepath);
            return $filepath;

        } catch (Exception $e) {
            error_log('MM PDF - Errore fatale: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera HTML per allegato email (senza output diretto)
     */
    public static function generate_pdf_html_for_attachment($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Genera lo stesso HTML del PDF ma senza i pulsanti azione
        ob_start();

        // Usa generate_simple_html come base per l'allegato
        echo self::generate_attachment_html($preventivo, $company_name, $company_address, $company_phone, $company_email, $company_piva, $company_cf, $company_logo);

        return ob_get_clean();
    }

    /**
     * Genera HTML per allegato PDF (versione compatta, una pagina A4)
     */
    private static function generate_attachment_html($preventivo, $company_name, $company_address, $company_phone, $company_email, $company_piva, $company_cf, $company_logo) {
        // Calcoli
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto_fisso = floatval($preventivo['sconto']);
        $sconto_percentuale = floatval($preventivo['sconto_percentuale']);
        $sconto_perc_importo = $totale_servizi * ($sconto_percentuale / 100);
        $subtotale = $totale_servizi - $sconto_fisso - $sconto_perc_importo;

        $enpals_committente = floatval($preventivo['enpals_committente']);
        $iva = floatval($preventivo['iva']);
        $totale = floatval($preventivo['totale']);

        $applica_enpals = !empty($preventivo['applica_enpals']);
        $applica_iva = !empty($preventivo['applica_iva']);

        $servizi = isset($preventivo['servizi']) && is_array($preventivo['servizi']) ? $preventivo['servizi'] : array();
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();

        $totale_acconti = 0;
        foreach ($acconti as $acconto) {
            $totale_acconti += floatval($acconto['importo_acconto']);
        }

        $html = '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Preventivo ' . esc_html($preventivo['numero_preventivo']) . '</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            padding: 20mm;
        }
        .header-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .logo-cell {
            width: 50%;
        }
        .logo-cell img {
            max-height: 45px;
            max-width: 150px;
        }
        .title-cell {
            width: 50%;
            text-align: right;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #c2185b;
            margin: 0;
        }
        .doc-number {
            font-size: 11px;
            color: #666;
        }
        .info-section {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 8px 3px 0;
            vertical-align: top;
        }
        .info-label {
            font-size: 8px;
            color: #888;
            text-transform: uppercase;
            font-weight: bold;
        }
        .info-value {
            font-size: 11px;
            color: #333;
            font-weight: 600;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #c2185b;
            padding: 6px 0 4px 0;
            border-bottom: 2px solid #c2185b;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .services-table th {
            background: #c2185b;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .services-table th.price-col {
            text-align: right;
            width: 80px;
        }
        .services-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .services-table td.price-col {
            text-align: right;
            font-weight: 600;
        }
        .totals-section {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-section td {
            padding: 0;
            vertical-align: top;
        }
        .totals-left {
            width: 55%;
        }
        .totals-right {
            width: 45%;
        }
        .totals-box {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 8px 10px;
        }
        .total-row {
            width: 100%;
            border-collapse: collapse;
        }
        .total-row td {
            padding: 3px 0;
            font-size: 10px;
        }
        .total-row td.label {
            text-align: left;
        }
        .total-row td.value {
            text-align: right;
            font-weight: 600;
        }
        .total-row.main td {
            font-size: 14px;
            font-weight: bold;
            color: #c2185b;
            padding-top: 6px;
            border-top: 2px solid #c2185b;
        }
        .acconti-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .acconti-table th {
            background: #eee;
            padding: 4px 6px;
            text-align: left;
            font-weight: bold;
        }
        .acconti-table th.price-col {
            text-align: right;
        }
        .acconti-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #eee;
        }
        .acconti-table td.price-col {
            text-align: right;
        }
        .acconti-table tr.totale td {
            font-weight: bold;
            border-top: 1px solid #ccc;
        }
        .notes-box {
            background: #fffde7;
            border: 1px solid #ffeb3b;
            padding: 8px 10px;
            margin-top: 10px;
            font-size: 9px;
        }
        .notes-box strong {
            color: #f57c00;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .footer .company {
            font-weight: bold;
            color: #c2185b;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Header con logo e titolo -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">';

        if (!empty($company_logo)) {
            $html .= '<img src="' . esc_url($company_logo) . '" alt="Logo">';
        } else {
            $html .= '<span style="font-size: 14px; font-weight: bold; color: #c2185b;">' . esc_html($company_name) . '</span>';
        }

        $html .= '</td>
            <td class="title-cell">
                <div class="doc-title">PREVENTIVO</div>
                <div class="doc-number">N° ' . esc_html($preventivo['numero_preventivo']) . ' del ' . date('d/m/Y') . '</div>
            </td>
        </tr>
    </table>

    <!-- Info Evento -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">' . esc_html($preventivo['sposi']) . '</div>
                </td>
                <td style="width: 25%;">
                    <div class="info-label">Data Evento</div>
                    <div class="info-value">' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</div>
                </td>
                <td style="width: 25%;">';

        if (!empty($preventivo['tipo_evento'])) {
            $html .= '<div class="info-label">Tipo</div>
                    <div class="info-value">' . esc_html($preventivo['tipo_evento']) . '</div>';
        }

        $html .= '</td>
            </tr>';

        if (!empty($preventivo['location'])) {
            $html .= '<tr>
                <td colspan="3">
                    <div class="info-label">Location</div>
                    <div class="info-value">' . esc_html($preventivo['location']) . '</div>
                </td>
            </tr>';
        }

        $html .= '</table>
    </div>

    <!-- Servizi -->
    <div class="section-title">Servizi Inclusi</div>
    <table class="services-table">
        <thead>
            <tr>
                <th>Descrizione Servizio</th>
                <th class="price-col">Importo</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($servizi as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto_serv = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_serv = $prezzo - $sconto_serv;

            $html .= '<tr>
                <td>' . esc_html($servizio['nome_servizio']) . '</td>
                <td class="price-col">' . number_format($totale_serv, 2, ',', '.') . ' &euro;</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>

    <!-- Totali e Acconti affiancati -->
    <table class="totals-section">
        <tr>
            <td class="totals-left">';

        // Mostra acconti se presenti
        if (!empty($acconti)) {
            $html .= '<div class="section-title" style="font-size: 10px;">Piano Pagamenti</div>
                <table class="acconti-table">
                    <thead>
                        <tr>
                            <th>Scadenza</th>
                            <th class="price-col">Importo</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($acconti as $acconto) {
                $html .= '<tr>
                    <td>' . date('d/m/Y', strtotime($acconto['data_acconto'])) . '</td>
                    <td class="price-col">' . number_format(floatval($acconto['importo_acconto']), 2, ',', '.') . ' &euro;</td>
                </tr>';
            }

            $html .= '<tr class="totale">
                        <td>Totale Acconti</td>
                        <td class="price-col">' . number_format($totale_acconti, 2, ',', '.') . ' &euro;</td>
                    </tr>
                    <tr>
                        <td><strong>Saldo Finale</strong></td>
                        <td class="price-col"><strong>' . number_format($totale - $totale_acconti, 2, ',', '.') . ' &euro;</strong></td>
                    </tr>
                </tbody>
            </table>';
        }

        $html .= '</td>
            <td class="totals-right">
                <div class="totals-box">
                    <table class="total-row">
                        <tr>
                            <td class="label">Subtotale</td>
                            <td class="value">' . number_format($subtotale, 2, ',', '.') . ' &euro;</td>
                        </tr>';

        if ($applica_enpals && $enpals_committente > 0) {
            $html .= '<tr>
                            <td class="label">ENPALS Committente</td>
                            <td class="value">' . number_format($enpals_committente, 2, ',', '.') . ' &euro;</td>
                        </tr>';
        }

        if ($applica_iva && $iva > 0) {
            $html .= '<tr>
                            <td class="label">IVA 22%</td>
                            <td class="value">' . number_format($iva, 2, ',', '.') . ' &euro;</td>
                        </tr>';
        }

        $html .= '<tr class="main">
                            <td class="label">TOTALE</td>
                            <td class="value">' . number_format($totale, 2, ',', '.') . ' &euro;</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>';

        // Note (solo se presenti)
        if (!empty($preventivo['note'])) {
            $html .= '<div class="notes-box">
                <strong>Note:</strong> ' . nl2br(esc_html($preventivo['note'])) . '
            </div>';
        }

        // Footer
        $html .= '
    <div class="footer">
        <div class="company">' . esc_html($company_name) . '</div>
        ' . esc_html($company_address) . ' | Tel. ' . esc_html($company_phone) . ' | ' . esc_html($company_email) . '<br>
        ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Genera HTML semplice per il preventivo
     */
    private static function generate_simple_html($preventivo) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
            <title>Preventivo <?php echo esc_html($preventivo['numero_preventivo']); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                    background: #f5f5f5;
                }
                .header {
                    background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%);
                    color: white;
                    padding: 20px;
                    text-align: center;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header h1 {
                    font-size: 24px;
                    margin-bottom: 5px;
                }
                .section {
                    margin: 20px 0;
                    padding: 15px;
                    background: white;
                    border-left: 4px solid #e91e63;
                    border-radius: 4px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
                }
                .section h2 {
                    color: #e91e63;
                    font-size: 18px;
                    margin-bottom: 12px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #f8bbd0;
                }
                .section p {
                    margin: 8px 0;
                    line-height: 1.6;
                }
                .totale {
                    font-size: 28px;
                    color: #e91e63;
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    background: white;
                }
                th, td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background: #e91e63;
                    color: white;
                    font-weight: 600;
                }
                td:last-child {
                    text-align: right;
                }
                .footer {
                    text-align: center;
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #e91e63;
                    color: #666;
                    font-size: 14px;
                }

                /* Mobile Optimization */
                @media only screen and (max-width: 768px) {
                    body {
                        padding: 10px;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .header p {
                        font-size: 14px;
                    }
                    .section {
                        padding: 12px;
                        margin: 15px 0;
                    }
                    .section h2 {
                        font-size: 16px;
                    }
                    .section p {
                        font-size: 14px;
                    }
                    .totale {
                        font-size: 24px;
                    }
                    table {
                        font-size: 13px;
                        display: block;
                        overflow-x: auto;
                        -webkit-overflow-scrolling: touch;
                    }
                    th, td {
                        padding: 8px 6px;
                        font-size: 12px;
                    }
                }

                @media only screen and (max-width: 375px) {
                    .header h1 {
                        font-size: 18px;
                    }
                    .section h2 {
                        font-size: 14px;
                    }
                    .totale {
                        font-size: 22px;
                    }
                    table {
                        font-size: 12px;
                    }
                    th, td {
                        padding: 6px 4px;
                        font-size: 11px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Preventivo n. <?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
                <p><?php echo esc_html(get_bloginfo('name')); ?></p>
            </div>

            <div class="section">
                <h2>Informazioni Cliente</h2>
                <p><strong>Cliente:</strong> <?php echo esc_html($preventivo['sposi']); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($preventivo['email']); ?></p>
                <p><strong>Telefono:</strong> <?php echo esc_html($preventivo['telefono']); ?></p>
            </div>

            <div class="section">
                <h2>Dettagli Evento</h2>
                <p><strong>Data Preventivo:</strong> <?php echo date('d/m/Y', strtotime($preventivo['data_preventivo'])); ?></p>
                <p><strong>Data Evento:</strong> <?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?></p>
                <p><strong>Location:</strong> <?php echo esc_html($preventivo['location']); ?></p>
                <?php if (!empty($preventivo['tipo_evento'])) : ?>
                    <p><strong>Momento:</strong> <?php echo esc_html($preventivo['tipo_evento']); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($preventivo['servizi']) && is_array($preventivo['servizi'])) : ?>
            <div class="section">
                <h2>Servizi</h2>
                <table>
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
                                <td style="text-align: right;"><?php echo number_format(floatval($servizio['prezzo']), 2, ',', '.'); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="section">
                <h2>Totale</h2>
                <p class="totale"><?php echo number_format($preventivo['totale'], 2, ',', '.'); ?> €</p>
            </div>

            <?php if (!empty($preventivo['note'])) : ?>
            <div class="section">
                <h2>Note</h2>
                <p><?php echo nl2br(esc_html($preventivo['note'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="footer">
                <p><strong><?php echo esc_html(get_bloginfo('name')); ?></strong></p>
                <p><?php echo esc_html(get_bloginfo('description')); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera HTML content per il PDF
     */
    private static function generate_html_content($preventivo) {
        // Riusa il codice esistente di generate_html_pdf ma senza output diretto
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
                .header { background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%); color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .info-box { background: #f8f8f8; padding: 15px; border-radius: 8px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #e91e63; color: white; }
                .totale { font-weight: bold; color: #e91e63; font-size: 16px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Preventivo n. <?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
                <p><?php echo esc_html($preventivo['sposi']); ?></p>
            </div>
            <!-- Resto del contenuto HTML del preventivo -->
            <?php
            // Includi qui il resto del codice HTML esistente
            echo '<p><strong>Data:</strong> ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</p>';
            echo '<p><strong>Evento:</strong> ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</p>';
            echo '<p><strong>Location:</strong> ' . esc_html($preventivo['location']) . '</p>';
            echo '<p><strong>Totale:</strong> <span class="totale">' . number_format($preventivo['totale'], 2, ',', '.') . ' €</span></p>';
            ?>
        </body>
        </html>
        <?php
    }

    /**
     * Genera PDF con DomPDF (fallback più compatibile)
     */
    private static function generate_with_dompdf($preventivo, $filepath) {
        // Genera HTML semplificato
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #333; padding: 20px; }
                .header { background: #e91e63; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
                .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #e91e63; }
                .totale { font-weight: bold; color: #e91e63; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Preventivo n. <?php echo esc_html($preventivo['numero_preventivo']); ?></h1>
            </div>
            <div class="section">
                <p><strong>Cliente:</strong> <?php echo esc_html($preventivo['sposi']); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($preventivo['email']); ?></p>
                <p><strong>Telefono:</strong> <?php echo esc_html($preventivo['telefono']); ?></p>
            </div>
            <div class="section">
                <p><strong>Data Preventivo:</strong> <?php echo date('d/m/Y', strtotime($preventivo['data_preventivo'])); ?></p>
                <p><strong>Data Evento:</strong> <?php echo date('d/m/Y', strtotime($preventivo['data_evento'])); ?></p>
                <p><strong>Location:</strong> <?php echo esc_html($preventivo['location']); ?></p>
                <?php if (!empty($preventivo['tipo_evento'])) : ?>
                    <p><strong>Momento:</strong> <?php echo esc_html($preventivo['tipo_evento']); ?></p>
                <?php endif; ?>
            </div>
            <div class="section">
                <h2>Totale</h2>
                <p class="totale"><?php echo number_format($preventivo['totale'], 2, ',', '.'); ?> €</p>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        // Cambia estensione a .html per essere sicuri che venga inviato correttamente
        $html_filepath = str_replace('.pdf', '.html', $filepath);

        // Salva HTML come file
        file_put_contents($html_filepath, $html);

        // Restituisci il path dell'HTML
        return $html_filepath;
    }

    /**
     * Genera PDF con TCPDF
     */
    private static function generate_tcpdf($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Crea PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Informazioni documento
        $pdf->SetCreator('Massimo Manca Preventivi Plugin');
        $pdf->SetAuthor($company_name);
        $pdf->SetTitle('Preventivo ' . $preventivo['numero_preventivo']);
        $pdf->SetSubject('Preventivo Evento');

        // Rimuovi header e footer default
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Margini
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);

        // Aggiungi pagina
        $pdf->AddPage();

        // Font
        $pdf->SetFont('helvetica', '', 10);

        // Header con logo
        $logo_html = '';
        if (!empty($company_logo) && filter_var($company_logo, FILTER_VALIDATE_URL)) {
            $logo_html = '<img src="' . esc_url($company_logo) . '" style="height: 80px; margin-bottom: 10px;">';
        }

        $html_header = '
        <table style="width: 100%; margin-bottom: 15px;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top;">
                    ' . $logo_html . '
                    <h1 style="color: #e91e63; font-size: 26px; margin: 0; font-weight: bold;">PREVENTIVO</h1>
                    <p style="color: #666; font-size: 10px; margin: 5px 0 0 0; text-transform: uppercase; letter-spacing: 1px;">DJ • Animazione • Scenografie • Photo Booth</p>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <p style="font-size: 11px; margin: 0; font-weight: bold; color: #333;">' . esc_html($company_name) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">' . esc_html($company_address) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">Tel. ' . esc_html($company_phone) . '</p>
                    <p style="font-size: 9px; margin: 3px 0; color: #666;">' . esc_html($company_email) . '</p>
                </td>
            </tr>
        </table>
        <hr style="border: none; border-top: 3px solid #e91e63; margin: 10px 0 20px 0;">
        ';

        $pdf->writeHTML($html_header, true, false, true, false, '');

        // Determina label cliente in base alla categoria evento
        $categoria_nome = !empty($preventivo['categoria_nome']) ? strtolower($preventivo['categoria_nome']) : '';
        $label_cliente = ($categoria_nome === 'matrimonio') ? 'Sposi' : 'Cliente';

        // Prepara stato preventivo con colore
        $stato = !empty($preventivo['stato']) ? $preventivo['stato'] : 'attivo';
        $stato_colors = array(
            'bozza' => '#ff9800',
            'attivo' => '#4caf50',
            'inviato' => '#2196f3',
            'accettato' => '#8bc34a',
            'rifiutato' => '#f44336',
            'archiviato' => '#9e9e9e'
        );
        $stato_color = isset($stato_colors[$stato]) ? $stato_colors[$stato] : '#4caf50';
        $stato_label = ucfirst($stato);

        // Box Tipo Evento e Stato in evidenza
        $categoria_display = !empty($preventivo['categoria_nome']) ? $preventivo['categoria_nome'] : 'Non specificato';
        $categoria_icona = !empty($preventivo['categoria_icona']) ? $preventivo['categoria_icona'] : '🎉';

        $html_tipo_evento = '
        <table style="width: 100%; margin-bottom: 10px; border-collapse: collapse;">
            <tr>
                <td style="width: 70%; padding: 6px 12px; background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%); border-radius: 6px 0 0 6px; box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3);">
                    <p style="margin: 0; font-size: 9px; color: #ffffff; font-weight: 600; letter-spacing: 0.3px; display: inline;">TIPO DI EVENTO RICHIESTO: </p>
                    <p style="margin: 0; font-size: 11px; color: #ffffff; font-weight: bold; letter-spacing: 0.3px; display: inline;">' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</p>
                </td>
                <td style="width: 30%; padding: 6px 12px; background: ' . $stato_color . '; border-radius: 0 6px 6px 0; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); text-align: center;">
                    <p style="margin: 0; font-size: 9px; color: #ffffff; font-weight: 600; letter-spacing: 0.3px; display: inline;">STATO: </p>
                    <p style="margin: 0; font-size: 11px; color: #ffffff; font-weight: bold; letter-spacing: 0.3px; display: inline;">' . strtoupper(esc_html($stato_label)) . '</p>
                </td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($html_tipo_evento, true, false, true, false, '');

        // Dati cliente e preventivo (torniamo a 2 colonne)
        $html_info = '
        <table style="width: 100%; font-size: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; padding: 8px; background-color: #f8f8f8; border-radius: 5px;">
                    <p style="margin: 0;"><strong style="color: #e91e63;">' . $label_cliente . ':</strong></p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold;">' . esc_html($preventivo['sposi']) . '</p>
                </td>
                <td style="width: 50%; padding: 8px; background-color: #f8f8f8; border-radius: 5px; margin-left: 10px;">
                    <p style="margin: 0;"><strong style="color: #e91e63;">N. Preventivo:</strong></p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold;">' . esc_html($preventivo['numero_preventivo']) . '</p>
                </td>
            </tr>
        </table>
        <table style="width: 100%; font-size: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;"><strong>Email:</strong> ' . esc_html($preventivo['email']) . '</td>
                <td style="width: 50%;"><strong>Data Preventivo:</strong> ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</td>
            </tr>
            <tr>
                <td><strong>Telefono:</strong> ' . esc_html($preventivo['telefono']) . '</td>
                <td><strong>Data Evento:</strong> ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</td>
            </tr>
            <tr>
                <td style="width: 50%;"><strong>Tipo Evento:</strong> ' . esc_html($preventivo['tipo_evento']) . '</td>
                <td style="width: 50%;"><strong>Location:</strong> ' . esc_html($preventivo['location']) . '</td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($html_info, true, false, true, false, '');

        // Riga cerimonia (se rito = SI)
        $cerimonia_array = !empty($preventivo['cerimonia']) ? (is_array($preventivo['cerimonia']) ? $preventivo['cerimonia'] : explode(',', $preventivo['cerimonia'])) : array();
        if (!empty($cerimonia_array)) {
            $cerimonia_items = array();
            foreach ($cerimonia_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $cerimonia_items[] = $item;
                }
            }

            if (!empty($cerimonia_items)) {
                $html_cerimonia = '
                <div style="margin: 10px 0 15px 0; padding: 8px 12px; background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-left: 4px solid #ff9800; border-radius: 4px;">
                    <span style="font-size: 9px; color: #e65100; font-weight: 700; letter-spacing: 0.5px;">RITO:</span>
                    <span style="font-size: 10px; color: #bf360c; font-weight: 600; margin-left: 8px;">' . implode(' • ', array_map('esc_html', $cerimonia_items)) . '</span>
                </div>';
                $pdf->writeHTML($html_cerimonia, true, false, true, false, '');
            }
        }

        // Servizi extra come fascia elegante con checkbox
        $extra_array = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array)) {
            $html_boxes = '<div style="margin: 15px 0; padding: 12px 15px; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border-left: 4px solid #9c27b0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">';

            $services_html = array();
            foreach ($extra_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $services_html[] = '<span style="display: inline-block; margin: 4px 6px; padding: 6px 12px; background: #ffffff; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 9px; font-weight: 500; color: #424242; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><span style="display: inline-block; width: 12px; height: 12px; background: #9c27b0; border-radius: 3px; margin-right: 5px; text-align: center; line-height: 12px; color: white; font-size: 8px; vertical-align: middle;">✓</span>' . esc_html($item) . '</span>';
                }
            }

            $html_boxes .= implode('', $services_html);
            $html_boxes .= '</div>';
            $pdf->writeHTML($html_boxes, true, false, true, false, '');
        }

        // Servizi
        $html_servizi = '
        <h3 style="color: #e91e63; font-size: 13px; margin: 20px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Dettaglio Prezzi</h3>
        <table style="width: 100%; font-size: 10px; border-collapse: collapse; margin-bottom: 15px;">
            <thead>
                <tr style="background-color: #e91e63; color: white;">
                    <th style="padding: 8px; text-align: left; border: 1px solid #e91e63;">Servizio</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Prezzo</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Sconto</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #e91e63; width: 80px;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_servizio = $prezzo - $sconto;

            $html_servizi .= '
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background-color: #fafafa;">' . esc_html($servizio['nome_servizio']) . '</td>
                    <td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa;">€ ' . number_format($prezzo, 2, ',', '.') . '</td>';

            if ($sconto > 0) {
                $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa; color: #4caf50; font-weight: bold;">-€ ' . number_format($sconto, 2, ',', '.') . '</td>';
            } else {
                $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa;">-</td>';
            }

            $html_servizi .= '<td style="padding: 8px; text-align: right; border: 1px solid #ddd; background-color: #fafafa; font-weight: bold;">€ ' . number_format($totale_servizio, 2, ',', '.') . '</td>
                </tr>';
        }

        $html_servizi .= '
            </tbody>
        </table>';

        $pdf->writeHTML($html_servizi, true, false, true, false, '');

        // Note
        if (!empty($preventivo['note'])) {
            $html_note = '
            <h3 style="color: #e91e63; font-size: 13px; margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Note</h3>
            <p style="font-size: 10px; padding: 10px; background-color: #fffaf0; border-left: 4px solid #ff9800; line-height: 1.6;">' . nl2br(esc_html($preventivo['note'])) . '</p>
            ';
            $pdf->writeHTML($html_note, true, false, true, false, '');
        }

        // Calcolo totali
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;

        // Calcola sconto
        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }

        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        // Calcola tasse se attive
        $applica_enpals = isset($preventivo['applica_enpals']) ? $preventivo['applica_enpals'] : true;
        $applica_iva = isset($preventivo['applica_iva']) ? $preventivo['applica_iva'] : true;

        // Carica percentuali configurabili
        $enpals_committente_percentage = floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81));
        $enpals_lavoratore_percentage = floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19));
        $iva_percentage = floatval(get_option('mm_preventivi_iva_percentage', 22));

        $enpals_committente = $applica_enpals ? ($totale_dopo_sconto * ($enpals_committente_percentage / 100)) : 0;
        $enpals_lavoratore = $applica_enpals ? ($totale_dopo_sconto * ($enpals_lavoratore_percentage / 100)) : 0;

        // IMPORTANTE: L'ENPALS lavoratore NON viene incluso nel totale (è a carico del lavoratore)
        // Solo l'ENPALS committente viene aggiunto al totale
        $imponibile_iva = $totale_dopo_sconto + $enpals_committente;

        $iva = $applica_iva ? ($imponibile_iva * ($iva_percentage / 100)) : 0;
        $totale = $imponibile_iva + $iva;

        // Totali
        $evidenzia_totale_servizi = ($importo_sconto == 0); // Evidenzia se non ci sono sconti
        $bg_totale_servizi = $evidenzia_totale_servizi ? 'background-color: #fff9c4;' : '';

        $html_totali = '
        <table style="width: 100%; margin-top: 25px; font-size: 11px; border-top: 3px solid #e91e63;">
            <tr style="' . $bg_totale_servizi . '">
                <td style="padding: 10px 0; text-align: right; width: 70%; font-weight: bold;">Totale Servizi:</td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_servizi, 2, ',', '.') . '</td>
            </tr>';

        // Mostra sconto se presente
        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            $html_totali .= '
            <tr style="color: #4caf50;">
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">- ' . $sconto_label . ':</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">€ ' . number_format($importo_sconto, 2, ',', '.') . '</td>
            </tr>
            <tr style="border-top: 1px solid #ddd; background-color: #fff9c4;">
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">Subtotale:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_dopo_sconto, 2, ',', '.') . '</td>
            </tr>';
        }

        // Enpals con dettaglio committente/lavoratore
        if ($applica_enpals) {
            // Calcola il 33% del totale dopo sconto
            $enpals_33_percent = $totale_dopo_sconto * 0.33;
            // Il 9.19% viene sottratto (enpals lavoratore)
            $enpals_netto = $enpals_33_percent - $enpals_lavoratore;

            $html_totali .= '
            <tr>
                <td style="padding: 8px 0; text-align: right;">Ex Enpals 33%:</td>
                <td style="padding: 8px 0; text-align: right;">€ ' . number_format($enpals_33_percent, 2, ',', '.') . '</td>
            </tr>
            <tr style="background-color: #fffaf0; border-left: 3px solid #ff9800;">
                <td style="padding: 8px 0; text-align: right; font-size: 9px; color: #666;">- Ex Enpals Lavoratore ' . number_format($enpals_lavoratore_percentage, 2) . '%:</td>
                <td style="padding: 8px 0; text-align: right; font-size: 9px; color: #666;">- € ' . number_format($enpals_lavoratore, 2, ',', '.') . '</td>
            </tr>
            <tr style="border-top: 1px solid #ddd;">
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">Totale Ex Enpals:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">€ ' . number_format($enpals_netto, 2, ',', '.') . '</td>
            </tr>';
        }

        // IVA
        if ($applica_iva) {
            $html_totali .= '
            <tr>
                <td style="padding: 8px 0; text-align: right;">IVA (22%):</td>
                <td style="padding: 8px 0; text-align: right;">€ ' . number_format($iva, 2, ',', '.') . '</td>
            </tr>';
        }

        $html_totali .= '
            <tr style="border-top: 3px solid #e91e63; background-color: #f8bbd0;">
                <td style="padding: 12px 0; text-align: right; color: #e91e63;"><strong style="font-size: 15px;">TOTALE:</strong></td>
                <td style="padding: 12px 0; text-align: right; color: #e91e63;"><strong style="font-size: 16px;">' . number_format($totale, 2, ',', '.') . ' €</strong></td>
            </tr>
        </table>';

        $pdf->writeHTML($html_totali, true, false, true, false, '');

        // Validità preventivo (se non confermato) - DOPO i totali e note
        if ($stato !== 'accettato' && $stato !== 'confermato') {
            $html_validita = '
            <p style="font-size: 9px; color: #666; font-style: italic; margin: 15px 0 10px 0; padding: 8px; background: #fffaf0; border-left: 3px solid #ff9800; border-radius: 4px;">
                ⏰ Il presente preventivo è valido 14 giorni dalla data indicata
            </p>
            ';
            $pdf->writeHTML($html_validita, true, false, true, false, '');
        }

        // Form dati cliente NASCOSTO (verrà utilizzato se il preventivo è accettato per produrre un contratto proforma)
        /*
        $html_form_cliente = '
        <div style="margin-top: 25px; padding: 15px; border: 2px solid #e91e63; border-radius: 6px; background: linear-gradient(to bottom, #ffffff 0%, #fafafa 100%); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="color: #e91e63; font-size: 11px; margin: 0 0 12px 0; text-align: center; font-weight: bold; letter-spacing: 0.5px; border-bottom: 2px solid #e91e63; padding-bottom: 6px;">DATI ' . strtoupper($label_cliente) . ' DA COMPILARE</h3>
            <table style="width: 100%; font-size: 9px; line-height: 2; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; padding: 5px 10px 5px 0; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold;">Nome:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                    <td style="width: 50%; padding: 5px 0 5px 10px; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold;">Cognome:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold;">Città:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                    <td style="padding: 5px 0 5px 10px; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold;">Indirizzo:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold;">Codice Fiscale:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                    <td style="padding: 5px 0 5px 10px; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold;">CAP:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 5px 0; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold;">Partita IVA:</span><br/>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 10px;">&nbsp;</span>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 15px; padding: 10px; background-color: #fff3f8; border-radius: 4px; border-left: 3px solid #e91e63;">
                <p style="font-size: 9px; margin: 0 0 8px 0; font-weight: bold; color: #333;">
                    <span style="color: #e91e63;">✍</span> Per Accettazione:
                </p>
                <div style="border-bottom: 1px solid #999; min-height: 20px; margin-bottom: 4px;"></div>
                <p style="font-size: 8px; color: #666; margin: 0; font-style: italic; text-align: right;">
                    (Firma e data)
                </p>
            </div>
        </div>
        ';

        $pdf->writeHTML($html_form_cliente, true, false, true, false, '');
        */

        // Acconti multipli
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();

        // Retrocompatibilità: se non ci sono acconti nella nuova tabella, usa i vecchi campi
        if (empty($acconti) && !empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto'])) {
            $acconti = array(array(
                'data_acconto' => $preventivo['data_acconto'],
                'importo_acconto' => $preventivo['importo_acconto']
            ));
        }

        if (!empty($acconti)) {
            $totale_acconti = 0;
            $html_acconto = '<table style="width: 100%; margin-top: 15px; font-size: 10px; background-color: #e8f5e9; padding: 10px; border-radius: 5px;">';

            foreach ($acconti as $acconto) {
                $importo = floatval($acconto['importo_acconto']);
                $totale_acconti += $importo;
                $html_acconto .= '
                <tr>
                    <td style="padding: 5px;"><strong style="color: #2e7d32;">Acconto del ' . date('d/m/Y', strtotime($acconto['data_acconto'])) . ':</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; color: #2e7d32;">€ ' . number_format($importo, 2, ',', '.') . '</td>
                </tr>';
            }

            $restante = $totale - $totale_acconti;
            $html_acconto .= '
                <tr style="border-top: 2px solid #4caf50;">
                    <td style="padding: 5px;"><strong>Totale Acconti:</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; color: #2e7d32;">€ ' . number_format($totale_acconti, 2, ',', '.') . '</td>
                </tr>
                <tr>
                    <td style="padding: 5px;"><strong>Restante da saldare:</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold;">€ ' . number_format($restante, 2, ',', '.') . '</td>
                </tr>
            </table>';
            $pdf->writeHTML($html_acconto, true, false, true, false, '');
        }

        // Footer
        $pdf->SetY(-25);
        $html_footer = '
        <hr style="border: none; border-top: 1px solid #ddd; margin: 15px 0 10px 0;">
        <p style="text-align: center; font-size: 8px; color: #999; line-height: 1.4;">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            Tel. ' . esc_html($company_phone) . ' - Email: ' . esc_html($company_email) . '
        </p>';
        $pdf->writeHTML($html_footer, true, false, true, false, '');

        // Output PDF
        $filename = 'Preventivo_' . $preventivo['numero_preventivo'] . '.pdf';
        $pdf->Output($filename, 'D');
    }

    /**
     * Genera PDF in HTML (fallback se TCPDF non disponibile)
     */
    private static function generate_html_pdf($preventivo) {
        // Impostazioni aziendali
        $company_name = get_option('mm_preventivi_company_name', 'MONTERO MUSIC di Massimo Manca');
        $company_address = get_option('mm_preventivi_company_address', 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)');
        $company_phone = get_option('mm_preventivi_company_phone', '333-7512343');
        $company_email = get_option('mm_preventivi_company_email', 'info@massimomanca.it');
        $company_piva = get_option('mm_preventivi_company_piva', 'P.I. 04867450753');
        $company_cf = get_option('mm_preventivi_company_cf', 'C.F. MNCMSM79E01119H');
        $company_logo = get_option('mm_preventivi_logo', '');

        // Genera link pubblico per condivisione
        if (method_exists('MM_Frontend', 'get_public_link')) {
            $public_link = MM_Frontend::get_public_link($preventivo['id']);
        } else {
            // Fallback: usa link admin (richiede login)
            $public_link = admin_url('admin-ajax.php?action=mm_view_pdf&id=' . $preventivo['id'] . '&nonce=' . wp_create_nonce('mm_preventivi_view_pdf'));
        }

        // Header per download HTML
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="Preventivo_' . $preventivo['numero_preventivo'] . '.html"');

        echo '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventivo ' . esc_html($preventivo['numero_preventivo']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 4px solid #e91e63; padding-bottom: 20px; margin-bottom: 30px; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .logo { max-height: 100px; }
        .header-right { text-align: right; }
        .preventivo-title { color: #e91e63; font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .preventivo-numero { color: #666; font-size: 14px; }
        .subtitle { color: #666; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 25px 0; }
        .tipo-evento-stato-box { display: flex; margin: 15px 0 10px 0; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 8px rgba(233, 30, 99, 0.25); }
        .tipo-evento-left { flex: 70%; padding: 6px 15px; background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%); display: flex; align-items: center; }
        .tipo-evento-right { flex: 30%; padding: 6px 15px; display: flex; justify-content: center; align-items: center; text-align: center; color: white; }
        .tipo-evento-left p, .tipo-evento-right p { font-size: 11px; margin: 0; color: #ffffff; font-weight: 600; letter-spacing: 0.3px; }
        .tipo-evento-left p:first-child, .tipo-evento-right p:first-child { font-size: 9px; font-weight: 600; }
        .validita-box { font-size: 11px; color: #666; font-style: italic; margin: 8px 0 15px 0; padding: 10px; background: #fffaf0; border-left: 3px solid #ff9800; border-radius: 4px; }
        .service-tag { display: inline-block; margin: 4px 6px; padding: 6px 12px; background: #ffffff; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 11px; font-weight: 500; color: #424242; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .service-tag .checkbox { display: inline-block; width: 14px; height: 14px; background: #9c27b0; border-radius: 3px; margin-right: 6px; text-align: center; line-height: 14px; color: white; font-size: 10px; vertical-align: middle; }
        .services-extra-box { margin: 15px 0; padding: 12px 15px; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border-left: 4px solid #9c27b0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .totale-evidenziato { background-color: #fff9c4 !important; }
        .tipo-evento-right.stato-bozza { background: #ff9800; }
        .tipo-evento-right.stato-attivo { background: #4caf50; }
        .tipo-evento-right.stato-inviato { background: #2196f3; }
        .tipo-evento-right.stato-accettato { background: #8bc34a; }
        .tipo-evento-right.stato-rifiutato { background: #f44336; }
        .tipo-evento-right.stato-archiviato { background: #9e9e9e; }
        .info-box { background: #f8f8f8; padding: 18px; border-radius: 8px; border: 2px solid #e91e63; }
        .info-box-title { color: #e91e63; font-weight: bold; font-size: 14px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e91e63; }
        .info-row { margin: 8px 0; font-size: 13px; line-height: 1.6; }
        .info-row strong { color: #333; }
        h2 { color: #e91e63; font-size: 16px; margin: 25px 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f8bbd0; }
        .services-list { margin: 15px 0; font-size: 12px; line-height: 2; }
        .service-item { display: inline-block; background: #f0f0f0; padding: 4px 12px; margin: 4px 6px 4px 0; border-radius: 15px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #e91e63; color: white; font-weight: bold; font-size: 13px; }
        td:last-child { text-align: right; }
        .totals { background: #fafafa; padding: 20px; border-radius: 5px; margin-top: 30px; border: 2px solid #e91e63; }
        .totals table { margin: 0; }
        .totals tr { border: none; }
        .totals td { border: none; padding: 8px 0; font-size: 14px; }
        .total-row { border-top: 3px solid #e91e63 !important; background: #f8bbd0; font-size: 18px !important; font-weight: bold; color: #e91e63; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #999; }
        .acconto { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin-top: 20px; }
        .note { background: #fffaf0; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0; line-height: 1.6; }

        /* Checkbox Row */
        .checkbox-row {
            margin: 15px 0;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            font-size: 14px;
        }
        .checkbox-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .checkbox-item::before {
            content: "\\2611";
            color: #4caf50;
            font-size: 18px;
        }

        /* Two Column Grid */
        .two-column-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }
        .column-box {
            background: #fafafa;
            padding: 18px;
            border-radius: 6px;
            border: 2px solid #e91e63;
        }
        .column-box h3 {
            margin: 0 0 12px 0;
            color: #e91e63;
            font-size: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f8bbd0;
        }

        /* Pulsanti Azioni */
        .action-buttons {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-btn {
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
            min-width: 140px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 30, 99, 0.4);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .action-btn.whatsapp-btn {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }

        .action-btn.whatsapp-btn:hover {
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }

        .action-btn.email-btn {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .action-btn.email-btn:hover {
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .action-btn.share-btn {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        .action-btn.share-btn:hover {
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }

        .action-btn.pdf-btn {
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .action-btn.pdf-btn:hover {
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.4);
        }

        .action-btn.pdf-btn:disabled {
            background: #ccc;
            cursor: wait;
            box-shadow: none;
        }

        /* Pulsante Stampa (deprecato, ora dentro action-buttons) */
        .print-button {
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
            transition: all 0.3s;
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 30, 99, 0.4);
        }

        /* Ottimizzazione Mobile */
        @media only screen and (max-width: 768px) {
            body {
                padding: 10px !important;
            }

            .container {
                padding: 15px !important;
                box-shadow: none !important;
            }

            .header {
                flex-direction: column !important;
                text-align: center !important;
            }

            .header-left {
                flex-direction: column !important;
                align-items: center !important;
                gap: 10px !important;
                margin-bottom: 15px !important;
            }

            .header-right {
                text-align: center !important;
            }

            .logo {
                max-height: 60px !important;
            }

            .preventivo-title {
                font-size: 16px !important;
            }

            .preventivo-numero {
                font-size: 13px !important;
            }

            /* Info grid diventa stack verticale su mobile */
            .info-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            .info-box {
                padding: 12px !important;
            }

            .info-box-title {
                font-size: 12px !important;
            }

            .info-row {
                font-size: 12px !important;
            }

            /* Tipo evento e stato stack verticale */
            .tipo-evento-stato-box {
                flex-direction: column !important;
            }

            .tipo-evento-left,
            .tipo-evento-right {
                flex: 100% !important;
                padding: 10px 12px !important;
                border-radius: 0 !important;
            }

            .tipo-evento-left {
                border-radius: 6px 6px 0 0 !important;
            }

            .tipo-evento-right {
                border-radius: 0 0 6px 6px !important;
            }

            .tipo-evento-left p,
            .tipo-evento-right p {
                font-size: 12px !important;
            }

            /* Tabelle responsive con scroll orizzontale */
            table {
                font-size: 11px !important;
                display: block !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                white-space: nowrap !important;
            }

            thead, tbody, tr {
                display: table !important;
                width: 100% !important;
            }

            th, td {
                padding: 8px 6px !important;
                font-size: 11px !important;
            }

            /* Heading più piccoli */
            h2 {
                font-size: 14px !important;
                margin: 20px 0 10px !important;
            }

            /* Service tags più piccoli */
            .service-tag {
                font-size: 10px !important;
                padding: 5px 10px !important;
                margin: 3px 4px !important;
            }

            .service-tag .checkbox {
                width: 12px !important;
                height: 12px !important;
                line-height: 12px !important;
                font-size: 9px !important;
            }

            /* Two column grid diventa single column */
            .two-column-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            .column-box {
                padding: 12px !important;
            }

            .column-box h3 {
                font-size: 13px !important;
            }

            .column-box table {
                font-size: 11px !important;
            }

            /* Totali più compatti */
            .totals {
                padding: 12px !important;
            }

            .totals td {
                font-size: 12px !important;
                padding: 6px 0 !important;
            }

            .total-row {
                font-size: 16px !important;
            }

            /* Note e acconti */
            .note, .acconto {
                padding: 10px !important;
                font-size: 11px !important;
            }

            /* Footer */
            .footer {
                font-size: 10px !important;
                padding-top: 15px !important;
            }

            /* Action buttons su mobile */
            .action-buttons {
                top: 5px !important;
                right: 5px !important;
                gap: 6px !important;
            }

            .action-btn {
                padding: 10px 14px !important;
                font-size: 12px !important;
                min-width: 120px !important;
            }

            .print-button {
                position: fixed !important;
                top: 10px !important;
                right: 10px !important;
                padding: 10px 16px !important;
                font-size: 13px !important;
                z-index: 9999 !important;
            }

            /* Checkbox row */
            .checkbox-row {
                flex-direction: column !important;
                padding: 10px !important;
                gap: 10px !important;
            }

            .checkbox-item {
                font-size: 12px !important;
            }

            /* Validità box */
            .validita-box {
                font-size: 10px !important;
                padding: 8px !important;
            }
        }

        /* Ottimizzazione per schermi molto piccoli (< 375px) */
        @media only screen and (max-width: 375px) {
            body {
                padding: 5px !important;
            }

            .container {
                padding: 10px !important;
            }

            .preventivo-title {
                font-size: 14px !important;
            }

            .preventivo-numero {
                font-size: 12px !important;
            }

            .info-box-title {
                font-size: 11px !important;
            }

            table {
                font-size: 10px !important;
            }

            th, td {
                padding: 6px 4px !important;
                font-size: 10px !important;
            }

            h2 {
                font-size: 13px !important;
            }

            .action-btn {
                padding: 8px 12px !important;
                font-size: 11px !important;
                min-width: 100px !important;
            }

            .print-button {
                font-size: 12px !important;
                padding: 8px 12px !important;
            }
        }

        /* Ottimizzazione Stampa A4 */
        @media print {
            /* Reset base per stampa */
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm;
                height: 297mm;
            }

            /* Container ottimizzato per A4 */
            .container {
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 12mm 15mm !important;
                margin: 0 !important;
                page-break-after: avoid;
            }

            /* Nascondi pulsanti azione durante stampa */
            .action-buttons,
            .print-button {
                display: none !important;
            }

            /* Riduci spaziature per risparmiare spazio */
            .header {
                padding-bottom: 10px !important;
                margin-bottom: 15px !important;
            }

            .info-grid {
                grid-template-columns: 1fr 1fr !important;
                margin: 15px 0 !important;
                gap: 15px !important;
            }

            .info-box {
                padding: 12px !important;
            }

            .info-box-title {
                font-size: 11px !important;
            }

            .tipo-evento-stato-box {
                margin: 15px 0 10px !important;
            }

            .tipo-evento-left {
                padding: 6px 15px !important;
            }

            .tipo-evento-right {
                padding: 6px 15px !important;
            }

            .tipo-evento-left p,
            .tipo-evento-right p {
                font-size: 11px !important;
                display: inline !important;
            }

            .tipo-evento-left p:first-child,
            .tipo-evento-right p:first-child {
                font-size: 9px !important;
            }

            h2 {
                margin: 15px 0 10px !important;
                padding-bottom: 6px !important;
                font-size: 14px !important;
            }

            /* Servizi più compatti */
            .services-list {
                margin: 10px 0 !important;
                line-height: 1.6 !important;
            }

            .service-item {
                padding: 3px 10px !important;
                margin: 3px 4px 3px 0 !important;
                font-size: 11px !important;
            }

            /* Tabella più compatta */
            table {
                margin: 10px 0 !important;
                font-size: 11px !important;
            }

            th, td {
                padding: 8px !important;
            }

            /* Totali più compatti */
            .totals {
                margin-top: 15px !important;
                padding: 15px !important;
            }

            .totals td {
                padding: 6px 0 !important;
                font-size: 12px !important;
            }

            .total-row {
                font-size: 16px !important;
            }

            /* Note e altre sezioni */
            .note, .acconto {
                padding: 8px !important;
                margin: 8px 0 !important;
                font-size: 10px !important;
            }

            .acconto p {
                font-size: 10px !important;
            }

            /* Footer compatto */
            .footer {
                margin-top: 15px !important;
                padding-top: 10px !important;
                font-size: 9px !important;
            }

            /* Checkbox row */
            .checkbox-row {
                margin: 12px 0 !important;
                padding: 12px !important;
                font-size: 12px !important;
                gap: 14px !important;
            }

            .checkbox-item::before {
                font-size: 16px !important;
            }

            /* Two column grid */
            .two-column-grid {
                margin: 15px 0 !important;
                gap: 15px !important;
            }

            .column-box {
                padding: 12px !important;
            }

            .column-box h3 {
                font-size: 12px !important;
                margin-bottom: 8px !important;
            }

            .column-box table {
                font-size: 10px !important;
            }

            /* Previeni interruzioni pagina inappropriate */
            .header { page-break-inside: avoid; page-break-after: avoid; }
            .info-grid { page-break-inside: avoid; }
            .info-box { page-break-inside: avoid; }
            .checkbox-row { page-break-inside: avoid; }
            table { page-break-inside: avoid; }
            .two-column-grid { page-break-inside: avoid; }
            .column-box { page-break-inside: avoid; }
            .totals { page-break-inside: avoid; }
            .acconto { page-break-inside: avoid; }
            h2 { page-break-after: avoid; }

            /* Sezione servizi disponibili - può andare su pagina separata se necessario */
            h2:has(+ div > table) {
                page-break-before: auto;
            }

            /* Assicura che il contenuto non vada su più pagine */
            * {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Pulsanti Azione (visibili solo su schermo) -->
        <div class="action-buttons">
            <button onclick="window.print()" class="action-btn">
                🖨️ Stampa
            </button>';

        // Mostra pulsanti aggiuntivi solo se l'utente è loggato
        if (MM_Auth::is_logged_in()) {
            echo '
            <button onclick="sharePreventivo()" class="action-btn share-btn">
                📤 Condividi
            </button>
            <button onclick="downloadPDF()" class="action-btn pdf-btn">
                📄 PDF
            </button>
            <a href="mailto:' . esc_attr($preventivo['email']) . '?subject=Preventivo ' . esc_attr($preventivo['numero_preventivo']) . '&body=Gentile Cliente, trova il preventivo al seguente link: ' . urlencode($public_link) . '" class="action-btn email-btn">
                ✉️ Email
            </a>
            <a href="https://wa.me/' . preg_replace('/[^0-9+]/', '', $preventivo['telefono']) . '?text=' . urlencode('🎉 Ciao! Ti invio il preventivo ' . $preventivo['numero_preventivo'] . '. Puoi visualizzarlo qui: ' . $public_link) . '" target="_blank" class="action-btn whatsapp-btn">
                💬 WhatsApp
            </a>';
        }

        echo '
        </div>

        <div class="header">
            <div class="header-left">';

        if (!empty($company_logo)) {
            echo '<img src="' . esc_url($company_logo) . '" alt="Logo" class="logo">';
        }

        echo '<div>
                    <div style="color: #e91e63; font-size: 22px; font-weight: bold;">' . esc_html($company_name) . '</div>
                    <p class="subtitle">DJ • Animazione • Scenografie • Photo Booth</p>
                </div>
            </div>
            <div class="header-right">
                <div class="preventivo-title">PREVENTIVO</div>
                <div class="preventivo-numero">N. ' . esc_html($preventivo['numero_preventivo']) . '</div>
                <div class="preventivo-numero" style="margin-top: 5px; font-size: 12px;">del ' . date('d/m/Y', strtotime($preventivo['data_preventivo'])) . '</div>
            </div>
        </div>';

        // Prepara variabili per la versione HTML (stesso codice della versione TCPDF)
        $categoria_nome = !empty($preventivo['categoria_nome']) ? strtolower($preventivo['categoria_nome']) : '';
        $label_cliente = ($categoria_nome === 'matrimonio') ? 'Sposi' : 'Cliente';

        $stato = !empty($preventivo['stato']) ? $preventivo['stato'] : 'attivo';
        $stato_label = ucfirst($stato);

        $categoria_display = !empty($preventivo['categoria_nome']) ? $preventivo['categoria_nome'] : 'Non specificato';
        $categoria_icona = !empty($preventivo['categoria_icona']) ? $preventivo['categoria_icona'] : '🎉';

        echo '
        <!-- Box Tipo Evento e Stato -->
        <div class="tipo-evento-stato-box">
            <div class="tipo-evento-left">
                <p style="display: inline; margin-right: 8px;">TIPO DI EVENTO RICHIESTO:</p>
                <p style="display: inline;">' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</p>
            </div>
            <div class="tipo-evento-right stato-' . esc_attr($stato) . '">
                <p style="display: inline; margin-right: 8px;">STATO:</p>
                <p style="display: inline;">' . strtoupper(esc_html($stato_label)) . '</p>
            </div>
        </div>

        <div class="info-grid">';

        echo '<div class="info-box">
                <div class="info-box-title">' . $label_cliente . '</div>
                <div class="info-row"><strong style="font-size: 18px;">' . esc_html($preventivo['sposi']) . '</strong></div>
                <div class="info-row">📧 ' . esc_html($preventivo['email']) . '</div>
                <div class="info-row">📞 ' . esc_html($preventivo['telefono']) . '</div>
            </div>
            <div class="info-box">
                <div class="info-box-title">Dettagli Evento</div>
                <div class="info-row"><strong style="font-size: 18px;">📅 ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '</strong></div>
                <div class="info-row">🎉 ' . esc_html($preventivo['tipo_evento']) . '</div>
                <div class="info-row">📍 ' . esc_html($preventivo['location']) . '</div>
            </div>
        </div>';

        // Riga cerimonia (se rito = SI)
        $cerimonia_array_html = !empty($preventivo['cerimonia']) ? (is_array($preventivo['cerimonia']) ? $preventivo['cerimonia'] : explode(',', $preventivo['cerimonia'])) : array();
        if (!empty($cerimonia_array_html)) {
            $cerimonia_items = array();
            foreach ($cerimonia_array_html as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $cerimonia_items[] = esc_html($item);
                }
            }

            if (!empty($cerimonia_items)) {
                echo '<div style="margin: 10px 0 15px 0; padding: 8px 12px; background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-left: 4px solid #ff9800; border-radius: 4px;">
                        <span style="font-size: 11px; color: #e65100; font-weight: 700; letter-spacing: 0.5px;">RITO:</span>
                        <span style="font-size: 12px; color: #bf360c; font-weight: 600; margin-left: 8px;">' . implode(' • ', $cerimonia_items) . '</span>
                      </div>';
            }
        }

        // Servizi extra come fascia elegante con checkbox
        $extra_array_html = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array_html)) {
            echo '<div style="margin: 15px 0; padding: 12px 15px; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border-radius: 8px; border-left: 4px solid #9c27b0; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">';

            foreach ($extra_array_html as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    echo '<span style="display: inline-block; margin: 4px 6px; padding: 6px 12px; background: #ffffff; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 11px; font-weight: 500; color: #424242; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <span style="display: inline-block; width: 14px; height: 14px; background: #9c27b0; border-radius: 3px; margin-right: 6px; text-align: center; line-height: 14px; color: white; font-size: 10px; vertical-align: middle;">✓</span>' . esc_html($item) . '</span>';
                }
            }

            echo '</div>';
        }

        // Verifica se esiste almeno uno sconto tra i servizi
        $ha_sconti = false;
        foreach ($preventivo['servizi'] as $servizio) {
            if (isset($servizio['sconto']) && floatval($servizio['sconto']) > 0) {
                $ha_sconti = true;
                break;
            }
        }

        // Tabella dettagliata con TUTTI i servizi
        echo '<h2 style="margin-top: 25px;">Dettaglio Prezzi</h2>
        <table style="font-size: 10px;">
            <thead>
                <tr>
                    <th>Servizio</th>
                    <th style="width: 90px;">Prezzo</th>';

        // Mostra colonna sconto solo se presente
        if ($ha_sconti) {
            echo '<th style="width: 90px;">Sconto</th>';
        }

        echo '<th style="width: 90px;">Totale</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_servizio = $prezzo - $sconto;

            echo '<tr>
                <td>' . esc_html($servizio['nome_servizio']) . '</td>
                <td>' . ($prezzo > 0 ? '€ ' . number_format($prezzo, 2, ',', '.') : '<span style="color: #999;">Incluso</span>') . '</td>';

            // Mostra colonna sconto solo se ha_sconti
            if ($ha_sconti) {
                echo '<td>' . ($sconto > 0 ? '<span style="color: #4caf50; font-weight: bold;">-€ ' . number_format($sconto, 2, ',', '.') . '</span>' : '—') . '</td>';
            }

            echo '<td style="font-weight: ' . ($prezzo > 0 ? 'bold' : 'normal') . ';">' . ($prezzo > 0 ? '€ ' . number_format($totale_servizio, 2, ',', '.') : '—') . '</td>
            </tr>';
        }

        echo '</tbody></table>';

        // Calcoli totali
        $totale_servizi = floatval($preventivo['totale_servizi']);
        $sconto = isset($preventivo['sconto']) ? floatval($preventivo['sconto']) : 0;
        $sconto_percentuale = isset($preventivo['sconto_percentuale']) ? floatval($preventivo['sconto_percentuale']) : 0;

        $importo_sconto = 0;
        if ($sconto_percentuale > 0) {
            $importo_sconto = $totale_servizi * ($sconto_percentuale / 100);
        } elseif ($sconto > 0) {
            $importo_sconto = $sconto;
        }

        $totale_dopo_sconto = $totale_servizi - $importo_sconto;

        $applica_enpals = isset($preventivo['applica_enpals']) && $preventivo['applica_enpals'] == 1;
        $applica_iva = isset($preventivo['applica_iva']) && $preventivo['applica_iva'] == 1;

        // Carica aliquote configurabili
        $enpals_committente_percentage = floatval(get_option('mm_preventivi_enpals_committente_percentage', 23.81));
        $enpals_lavoratore_percentage = floatval(get_option('mm_preventivi_enpals_lavoratore_percentage', 9.19));
        $iva_percentage = floatval(get_option('mm_preventivi_iva_percentage', 22));

        $enpals_committente = $applica_enpals ? ($totale_dopo_sconto * ($enpals_committente_percentage / 100)) : 0;
        $enpals_lavoratore = $applica_enpals ? ($totale_dopo_sconto * ($enpals_lavoratore_percentage / 100)) : 0;

        // IMPORTANTE: L'ENPALS lavoratore NON viene incluso nel totale (è a carico del lavoratore)
        // Solo l'ENPALS committente viene aggiunto al totale
        $imponibile_iva = $totale_dopo_sconto + $enpals_committente;
        $iva = $applica_iva ? ($imponibile_iva * ($iva_percentage / 100)) : 0;
        $totale = $imponibile_iva + $iva;

        // Determina se evidenziare Totale Servizi in giallo
        $evidenzia_totale_servizi = ($importo_sconto == 0);
        $style_totale_servizi = $evidenzia_totale_servizi ? 'background-color: #fff9c4;' : '';

        // Layout a due colonne: Note (sinistra) e Totali (destra)
        echo '<div class="two-column-grid">
            <!-- Colonna SINISTRA: Note -->
            <div class="column-box">';

        if (!empty($preventivo['note'])) {
            echo '<h3>📝 Note</h3>
            <div style="line-height: 1.6; font-size: 12px;">' . nl2br(esc_html($preventivo['note'])) . '</div>';
        } else {
            echo '<h3>📝 Note</h3>
            <p style="color: #999; font-style: italic; font-size: 12px;">Nessuna nota aggiuntiva</p>';
        }

        echo '</div>

            <!-- Colonna DESTRA: Totali -->
            <div class="column-box">
                <h3>💰 Riepilogo Importi</h3>
                <table style="width: 100%; margin: 0; font-size: 12px; border: none;">
                    <tr style="' . $style_totale_servizi . '"><td style="border: none; padding: 6px 0;"><strong>Totale Servizi</strong></td><td style="border: none; padding: 6px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_servizi, 2, ',', '.') . '</td></tr>';

        if ($importo_sconto > 0) {
            $sconto_label = 'Sconto';
            if ($sconto_percentuale > 0) {
                $sconto_label .= ' (' . number_format($sconto_percentuale, 0) . '%)';
            }
            echo '<tr><td style="border: none; padding: 6px 0; color: #4caf50;">- ' . $sconto_label . '</td><td style="border: none; padding: 6px 0; text-align: right; color: #4caf50; font-weight: bold;">€ ' . number_format($importo_sconto, 2, ',', '.') . '</td></tr>';
            echo '<tr style="border-top: 2px solid #ddd; background-color: #fff9c4;"><td style="border: none; padding: 6px 0;"><strong>Subtotale</strong></td><td style="border: none; padding: 6px 0; text-align: right; font-weight: bold;">€ ' . number_format($totale_dopo_sconto, 2, ',', '.') . '</td></tr>';
        }

        // Mostra dettaglio ENPALS con calcolo 33%
        if ($applica_enpals) {
            // Calcola il 33% del totale dopo sconto
            $enpals_33_percent = $totale_dopo_sconto * 0.33;
            // Il 9.19% viene sottratto (enpals lavoratore)
            $enpals_netto = $enpals_33_percent - $enpals_lavoratore;

            echo '<tr><td style="border: none; padding: 6px 0;">Ex Enpals 33%</td><td style="border: none; padding: 6px 0; text-align: right;">€ ' . number_format($enpals_33_percent, 2, ',', '.') . '</td></tr>';
            echo '<tr style="background-color: #fffaf0;"><td style="border: none; padding: 6px 0; font-size: 10px; color: #666;">- Ex Enpals Lavoratore ' . number_format($enpals_lavoratore_percentage, 2) . '%</td><td style="border: none; padding: 6px 0; text-align: right; font-size: 10px; color: #666;">- € ' . number_format($enpals_lavoratore, 2, ',', '.') . '</td></tr>';
            echo '<tr style="border-top: 1px solid #ddd;"><td style="border: none; padding: 6px 0;"><strong>Totale Ex Enpals</strong></td><td style="border: none; padding: 6px 0; text-align: right; font-weight: bold;">€ ' . number_format($enpals_netto, 2, ',', '.') . '</td></tr>';
        }

        // Mostra sempre IVA (anche se zero)
        echo '<tr><td style="border: none; padding: 6px 0;">IVA (' . number_format($iva_percentage, 1) . '%)</td><td style="border: none; padding: 6px 0; text-align: right;">€ ' . number_format($iva, 2, ',', '.') . '</td></tr>';

        echo '<tr class="total-row" style="border-top: 3px solid #e91e63;">
                        <td style="border: none; padding: 12px 0 6px 0;"><strong>TOTALE</strong></td>
                        <td style="border: none; padding: 12px 0 6px 0; text-align: right;"><strong>' . number_format($totale, 2, ',', '.') . ' €</strong></td>
                    </tr>
                </table>
            </div>
        </div>';

        // Validità preventivo (se non confermato) - DOPO i totali e note
        if ($stato !== 'accettato' && $stato !== 'confermato') {
            echo '<div class="validita-box">
                    ⏰ Il presente preventivo è valido 14 giorni dalla data indicata
                  </div>';
        }

        // Form dati cliente NASCOSTO (verrà utilizzato se il preventivo è accettato per produrre un contratto proforma)
        /*
        echo '<div class="client-form-section" style="margin-top: 25px; padding: 15px; border: 2px solid #e91e63; border-radius: 6px; background: linear-gradient(to bottom, #ffffff 0%, #fafafa 100%); box-shadow: 0 2px 4px rgba(0,0,0,0.1); page-break-inside: avoid;">
            <h3 style="color: #e91e63; font-size: 12px; margin: 0 0 12px 0; text-align: center; font-weight: bold; letter-spacing: 0.5px; border-bottom: 2px solid #e91e63; padding-bottom: 6px;">DATI ' . strtoupper($label_cliente_html) . ' DA COMPILARE</h3>
            <table style="width: 100%; font-size: 10px; line-height: 2; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; padding: 5px 10px 5px 0; border: none; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Nome:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                    <td style="width: 50%; padding: 5px 0 5px 10px; border: none; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Cognome:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; border: none; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Città:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                    <td style="padding: 5px 0 5px 10px; border: none; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Indirizzo:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; border: none; vertical-align: top;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Codice Fiscale:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                    <td style="padding: 5px 0 5px 10px; border: none; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">CAP:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 5px 0; border: none; vertical-align: top; text-align: right;">
                        <span style="color: #e91e63; font-weight: bold; display: block; margin-bottom: 3px;">Partita IVA:</span>
                        <span style="border-bottom: 1px dotted #999; display: inline-block; width: 100%; padding-bottom: 2px; min-height: 12px;">&nbsp;</span>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 15px; padding: 10px; background-color: #fff3f8; border-radius: 4px; border-left: 3px solid #e91e63;">
                <p style="font-size: 10px; margin: 0 0 8px 0; font-weight: bold; color: #333;">
                    <span style="color: #e91e63;">✍</span> Per Accettazione:
                </p>
                <div style="border-bottom: 1px solid #999; min-height: 22px; margin-bottom: 5px;"></div>
                <p style="font-size: 9px; color: #666; margin: 0; font-style: italic; text-align: right;">
                    (Firma e data)
                </p>
            </div>
        </div>';
        */

        // Acconti multipli
        $acconti_html = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();

        // Retrocompatibilità: se non ci sono acconti nella nuova tabella, usa i vecchi campi
        if (empty($acconti_html) && !empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto']) && floatval($preventivo['importo_acconto']) > 0) {
            $acconti_html = array(array(
                'data_acconto' => $preventivo['data_acconto'],
                'importo_acconto' => $preventivo['importo_acconto']
            ));
        }

        if (!empty($acconti_html)) {
            echo '<div class="acconto" style="margin-top: 15px; padding: 10px; font-size: 11px;">';
            $totale_acconti_html = 0;

            foreach ($acconti_html as $acconto_item) {
                $importo = floatval($acconto_item['importo_acconto']);
                $totale_acconti_html += $importo;
                echo '<p style="font-size: 11px; margin: 4px 0;"><strong>Acconto del ' . date('d/m/Y', strtotime($acconto_item['data_acconto'])) . ':</strong> € ' . number_format($importo, 2, ',', '.') . '</p>';
            }

            if (count($acconti_html) > 1) {
                echo '<p style="font-size: 11px; margin: 8px 0 4px 0; padding-top: 8px; border-top: 2px solid #4caf50;"><strong>Totale Acconti:</strong> € ' . number_format($totale_acconti_html, 2, ',', '.') . '</p>';
            }

            $restante = $totale - $totale_acconti_html;
            echo '<p style="font-size: 11px; margin: 4px 0;"><strong>Restante da saldare:</strong> € ' . number_format($restante, 2, ',', '.') . '</p>';
            echo '</div>';
        }

        // Sezione Servizi Disponibili (non selezionati)
        $servizi_catalogo = MM_Database::get_catalogo_servizi();
        $servizi_selezionati_nomi = array_map(function($s) {
            return strtolower(trim($s['nome_servizio']));
        }, $preventivo['servizi']);

        $servizi_disponibili = array_filter($servizi_catalogo, function($servizio) use ($servizi_selezionati_nomi) {
            return $servizio['attivo'] == 1 &&
                   !in_array(strtolower(trim($servizio['nome_servizio'])), $servizi_selezionati_nomi);
        });

        if (!empty($servizi_disponibili)) {
            echo '<h2 style="margin-top: 30px;">📋 Altri Servizi Disponibili</h2>
            <div style="background: #f8f8f8; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0;">
                <table style="font-size: 10px; margin: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Servizio</th>
                            <th style="width: 150px; text-align: left;">Categoria</th>
                            <th style="width: 100px; text-align: right;">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>';

            // Ordina per categoria e poi per ordinamento
            usort($servizi_disponibili, function($a, $b) {
                if ($a['categoria'] == $b['categoria']) {
                    return $a['ordinamento'] - $b['ordinamento'];
                }
                return strcmp($a['categoria'], $b['categoria']);
            });

            foreach ($servizi_disponibili as $servizio) {
                $prezzo_display = $servizio['prezzo_default'] > 0
                    ? '€ ' . number_format($servizio['prezzo_default'], 2, ',', '.')
                    : 'Su richiesta';

                echo '<tr>
                    <td style="padding: 8px 12px;">' . esc_html($servizio['nome_servizio']);

                if (!empty($servizio['descrizione'])) {
                    echo '<br><small style="color: #666; font-size: 9px;">' . esc_html($servizio['descrizione']) . '</small>';
                }

                echo '</td>
                    <td style="padding: 8px 12px; color: #666;">' . esc_html($servizio['categoria'] ?: '—') . '</td>
                    <td style="padding: 8px 12px; text-align: right; font-weight: 600; color: #e91e63;">' . $prezzo_display . '</td>
                </tr>';
            }

            echo '</tbody>
                </table>
                <p style="margin: 10px 0 0 0; font-size: 10px; color: #999; font-style: italic;">
                    I servizi sopra elencati sono disponibili su richiesta. Contattaci per maggiori informazioni.
                </p>
            </div>';
        }

        echo '<div class="footer">
            <strong>' . esc_html($company_name) . '</strong><br>
            ' . esc_html($company_address) . '<br>
            ' . esc_html($company_piva) . ' - ' . esc_html($company_cf) . '<br>
            Tel. ' . esc_html($company_phone) . ' - Email: ' . esc_html($company_email) . '
        </div>
    </div>

    <script>
        // Link pubblico per condivisione
        const publicLink = \'' . esc_js($public_link) . '\';
        const numeroPreventivo = \'' . esc_js($preventivo['numero_preventivo']) . '\';
        const preventivoId = ' . intval($preventivo['id']) . ';
        const pdfDownloadUrl = \'' . esc_js(admin_url('admin-ajax.php')) . '\';

        // Funzione download PDF (genera PDF reale lato server)
        function downloadPDF() {
            const btn = document.querySelector(\'.pdf-btn\');
            const originalText = btn.innerHTML;
            btn.innerHTML = \'⏳ Generando...\';
            btn.disabled = true;

            // Estrai token dall\'URL corrente se presente
            const urlParts = window.location.pathname.split(\'/\');
            let token = \'\';
            if (urlParts.length >= 4 && urlParts[1] === \'preventivo\') {
                token = urlParts[3] || \'\';
            }

            // Costruisci URL per download PDF
            let downloadUrl = pdfDownloadUrl + \'?action=mm_download_pdf&id=\' + preventivoId;
            if (token) {
                downloadUrl += \'&token=\' + token;
            }

            // Apri in nuova finestra per scaricare
            window.location.href = downloadUrl;

            // Ripristina pulsante dopo un breve delay
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        }

        // Funzione condivisione con Web Share API (o fallback)
        function sharePreventivo() {
            const shareData = {
                title: \'Preventivo ' . esc_js($preventivo['numero_preventivo']) . '\',
                text: \'Preventivo per ' . esc_js($preventivo['sposi']) . ' - Evento del ' . date('d/m/Y', strtotime($preventivo['data_evento'])) . '\',
                url: publicLink
            };

            // Controlla se il browser supporta Web Share API
            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => console.log(\'Condivisione riuscita\'))
                    .catch((error) => console.log(\'Errore condivisione:\', error));
            } else {
                // Fallback: copia URL negli appunti
                const tempInput = document.createElement(\'input\');
                tempInput.value = publicLink;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand(\'copy\');
                document.body.removeChild(tempInput);

                alert(\'Link pubblico copiato negli appunti!\\n\\nCondividi questo link per mostrare il preventivo.\');
            }
        }

        // Auto-print on load (opzionale)
        // window.onload = function() { window.print(); }

        // Aggiusta scroll delle tabelle su mobile
        document.addEventListener(\'DOMContentLoaded\', function() {
            const tables = document.querySelectorAll(\'table\');
            tables.forEach(table => {
                // Touch scroll ottimizzato
                let isDown = false;
                let startX;
                let scrollLeft;

                table.addEventListener(\'mousedown\', (e) => {
                    isDown = true;
                    startX = e.pageX - table.offsetLeft;
                    scrollLeft = table.scrollLeft;
                });

                table.addEventListener(\'mouseleave\', () => {
                    isDown = false;
                });

                table.addEventListener(\'mouseup\', () => {
                    isDown = false;
                });

                table.addEventListener(\'mousemove\', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - table.offsetLeft;
                    const walk = (x - startX) * 2;
                    table.scrollLeft = scrollLeft - walk;
                });
            });
        });
    </script>
</body>
</html>';
        exit;
    }
}
