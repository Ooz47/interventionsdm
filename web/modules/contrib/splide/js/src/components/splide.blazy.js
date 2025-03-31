/**
 * @file
 * Provides Splide extensions for Blazy.
 */

(function ($, Drupal, _ds) {

  'use strict';

  var Blazy = function (Splide, Components) {
    var root = Splide.root;
    var _blazy = Drupal.blazy || {};
    var _firstView = true;

    return {
      mount: function () {
        var me = this;

        Splide.on('mounted.spb', function (e) {
          me.preload(false);
        });

        Splide.on('move.spb', function (e) {
          me.preload(true);
        });

        Splide.on('moved.spb', function (e) {
          _firstView = false;
        });

      },

      /**
       * Blazy is not loaded on perPage > 1 with type `loop`/ infinite, reload.
       *
       * @param {bool} ahead
       *   Whether to lazyload ahead (at move/ beforeChange event), or not.
       */
      preload: function (ahead) {
        if (!_blazy.init || _firstView) {
          return;
        }

        window.setTimeout(function () {
          var blazy = '.b-lazy:not(.b-loaded)';
          var visible = '.is-visible ' + blazy;
          var el = $.find(root, blazy);
          if ($.isElm(el)) {
            var els = $.findAll(root, ahead ? blazy : visible);
            if (els.length) {
              _blazy.init.load(els);
            }
          }

          // Cleans up preloader if any named b-loader due to clones.
          var preloader = $.find(root, '.b-loaded ~ .b-loader');
          $.remove(preloader);
        }, 100);
      }
    };
  };

  _ds.extend({
    Blazy: Blazy
  });

})(dBlazy, Drupal, dSplide);
