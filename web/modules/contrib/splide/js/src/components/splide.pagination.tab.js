/**
 * @file
 * Provides labeled/ tab-like dot pagination.
 */

(function ($, drupalSettings, _ds) {

  'use strict';

  var TabPagination = function (Splide, Components) {
    var paginationTexts = drupalSettings.splide.paginationTexts || [];

    return {
      mount: function () {
        var me = this;

        if (paginationTexts.length) {
          Splide.on('pagination:mounted.txtp', me.tabpaginate.bind(me));
        }
      },

      tabpaginate: function (data) {
        $.each(data.items, function (item) {
          var text = paginationTexts[item.page];
          if (text && item.button) {
            item.button.textContent = text;
          }
        });
      }

    };
  };

  _ds.listen({
    TabPagination: TabPagination
  });

})(dBlazy, drupalSettings, dSplide);
