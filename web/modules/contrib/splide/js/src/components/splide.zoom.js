/**
 * @file
 * Provides Splide extensions for onwheel event.
 */

(function ($, _ds, _win, _doc, _html) {

  'use strict';

  var ID = 'spz';

  var xZoom = function (Splide, Components) {
    var SPLIDE_ROOT = Splide.root;
    var OPT = Splide.options;
    var OZ = OPT.zoom || {};
    var V_MAX = OZ.max || 1.5;
    var V_MIN = OZ.min || 1;
    var IS_ENABLED = OZ.on || false;
    var IS_SCALABLE = OZ.scale || false;
    var IS_CLICKABLE = OZ.click || false;
    var S_ZOOM_ROOT = OZ.root || false;
    var C_NICK = OZ.nickClass || 'sbox';
    var IS_NICK = 'is-' + C_NICK;
    var IS_SLIDE = 'is-slide';
    var C_ZOOM_ROOT = IS_NICK + '-zoomed';
    var C_PINCH = IS_NICK + '-zoom';
    // var C_DRAG = IS_NICK + '-dragging';
    var S_ZOOM_PARENT = OZ.target || '.slide__media';
    var C_TOUCH = 'touchevents';
    var C_NO_TOUCH = 'no-' + C_TOUCH;
    var IS_PINCHED = false;
    var C_IS_ZOOMABLE = 'is-zoomable';
    var S_IS_ZOOMABLE = '.' + C_IS_ZOOMABLE;
    var V_POS = {
      x: 0,
      y: 0
    };
    var EL_ZOOMED = null;
    var V_TOGGLING;
    var V_LAST_SCALE = V_MAX;

    function drag(v) {
      Splide.options = {
        drag: v
      };
    }

    // https://stackoverflow.com/questions/3971841
    // @todo remove for $.image.scale() post blazy:2.26.
    function aspectRatio(srcWidth, srcHeight, maxWidth, maxHeight) {
      var ratio = Math.min(maxWidth / srcWidth, maxHeight / srcHeight);

      return {
        width: srcWidth * ratio,
        height: srcHeight * ratio,
        ratio: ratio
      };
    }

    function removableClass(cn, v) {
      $.addClass(cn, IS_NICK + '-' + v);

      _win.clearTimeout(V_TOGGLING);

      V_TOGGLING = setTimeout(function () {
        V_TOGGLING = null;

        $.removeClass(cn, IS_NICK + '-' + v);
      }, 250);
    }

    function toTransform(x, y, scale) {
      var str = 'translate3d(' + x + 'px, ' + (y || 0) + 'px, 0)';

      if (!$.isUnd(scale)) {
        str += ' scale3d(' + scale + ', ' + scale + ', 1)';
      }

      return str;
    }

    function toParent(el) {
      var tg = el;

      if ($.is(el, 'img')) {
        tg = $.closest(el, S_ZOOM_PARENT) || el.parentNode;
      }
      return tg;
    }

    function inject(el, x, y, scale, transform) {
      var tg = toParent(el);
      tg.style.transform = toTransform(x, y, scale);

      // $.on(tg, 'transitionend.' + ID, function () {
      // tg.style.transition = '';
      // });
      // setTimeout(function () {
      // tg.style.transition = '';
      // }, 700);
    }

    function updateDim(el, data) {
      if (!el || !data) {
        return;
      }

      var fit = data.fit;
      var cw = fit ? data.aw : data.cw;
      var ch = fit ? data.ah : data.ch;

      // el.style.width = cw + 'px';
      // el.style.height = (fit ? eh : ch) + 'px';

      var picture = $.closest(el, 'picture');
      if ($.isElm(picture)) {
        picture.style.width = cw + 'px';
        picture.style.height = ch + 'px';
      }

      var tg = toParent(el);
      var slide = $.closest(tg, '.slide');
      $.addClass(slide, C_IS_ZOOMABLE + '-slide');
    }

    return {
      items: [],
      data: {},
      canZoom: true,

      /*
      isOffset: function (img, x, y) {
        var me = this;

        var id = img.dataset.sId;
        var set = me.data[id];

        if (!set) {
          return false;
        }

        var win = set.window;

        var nw = set.nw / 3;
        var wh = win.height / 3;
        var sy = Math.ceil((set.height - win.height) / 3);

        return Math.abs(y) >= sy && Math.abs(y) > wh || Math.abs(x) > nw;
      },
      */

      isZoomed: function () {
        return IS_ENABLED && $.hasClass(this.root(), C_ZOOM_ROOT);
      },

      toggleClass: function (zooming, cls, delayed) {
        var el = this.root();
        if (el) {
          if (delayed) {
            if (zooming) {
              $.addClass(el, cls || C_ZOOM_ROOT);
            }
            else {
              setTimeout(function () {
                $.removeClass(el, cls || C_ZOOM_ROOT);
              }, delayed);
            }
          }
          else {
            $[zooming ? 'addClass' : 'removeClass'](el, cls || C_ZOOM_ROOT);
          }
        }
      },

      on: function () {
        this.canZoom = true;
      },

      off: function () {
        this.canZoom = false;
      },

      fit: function (img, data) {
        var me = this;
        var slide = $.closest(img, '.slide');
        var win = data.window;
        var ph = win.height;
        var pw = win.width;
        var nw = data.nw = img.naturalWidth;
        var nh = data.nh = img.naturalHeight;
        var ah = data.ah = $.toInt($.attr(img, 'height'), 0);
        var xl = data.xl = nh >= win.height;
        var fit = data.fit = ah < ph;
        var sm = data.sm = nh < ph && nw < pw;
        var min;
        var cls;
        var as;
        var cw;
        var ch;
        var id;

        data.src = img.src;
        data.aw = $.toInt($.attr(img, 'width'), 0);

        // Room for thumbnails, and avoid excessive height.
        if ($.matchMedia('1700px', 'min')) {
          min = 210;
        }
        else if ($.matchMedia('1400px', 'min')) {
          min = 180;
        }
        else {
          min = 120;
        }

        as = aspectRatio(nw, nh, pw - min, ph - min);
        cw = data.cw = $.toInt(as.width, 0);
        ch = data.ch = $.toInt(as.height, 0);

        data.ratio = as.ratio;
        data.lg = nw > cw && nh > ch;

        if (xl) {
          cls = 'xl';
        }
        else if (fit) {
          cls = 'fit';
        }
        else if (sm) {
          cls = 'sm';
        }

        data.size = cls;
        if (cls) {
          $.addClass(slide, IS_SLIDE + '-' + cls);
        }

        if (!data.id) {
          id = Math.random().toString(16).slice(2);
          data.id = id;
          img.dataset.sId = id;

          me.data[id] = data;
        }

        setTimeout(function () {
          updateDim(img, data);
        });
      },

      onMounted: function () {
        var me = this;
        var items = [];
        var imgs = [];
        var children = Components.Elements.slides;

        // @todo figure out why 1 item fails, likely being destroyed somewhere.
        if (!children.length) {
          children = Components.Elements.list.children;
        }

        $.each(children, function (slide) {
          var item = $.find(slide, S_ZOOM_PARENT);

          if ($.isElm(item)) {
            var img = $.find(item, S_IS_ZOOMABLE);
            if (img) {
              imgs.push(img);
            }
            items.push(item);
          }
        });

        me.items = items;

        var checkSizes = function (obj, data, entry) {
          if (!data) {
            // @todo reacts on obj.target || obj.matches for mobile devices.
            return;
          }

          data.window = data.window || $.windowSize();
          var img = entry.target;

          $.decode(img)
            .then(function () {
              me.fit(img, data);
            })
            .catch(function () {
              me.fit(img, data);
            });
        };

        if (imgs.length) {
          $.resize(checkSizes, imgs)();

          me.dragon();
        }
      },

      onActive: function (slide) {
        var me = this;

        EL_ZOOMED = $.find(slide.slide, S_IS_ZOOMABLE);

        if (EL_ZOOMED) {
          me.on();
        }
      },

      onWheel: function (e, data) {
        var me = this;
        var el = e.target;

        if ($.hasClass(_html, C_NO_TOUCH)) {
          if (me.isZoomed()) {
            V_LAST_SCALE = data.scale;

            inject(el, data.x, data.y, V_LAST_SCALE || V_MAX);
          }
          else {
            me.canZoom = data.dir === 'down';
            me.zoomon(el, true);
          }
        }
      },

      mount: function () {
        var me = this;

        if (!IS_ENABLED) {
          return;
        }

        Splide.on('mounted.' + ID, function () {
          setTimeout(function () {
            me.onMounted();
          }, 300);
        });

        Splide.on('active.' + ID, me.onActive.bind(me));

        Splide.on('move.' + ID, function () {
          me.toggleClass(false);
        });

        Splide.on('inactive.' + ID, function (slide) {
          var oldImg = $.find(slide.slide, S_IS_ZOOMABLE);
          if ($.isElm(oldImg)) {
            me.zoomOut(oldImg);
          }
        });
      },

      root: function () {
        var root;
        if (S_ZOOM_ROOT) {
          root = $.find(_doc, S_ZOOM_ROOT);
        }
        return root || SPLIDE_ROOT;
      },

      zoomIn: function (el) {
        var me = this;
        var valid = el && el.dataset;

        if (!valid) {
          return false;
        }

        var id = el.dataset.sId;
        var set = me.data[id];

        if (!set) {
          return false;
        }

        var win = set.window;
        var nh = set.nh;
        var nw = set.nw;
        var fit = set.fit;
        var small = set.sm;
        var largeEnough = set.lg;

        if (small || fit || !IS_SCALABLE) {
          me.toggleClass(false);

          if (small && largeEnough) {
            // @todo.
          }
          else {
            return false;
          }
        }

        me.toggleClass(true);

        var limit = win.width > win.height ? win.width : win.height;
        var as = aspectRatio(nw, nh, limit - 80, nh - 80);

        el.style.width = as.width + 'px';
        el.style.height = as.height + 'px';

        removableClass(me.root(), 'zoomin');

        setTimeout(function () {
          inject(el, V_POS.x, V_POS.y, V_LAST_SCALE || V_MAX);
          me.off();
          drag(false);
        });
      },

      zoomOut: function (el) {
        var me = this;

        if (el) {
          var cn = toParent(el);
          cn.style.transform = 'scale3d(' + V_MIN + ', ' + V_MIN + ', ' + V_MIN + ')';

          var transend = function () {
            el.style.width = ''; // w + 'px';
            el.style.height = '';
            // el.style.transition = '';
            // cn.style.transition = '';
          };

          // if (me.isZoomed()) {
          // $.on(cn, 'transitionend.' + ID, transend);
          // }
          setTimeout(transend, 650);

          removableClass(me.root(), 'zoomout');

          me.toggleClass(false, C_ZOOM_ROOT, 100);
        }

        setTimeout(function () {
          me.on();
          drag(true);
        });
      },

      zoomon: function (el, reset) {
        var me = this;
        el = el || EL_ZOOMED;
        var img = $.hasClass(el, C_IS_ZOOMABLE) ? el : $.find(el, S_IS_ZOOMABLE);

        if (me.canZoom) {
          me.zoomIn(img);
        }
        else {
          me.zoomOut(img);
          if (reset) {
            inject(img, '', '', 1);
          }
        }
      },

      onClick: function (e, data) {
        var me = this;
        var img = e.target;

        if ($.hasClass(img, C_IS_ZOOMABLE)) {
          V_POS.x = data.x;
          V_POS.y = data.y;

          EL_ZOOMED = img;

          if (IS_PINCHED) {
            me.zoomOut(img);
            IS_PINCHED = false;
          }
          else {
            //  && !$.hasClass(me.root(), C_DRAG)
            if (!_ds.doubletap()) {
              me.zoomon(img);
            }
          }
        }
      },

      onZoomIn: function (e, data) {
        var me = this;
        var img = e.target;

        if ($.hasClass(img, C_IS_ZOOMABLE)) {
          EL_ZOOMED = img;

          // me.zoomon(img);
          me.toggleClass(true);
        }
      },

      onZoomOut: function (e, data) {
        var me = this;
        var img = e.target;

        if ($.hasClass(img, C_IS_ZOOMABLE)) {
          EL_ZOOMED = img;

          me.zoomOut(img);
        }
      },

      onPinched: function (e, data) {
        IS_PINCHED = true;
      },

      dragon: function () {
        var me = this;
        var cn = me.root();
        var opts = OZ;
        var el;
        var phase;
        // var dir;
        // var reset = false;
        // var x = 0;
        // var y = 0;

        function start(data) {
          V_POS.x = data.x;
          V_POS.y = data.y;

          el = data.el;

          el.style.transition = '';
        }

        function move(data) {
          V_POS.x = data.x;
          V_POS.y = data.y;

          var img = data.img;
          var touchData = data.zoom;
          var inoutCls = C_PINCH +
            'in ' + C_PINCH + 'out';

          me.toggleClass(false, inoutCls);

          if (me.isZoomed()) {
            if (touchData) {
              me.toggleClass(true, C_PINCH + touchData);
            }
            else {
              me.toggleClass(false, inoutCls, 500);

              V_LAST_SCALE = data.scale === 1 ? V_LAST_SCALE : data.scale;

              if ($.hasClass(_html, C_NO_TOUCH)) {
                inject(img, V_POS.x, V_POS.y, V_LAST_SCALE);
              }
            }
          }
          else {
            // me.zoomOut(img);
            me.toggleClass(false, inoutCls, 500);
          }
        }

        function end(data) {
          V_POS.x = data.x;
          V_POS.y = data.y;

          /*
          var img = data.img;
          el = data.el || el;
          dir = data.dir;
          var reset = me.isOffset(img, data.x, data.y);
          var downward = dir === 'down';
          // var upward = dir === 'up';
          var toRight = dir === 'right';
          var toLeft = dir === 'left';
          var horiz = toRight || toLeft;

          if (reset) {
            if (horiz) {
              x = toRight ? 0 : -1;
            }
            else {
              y = data.y > 0 ? sy : -sy;
            }
          }

          $[reset ? 'addClass' : 'removeClass'](me.root(), IS_NICK + '-reset');

          setTimeout(function () {
            if (reset && data.trigger === data.event.type) {
              inject(img, x, y, V_LAST_SCALE || data.scale);
              V_POS.x = x;
              V_POS.y = y;
            }

            $.removeClass(me.root(), IS_NICK + '-reset');
          }, 1200);
          */
        }

        var callback = function (e, data) {
          el = data.el;

          phase = data.phase;
          data.img = $.find(el, S_IS_ZOOMABLE);

          if (phase === 'start') {
            start(data);
          }
          else if (phase === 'move') {
            move(data);
          }
          else if (phase === 'end') {
            end(data);
          }
        };

        opts.onClick = IS_CLICKABLE ? me.onClick.bind(me) : false;
        opts.onZoomIn = me.onZoomIn.bind(me);
        opts.onZoomOut = me.onZoomOut.bind(me);
        opts.onPinched = me.onPinched.bind(me);
        opts.onWheel = me.onWheel.bind(me);
        opts.callback = callback;
        opts.activatedClass = C_ZOOM_ROOT;

        var items = me.items;
        if (items.length) {
          opts.elms = items;

          new SwipeDetect(cn, opts);
        }
      }
    };
  };

  _ds.extend({
    xZoom: xZoom
  });

})(dBlazy, dSplide, this, this.document, this.document.documentElement);
