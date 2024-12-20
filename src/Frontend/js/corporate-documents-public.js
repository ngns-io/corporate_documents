(function ($) {
    'use strict';

    const CorporateDocuments = {
        init: function () {
            this.cacheDom();
            this.bindEvents();
        },

        cacheDom: function () {
            this.$filterForm = $('#cdox-filter-form');
            this.$documentList = $('#cdox-document-list');
            this.$documentLinks = $('.cdox-document-link');
            this.$loadingIndicator = $('.cdox-loading');
        },

        bindEvents: function () {
            this.$filterForm.on('submit', this.handleFilter.bind(this));
            this.$documentLinks.on('click', this.handleDownload.bind(this));
        },

        handleFilter: function (e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const formData = new FormData($form[0]);

            formData.append('action', 'cdox_filter');
            formData.append('nonce', cdoxAjax.nonce);

            this.showLoading();

            $.ajax({
                url: cdoxAjax.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: this.handleFilterSuccess.bind(this),
                error: this.handleError.bind(this)
            });
        },

        handleDownload: function (e) {
            const $link = $(e.currentTarget);
            const documentId = $link.data('document-id');

            // Track download
            $.ajax({
                url: cdoxAjax.url,
                type: 'POST',
                data: {
                    action: 'cdox_track_download',
                    nonce: cdoxAjax.nonce,
                    document_id: documentId
                }
            });
        },

        handleFilterSuccess: function (response) {
            this.hideLoading();

            if (response.success && response.data) {
                this.$documentList.html(response.data.content);
                this.refreshDocumentLinks();
            } else {
                this.showError(cdoxAjax.i18n.error);
            }
        },

        handleError: function () {
            this.hideLoading();
            this.showError(cdoxAjax.i18n.error);
        },

        refreshDocumentLinks: function () {
            this.$documentLinks = $('.cdox-document-link');
            this.$documentLinks.on('click', this.handleDownload.bind(this));
        },

        showLoading: function () {
            this.$loadingIndicator.show();
            this.$documentList.addClass('loading');
        },

        hideLoading: function () {
            this.$loadingIndicator.hide();
            this.$documentList.removeClass('loading');
        },

        showError: function (message) {
            const $error = $('<div class="cdox-error"></div>').text(message);
            this.$documentList.html($error);
        }
    };

    $(document).ready(function () {
        CorporateDocuments.init();
    });

})(jQuery);