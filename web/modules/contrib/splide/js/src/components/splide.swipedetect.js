/**
 * @file
 * Provides Splide swipe detection.
 *
 * https://www.javascriptkit.com/javatutors/touchevents3.shtml
 * @todo re-check if the Splide library has this data exposed.
 * This file is not directly used by Splide module, but is at Splidebox. Moved
 * here for re-use and other advanced usages, like zoom, etc.
 *
 * @see https://caniuse.com/?search=Pointer%20Events
 */

(function ($, _ds, _win, _doc) {

  'use strict';

  var NICK = 'swipedetect';
  var E_NICK = '.swidet';
  var POINTERDOWN = 'pointerdown';
  var POINTERMOVE = 'pointermove';
  var POINTERUP = 'pointerup';
  var E_CLICK = 'click';
  var E_DBLCLICK = 'dblclick';
  var E_WHEEL = 'wheel';
  var DIR_DOWN = 'down';
  var DIR_UP = 'up';
  var DIR_LEFT = 'left';
  var DIR_RIGHT = 'right';
  var DIR_NONE = 'none';
  var PANNING = false;
  var PAN_TIMER = null;
  var DOWN_DELAY = 300;
  var IS_PANNED = false;
  // var MOVE_RAF = null;
  var E_CACHE = [];
  var WINSIZE = $.windowSize() || {};
  var DATA = {
    prevDiff: -1,
    scale: 1,
    x: 0,
    y: 0
  };

  // Private functions.
  function toData(v) {
    var distance = (v.dir === DIR_LEFT || v.dir === DIR_RIGHT) ? v.x : v.y;
    v.distance = v.phase === 'start' ? 0 : distance;
    v.rect = $.rect(v.el);
    return v;
  }

  function abs(v) {
    return Math.abs(v);
  }

  function detect(el) {
    var me = this;
    var root = me.root;
    var options = me.options;
    var nick = options.nickClass || '';
    var isNick = nick ? 'is-' + nick : 'is';
    var dragClass = isNick + '-dragging';
    var targetClass = isNick + '-moved';
    var wheelClass = isNick + '-wheeled';
    var activatedClass = options.activatedClass || 'is-activated';
    var noob = function (evt, v) {};
    var v = {};
    var opClick = options.onClick || noob;
    var opTouch = options.callback || noob;
    var opZoomIn = options.onZoomIn || noob;
    var opZoomOut = options.onZoomOut || noob;
    var opPinched = options.onPinched || noob;
    var opWheel = options.onWheel || false;
    var maxScale = options.max || 2;
    var isVertical = options.vertical || false;
    var threshold = options.threshold || 100;
    var dir = DIR_NONE;
    var startX;
    var startY;
    var lastX;
    var lastY;

    DOWN_DELAY = options.downDelay || DOWN_DELAY;

    function isActivated() {
      return $.hasClass(root, activatedClass);
    }

    function onClick(e) {
      if ($.equal(e.target, 'img') && !IS_PANNED) {
        e.stopPropagation();

        PANNING = false;
        DATA.img = e.target;
        DATA.isDown = false;
        DATA.trigger = e.type;
        DATA.x = 0;
        DATA.y = 0;

        v = toData(DATA);
        opClick(e, v);
      }
    }

    function onWheel(e) {
      // e.preventDefault();
      $.addClass(root, wheelClass);
      var rec = $.rect(el);
      var x = (e.clientX - rec.x) / DATA.scale;
      var y = (e.clientY - rec.y) / DATA.scale;
      var delta = _ds.wheelDelta(e);
      var scale = (delta > 0) ? (DATA.scale + 0.2) : (DATA.scale - 0.2);

      // Restrict scale.
      DATA.scale = Math.min(Math.max(1, scale), maxScale);

      var m = (delta > 0) ? 0.1 : -0.1;
      var ww = WINSIZE.width / 2;
      var wh = WINSIZE.height / 2;

      DATA.x += (-x * m * 2) + (el.offsetWidth * m);
      DATA.y += (-y * m * 2) + (el.offsetHeight * m);

      // Restrict x, y.
      var rx = isVertical ? 0 : Math.min(Math.max(-ww, DATA.x), ww);
      var ry = Math.min(Math.max(-wh, DATA.y), wh);

      PANNING = false;
      DATA.el = el;
      DATA.isDown = false;
      DATA.isMin = DATA.isMax = false;
      DATA.trigger = e.type;
      DATA.event = e;

      // @todo disable transform when max or min reached.
      if (delta < 0) {
        DATA.dir = 'up';

        if (DATA.y === ry) {
          DATA.isMin = true;
          opZoomOut(e, DATA);
        }
      }
      else {
        DATA.dir = 'down';
        DATA.isMax = true;
      }

      DATA.x = rx;
      DATA.y = ry;

      v = toData(DATA);
      opWheel(e, v);
    }

    function toggleClick(elm, add) {
      $[add ? 'on' : 'off'](elm, E_CLICK + E_NICK + ' ' + E_DBLCLICK + E_NICK, onClick);
    }

    function toggleWheel(elm, add) {
      $[add ? 'on' : 'off'](elm, E_WHEEL + E_NICK, onWheel, {
        passive: true
      });
    }

    function toggleClass(elm, className, add, delayed) {
      if (elm && className) {
        if (delayed) {
          if (add) {
            $.addClass(elm, className);
          }
          else {
            setTimeout(function () {
              $.removeClass(elm, className);
            }, className === dragClass ? 5 : 10);
          }
        }
        else {
          $[add ? 'addClass' : 'removeClass'](elm, className);
        }
      }
    }

    function toggleDragClass(add, delayed) {
      toggleClass(root, dragClass, add, delayed);
    }

    function toggleItemClass(elm, add) {
      toggleClass(elm, targetClass, add);
    }

    function removeEvent(e) {
      for (var i = 0; i < E_CACHE.length; i++) {
        if (E_CACHE[i].pointerId === e.pointerId) {
          E_CACHE.splice(i, 1);
          break;
        }
      }
    }

    function reset(e) {
      $.off(el, POINTERMOVE, onMove);
      $.off(el, POINTERUP, onRelease);

      // if (MOVE_RAF) {
      // _win.cancelAnimationFrame(MOVE_RAF);
      // }
      removeEvent(e);
      toggleItemClass(el, false);
      toggleDragClass(false, true);
      el.style.transition = '';
    }

    function pointerIndex(idToFind) {
      for (var i = 0; i < E_CACHE.length; i++) {
        var id = E_CACHE[i].pointerId;

        if (id === idToFind) {
          return i;
        }
      }

      // not found.
      return -1;
    }

    function onMove(e) {
      if (!PANNING) {
        return false;
      }

      // Prevent scrolling when inside DIV.
      e.preventDefault();

      // Cancel if an animation frame was already requested.
      // if (MOVE_RAF) {
      // _win.cancelAnimationFrame(MOVE_RAF);
      // }
      // Find this event in the cache and update its record with this event.
      for (var i = 0; i < E_CACHE.length; i++) {
        if (e.pointerId === E_CACHE[i].pointerId) {
          E_CACHE[i] = e;
          break;
        }
      }

      var idx = pointerIndex(e.pointerId);
      var evt = E_CACHE[idx] || e;
      var x = evt.clientX;
      var y = evt.clientY;
      var diffX = x - startX;
      var diffY = y - startY;
      var ratioX = abs(diffX / diffY);
      var ratioY = abs(diffY / diffX);
      var absDiff = abs(ratioX > ratioY ? diffX : diffY);

      // Get horizontal dist traveled by finger while in contact with surface.
      var nx = isVertical ? 0 : lastX + diffX;

      // Get vertical dist traveled by finger while in contact with surface.
      var ny = lastY + diffY;
      var cancel = false;

      // MOVE_RAF = _win.requestAnimationFrame(function () {
      // If distance traveled horizontally is greater than vertically,
      // consider this a horizontal movement.
      if (absDiff < threshold) {
        if (ratioX > ratioY) {
          dir = diffX < 0 ? DIR_LEFT : DIR_RIGHT;
          cancel = isVertical;
        }
        // Else consider this a vertical movement.
        else {
          dir = diffY < 0 ? DIR_UP : DIR_DOWN;
        }
      }

      if (cancel) {
        toggleDragClass(false);
        return false;
      }

      DATA.x = nx;
      DATA.y = ny;
      DATA.dir = dir;
      DATA.event = evt;
      DATA.trigger = e.type;

      // If two pointers are down, check for pinch gestures.
      if (E_CACHE.length === 2) {
        // Calculate the distance between the two pointers.
        var curDiff = Math.sqrt(Math.pow(E_CACHE[1].clientX - E_CACHE[0].clientX, 2) + Math.pow(E_CACHE[1].clientY - E_CACHE[0].clientY, 2));

        DATA.curDiff = curDiff;

        if (DATA.prevDiff > 0) {
          // Pinch moving OUT -> Zoom in.
          // The distance between the two pointers has increased.
          if (curDiff > DATA.prevDiff) {
            DATA.zoom = 'in';
            $.trigger(root, NICK + ':zoomin', {
              touch: DATA
            });
            opZoomIn(e, DATA);
          }

          // Pinch moving IN -> Zoom out.
          // The distance between the two pointers has decreased.
          if (curDiff < DATA.prevDiff) {
            DATA.zoom = 'out';
            $.trigger(root, NICK + ':zoomout', {
              touch: DATA
            });
            opZoomOut(e, DATA);
          }
        }

        opPinched(e, DATA);

        // Cache the distance for the next move event.
        DATA.prevDiff = curDiff;
      }

      toggleDragClass(true);
      DATA.phase = 'move';
      v = toData(DATA);

      opTouch(e, v);

      // dir = v.dir;
      // MOVE_RAF = null;
      el.style.transition = 'none 0s ease 0s';
      // });
    }

    function _onRelease(e) {
      if (!PANNING) {
        return false;
      }

      DATA.event = e;
      DATA.phase = 'end';
      DATA.isDown = false;
      DATA.trigger = e.type;

      v = toData(DATA);

      opTouch(e, v);

      reset(e);
    }

    function onRelease(e) {
      _onRelease(e);

      // If the number of pointers down is less than 2 then reset diff tracker.
      if (E_CACHE.length < 2) {
        DATA.prevDiff = -1;
      }

      if (PAN_TIMER) {
        clearTimeout(PAN_TIMER);
      }
      if (IS_PANNED) {
        setTimeout(function () {
          IS_PANNED = false;
        }, 31);
      }

      DATA.isDown = false;
      PANNING = false;
    }

    function _onDown(e) {
      // Prevent default click action, but also makes text unselectable.
      if ($.equal(e.target, 'img')) {
        DATA.img = e.target;
        e.preventDefault();
      }

      PANNING = true;

      // The pointerdown event signals the start of a touch interaction.
      // This event is cached to support 2-finger gestures.
      E_CACHE.push(e);

      startX = e.clientX;
      startY = e.clientY;
      lastX = DATA.x;
      lastY = DATA.y;

      // Record time when finger first makes contact with surface.
      // startTime = new Date().getTime();
      var opts = {};
      DATA.el = el;
      DATA.dir = dir;
      DATA.phase = 'start';
      DATA.viewport = $.isUnd($.viewport) ? {} : $.viewport.init(opts);
      DATA.event = e;
      DATA.isDown = true;
      DATA.trigger = e.type;

      $.removeClass(root, wheelClass);

      v = toData(DATA);

      toggleDragClass(false);
      toggleItemClass(el, true);

      opTouch(e, v);

      // @todo compare raf vs. debounce.
      $.on(el, POINTERMOVE, $.debounce(onMove, 250));
      $.on(el, POINTERUP, onRelease);
    }

    function onDown(e) {
      if (isActivated()) {
        _onDown(e);

        PAN_TIMER = setTimeout(function () {
          PAN_TIMER = null;
          IS_PANNED = true;
        }, DOWN_DELAY);
      }
    }

    if (opWheel) {
      toggleWheel(el, true);
    }

    toggleClick(el, true);
    $.on(el, POINTERDOWN, onDown);
  }

  function init() {
    var me = this;
    var target = me.options.target || '.slide__content';
    var elms = me.options.elms || $.findAll(me.root, target);

    if (elms && elms.length) {
      $.each(elms, detect.bind(me));
    }
  }

  /**
   * SwipeDetect constructor.
   *
   * @namespace
   *
   * @param {HTMLElement} el
   *   The container element to detect swiping.
   * @param {Object} options
   *   An options object.
   *
   * @return {String}
   *   Returns SwipeDetect instance.
   */
  _win.SwipeDetect = function (el, options) {
    var me = this;

    if ($.isStr(el)) {
      el = $.find(_doc, el);
    }

    me.root = $.isElm(el) ? el : _doc;
    me.options = options;

    setTimeout(function () {
      init.call(me);
    });

    return me;
  };

  var _proto = SwipeDetect.prototype;
  _proto.constructor = SwipeDetect;

})(dBlazy, dSplide, this, this.document);
