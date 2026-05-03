/**
 * SneakerHead widget — settings page script.
 * Validates api_url and api_key before save.
 */
define(['jquery'], function ($) {
  'use strict';

  return {
    callbacks: {
      settings: function () {
        return this;
      },
      onSave: function () {
        var apiUrl = $.trim($('input[name="api_url"]').val() || $('input[name="shop_url"]').val());
        var apiKey = $.trim($('input[name="api_key"]').val() || $('input[name="api_token"]').val());

        if (!apiUrl || !apiUrl.match(/^https?:\/\//)) {
          alert('Укажите корректный URL магазина (с http:// или https://)');
          return false;
        }
        if (!apiKey) {
          alert('Укажите API ключ из настроек плагина AmoCRM');
          return false;
        }
        return true;
      },
    },
  };
});
