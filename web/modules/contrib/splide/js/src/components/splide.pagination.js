/**
 * @file
 * Provides thumbnail grid/ hover on dot pagination as asnavfor alternative.
 */

(function ($, Drupal, _ds) {

  'use strict';

  var ThumbPagination = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var _pagination = o.pagination;
    var _fx = 'is-paginated--fx';
    // @todo remove data-thumb for data-b-thumb at 3.x.
    var _dataThumb = 'data-thumb';
    var _dataBThumb = 'data-b-thumb';
    var _thumbed = _pagination === 'thumb'
      || $.hasClass(root, _fx + '-grid')
      || $.hasClass(root, _fx + '-hover');

    return {
      mount: function () {
        var me = this;

        if (_pagination && _thumbed) {
          Splide.on('pagination:mounted.tnp', me.thumbify.bind(me));
        }
      },

      thumbify: function (data) {
        $.each(data.items, function (item, i) {
          var btn = item.button;
          if (btn && btn.nextElementSibling === null) {
            var obj = Components.Slides.getAt(i);

            if (obj) {
              var slide = obj.slide;
              var media = $.find(slide, '[' + _dataThumb + ']');
              var dThumb = _dataThumb;

              if (!$.isElm(media)) {
                media = $.find(slide, '[' + _dataBThumb + ']');
                dThumb = _dataBThumb;
              }

              if ($.isElm(media)) {
                var url = $.attr(media, dThumb);
                var stage = $.find(slide, 'img');
                var alt = $.isElm(stage) ? $.attr(stage, 'alt') : 'Preview';

                alt = Drupal.checkPlain(alt);

                var img = '<img alt="' + Drupal.t(alt) + '" src="' + url + '" loading="lazy" decoding="async" />';
                var el = document.createElement('span');
                el.innerHTML = img;
                el.className = 'splide__pagination__tn';
                btn.insertAdjacentElement('afterend', el);
              }
            }
          }
        });
      }

    };
  };

  _ds.listen({
    ThumbPagination: ThumbPagination
  });

})(dBlazy, Drupal, dSplide);
