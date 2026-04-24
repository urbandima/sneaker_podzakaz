/**
 * SneakerHead widget — settings page script.
 * Validates shop_url and api_token before save.
 */
define(['jquery'], function ($) {
  'use strict';

  return {
    callbacks: {
      settings: function () {
        // Nothing special — standard AmoCRM settings form handles the fields
        return this;
      },
      onSave: function () {
        var shopUrl  = $.trim($('input[name="shop_url"]').val());
        var apiToken = $.trim($('input[name="api_token"]').val());

        if (!shopUrl || !shopUrl.match(/^https?:\/\//)) {
          alert('Укажите корректный URL магазина (с https://)');
          return false;
        }
        if (!apiToken) {
          alert('Укажите API токен из настроек плагина AmoCRM');
          return false;
        }
        return true;
      },
    },
  };
});
