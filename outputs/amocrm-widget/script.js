/**
 * SneakerHead Orders Widget — AmoCRM marketplace widget
 * Shows store order data on a lead card and allows manual sync.
 */
define(['jquery'], function ($) {
  'use strict';

  var SELF = {

    /* Called once when the widget is loaded */
    init: function () {
      return this;
    },

    /* Called when navigating to the lead card */
    render: function () {
      var self = this;
      var settings = self.system().settings;
      var shopUrl  = (settings.shop_url  || '').replace(/\/$/, '');
      var apiToken = settings.api_token  || '';
      var leadId   = self.system().lead_id;
      var lang     = self.i18n();

      var $wrap = $('#sneakerhead-widget-wrap');
      if (!$wrap.length) return self;

      $wrap.html('<div class="sh-loading">' + lang('loading') + '</div>');

      $.ajax({
        url:     shopUrl + '/api/amocrm/order?external_id=' + leadId,
        method:  'GET',
        headers: { Authorization: 'Bearer ' + apiToken },
        success: function (data) {
          if (!data.found) {
            $wrap.html('<p class="sh-empty">' + lang('order_not_found') + '</p>');
            return;
          }
          var statusLabels = {
            new: 'Новый', paid: 'Оплачен', processing: 'В обработке',
            shipped: 'Отправлен', delivered: 'Доставлен', cancelled: 'Отменён',
          };
          $wrap.html(
            '<table class="sh-table">' +
            '<tr><td>' + lang('order_id') + '</td><td><a href="' + shopUrl + '/admin/order/' + data.order_id + '" target="_blank" class="sh-link">#' + data.order_id + '</a></td></tr>' +
            '<tr><td>' + lang('status') + '</td><td><span class="sh-badge sh-badge-' + data.status + '">' + (statusLabels[data.status] || data.status) + '</span></td></tr>' +
            '<tr><td>' + lang('total') + '</td><td>' + parseFloat(data.total).toFixed(2) + ' BYN</td></tr>' +
            '<tr><td>' + lang('client') + '</td><td>' + (data.recipient_name || '—') + '</td></tr>' +
            '<tr><td>' + lang('phone') + '</td><td>' + (data.phone || '—') + '</td></tr>' +
            '</table>' +
            '<div class="sh-actions">' +
            '<button class="button-input" id="sh-sync-btn">' + lang('sync') + '</button>' +
            '</div>'
          );

          $('#sh-sync-btn').on('click', function () {
            var $btn = $(this);
            $btn.prop('disabled', true).text('...');
            $.ajax({
              url:         shopUrl + '/api/amocrm/sync',
              method:      'POST',
              contentType: 'application/json',
              headers:     { Authorization: 'Bearer ' + apiToken },
              data:        JSON.stringify({ lead_id: leadId }),
              success: function (r) {
                $btn.prop('disabled', false).text(r.success ? lang('sync_ok') : lang('sync_error'));
              },
              error: function () {
                $btn.prop('disabled', false).text(lang('sync_error'));
              },
            });
          });
        },
        error: function () {
          $wrap.html('<p class="sh-error">' + lang('error_load') + '</p>');
        },
      });

      return self;
    },

    /* Callback on lead save — sync automatically if configured */
    onSave: function () {
      return this;
    },

    /* Settings page is rendered by settings.js */
    settings: function () {
      return this;
    },

    callbacks: {
      render:         function () { return SELF.render.call(SELF); },
      init:           function () { return SELF.init.call(SELF); },
      bind_actions:   function () { return SELF; },
      settings:       function () { return SELF.settings.call(SELF); },
      onSave:         function () { return SELF.onSave.call(SELF); },
      destroy:        function () { return SELF; },
    },
  };

  return SELF;
});
