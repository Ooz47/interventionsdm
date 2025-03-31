/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds, _win, _doc) {

  'use strict';

  var ID = 'splide';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-mounted';
  var P_DATA = 'data-';
  var S_ELEMENT = '.' + ID + '--default:not(.' + C_MOUNTED + ', .' + ID + '--vanilla)';
  var CONFIG = drupalSettings.splide || {};

  /**
   * Splide public methods.
   *
   * @namespace
   */
  _ds = $.extend(_ds || {}, {

    /**
     * Initializes the Splide.
     *
     * Saves instance in DOM to sync navigations without re-instantiation.
     *
     * @param {HTMLElement} elm
     *   The .splide HTML element.
     *
     * @return {Splide}
     *   The Splide instance.
     */
    init: function (elm) {
      if (!elm.splide) {
        elm.splide = doSplide(elm);
      }
      return elm.splide;
    }

  });

  /**
   * Splide private functions.
   *
   * @param {HTMLElement} elm
   *   The .splide HTML element.
   *
   * @return {Splide}
   *   The Splide instance.
   */
  function doSplide(elm) {
    var d = $.attr(elm, P_DATA + ID);
    var s = $.parse(d);
    var o = $.extend({}, CONFIG.defaults || {}, s);
    var r = o.breakpoints;
    var x = CONFIG.extras || {};
    var p = elm.parentNode;
    var unSplide = $.hasClass(elm, 'unsplide');
    var instance;
    var resets;
    var b;

    // @todo remove if these are taken care of by the library.
    // Basically a soft destroy if one slide: disable arrows, pagination, etc.
    if (o.count && o.count === 1) {
      resets = CONFIG.resets || {};
      x = $.extend(x, resets);
      o = $.extend(o, resets);
    }

    // Still required by v4, else destroy bubbles up to the closest breakpoint.
    // This basically feeds each breakpoints with default values, a legacy
    // Slick approach which do not feed breakpoints with their defaults.
    if (r) {
      for (b in r) {
        if ($.hasProp(r, b)) {
          var breakpoint = r[b];
          if (!breakpoint.destroy) {
            r[b] = $.extend({}, x, breakpoint);
          }
        }
      }
    }

    /**
     * The event must be bound prior to splide being mounted/ initialized.
     */
    function beforeInit() {
      _ds.initExtensions();
      _ds.initListeners(instance);
    }

    /**
     * The event must be bound after splide being mounted/ initialized.
     */
    function afterInit() {
      // Arrow down jumper.
      $.on(elm, 'click', '.' + ID + '__arrow--down', function (e) {
        e.preventDefault();
        var tg = $.attr(e.target, P_DATA + 'target');
        var el = tg ? $.find(_doc, tg) : null;
        var offset = $.toInt($.attr(e.target, P_DATA + 'offset'), 80);

        if ($.isElm(el)) {
          // The el.offsetTop failed with fixed header, hence getBoundingRect().
          var rect = $.rect(el);
          _win.scroll({
            top: rect.top - offset,
            behavior: 'smooth'
          });
        }
      });
    }

    /**
     * Randomize slide start, for ads/products rotation within cached blocks.
     */
    function randomize() {
      if (o.randomize) {
        var list = $.find(elm, '.' + ID + '__list');
        if ($.isElm(list) && list.children) {
          var len = list.children.length;

          if (len) {
            var rand = Math.floor(Math.random() * len);
            if (rand >= 0 && rand < len) {
              o.start = rand;
            }
          }
        }
      }
    }

    // Build the Splide.
    randomize();
    instance = new Splide(elm, o);

    beforeInit();

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!$.hasClass(p, ID + '-wrapper')) {
      var fx = o.animation || o.type ? _ds.getTransition(o.animation || o.type) : null;
      instance.mount(_ds.extensions || {}, fx);
    }

    afterInit();

    // Destroy Splide if it is an enforced unsplide.
    // This allows Splide lazyload to run, but prevents further complication.
    // Should use lazyLoaded event, but images are not always there.
    if (unSplide) {
      instance.destroy(true);
    }

    // Add helper class for arrow visibility as they are outside slider.
    $.addClass(elm, C_MOUNTED);
    return instance;
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splide = {
    attach: function (context) {
      $.once(_ds.init, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide, this, this.document);
