/**
 * Massimo Manca Preventivi - Frontend JavaScript
 * Handles form interactions, calculations, and AJAX submissions
 */

(function($) {
    'use strict';

    const MMPreventivi = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.setDefaultDate();
            this.calculateTotals();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Calculate on service change
            $(document).on('change input', '.mm-service-item input', this.calculateTotals.bind(this));

            // Calculate on discount or options change
            $(document).on('change input', '#sconto, #sconto_percentuale, #applica_enpals, #applica_iva', this.calculateTotals.bind(this));

            // Calculate when cerimonia price changes
            $(document).on('change input', '#prezzo_cerimonia', this.calculateTotals.bind(this));

            // Form submission
            $('#mm-preventivo-form').on('submit', this.handleSubmit.bind(this));

            // Reset button
            $('.mm-btn-reset').on('click', this.handleReset.bind(this));

            // Preview button
            $('.mm-btn-preview').on('click', this.handlePreview.bind(this));

            // Save button in preview modal
            $(document).on('click', '.mm-preview-save-btn', this.handleSaveFromPreview.bind(this));

            // Modal close buttons
            $(document).on('click', '.mm-modal-close, .mm-modal-close-btn, .mm-modal-overlay', this.closePreview.bind(this));

            // Clear discount percentuale when discount fisso is used
            $('#sconto').on('input', function() {
                if ($(this).val() > 0) {
                    $('#sconto_percentuale').val(0);
                }
            });

            // Show/hide "Altro rito" field
            $(document).on('change', '#altro_cerimonia', function() {
                if ($(this).is(':checked')) {
                    $('#altro_rito_container').slideDown(200);
                } else {
                    $('#altro_rito_container').slideUp(200);
                    $('#altro_rito_testo').val('');
                }
            });

            // Clear discount fisso when discount percentuale is used
            $('#sconto_percentuale').on('input', function() {
                if ($(this).val() > 0) {
                    $('#sconto').val(0);
                }
            });
        },
        
        /**
         * Set default date
         */
        setDefaultDate: function() {
            const today = new Date().toISOString().split('T')[0];
            $('#data_preventivo').val(today);
        },
        
        /**
         * Calculate totals
         */
        calculateTotals: function() {
            let totaleServizi = 0;

            // Aggiungi prezzo cerimonia se presente
            const prezzoCerimonia = parseFloat($('#prezzo_cerimonia').val()) || 0;
            if (prezzoCerimonia > 0) {
                totaleServizi += prezzoCerimonia;
            }

            // Sum all checked services with individual discounts
            $('.mm-service-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const priceInput = $(this).find('.mm-price-input');
                const discountInput = $(this).find('.mm-discount-input');

                if (checkbox.is(':checked') && priceInput.val()) {
                    const price = parseFloat(priceInput.val()) || 0;
                    const discount = parseFloat(discountInput.val()) || 0;
                    const finalPrice = Math.max(0, price - discount); // Non può essere negativo
                    totaleServizi += finalPrice;
                }
            });

            // Get discount values
            const scontoFisso = parseFloat($('#sconto').val()) || 0;
            const scontoPercentuale = parseFloat($('#sconto_percentuale').val()) || 0;

            // Calculate discount
            let importoSconto = 0;
            if (scontoPercentuale > 0) {
                importoSconto = totaleServizi * (scontoPercentuale / 100);
            } else if (scontoFisso > 0) {
                importoSconto = scontoFisso;
            }

            // Subtotal after discount
            const totaleDopoSconto = totaleServizi - importoSconto;

            // Check if taxes should be applied
            const applicaEnpals = $('#applica_enpals').is(':checked');
            const applicaIva = $('#applica_iva').is(':checked');

            // Usa le aliquote configurabili (split committente/lavoratore)
            const enpalsCommittentePercentage = mmPreventivi.enpalsCommittentePercentage || 23.81;
            const enpalsLavoratorePercentage = mmPreventivi.enpalsLavoratorePercentage || 9.19;
            const ivaPercentage = mmPreventivi.ivaPercentage || 22;

            // Calculate ENPALS
            const enpalsCommittente = applicaEnpals ? (totaleDopoSconto * (enpalsCommittentePercentage / 100)) : 0;
            const enpalsLavoratore = applicaEnpals ? (totaleDopoSconto * (enpalsLavoratorePercentage / 100)) : 0;

            // IMPORTANTE: Solo l'ENPALS committente viene incluso nel totale
            // L'ENPALS lavoratore è solo informativo (a carico del lavoratore)
            const imponibileIva = totaleDopoSconto + enpalsCommittente;
            const iva = applicaIva ? (imponibileIva * (ivaPercentage / 100)) : 0;
            const totale = imponibileIva + iva;

            // Update display
            $('#subtotal').text('€ ' + this.formatCurrency(totaleServizi));

            // Show/hide discount row
            if (importoSconto > 0) {
                $('#sconto-row').show();
                let scontoLabel = 'Sconto';
                if (scontoPercentuale > 0) {
                    scontoLabel = 'Sconto (' + scontoPercentuale.toFixed(0) + '%)';
                }
                $('#sconto-row .label').text('- ' + scontoLabel + ':');
                $('#sconto-display').text('€ ' + this.formatCurrency(importoSconto));
                $('#subtotal-sconto-row').show();
                $('#subtotal-sconto').text('€ ' + this.formatCurrency(totaleDopoSconto));
            } else {
                $('#sconto-row').hide();
                $('#subtotal-sconto-row').hide();
            }

            // Show/hide tax rows (mostra solo ENPALS committente nel totale)
            if (applicaEnpals) {
                $('#enpals-row').show();
                $('#enpals').text('€ ' + this.formatCurrency(enpalsCommittente));
            } else {
                $('#enpals-row').hide();
            }

            if (applicaIva) {
                $('#iva-row').show();
                $('#iva').text('€ ' + this.formatCurrency(iva));
            } else {
                $('#iva-row').hide();
            }

            $('#total').text('€ ' + this.formatCurrency(totale));

            // Store values in hidden fields or data attributes
            $('#mm-preventivo-form').data('totals', {
                totaleServizi: totaleServizi,
                sconto: scontoFisso,
                scontoPercentuale: scontoPercentuale,
                applicaEnpals: applicaEnpals,
                applicaIva: applicaIva,
                enpalsCommittente: enpalsCommittente,
                enpalsLavoratore: enpalsLavoratore,
                iva: iva,
                totale: totale
            });
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return parseFloat(amount).toFixed(2).replace('.', ',');
        },
        
        /**
         * Get selected services
         */
        getSelectedServices: function() {
            const services = [];

            // Aggiungi rito come servizio se ha un prezzo
            const prezzoCerimonia = parseFloat($('#prezzo_cerimonia').val()) || 0;
            const cerimonieSelezionate = this.getSelectedCerimonie();

            console.log('=== INIZIO getSelectedServices ===');
            console.log('Prezzo rito:', prezzoCerimonia);
            console.log('Rito selezionato:', cerimonieSelezionate);

            if (prezzoCerimonia > 0 && cerimonieSelezionate.length > 0) {
                const servizio_cerimonia = {
                    nome: 'Rito (' + cerimonieSelezionate.join(', ') + ')',
                    prezzo: prezzoCerimonia,
                    sconto: 0
                };
                console.log('Aggiungendo servizio cerimonia:', servizio_cerimonia);
                services.push(servizio_cerimonia);
            }

            $('.mm-service-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const label = $(this).find('label').text().trim();
                const priceInput = $(this).find('.mm-price-input');
                const discountInput = $(this).find('.mm-discount-input');

                if (checkbox.is(':checked') && priceInput.val()) {
                    const price = parseFloat(priceInput.val()) || 0;
                    const discount = parseFloat(discountInput.val()) || 0;

                    services.push({
                        nome: label,
                        prezzo: price,
                        sconto: discount
                    });
                }
            });

            console.log('=== FINE getSelectedServices - Totale servizi:', services.length, '===');
            console.log('Servizi da inviare:', services);
            return services;
        },
        
        /**
         * Get selected extras
         */
        getSelectedExtras: function() {
            const extras = [];
            
            $('input[name="servizi_extra[]"]:checked').each(function() {
                extras.push($(this).val());
            });
            
            return extras;
        },
        
        /**
         * Get selected cerimonie
         */
        getSelectedCerimonie: function() {
            const cerimonie = [];

            $('input[name="cerimonia[]"]:checked').each(function() {
                const value = $(this).val();
                if (value === 'Altro') {
                    const altroTesto = $('#altro_rito_testo').val();
                    if (altroTesto && altroTesto.trim() !== '') {
                        cerimonie.push('Altro: ' + altroTesto.trim());
                    } else {
                        cerimonie.push(value);
                    }
                } else {
                    cerimonie.push(value);
                }
            });

            return cerimonie;
        },
        
        /**
         * Validate form
         */
        validateForm: function() {
            const errors = [];
            
            // Check required fields
            const requiredFields = {
                'data_preventivo': 'Data preventivo',
                'sposi': 'Nome sposi/cliente',
                'data_evento': 'Data evento'
            };
            
            $.each(requiredFields, function(field, label) {
                if (!$('#' + field).val()) {
                    errors.push(label + ' è obbligatorio');
                }
            });
            
            // Check if at least one service is selected
            const services = this.getSelectedServices();
            if (services.length === 0) {
                errors.push('Seleziona almeno un servizio');
            }
            
            return errors;
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type) {
            const messageHtml = `
                <div class="mm-message mm-message-${type}">
                    ${type === 'success' ? '✓' : '✗'} ${message}
                </div>
            `;
            
            $('.mm-form-body').prepend(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $('.mm-message').offset().top - 100
            }, 300);
            
            // Remove after 5 seconds
            setTimeout(function() {
                $('.mm-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Handle form submit
         */
        handleSubmit: function(e) {
            e.preventDefault();
            
            // Remove previous messages
            $('.mm-message').remove();
            
            // Validate
            const errors = this.validateForm();
            if (errors.length > 0) {
                this.showMessage(errors.join('<br>'), 'error');
                return;
            }
            
            // Get totals
            const totals = $('#mm-preventivo-form').data('totals');
            
            // Prepare data
            const formData = {
                action: 'mm_save_preventivo',
                nonce: mmPreventivi.nonce,
                categoria_id: $('#categoria_id').val(),
                data_preventivo: $('#data_preventivo').val(),
                sposi: $('#sposi').val(),
                email: $('#email').val(),
                telefono: $('#telefono').val(),
                data_evento: $('#data_evento').val(),
                location: $('#location').val(),
                tipo_evento: $('input[name="tipo_evento"]:checked').val() || '',
                cerimonia: this.getSelectedCerimonie(),
                servizi_extra: this.getSelectedExtras(),
                note: $('#note').val(),
                totale_servizi: totals.totaleServizi,
                sconto: totals.sconto,
                sconto_percentuale: totals.scontoPercentuale,
                applica_enpals: totals.applicaEnpals,
                applica_iva: totals.applicaIva,
                enpals_committente: totals.enpalsCommittente,
                enpals_lavoratore: totals.enpalsLavoratore,
                iva: totals.iva,
                totale: totals.totale,
                data_acconto: $('#data_acconto').val(),
                importo_acconto: $('#importo_acconto').val() || 0,
                servizi: this.getSelectedServices()
            };

            // Debug log
            console.log('Dati preventivo da inviare:', formData);
            console.log('SERVIZI INVIATI:', formData.servizi);
            console.log('NUMERO SERVIZI:', formData.servizi.length);
            console.log('applica_enpals type:', typeof formData.applica_enpals, 'value:', formData.applica_enpals);
            console.log('applica_iva type:', typeof formData.applica_iva, 'value:', formData.applica_iva);

            // Disable submit button
            const $submitBtn = $('.mm-btn-primary');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true)
                     .html('<span class="mm-loading"></span> Salvataggio...');
            
            // Send AJAX request
            $.ajax({
                url: mmPreventivi.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    console.log('Risposta server:', response);

                    if (response.success) {
                        // Show success with PDF button
                        const preventivoId = response.data.preventivo_id;
                        const pdfUrl = mmPreventivi.ajaxurl + '?action=mm_view_pdf&id=' + preventivoId + '&nonce=' + mmPreventivi.pdfNonce;

                        const successHtml = `
                            <div class="mm-message mm-message-success">
                                ✓ ${response.data.message}
                                <div style="margin-top: 15px;">
                                    <a href="${pdfUrl}" target="_blank" class="mm-btn mm-btn-primary" style="display: inline-block; text-decoration: none; padding: 12px 24px; border-radius: 8px;">
                                        📄 Visualizza PDF
                                    </a>
                                </div>
                            </div>
                        `;

                        $('.mm-form-body').prepend(successHtml);

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.mm-message').offset().top - 100
                        }, 300);

                        // Reset form after 5 seconds
                        setTimeout(() => {
                            this.handleReset();
                        }, 5000);
                    } else {
                        console.error('Errore dal server:', response.data);
                        let errorMsg = response.data.message || 'Errore sconosciuto';
                        if (response.data.debug) {
                            errorMsg += ' (Debug: ' + response.data.debug + ')';
                        }
                        this.showMessage(errorMsg, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Errore AJAX:', {xhr, status, error});
                    console.error('Response Text:', xhr.responseText);
                    this.showMessage('Errore di connessione: ' + error, 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * Handle reset
         */
        handleReset: function() {
            if (confirm('Sei sicuro di voler resettare il form?')) {
                $('#mm-preventivo-form')[0].reset();
                this.setDefaultDate();
                this.calculateTotals();
                $('.mm-message').remove();
            }
        },

        /**
         * Handle preview
         */
        handlePreview: function() {
            // Validate basic fields
            const errors = this.validateForm();
            if (errors.length > 0) {
                this.showMessage(errors.join('<br>'), 'error');
                return;
            }

            // Get form data
            const totals = $('#mm-preventivo-form').data('totals');
            const servizi = this.getSelectedServices();
            const cerimonia = this.getSelectedCerimonie();
            const extras = this.getSelectedExtras();

            // Get company info (could be loaded from settings)
            const companyName = 'MONTERO MUSIC di Massimo Manca';
            const companyAddress = 'Via Ofanto, 37 73047 Monteroni di Lecce (LE)';
            const companyPhone = '333-7512343';
            const companyEmail = 'info@massimomanca.it';
            const companyPiva = 'P.I. 04867450753';
            const companyCf = 'C.F. MNCMSM79E01119H';

            // Generate preview HTML
            let html = `
                <div class="mm-preview-document">
                    <div class="mm-preview-header">
                        <div class="mm-preview-logo-section">
                            <h1 style="color: #e91e63; margin: 0;">PREVENTIVO</h1>
                            <p style="color: #666; font-size: 12px; margin: 5px 0 0;">DJ • Animazione • Scenografie • Photo Booth</p>
                        </div>
                        <div class="mm-preview-company">
                            <p style="margin: 0; font-weight: bold;">${companyName}</p>
                            <p style="margin: 3px 0; font-size: 13px;">${companyAddress}</p>
                            <p style="margin: 3px 0; font-size: 13px;">Tel. ${companyPhone}</p>
                            <p style="margin: 3px 0; font-size: 13px;">${companyEmail}</p>
                        </div>
                    </div>

                    <hr style="border: none; border-top: 3px solid #e91e63; margin: 20px 0;">

                    <div class="mm-preview-section">
                        <h3 style="color: #e91e63; border-bottom: 2px solid #f8bbd0; padding-bottom: 8px;">Informazioni Cliente</h3>
                        <table class="mm-preview-info-table">
                            <tr>
                                <td><strong>Cliente/Sposi:</strong></td>
                                <td>${this.escapeHtml($('#sposi').val())}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>${this.escapeHtml($('#email').val()) || '—'}</td>
                            </tr>
                            <tr>
                                <td><strong>Telefono:</strong></td>
                                <td>${this.escapeHtml($('#telefono').val()) || '—'}</td>
                            </tr>
                            <tr>
                                <td><strong>Data Preventivo:</strong></td>
                                <td>${this.formatDate($('#data_preventivo').val())}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="mm-preview-section">
                        <h3 style="color: #e91e63; border-bottom: 2px solid #f8bbd0; padding-bottom: 8px;">Dettagli Evento</h3>
                        <table class="mm-preview-info-table">
                            <tr>
                                <td><strong>Data Evento:</strong></td>
                                <td>${this.formatDate($('#data_evento').val())}</td>
                            </tr>
                            <tr>
                                <td><strong>Location:</strong></td>
                                <td>${this.escapeHtml($('#location').val()) || '—'}</td>
                            </tr>
                            <tr>
                                <td><strong>Tipo Evento:</strong></td>
                                <td>${this.escapeHtml($('input[name="tipo_evento"]:checked').val() || 'Cena')}</td>
                            </tr>
                            ${cerimonia.length > 0 ? `
                            <tr>
                                <td><strong>Cerimonia:</strong></td>
                                <td>${cerimonia.map(c => this.escapeHtml(c)).join(', ')}</td>
                            </tr>
                            ` : ''}
                        </table>
                    </div>

                    <div class="mm-preview-section">
                        <h3 style="color: #e91e63; border-bottom: 2px solid #f8bbd0; padding-bottom: 8px;">Servizi Richiesti</h3>
                        <table class="mm-preview-services-table">
                            <thead>
                                <tr>
                                    <th>Servizio</th>
                                    <th style="text-align: right;">Prezzo</th>
                                    <th style="text-align: right;">Sconto</th>
                                    <th style="text-align: right;">Totale</th>
                                </tr>
                            </thead>
                            <tbody>`;

            servizi.forEach(servizio => {
                const prezzo = parseFloat(servizio.prezzo);
                const sconto = parseFloat(servizio.sconto) || 0;
                const totaleServizio = prezzo - sconto;

                html += `
                    <tr>
                        <td>${this.escapeHtml(servizio.nome)}</td>
                        <td style="text-align: right;">€ ${this.formatCurrency(prezzo)}</td>
                        <td style="text-align: right; color: ${sconto > 0 ? '#4caf50' : '#999'}; font-weight: ${sconto > 0 ? 'bold' : 'normal'};">
                            ${sconto > 0 ? '-€ ' + this.formatCurrency(sconto) : '—'}
                        </td>
                        <td style="text-align: right; font-weight: bold;">€ ${this.formatCurrency(totaleServizio)}</td>
                    </tr>`;
            });

            html += `
                            </tbody>
                        </table>
                    </div>`;

            // Servizi extra
            if (extras.length > 0) {
                html += `
                    <div class="mm-preview-section">
                        <h3 style="color: #e91e63; border-bottom: 2px solid #f8bbd0; padding-bottom: 8px;">Servizi Aggiuntivi</h3>
                        <p style="background: #fafafa; padding: 12px; border-left: 4px solid #e91e63; border-radius: 4px;">
                            ${extras.map(e => this.escapeHtml(e)).join(', ')}
                        </p>
                    </div>`;
            }

            // Note
            const note = $('#note').val();
            if (note) {
                html += `
                    <div class="mm-preview-section">
                        <h3 style="color: #e91e63; border-bottom: 2px solid #f8bbd0; padding-bottom: 8px;">Note</h3>
                        <p style="background: #fffaf0; padding: 12px; border-left: 4px solid #ff9800; border-radius: 4px; line-height: 1.6;">
                            ${this.escapeHtml(note).replace(/\n/g, '<br>')}
                        </p>
                    </div>`;
            }

            // Totali
            const scontoFisso = parseFloat($('#sconto').val()) || 0;
            const scontoPercentuale = parseFloat($('#sconto_percentuale').val()) || 0;
            let importoSconto = 0;
            if (scontoPercentuale > 0) {
                importoSconto = totals.totaleServizi * (scontoPercentuale / 100);
            } else if (scontoFisso > 0) {
                importoSconto = scontoFisso;
            }
            const totaleDopoSconto = totals.totaleServizi - importoSconto;

            html += `
                <div class="mm-preview-totals">
                    <table style="width: 100%; margin-top: 20px; border-top: 3px solid #e91e63; padding-top: 15px;">
                        <tr>
                            <td style="text-align: right; padding: 8px 0; font-weight: bold;">Totale Servizi:</td>
                            <td style="text-align: right; padding: 8px 0; font-weight: bold; width: 120px;">€ ${this.formatCurrency(totals.totaleServizi)}</td>
                        </tr>`;

            if (importoSconto > 0) {
                let scontoLabel = 'Sconto';
                if (scontoPercentuale > 0) {
                    scontoLabel += ' (' + scontoPercentuale.toFixed(0) + '%)';
                }
                html += `
                        <tr style="color: #4caf50;">
                            <td style="text-align: right; padding: 8px 0; font-weight: bold;">- ${scontoLabel}:</td>
                            <td style="text-align: right; padding: 8px 0; font-weight: bold;">€ ${this.formatCurrency(importoSconto)}</td>
                        </tr>
                        <tr style="border-top: 1px solid #ddd;">
                            <td style="text-align: right; padding: 8px 0; font-weight: bold;">Subtotale:</td>
                            <td style="text-align: right; padding: 8px 0; font-weight: bold;">€ ${this.formatCurrency(totaleDopoSconto)}</td>
                        </tr>`;
            }

            if (totals.applicaEnpals) {
                const enpalsCommittentePercentage = mmPreventivi.enpalsCommittentePercentage || 23.81;
                const enpalsLavoratorePercentage = mmPreventivi.enpalsLavoratorePercentage || 9.19;
                html += `
                        <tr>
                            <td style="text-align: right; padding: 8px 0;">Ex Enpals Committente (${enpalsCommittentePercentage.toFixed(2)}%):</td>
                            <td style="text-align: right; padding: 8px 0;">€ ${this.formatCurrency(totals.enpalsCommittente)}</td>
                        </tr>
                        <tr style="background-color: #fffaf0;">
                            <td style="text-align: right; padding: 8px 0; font-size: 12px; color: #999; font-style: italic;">Ex Enpals Lavoratore (${enpalsLavoratorePercentage.toFixed(2)}%) - solo informativo:</td>
                            <td style="text-align: right; padding: 8px 0; font-size: 12px; color: #999; font-style: italic;">€ ${this.formatCurrency(totals.enpalsLavoratore)}</td>
                        </tr>`;
            }

            if (totals.applicaIva) {
                const ivaPercentage = mmPreventivi.ivaPercentage || 22;
                html += `
                        <tr>
                            <td style="text-align: right; padding: 8px 0;">IVA (${ivaPercentage}%):</td>
                            <td style="text-align: right; padding: 8px 0;">€ ${this.formatCurrency(totals.iva)}</td>
                        </tr>`;
            }

            html += `
                        <tr style="border-top: 3px solid #e91e63; background: #f8bbd0;">
                            <td style="text-align: right; padding: 15px 0; color: #e91e63;"><strong style="font-size: 18px;">TOTALE:</strong></td>
                            <td style="text-align: right; padding: 15px 0; color: #e91e63;"><strong style="font-size: 20px;">€ ${this.formatCurrency(totals.totale)}</strong></td>
                        </tr>
                    </table>
                </div>`;

            // Acconto
            const dataAcconto = $('#data_acconto').val();
            const importoAcconto = parseFloat($('#importo_acconto').val()) || 0;
            if (dataAcconto && importoAcconto > 0) {
                const restante = totals.totale - importoAcconto;
                html += `
                    <div class="mm-preview-acconto" style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; border-radius: 4px; margin-top: 20px;">
                        <p style="margin: 0 0 10px; color: #2e7d32;"><strong>Acconto del ${this.formatDate(dataAcconto)}:</strong> € ${this.formatCurrency(importoAcconto)}</p>
                        <p style="margin: 0;"><strong>Restante da saldare:</strong> € ${this.formatCurrency(restante)}</p>
                    </div>`;
            }

            // Footer
            html += `
                    <div class="mm-preview-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #666;">
                        <p style="margin: 0;"><strong>${companyName}</strong></p>
                        <p style="margin: 3px 0;">${companyAddress}</p>
                        <p style="margin: 3px 0;">${companyPiva} - ${companyCf}</p>
                        <p style="margin: 3px 0;">Tel. ${companyPhone} - Email: ${companyEmail}</p>
                    </div>
                </div>
            `;

            // Inject HTML and show modal
            $('#mm-preview-content').html(html);
            $('#mm-preview-modal').fadeIn(300);
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close preview modal
         */
        closePreview: function(e) {
            if (e.target === e.currentTarget || $(e.target).hasClass('mm-modal-close') || $(e.target).hasClass('mm-modal-close-btn')) {
                $('#mm-preview-modal').fadeOut(300);
                $('body').css('overflow', '');
            }
        },

        /**
         * Handle save from preview modal
         */
        handleSaveFromPreview: function() {
            // Close the modal first
            $('#mm-preview-modal').fadeOut(300);
            $('body').css('overflow', '');

            // Trigger form submission
            $('#mm-preventivo-form').trigger('submit');
        },

        /**
         * Format date for display
         */
        formatDate: function(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString + 'T00:00:00');
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#mm-preventivo-form').length) {
            MMPreventivi.init();
        }
    });

})(jQuery);
