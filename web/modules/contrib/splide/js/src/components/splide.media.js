/**
 * @file
 * Provides Splide extensions for (local|remote) videos.
 *
 * This extension is using Blazy media player, not the Splide video extension
 * for reasons: already existed years ago, cross-modules, not only Splide,
 * php-based for cacheability, less size, etc. The caveat is lacking of options.
 * However the options respect Splide conventions to easily override/ switch.
 */

(function ($, _ds, _win) {

  'use strict';

  var xMedia = function (Splide, Components) {
    var ROOT = Splide.root;
    var OPTS = Splide.options;
    var OVIDEO = OPTS.video || {};
    var AUTOVIDEO = OVIDEO.autoplay || false;
    var VIDEO_TIMER;
    var INTERRUPTED = false;
    var IS_PLAYING = 'is-playing';
    var IS_PAUSED = 'is-paused';
    var ICON = '.media__icon';
    var BTN_CLOSE = ICON + '--close';
    var BTN_PLAY = ICON + '--play';
    var FN_MULTIMEDIA = $.multimedia || false;
    var FN_VIEWPORT = $.viewport || false;

    return {
      mount: function () {
        var me = this;

        Splide.on('visible.spm', me.play.bind(me));
        Splide.on('moved.spm', me.close.bind(me));
        $.on(ROOT, 'click.spm', BTN_CLOSE, me.stop.bind(me));
        $.on(ROOT, 'click.spm', BTN_PLAY, me.pause.bind(me));
      },

      /**
       * Turns off any playing (local|remote) videos.
       */
      close: function () {
        var me = this;

        me.stop();
        me.stopLocalVideo();
        INTERRUPTED = false;
      },

      play: function (slide) {
        var me = this;
        var elSlide = slide.slide;
        var media;
        var visible;
        var btn;

        if (OPTS.perPage > 1 || !AUTOVIDEO || INTERRUPTED) {
          return;
        }

        if (!$.hasClass(elSlide, 'is-visible')) {
          return;
        }

        media = $.find(elSlide, '.media--player');

        if (FN_VIEWPORT) {
          visible = FN_VIEWPORT.isVisible(media);

          if (!visible) {
            return;
          }
        }

        if ($.isElm(media)) {
          me.close();

          btn = $.find(media, BTN_PLAY);
          // var vid = $.find(media, 'video');
          if ($.isElm(btn)) {
            me._play(btn);
          }
          // @fixme, not as expected.
          // else if ($.isElm(vid)) {
          // me._play(vid);
          // }
        }
      },

      _play: function (el) {
        _win.clearTimeout(VIDEO_TIMER);
        VIDEO_TIMER = _win.setTimeout(function () {

          el[$.equal(el, 'video') ? 'play' : 'click']();
        }, 501);
      },

      /**
       * Reset any (local) video/ audio to avoid multiple elements from playing.
       */
      stopLocalVideo: function () {
        if (FN_MULTIMEDIA) {
          FN_MULTIMEDIA.pause();
        }
      },

      /**
       * Stops the remote video.
       */
      stop: function () {
        var btn;
        // Clean up any pause marker at slider container.
        $.removeClass(ROOT, IS_PAUSED);

        var cn = $.find(ROOT, '.' + IS_PLAYING);
        if ($.isElm(cn)) {
          $.removeClass(cn, IS_PLAYING);
          btn = $.find(cn, BTN_CLOSE);

          if ($.isElm(btn)) {
            INTERRUPTED = true;
            btn.click();
          }
        }
      },

      /**
       * Trigger pause on splide instance when media playing a video.
       */
      pause: function () {
        INTERRUPTED = true;
        $.addClass(ROOT, IS_PAUSED);
        if (OPTS.autoplay) {
          Splide.off('autoplay:playing');
        }
      }
    };
  };

  _ds.extend({
    xMedia: xMedia
  });

})(dBlazy, dSplide, this);
