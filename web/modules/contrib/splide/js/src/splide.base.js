/**
 * @file
 * Provides Splide utilities.
 */

(function (factory) {

  'use strict';

  // Browser globals (root is window).
  factory(window.dBlazy, window, window.document);

})(function ($, _win, _doc) {

  'use strict';

  /**
   * Shared objects for drupalSplide.
   *
   * @namespace
   */
  _win.dSplide = {};

  var DS = _win.dSplide;
  var ID = 'splide';
  var NICK = ID + 'base';
  var S_BASE = '.' + ID;
  var AUTOPLAY_OFF = 'is-autoplay--off';
  var IS_ARROWED = 'is-arrowed';
  var IS_DRAGGING = 'is-dragging';
  var IS_LESS = 'is-less';
  var C_TOUCH = 'touchevents';
  var LAST_TAP;

  // @todo remove post blazy:2.29 || 3.0.8.
  if ($.isUnd($.isTouch)) {
    $.isTouch = function isTouch(cb) {
      var query = {};

      // @todo remove check when min D.10.
      if ('matchMedia' in _win) {
        query = _win.matchMedia('(hover: none), (pointer: coarse)');
        if (cb) {
          query.addEventListener('change', cb);
        }
      }

      return (
        ('ontouchstart' in _win) ||
        (_win.DocumentTouch && _doc instanceof _win.DocumentTouch) ||
        query.matches ||
        (navigator.maxTouchPoints > 0) ||
        (navigator.msMaxTouchPoints > 0)
      );
    };

    $.touchOrNot = function touchOrNot() {
      var html = _doc.documentElement;
      var matches = $.isTouch($.touchOrNot);

      $.removeClass(html, [C_TOUCH, 'no-' + C_TOUCH]);
      $.addClass(html, matches ? C_TOUCH : 'no-' + C_TOUCH);
    };
  }

  // Add touchevents classes.
  $.touchOrNot();

  DS.extensions = {};
  DS.listeners = {};
  DS.transitions = [];
  DS.options = {};

  // Init non-module library built-in, yet separated, extensions.
  DS.initExtensions = function () {
    var me = this;
    if (_win.splide && _win.splide.Extensions) {
      if (_win.splide.Extensions.AutoScroll) {
        me.extend({
          AutoScroll: _win.splide.Extensions.AutoScroll
        });
      }
      if (_win.splide.Extensions.Intersection) {
        me.extend({
          Intersection: _win.splide.Extensions.Intersection
        });
      }
    }
  };

  // Init module/ custom listener extensions, must be called before init event.
  DS.initListeners = function (instance) {
    var me = this;
    var root = instance.root;
    var userOptions = $.parse(root.dataset.splide);

    // Need to get a live copy of instance.options as it's modified elsewhere as this runs, and we also want a clone instead of modifying the original.
    var getMergedOptions = function () {
      return $.extend({}, instance.options, userOptions);
    };

    var append = function (prev, sel) {
      var el = $.find(root, sel);
      if ($.isElm(el)) {
        prev.insertAdjacentElement('afterend', el);
      }
    };

    instance.on('drag.' + NICK + ' dragging.' + NICK, function () {
      $.addClass(root, IS_DRAGGING);
    });

    instance.on('dragged.' + NICK, function () {
      // Prevents ending drags from triggering click events, if any registered,
      // by conditioning this IS_DRAGGING class.
      setTimeout(function () {
        $.removeClass(root, IS_DRAGGING);
      }, 101);
    });

    instance.on('mounted.' + NICK + ' resized.' + NICK, function () {
      var opts = getMergedOptions();
      var count = opts.count;
      $[count <= opts.perPage ? 'addClass' : 'removeClass'](root, IS_LESS);
      $[count > 1 && opts.arrows ? 'addClass' : 'removeClass'](root, IS_ARROWED);
    });

    instance.on('autoplay:play.' + NICK, function () {
      $.removeClass(root, AUTOPLAY_OFF);
    });

    instance.on('autoplay:pause.' + NICK, function () {
      $.addClass(root, AUTOPLAY_OFF);
    });

    // Adjust Autoplay behaviors.
    if ($.hasClass(root, 'is-autoplay')) {
      $.on(root, 'click.' + NICK, '.splide__arrow, .splide__pagination__page', function () {
        var auto = instance.Components.Autoplay;

        $.removeClass(root, AUTOPLAY_OFF);

        // @todo remove when the library takes care of this click interaction.
        if (auto) {
          auto.pause();
          auto.play();
        }
      });
    }
    else {
      // Do not interfere autoplay, already managed by library.
      var bar = $.find(root, S_BASE + '__progress__bar');
      if (bar) {
        // Updates the bar width whenever the carousel moves:
        instance.on('mounted.' + NICK + ' move.' + NICK, function () {
          var end = instance.Components.Controller.getEnd() + 1;
          var rate = Math.min((instance.index + 1) / end, 1);

          bar.style.width = String(100 * rate) + '%';
        });
      }
    }

    // Adds arrows down and or pagination inside arrows.
    instance.on('arrows:mounted.' + NICK, function (prev, next) {
      var opts = getMergedOptions();

      if (prev === null) {
        return;
      }

      // Pagination was generated after arrows.
      _win.setTimeout(function () {
        // Puts dots inbetween arrows for easy theming like this: < ooooo >.
        // The library doesn't support other than `slider`, the module does.
        // V4 does not support string.
        // See https://splidejs.com/v3/guides/options/#pagination.
        // See https://splidejs.com/guides/options/#pagination
        if (opts.pagination === S_BASE + '__arrows') {
          append(prev, S_BASE + '__pagination');
        }

        // Puts arrow down inbetween arrows for easy theming like this: < v >.
        if (opts.down) {
          append(prev, S_BASE + '__arrow--down');
        }
      }, 100);
    });

    /*
    @todo remove irrelevant since some v4, no longer moved around by JS
    since the markups were hard-coded in TWIG, unless being disabled.
    Default pagination placements.

    v4 will have is-paginated--inner, inside .splide__slider:
    .splide > .splide__slider
       > .splide__track > UL.splide__list
       > UL.splide__pagination

    v3 will have is-paginated--outer, outside .splide__slider, till changed by
    `slider` value, v3 only:
    .splide
      > .splide__slider > .splide__track > UL.splide__list
      > UL.splide__pagination
      */
    instance.on('pagination:mounted.' + NICK, function (data) {
      var prev = $.prev(data.list);
      var siblings = $.hasClass(prev, 'splide__slider');

      $.addClass(root, siblings ? 'is-paginated--outer' : 'is-paginated--inner');
    });

    instance.on('lazyload:loaded.' + NICK, $.unloading);

    var listeners = me.listeners;
    if (listeners) {
      $.each(listeners, function (listener) {
        if (listener && typeof listener === 'function') {
          var opts = getMergedOptions();
          var fn = listener(instance, instance.Components, opts);
          if ('mount' in fn) {
            fn.mount();
          }
        }
      });
    }
  };

  // Register module/ custom extensions not bound to before init event.
  DS.extend = function (fn) {
    this.extensions = $.extend({}, this.extensions, fn);
  };

  // Register module/ custom listener plugins, must be called before init event.
  DS.listen = function (fn) {
    this.listeners = $.extend({}, this.listeners, fn);
  };

  // Register module/ custom transitions aside from defaults: loop, slide, fade.
  DS.addTransition = function (fn) {
    this.transitions.push(fn);
  };

  DS.getTransition = function (type) {
    var me = this;
    var fn = null;
    if (me.transitions.length) {
      $.each(me.transitions, function (obj) {
        if (obj.fn) {
          if (obj.type && obj.type === type) {
            fn = obj.fn;
            return false;
          }
        }
      });
    }
    return fn;
  };

  DS.fsIconOn = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M5.12 5.12v8.16h2.72V7.84h5.44V5.12zm13.6 0v2.72h5.44v5.44h2.72V5.12Zm-13.6 13.6v8.16h8.16v-2.72H7.84v-5.44zm19.04 0v5.44h-5.44v2.72h8.16v-8.16z" style="stroke-width:.785414"/></svg>';

  DS.applyStyle = function (elm, styles) {
    if (elm) {
      $.each(styles, function (value, prop) {
        if (!$.isNull(value)) {
          elm.style[prop] = value;
        }
      });
    }
  };

  DS.doubletap = function () {
    var now = new Date().getTime();
    var timesince = now - LAST_TAP;

    if ((timesince < 600) && (timesince > 0)) {
      return true;
    }

    LAST_TAP = new Date().getTime();
    return false;
  };

  // https://stackoverflow.com/questions/5527601/normalizing-mousewheel-speed-across-browsers
  DS.wheelDelta = function (e) {
    // FIREFOX WIN / MAC | IE.
    var delta = e.deltaY;
    if (!delta) {
      if (e.wheelDelta) {
        // CHROME WIN/MAC | SAFARI 7 MAC | OPERA WIN/MAC | EDGE.
        delta = e.wheelDelta / 120;
      }
      else if (e.detail) {
        // W3C.
        delta = -e.detail / 2;
      }
    }

    return delta > 0 ? 1 : -1;
  };

  // @todo replace with $.resize post blazy:2.28.
  DS.checkSizes = function (img, parent) {
    var _sizes = {};

    if (!img) {
      return _sizes;
    }

    parent = parent || img.parentNode;

    var recheck = function (e) {
      var aw = $.toInt($.attr(img, 'width'), 0);
      var ah = $.toInt($.attr(img, 'height'), 0);

      _sizes = {
        w: img.offsetWidth,
        h: img.offsetHeight,
        nw: img.naturalWidth || aw,
        nh: img.naturalHeight || ah,
        aw: aw,
        ah: ah,
        pw: parent.offsetWidth,
        ph: parent.offsetHeight
      };

      if (e) {
        $.off(img, 'load.' + NICK, recheck);
      }
    };

    if ($.isDecoded(img) || $.attr(img, 'data-src')) {
      recheck();
    }
    else {
      $.on(img, 'load.' + NICK, recheck);
    }

    return _sizes;
  };

  return DS;

});
