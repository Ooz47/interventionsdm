/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds) {

  'use strict';

  var ID = 'splide-wrapper';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-sw-mounted';
  var S_ELEMENT = '.' + ID + ':not(.' + C_MOUNTED + ')';

  /**
   * Splide wrapper utility functions.
   *
   * @param {HTMLElement} elm
   *   The .splide-wrapper HTML element.
   */
  function process(elm) {
    // Respects nested.
    var main = $.findAll(elm, '.splide--main');
    var nav = $.findAll(elm, '.splide--nav');
    if (!main.length || !nav.length) {
      return;
    }

    var ok = false;
    var valid = main[0] && 'splide' in main[0];

    if (valid) {
      var splide = main[0].splide;
      var o = splide.options || {};
      var extensions = _ds.extensions || {};
      var fx = o.type ? _ds.getTransition(o.type) : null;

      if ($.isUnd(nav[0])) {
        splide.mount(extensions, fx);
      }
      else {
        if ('splide' in nav[0]) {
          ok = true;
          var navInstance = nav[0].splide;
          splide.sync(navInstance);
          splide.mount(extensions, fx);
          navInstance.mount(extensions, fx);
        }
      }
    }

    // Ensures sitewide option with improper synching doesn't screw up.
    if (ok) {
      $.addClass(elm, C_MOUNTED);
    }
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideNav = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide);
