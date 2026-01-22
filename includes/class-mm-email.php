<?php
/**
 * Gestione Email Preventivi
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Email {

    /**
     * Genera link WhatsApp per invio preventivo
     */
    public static function generate_whatsapp_link($preventivo_id) {
        $preventivo = MM_Database::get_preventivo($preventivo_id);

        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato', 'mm-preventivi'));
        }

        // Numero telefono del cliente (rimuovi spazi, trattini, ecc.)
        $telefono = preg_replace('/[^0-9+]/', '', $preventivo['telefono']);

        if (empty($telefono)) {
            return new WP_Error('phone_missing', __('Numero di telefono del cliente mancante', 'mm-preventivi'));
        }

        // Se il numero non inizia con +, aggiungi prefisso Italia
        if (substr($telefono, 0, 1) !== '+') {
            $telefono = '+39' . ltrim($telefono, '0');
        }

        // Genera messaggio formattato
        $messaggio = self::generate_whatsapp_message($preventivo);

        // Genera link WhatsApp
        $link = 'https://wa.me/' . $telefono . '?text=' . urlencode($messaggio);

        return $link;
    }

    /**
     * Genera messaggio WhatsApp formattato
     */
    private static function generate_whatsapp_message($preventivo) {
        // Calcola subtotale
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

        // Intestazione
        $msg = "🎵 *PREVENTIVO " . $preventivo['numero_preventivo'] . "*\n";
        $msg .= "📅 Data Evento: " . date('d/m/Y', strtotime($preventivo['data_evento'])) . "\n";
        if (!empty($preventivo['location'])) {
            $msg .= "📍 Location: " . $preventivo['location'] . "\n";
        }
        $msg .= "\n";

        // Servizi
        $msg .= "*🎵 SERVIZI INCLUSI*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

        $servizi = isset($preventivo['servizi']) && is_array($preventivo['servizi']) ? $preventivo['servizi'] : array();
        foreach ($servizi as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $sconto_serv = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
            $totale_serv = $prezzo - $sconto_serv;

            $msg .= "• " . $servizio['nome_servizio'];
            if ($sconto_serv > 0) {
                $msg .= "\n  " . number_format($prezzo, 2, ',', '.') . " € - " . number_format($sconto_serv, 2, ',', '.') . " € = *" . number_format($totale_serv, 2, ',', '.') . " €*";
            } else {
                $msg .= "\n  *" . number_format($prezzo, 2, ',', '.') . " €*";
            }
            $msg .= "\n";
        }
        $msg .= "\n";

        // Totali
        $msg .= "*💰 TOTALI*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Totale Servizi: " . number_format($totale_servizi, 2, ',', '.') . " €\n";

        if ($sconto_fisso > 0) {
            $msg .= "Sconto Fisso: -" . number_format($sconto_fisso, 2, ',', '.') . " €\n";
        }
        if ($sconto_percentuale > 0) {
            $msg .= "Sconto " . number_format($sconto_percentuale, 0) . "%: -" . number_format($sconto_perc_importo, 2, ',', '.') . " €\n";
        }

        $msg .= "*Subtotale: " . number_format($subtotale, 2, ',', '.') . " €*\n\n";

        if ($applica_enpals && $enpals_committente > 0) {
            $msg .= "ENPALS (23.81%): +" . number_format($enpals_committente, 2, ',', '.') . " €\n";
        }
        if ($applica_iva && $iva > 0) {
            $msg .= "IVA (22%): +" . number_format($iva, 2, ',', '.') . " €\n";
        }

        // Nota IVA/ENPALS
        $note_parts = array();
        if ($applica_iva) {
            $note_parts[] = 'IVA';
        }
        if ($applica_enpals) {
            $note_parts[] = 'ENPALS';
        }
        if (!empty($note_parts)) {
            $msg .= "_(" . implode(' e ', $note_parts) . " ove applicabile/i)_\n";
        }

        $msg .= "\n*🎯 TOTALE: " . number_format($totale, 2, ',', '.') . " €*\n\n";

        // Acconti
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();
        if (!empty($acconti)) {
            $totale_acconti = 0;
            $msg .= "*💸 ACCONTI*\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

            foreach ($acconti as $acconto) {
                $importo = floatval($acconto['importo_acconto']);
                $totale_acconti += $importo;
                $msg .= "• " . date('d/m/Y', strtotime($acconto['data_acconto'])) . ": " . number_format($importo, 2, ',', '.') . " €\n";
            }

            $msg .= "\nTotale Acconti: *" . number_format($totale_acconti, 2, ',', '.') . " €*\n";
            $msg .= "Restante: *" . number_format($totale - $totale_acconti, 2, ',', '.') . " €*\n\n";
        }

        // Note
        if (!empty($preventivo['note'])) {
            $msg .= "*📝 NOTE*\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= $preventivo['note'] . "\n\n";
        }

        // Link pubblico per visualizzare il preventivo
        if (method_exists('MM_Frontend', 'get_public_link')) {
            $public_link = MM_Frontend::get_public_link($preventivo['id']);
        } else {
            $public_link = home_url();
        }
        $msg .= "📄 *VISUALIZZA PREVENTIVO COMPLETO*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= $public_link . "\n\n";

        // Footer
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🎉 *" . get_bloginfo('name') . "*\n";
        $msg .= "Grazie per averci scelto! 🎶";

        return $msg;
    }

    /**
     * Invia preventivo via email
     */
    public static function send_preventivo($preventivo_id) {
        $preventivo = MM_Database::get_preventivo($preventivo_id);

        if (!$preventivo) {
            return new WP_Error('preventivo_not_found', __('Preventivo non trovato', 'mm-preventivi'));
        }

        // Email destinatario
        $to = $preventivo['email'];

        if (empty($to)) {
            return new WP_Error('email_missing', __('Email del cliente mancante', 'mm-preventivi'));
        }

        // Oggetto email
        $subject = sprintf(
            'Preventivo %s - %s',
            $preventivo['numero_preventivo'],
            get_bloginfo('name')
        );

        // Genera HTML email
        $message = self::generate_email_html($preventivo);

        // Genera PDF allegato
        $pdf_path = self::generate_pdf_attachment($preventivo);
        $attachments = array();
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }

        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Invia email con allegato
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);

        // Rimuovi file temporaneo PDF dopo l'invio
        if ($pdf_path && file_exists($pdf_path)) {
            @unlink($pdf_path);
        }

        if ($sent) {
            // Log successo
            MM_Security::log_security_event('preventivo_email_sent', array(
                'preventivo_id' => $preventivo_id,
                'email' => $to,
                'has_attachment' => !empty($attachments)
            ));

            return true;
        } else {
            return new WP_Error('email_send_failed', __('Impossibile inviare l\'email', 'mm-preventivi'));
        }
    }

    /**
     * Genera PDF temporaneo per allegato email
     */
    private static function generate_pdf_attachment($preventivo) {
        // Genera HTML del preventivo
        $html = MM_PDF_Generator::generate_pdf_html_for_attachment($preventivo);

        if (empty($html)) {
            return false;
        }

        // Crea directory temporanea se non esiste
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mm-preventivi-temp/';

        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
            // Proteggi directory con .htaccess
            file_put_contents($temp_dir . '.htaccess', 'deny from all');
        }

        // Nome file PDF
        $filename = 'Preventivo_' . sanitize_file_name($preventivo['numero_preventivo']) . '_' . time() . '.pdf';
        $filepath = $temp_dir . $filename;

        // Prova a generare PDF con dompdf
        try {
            // Verifica che dompdf sia disponibile
            $dompdf_autoload = MM_PREVENTIVI_PLUGIN_DIR . 'includes/dompdf/autoload.php';
            if (file_exists($dompdf_autoload)) {
                require_once $dompdf_autoload;

                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('defaultFont', 'DejaVu Sans');
                $options->set('tempDir', $temp_dir);
                $options->set('chroot', $upload_dir['basedir']);

                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                // Imposta A4 con margini 20mm (56.69 punti)
                // A4 = 595.28 x 841.89 punti
                // Con margini 20mm: area utile inizia a 56.69pt dai bordi
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Salva PDF su file
                $pdf_content = $dompdf->output();
                $result = file_put_contents($filepath, $pdf_content);

                if ($result !== false) {
                    error_log('MM Email - PDF generato con successo: ' . $filepath);
                    return $filepath;
                }
            } else {
                error_log('MM Email - Dompdf autoload non trovato: ' . $dompdf_autoload);
            }
        } catch (\Exception $e) {
            error_log('MM Email - Errore generazione PDF: ' . $e->getMessage());
        }

        // Fallback: salva come HTML se dompdf fallisce
        $html_filename = 'Preventivo_' . sanitize_file_name($preventivo['numero_preventivo']) . '_' . time() . '.html';
        $html_filepath = $temp_dir . $html_filename;
        file_put_contents($html_filepath, $html);

        return $html_filepath;
    }

    /**
     * Genera HTML email professionale
     */
    private static function generate_email_html($preventivo) {
        // Ottieni logo (se presente) - priorità al logo del plugin
        $logo_url = get_option('mm_preventivi_logo', '');
        if (empty($logo_url)) {
            $logo_url = get_option('mm_preventivi_logo_url', '');
        }
        if (empty($logo_url)) {
            $logo_url = get_site_icon_url(200);
        }

        // Dati aziendali
        $company_name = get_option('mm_preventivi_company_name', get_bloginfo('name'));
        $company_phone = get_option('mm_preventivi_company_phone', '');
        $company_email_address = get_option('mm_preventivi_company_email', get_option('admin_email'));

        // Colori brand
        $primary_color = '#e91e63';
        $secondary_color = '#9c27b0';

        // Calcola subtotale (totale servizi - sconti)
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

        // Servizi
        $servizi = isset($preventivo['servizi']) && is_array($preventivo['servizi']) ? $preventivo['servizi'] : array();

        // Acconti
        $acconti = isset($preventivo['acconti']) && is_array($preventivo['acconti']) ? $preventivo['acconti'] : array();
        $totale_acconti = 0;
        foreach ($acconti as $acconto) {
            $totale_acconti += floatval($acconto['importo_acconto']);
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventivo <?php echo esc_html($preventivo['numero_preventivo']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $secondary_color; ?> 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
        }
        .email-header img {
            max-width: 180px;
            max-height: 100px;
            height: auto;
            margin-bottom: 25px;
            border-radius: 8px;
        }
        .email-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .email-header p {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
        }
        .email-header .preventivo-number {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 25px;
            margin-top: 15px;
            font-size: 16px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: <?php echo $primary_color; ?>;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid <?php echo $primary_color; ?>;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 6px;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .services-table th {
            background: <?php echo $primary_color; ?>;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        .services-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        .services-table tr:last-child td {
            border-bottom: none;
        }
        .services-table .text-right {
            text-align: right;
        }
        .totals-box {
            background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 15px;
        }
        .total-row.main {
            font-size: 18px;
            font-weight: 700;
            color: <?php echo $primary_color; ?>;
            padding-top: 15px;
            border-top: 2px solid <?php echo $primary_color; ?>;
            margin-top: 10px;
        }
        .total-row .label {
            color: #666;
        }
        .total-row .value {
            font-weight: 600;
            color: #333;
        }
        .total-row.main .value {
            color: <?php echo $primary_color; ?>;
        }
        .note-text {
            font-size: 13px;
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }
        .acconti-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #4caf50;
        }
        .acconto-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .acconto-item .date {
            color: #2e7d32;
            font-weight: 600;
        }
        .acconto-item .amount {
            font-weight: 700;
            color: #2e7d32;
        }
        .email-footer {
            background: #f5f5f5;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer p {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }
        .email-footer .company-name {
            font-size: 16px;
            font-weight: 700;
            color: <?php echo $primary_color; ?>;
            margin-bottom: 15px;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 20px 15px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .email-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <?php if (!empty($logo_url)) : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>">
            <?php endif; ?>
            <h1>PREVENTIVO</h1>
            <p><?php echo esc_html($company_name); ?></p>
            <div class="preventivo-number">N° <?php echo esc_html($preventivo['numero_preventivo']); ?></div>
        </div>

        <!-- Body -->
        <div class="email-body">

            <!-- Informazioni Evento -->
            <div class="section">
                <div class="section-title">📋 Informazioni Evento</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Cliente</div>
                        <div class="info-value"><?php echo esc_html($preventivo['sposi']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data Evento</div>
                        <div class="info-value"><?php echo esc_html(date('d/m/Y', strtotime($preventivo['data_evento']))); ?></div>
                    </div>
                    <?php if (!empty($preventivo['location'])) : ?>
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div class="info-value"><?php echo esc_html($preventivo['location']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($preventivo['tipo_evento'])) : ?>
                    <div class="info-item">
                        <div class="info-label">Tipo Evento</div>
                        <div class="info-value"><?php echo esc_html($preventivo['tipo_evento']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">Data Preventivo</div>
                        <div class="info-value"><?php echo esc_html(date('d/m/Y', strtotime($preventivo['data_preventivo']))); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Validità</div>
                        <div class="info-value">30 giorni dalla data del preventivo</div>
                    </div>
                </div>
            </div>

            <!-- Servizi -->
            <div class="section">
                <div class="section-title">🎵 Servizi Inclusi</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Servizio</th>
                            <th class="text-right">Prezzo</th>
                            <?php if (array_filter($servizi, function($s) { return !empty($s['sconto']); })) : ?>
                            <th class="text-right">Sconto</th>
                            <th class="text-right">Totale</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servizi as $servizio) :
                            $prezzo = floatval($servizio['prezzo']);
                            $sconto_serv = isset($servizio['sconto']) ? floatval($servizio['sconto']) : 0;
                            $totale_serv = $prezzo - $sconto_serv;
                        ?>
                        <tr>
                            <td><?php echo esc_html($servizio['nome_servizio']); ?></td>
                            <td class="text-right">€ <?php echo number_format($prezzo, 2, ',', '.'); ?></td>
                            <?php if (array_filter($servizi, function($s) { return !empty($s['sconto']); })) : ?>
                            <td class="text-right">
                                <?php echo $sconto_serv > 0 ? '- € ' . number_format($sconto_serv, 2, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right"><strong>€ <?php echo number_format($totale_serv, 2, ',', '.'); ?></strong></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totali -->
            <div class="section">
                <div class="totals-box">
                    <div class="total-row">
                        <span class="label">Totale Servizi:</span>
                        <span class="value">€ <?php echo number_format($totale_servizi, 2, ',', '.'); ?></span>
                    </div>

                    <?php if ($sconto_fisso > 0) : ?>
                    <div class="total-row">
                        <span class="label">Sconto Fisso:</span>
                        <span class="value" style="color: #f44336;">- € <?php echo number_format($sconto_fisso, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($sconto_percentuale > 0) : ?>
                    <div class="total-row">
                        <span class="label">Sconto <?php echo number_format($sconto_percentuale, 0); ?>%:</span>
                        <span class="value" style="color: #f44336;">- € <?php echo number_format($sconto_perc_importo, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="total-row" style="font-size: 16px; font-weight: 600; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                        <span class="label">Subtotale:</span>
                        <span class="value">€ <?php echo number_format($subtotale, 2, ',', '.'); ?></span>
                    </div>

                    <?php if ($applica_enpals && $enpals_committente > 0) : ?>
                    <div class="total-row">
                        <span class="label">ENPALS Committente (23.81%):</span>
                        <span class="value">€ <?php echo number_format($enpals_committente, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($applica_iva && $iva > 0) : ?>
                    <div class="total-row">
                        <span class="label">IVA (22%):</span>
                        <span class="value">€ <?php echo number_format($iva, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="total-row main">
                        <span class="label">TOTALE:</span>
                        <span class="value">€ <?php echo number_format($totale, 2, ',', '.'); ?></span>
                    </div>

                    <p class="note-text">
                        <?php
                        $note_parts = array();
                        if ($applica_iva) {
                            $note_parts[] = 'IVA';
                        }
                        if ($applica_enpals) {
                            $note_parts[] = 'ENPALS';
                        }
                        if (!empty($note_parts)) {
                            echo '* ' . implode(' e ', $note_parts) . ' applicabile/i';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Acconti -->
            <?php if (!empty($acconti)) : ?>
            <div class="section">
                <div class="acconti-box">
                    <div class="section-title" style="color: #2e7d32; border-color: #4caf50;">💰 Acconti Previsti</div>
                    <?php foreach ($acconti as $acconto) : ?>
                    <div class="acconto-item">
                        <span class="date"><?php echo esc_html(date('d/m/Y', strtotime($acconto['data_acconto']))); ?></span>
                        <span class="amount">€ <?php echo number_format(floatval($acconto['importo_acconto']), 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="acconto-item" style="border-top: 2px solid #4caf50; margin-top: 10px; padding-top: 15px; font-size: 16px;">
                        <span class="date"><strong>Totale Acconti:</strong></span>
                        <span class="amount" style="font-size: 18px;">€ <?php echo number_format($totale_acconti, 2, ',', '.'); ?></span>
                    </div>

                    <div class="acconto-item" style="padding-bottom: 0;">
                        <span class="date">Restante:</span>
                        <span class="amount">€ <?php echo number_format($totale - $totale_acconti, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Note -->
            <?php if (!empty($preventivo['note'])) : ?>
            <div class="section">
                <div class="section-title">📝 Note</div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; font-size: 14px; line-height: 1.6;">
                    <?php echo nl2br(esc_html($preventivo['note'])); ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="company-name"><?php echo esc_html($company_name); ?></div>
            <p style="font-size: 15px; margin-bottom: 15px;">Grazie per averci scelto per il tuo evento speciale! 🎉</p>
            <?php if (!empty($company_phone)) : ?>
            <p>📞 Tel: <?php echo esc_html($company_phone); ?></p>
            <?php endif; ?>
            <p>✉️ Email: <?php echo esc_html($company_email_address); ?></p>
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Per qualsiasi domanda o informazione, non esitare a contattarci.
            </p>
            <p style="margin-top: 25px; font-size: 11px; color: #999;">
                In allegato trovi il preventivo completo in formato PDF.<br>
                Questo preventivo ha validità 30 giorni dalla data di emissione.<br>
                © <?php echo date('Y'); ?> <?php echo esc_html($company_name); ?>. Tutti i diritti riservati.
            </p>
        </div>
    </div>
</body>
</html>
        <?php

        return ob_get_clean();
    }
}
