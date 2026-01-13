<?php
/**
 * Generatore PDF - Massimo Manca Preventivi
 * Supporta TCPDF (se disponibile) con fallback HTML to PDF
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_PDF_Generator {

    /**
     * Genera PDF preventivo
     */
    public static function generate_pdf($preventivo) {
        // PHP 8+ ha problemi con vecchie versioni di TCPDF
        // Usa sempre HTML fallback che funziona perfettamente
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            error_log('MM Preventivi - Usando HTML fallback (PHP ' . PHP_VERSION . ')');
            self::generate_html_pdf($preventivo);
            return;
        }

        // Per PHP < 8, prova a usare TCPDF se disponibile
        if (!class_exists('TCPDF')) {
            // Prova a caricare TCPDF da plugin comuni o installazioni WordPress
            $tcpdf_paths = array(
                ABSPATH . 'wp-content/plugins/tcpdf/tcpdf.php',
                ABSPATH . 'wp-includes/TCPDF/tcpdf.php',
                dirname(__FILE__) . '/tcpdf/tcpdf.php',
            );

            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
        }

        // Se TCPDF è disponibile, usalo
        if (class_exists('TCPDF')) {
            try {
                self::generate_tcpdf($preventivo);
            } catch (Exception $e) {
                error_log('MM Preventivi - Errore TCPDF, fallback a HTML: ' . $e->getMessage());
                self::generate_html_pdf($preventivo);
            }
        } else {
            // Altrimenti usa il fallback HTML
            self::generate_html_pdf($preventivo);
        }
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
     * Genera HTML semplice per il preventivo
     */
    private static function generate_simple_html($preventivo) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Preventivo <?php echo esc_html($preventivo['numero_preventivo']); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    background: #e91e63;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .section {
                    margin: 20px 0;
                    padding: 15px;
                    background: #f9f9f9;
                    border-left: 4px solid #e91e63;
                }
                .totale {
                    font-size: 24px;
                    color: #e91e63;
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background: #e91e63;
                    color: white;
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

            <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e91e63;">
                <p><?php echo esc_html(get_bloginfo('name')); ?></p>
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
                <td style="width: 70%; padding: 8px 12px; background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%); border-radius: 6px 0 0 6px; box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3);">
                    <p style="margin: 0; font-size: 8px; color: rgba(255,255,255,0.85); font-weight: 600; letter-spacing: 0.3px;">TIPO DI EVENTO RICHIESTO</p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #ffffff; font-weight: bold; letter-spacing: 0.3px;">' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</p>
                </td>
                <td style="width: 30%; padding: 8px 12px; background: ' . $stato_color . '; border-radius: 0 6px 6px 0; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); text-align: center;">
                    <p style="margin: 0; font-size: 8px; color: rgba(255,255,255,0.85); font-weight: 600; letter-spacing: 0.3px;">STATO</p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #ffffff; font-weight: bold; letter-spacing: 0.3px;">' . strtoupper(esc_html($stato_label)) . '</p>
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

        // Servizi extra come fascia elegante e sottile
        $extra_array = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array)) {
            $html_boxes = '<div style="margin: 10px 0; padding: 8px 12px; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 6px; border: 1px solid #4caf50;">';

            $services_html = array();
            foreach ($extra_array as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $services_html[] = '<span style="display: inline-block; margin: 2px 4px; padding: 4px 10px; background: #ffffff; border: 1px solid #4caf50; border-radius: 4px; font-size: 9px; font-weight: 600; color: #2e7d32;">✓ ' . esc_html($item) . '</span>';
                }
            }

            $html_boxes .= implode('', $services_html);
            $html_boxes .= '</div>';
            $pdf->writeHTML($html_boxes, true, false, true, false, '');
        }

        // Servizi
        $html_servizi = '
        <h3 style="color: #e91e63; font-size: 13px; margin: 20px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Servizi Richiesti</h3>
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

        // Servizi extra
        if (!empty($preventivo['servizi_extra'])) {
            $extra_list = is_array($preventivo['servizi_extra']) ? implode(', ', $preventivo['servizi_extra']) : $preventivo['servizi_extra'];
            $html_extra = '
            <h3 style="color: #e91e63; font-size: 13px; margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #f8bbd0;">Servizi Aggiuntivi</h3>
            <p style="font-size: 10px; padding: 10px; background-color: #fafafa; border-left: 4px solid #e91e63;">' . esc_html($extra_list) . '</p>
            ';
            $pdf->writeHTML($html_extra, true, false, true, false, '');
        }

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

        // Acconto
        if (!empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto'])) {
            $importo_acconto = floatval($preventivo['importo_acconto']);
            $restante = $totale - $importo_acconto;

            $html_acconto = '
            <table style="width: 100%; margin-top: 15px; font-size: 10px; background-color: #e8f5e9; padding: 10px; border-radius: 5px;">
                <tr>
                    <td style="padding: 5px;"><strong style="color: #2e7d32;">Acconto del ' . date('d/m/Y', strtotime($preventivo['data_acconto'])) . ':</strong></td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; color: #2e7d32;">€ ' . number_format($importo_acconto, 2, ',', '.') . '</td>
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
        .tipo-evento-left { flex: 70%; padding: 10px 15px; background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%); }
        .tipo-evento-right { flex: 30%; padding: 10px 15px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; }
        .tipo-evento-left p:first-child, .tipo-evento-right p:first-child { font-size: 9px; margin: 0; color: rgba(255,255,255,0.85); font-weight: 600; letter-spacing: 0.3px; }
        .tipo-evento-left p:last-child, .tipo-evento-right p:last-child { font-size: 14px; margin: 4px 0 0 0; color: #ffffff; font-weight: bold; letter-spacing: 0.3px; }
        .validita-box { font-size: 11px; color: #666; font-style: italic; margin: 8px 0 15px 0; padding: 10px; background: #fffaf0; border-left: 3px solid #ff9800; border-radius: 4px; }
        .service-tag { display: inline-block; margin: 5px 7px; padding: 12px 18px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; border-radius: 8px; font-size: 12px; font-weight: 600; color: #0d47a1; box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2); }
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

        /* Pulsante Stampa */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
            z-index: 1000;
            transition: all 0.3s;
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 30, 99, 0.4);
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

            /* Nascondi pulsante stampa */
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
                padding: 12px 15px !important;
            }

            .tipo-evento-right {
                padding: 12px 15px !important;
            }

            .tipo-evento-left p:first-child,
            .tipo-evento-right p:first-child {
                font-size: 10px !important;
            }

            .tipo-evento-left p:last-child,
            .tipo-evento-right p:last-child {
                font-size: 16px !important;
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
        <!-- Pulsante stampa (visibile solo su schermo) -->
        <button onclick="window.print()" class="print-button">
            🖨️ Stampa Preventivo
        </button>

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
                <p>TIPO DI EVENTO RICHIESTO</p>
                <p>' . esc_html($categoria_icona) . ' ' . strtoupper(esc_html($categoria_display)) . '</p>
            </div>
            <div class="tipo-evento-right stato-' . esc_attr($stato) . '">
                <p>STATO</p>
                <p>' . strtoupper(esc_html($stato_label)) . '</p>
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

        // Servizi extra come fascia elegante e sottile
        $extra_array_html = !empty($preventivo['servizi_extra']) ? (is_array($preventivo['servizi_extra']) ? $preventivo['servizi_extra'] : explode(',', $preventivo['servizi_extra'])) : array();
        if (!empty($extra_array_html)) {
            echo '<div style="margin: 10px 0; padding: 8px 12px; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 6px; border: 1px solid #4caf50;">';

            foreach ($extra_array_html as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    echo '<span style="display: inline-block; margin: 2px 4px; padding: 4px 10px; background: #ffffff; border: 1px solid #4caf50; border-radius: 4px; font-size: 11px; font-weight: 600; color: #2e7d32;">✓ ' . esc_html($item) . '</span>';
                }
            }

            echo '</div>';
        }

        // Lista servizi in formato compatto
        echo '<h2>Servizi Richiesti</h2>
        <div class="services-list">';

        $servizi_con_prezzo = array();
        $servizi_senza_prezzo = array();

        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            if ($prezzo > 0) {
                $servizi_con_prezzo[] = $servizio;
            } else {
                $servizi_senza_prezzo[] = $servizio;
            }
        }

        // Mostra tutti i servizi come tag
        foreach ($preventivo['servizi'] as $servizio) {
            $prezzo = floatval($servizio['prezzo']);
            $nome = esc_html($servizio['nome_servizio']);

            if ($prezzo > 0) {
                echo '<span class="service-item" style="background: #e3f2fd; border: 1px solid #2196f3; color: #0d47a1; font-weight: 500;">' . $nome . '</span>';
            } else {
                echo '<span class="service-item">' . $nome . '</span>';
            }
        }

        echo '</div>';

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

        // Layout a due colonne: Totali (sinistra) e Note (destra)
        echo '<div class="two-column-grid">
            <!-- Colonna SINISTRA: Totali -->
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

            <!-- Colonna DESTRA: Note -->
            <div class="column-box">';

        if (!empty($preventivo['note'])) {
            echo '<h3>📝 Note</h3>
            <div style="line-height: 1.6; font-size: 12px;">' . nl2br(esc_html($preventivo['note'])) . '</div>';
        } else {
            echo '<h3>📝 Note</h3>
            <p style="color: #999; font-style: italic; font-size: 12px;">Nessuna nota aggiuntiva</p>';
        }

        echo '  </div>
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

        if (!empty($preventivo['data_acconto']) && !empty($preventivo['importo_acconto']) && floatval($preventivo['importo_acconto']) > 0) {
            $importo_acconto = floatval($preventivo['importo_acconto']);
            $restante = $totale - $importo_acconto;
            echo '<div class="acconto" style="margin-top: 15px; padding: 10px; font-size: 11px;">
                <p style="font-size: 11px;"><strong>Acconto del ' . date('d/m/Y', strtotime($preventivo['data_acconto'])) . ':</strong> € ' . number_format($importo_acconto, 2, ',', '.') . '</p>
                <p style="margin-top: 6px; font-size: 11px;"><strong>Restante da saldare:</strong> € ' . number_format($restante, 2, ',', '.') . '</p>
            </div>';
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
        // Auto-print on load (opzionale)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>';
        exit;
    }
}
