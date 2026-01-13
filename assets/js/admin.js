/**
 * Massimo Manca Preventivi - Admin JavaScript
 * Handles admin panel interactions and AJAX operations
 */

(function($) {
    'use strict';

    const MMPreventiviAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Delete preventivo
            $(document).on('click', '.mm-btn-delete', this.handleDelete.bind(this));

            // Update status
            $(document).on('change', '.mm-status-select', this.handleStatusUpdate.bind(this));

            // Export PDF
            $(document).on('click', '.mm-btn-pdf', this.handlePDFExport.bind(this));

            // Filters
            $('.mm-filter-btn').on('click', this.handleFilter.bind(this));
            $('.mm-reset-filter').on('click', this.resetFilters.bind(this));

            // Logo upload
            $('.mm-upload-logo-btn').on('click', this.handleLogoUpload.bind(this));

            // Run migrations
            $('#mm-run-migrations').on('click', this.handleRunMigrations.bind(this));

            // Service management
            $('#mm-add-service-btn').on('click', this.openServiceModal.bind(this));
            $(document).on('click', '.mm-edit-service', this.editService.bind(this));
            $(document).on('click', '.mm-delete-service', this.deleteService.bind(this));
            $(document).on('click', '.mm-modal-close', this.closeModal.bind(this));
            $('#mm-service-form').on('submit', this.saveService.bind(this));

            // Quick status change
            $(document).on('click', '.mm-btn-quick-status', this.handleQuickStatus.bind(this));
        },
        
        /**
         * Handle delete
         */
        handleDelete: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const preventivoId = $button.data('id');
            const preventivoNumero = $button.data('numero');
            
            if (!confirm(`Sei sicuro di voler eliminare il preventivo ${preventivoNumero}?`)) {
                return;
            }
            
            // Show loading
            $button.prop('disabled', true).html('⏳');
            
            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_delete_preventivo',
                    nonce: mmPreventiviAdmin.nonce,
                    id: preventivoId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        
                        // Remove row with animation
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            if ($('.mm-table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).html('🗑️');
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $button.prop('disabled', false).html('🗑️');
                }
            });
        },
        
        /**
         * Handle status update
         */
        handleStatusUpdate: function(e) {
            const $select = $(e.currentTarget);
            const preventivoId = $select.data('id');
            const newStatus = $select.val();
            const oldStatus = $select.data('old-status');
            
            if (!confirm('Confermi il cambio di stato?')) {
                $select.val(oldStatus);
                return;
            }
            
            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_update_stato',
                    nonce: mmPreventiviAdmin.nonce,
                    id: preventivoId,
                    stato: newStatus
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        $select.data('old-status', newStatus);
                        
                        // Update badge in row
                        const $badge = $select.closest('tr').find('.mm-status-badge');
                        $badge.removeClass().addClass('mm-status-badge ' + newStatus).text(newStatus);
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $select.val(oldStatus);
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $select.val(oldStatus);
                }
            });
        },
        
        /**
         * Handle PDF export
         */
        handlePDFExport: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const preventivoId = $button.data('id');
            
            // Show loading
            const originalHtml = $button.html();
            $button.html('⏳').prop('disabled', true);
            
            // Open PDF in new window
            const pdfUrl = mmPreventiviAdmin.ajaxurl + '?action=mm_export_pdf&id=' + preventivoId;
            window.open(pdfUrl, '_blank');
            
            // Reset button after delay
            setTimeout(() => {
                $button.html(originalHtml).prop('disabled', false);
            }, 1000);
        },
        
        /**
         * Handle filter
         */
        handleFilter: function(e) {
            e.preventDefault();
            
            const stato = $('#filter-stato').val();
            const search = $('#filter-search').val();
            const dataDa = $('#filter-data-da').val();
            const dataA = $('#filter-data-a').val();
            
            // Build URL with parameters
            let url = window.location.pathname + '?page=mm-preventivi';
            
            if (stato) {
                url += '&stato=' + encodeURIComponent(stato);
            }
            
            if (search) {
                url += '&search=' + encodeURIComponent(search);
            }
            
            if (dataDa) {
                url += '&data_da=' + encodeURIComponent(dataDa);
            }
            
            if (dataA) {
                url += '&data_a=' + encodeURIComponent(dataA);
            }
            
            window.location.href = url;
        },
        
        /**
         * Reset filters
         */
        resetFilters: function() {
            window.location.href = window.location.pathname + '?page=mm-preventivi';
        },
        
        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const noticeHtml = `
                <div class="mm-notice mm-notice-${type}">
                    ${message}
                </div>
            `;
            
            $('.wrap').prepend(noticeHtml);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $('.mm-notice').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Confirm action
         */
        confirmAction: function(message) {
            return confirm(message);
        },

        /**
         * Handle logo upload using WordPress Media Library
         */
        handleLogoUpload: function(e) {
            e.preventDefault();

            // Se il media frame esiste già, aprilo
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }

            // Crea il media frame
            this.mediaFrame = wp.media({
                title: 'Seleziona o Carica il Logo',
                button: {
                    text: 'Usa questo Logo'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });

            // Quando un'immagine viene selezionata
            this.mediaFrame.on('select', () => {
                const attachment = this.mediaFrame.state().get('selection').first().toJSON();

                // Inserisci l'URL nel campo
                $('#company_logo').val(attachment.url);

                // Mostra preview
                const previewHtml = `
                    <div style="margin-top: 10px;">
                        <img src="${attachment.url}"
                             alt="Logo Preview"
                             style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: white;">
                    </div>
                `;

                // Rimuovi preview esistente e aggiungi la nuova
                $('#company_logo').siblings('div').remove();
                $('#company_logo').parent().after(previewHtml);

                this.showNotice('Logo selezionato! Ricorda di salvare le impostazioni.', 'success');
            });

            // Apri il media frame
            this.mediaFrame.open();
        },

        /**
         * Handle run migrations
         */
        handleRunMigrations: function(e) {
            e.preventDefault();

            const $button = $('#mm-run-migrations');
            const $result = $('#mm-migration-result');

            if (!confirm('Sei sicuro di voler eseguire le migrazioni del database? Questa operazione aggiungerà eventuali colonne mancanti alle tabelle.')) {
                return;
            }

            // Show loading
            $button.prop('disabled', true).html('⏳ Esecuzione in corso...');
            $result.html('');

            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_run_migrations',
                    nonce: mmPreventiviAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $result.html(`<div class="mm-notice mm-notice-success">${response.data.message}</div>`);
                    } else {
                        $result.html(`<div class="mm-notice mm-notice-error">${response.data.message}</div>`);
                    }
                    $button.prop('disabled', false).html('🔧 Esegui Migrazioni Database');
                },
                error: () => {
                    $result.html('<div class="mm-notice mm-notice-error">Errore di connessione</div>');
                    $button.prop('disabled', false).html('🔧 Esegui Migrazioni Database');
                }
            });
        },

        /**
         * Open service modal for adding new service
         */
        openServiceModal: function(e) {
            e.preventDefault();

            // Reset form - controlla prima che esista
            const $form = $('#mm-service-form');
            if ($form.length && $form[0]) {
                $form[0].reset();
            }

            $('#service-id').val('');
            $('#mm-service-modal-title').text('Aggiungi Servizio');
            $('#service-attivo').prop('checked', true);

            // Show modal
            $('#mm-service-modal').fadeIn(200);
        },

        /**
         * Edit service
         */
        editService: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const serviceId = $button.data('id');

            // Show loading
            $button.prop('disabled', true);

            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_get_service',
                    nonce: mmPreventiviAdmin.nonce,
                    id: serviceId
                },
                success: (response) => {
                    if (response.success) {
                        const servizio = response.data.servizio;

                        // Populate form
                        $('#service-id').val(servizio.id);
                        $('#service-nome').val(servizio.nome_servizio);
                        $('#service-descrizione').val(servizio.descrizione);
                        $('#service-categoria').val(servizio.categoria);
                        $('#service-prezzo').val(servizio.prezzo_default);
                        $('#service-ordinamento').val(servizio.ordinamento);
                        $('#service-attivo').prop('checked', servizio.attivo == 1);

                        // Update title and show modal
                        $('#mm-service-modal-title').text('Modifica Servizio');
                        $('#mm-service-modal').fadeIn(200);
                    } else {
                        this.showNotice(response.data.message, 'error');
                    }
                    $button.prop('disabled', false);
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Delete service
         */
        deleteService: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const serviceId = $button.data('id');

            if (!confirm('Sei sicuro di voler eliminare questo servizio?')) {
                return;
            }

            $button.prop('disabled', true).html('⏳');

            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_delete_service',
                    nonce: mmPreventiviAdmin.nonce,
                    id: serviceId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');

                        // Remove row
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();

                            // Check if table is empty
                            if ($('#mm-services-list .mm-table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).html('🗑️');
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $button.prop('disabled', false).html('🗑️');
                }
            });
        },

        /**
         * Save service
         */
        saveService: function(e) {
            e.preventDefault();

            const $form = $('#mm-service-form');
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('💾 Salvataggio...');

            // Prepare form data
            const formData = {
                action: 'mm_save_service',
                nonce: mmPreventiviAdmin.nonce,
                service_id: $('#service-id').val(),
                nome_servizio: $('#service-nome').val(),
                descrizione: $('#service-descrizione').val(),
                categoria: $('#service-categoria').val(),
                prezzo_default: $('#service-prezzo').val(),
                ordinamento: $('#service-ordinamento').val()
            };

            if ($('#service-attivo').is(':checked')) {
                formData.attivo = 1;
            }

            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        this.closeModal();

                        // Reload page to show updated list
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.mm-modal').fadeOut(200);
        },

        /**
         * Handle quick status change (Accettato/Rifiutato)
         */
        handleQuickStatus: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const preventivoId = $button.data('id');
            const newStatus = $button.data('status');
            const statusLabel = newStatus === 'accettato' ? 'Accettato' : 'Rifiutato';

            if (!confirm(`Sei sicuro di voler segnare questo preventivo come ${statusLabel}?`)) {
                return;
            }

            const originalHtml = $button.html();
            $button.prop('disabled', true).html('⏳');

            $.ajax({
                url: mmPreventiviAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mm_update_stato',
                    nonce: mmPreventiviAdmin.nonce,
                    id: preventivoId,
                    stato: newStatus
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');

                        // Update status badge in the same row
                        const $row = $button.closest('tr');
                        const $statusBadge = $row.find('.mm-status-badge');
                        $statusBadge
                            .removeClass('bozza attivo accettato rifiutato completato')
                            .addClass(newStatus)
                            .text(statusLabel);

                        // Remove quick action buttons after status change
                        $button.siblings('.mm-btn-quick-status').addBack().fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        this.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).html(originalHtml);
                    }
                },
                error: () => {
                    this.showNotice('Errore di connessione', 'error');
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.mm-admin-page').length) {
            MMPreventiviAdmin.init();
        }
    });

})(jQuery);
