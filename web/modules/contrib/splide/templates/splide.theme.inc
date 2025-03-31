<?php

/**
 * @file
 * Hooks and preprocess functions for the Splide module.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use Drupal\splide\Entity\Splide;
use Drupal\splide\SplideDefault as Defaults;

/**
 * Prepares variables for splide.html.twig templates.
 */
function template_preprocess_splide(&$variables) {
  $element = $variables['element'];

  foreach (Defaults::themeProperties() as $key => $default) {
    // A special variables#attached is taken care of upstream, not here.
    // Even if needed it should be $variables["#$key"] due to being special.
    // You can always add it even if skipped here to avoid dups. Core thingies
    // like user, etc. They are there/ respected even if not declared here.
    // See https://www.drupal.org/project/drupal/issues/2346369.
    if ($key == 'attached') {
      continue;
    }
    $variables[$key] = $element["#$key"] ?? $default;
  }

  // Excluded as this was hardly used/ elaborated upstream.
  $key = 'content_attributes';
  $variables[$key] = $element["#$key"] ?? new Attribute();

  $sets = &$variables['settings'];
  splide()->verifySafely($sets);

  $blazies   = $sets['blazies'];
  $splides   = $sets['splides'];
  $optionset = Splide::verifyOptionset($variables, $sets['optionset']);

  // Shortcuts.
  $js        = &$variables['options'];
  $js       += $optionset->getSettings();
  $display   = $variables['display'] = $sets['display'];
  $direction = $js['direction'] ?? 'ltr';
  $id        = $blazies->get('css.id', $sets['id'] ?? NULL);
  $id        = splide()->getHtmlId('splide', $id);
  $id        = $sets['id'] = 'splide-' . substr(md5($id), 0, 11);
  $attrs     = &$variables['attributes'];
  $classes   = (array) ($attrs['class'] ?? []);
  $minimal   = $sets['vanilla'] || $display == 'nav';
  $dummy     = $sets['dummy_template'] ?? FALSE;

  // @see SplideManager::buildGrid(), and this should make sense.
  $sets['count']     = $blazies->get('count') ?: count($element['#items']);
  $sets['vertical']  = $direction == 'ttb';
  $sets['fullwidth'] = !empty($sets['skin']) && strpos($sets['skin'], 'full') !== FALSE;
  $attrs['id']       = $display == 'nav' ? $id . '-nav' : $id;
  $attrs['class']    = array_merge(['splide', 'splide--default'], $classes);

  // @todo remove $sets to $splides object after migration.
  foreach (Defaults::jsSettings() as $key => $default) {
    $sets[$key] = $js[$key] ?? $default;
  }

  // Blazy can still lazyload an unsplide.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  unset($js['lazyLoad']);
  if (!empty($sets['background'])) {
    $js['cover'] = FALSE;
  }

  // Make splide language-direction-aware.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->getDirection() == 'rtl') {
    $attrs['dir'] = 'rtl';
    // Ensures to not affect vertical direction which is mixed with rtl & ltr.
    if (!$sets['vertical']) {
      $js['direction'] = 'rtl';
    }
  }

  // Allows down arrow with a single slide.
  if ($display == 'main' && (!empty($js['down']) && !empty($js['downTarget']))) {
    $variables['arrow_down_attributes'] = new Attribute();
  }

  // Add optional .splide__slider wrapper as per v4.
  // Disables .splide__slider if unsplide, and or as requested.
  // @todo recheck anything which breaks v4 when .splide__slider is present.
  $many         = $blazies->count() > 1;
  $use_slider   = $splides->use('slider', !$splides->is('unsplide'));
  $use_autoplay = $splides->use('autoplay', !empty($sets['autoplay']) && $many);
  $use_progress = $splides->use('progress', !empty($sets['progress']) && $many);

  // When displaying UI, use `use` key, not `is` for intention clarity.
  $splides->set('use.slider', $use_slider);
  $splides->set('use.autoplay', $use_autoplay);
  $splides->set('use.progress', $use_progress);

  // Prevents broken splide when only one item given, or an enforced unsplide.
  if (!$splides->is('unsplide')) {
    // Add the configuration as JSON object into the splide container.
    if (!$dummy && $json = $optionset->toJson($js)) {
      $attrs['data-splide'] = Json::encode($json);
    }
  }

  // Shortcuts for easy calls.
  $variables['blazies'] = $blazies->storage();
  $variables['splides'] = $splides->storage();

  $transition = $splides->get('transition', 'slide');
  foreach (['arrows', 'list', 'pagination'] as $key) {
    $classes = ["splide__{$key}"];
    if ($key == 'list') {
      $classes[] = 'splide__list--' . str_replace('_', '-', $transition);
    }
    $subattrs['class'] = $classes;
    $variables["{$key}_attributes"] = new Attribute($subattrs);
  }

  // Process or interpolate individual item.
  foreach ($variables['items'] as $delta => $item) {
    if (!is_array($item)) {
      continue;
    }
    $item_sets = splide()->toHashtag($item);
    $item_sets += $sets;
    $item_attrs = splide()->toHashtag($item, 'attributes');
    $item_content_attrs = splide()->toHashtag($item, 'content_attributes');
    $item_sets['current_item'] = $display;

    // @todo refactor for hashed keys at 3.x to avoid this in the first place.
    // Was meant to be conveniences as these are elaborated much before
    // arriving here, but might cause render array troubles to those unaware of.
    // Otherwise leave them as they are for good. A good exercise nonetheless.
    unset(
      $item['settings'],
      $item['attributes'],
      $item['content_attributes'],
      $item['item']
    );

    // These themes are the only reason why we did this the way it is.
    // Kind of control room here to facilitate various item themes at one pass.
    $theme = $minimal ? 'minimal' : 'slide';
    $content = $item;
    unset($content['#entity'], $content['#cache']);

    $slide = [
      '#theme' => 'splide_' . $theme,
      // @todo rename item to content for core convention at 2.x.
      '#item' => $content,
      '#delta' => $delta,
      '#settings' => $item_sets,
      '#attributes' => $item_attrs,
      '#content_attributes' => $item_content_attrs,
    ];

    foreach (['cache', 'entity'] as $key) {
      if ($value = ($item["#$key"]) ?? NULL) {
        $slide["#$key"] = $value;
      }
    }

    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for splide-wrapper.html.twig templates.
 */
function template_preprocess_splide_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = $variables['element']["#$key"] ?? [];
  }

  // Shortcuts for easy calls.
  $sets = &$variables['settings'];
  splide()->verifySafely($sets);

  $variables['blazies'] = $sets['blazies']->storage();
  $variables['splides'] = $sets['splides']->storage();
}

/**
 * Prepares variables for splide-slide.html.twig templates.
 */
function template_preprocess_splide_slide(&$variables) {
  _splide_preprocess_splide_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = $variables['item']['caption'][$key] ?? [];
  }

  $item    = &$variables['item'];
  $sets    = &$variables['settings'];
  $blazies = $sets['blazies'];

  $variables['caption_attributes'] = new Attribute();

  // @todo remove the first check at 3.x.
  if ($blazies->use('theme_blazy')
    && $attrs = $blazies->get('item.attributes', [])) {
    $variables['attributes'] = splide()->merge($variables['attributes'], $attrs);
  }

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unsplide grid.
  // wrapper: Remove divities for a single item or grid to have clean markups.
  $unsplide          = !empty($sets['unsplide']);
  $item['slide']     = $slide = $item['slide'] ?? [];
  $item['caption']   = $caption = array_filter($item['caption']);
  $sets['split']     = $slide && !$unsplide && (!empty($sets['caption']) || !empty($sets['title']));
  $sets['data']      = !empty($caption['alt']) || !empty($caption['title']) || !empty($caption['data']);
  $sets['fullwidth'] = !empty($sets['skin']) && strpos($sets['skin'], 'full') !== FALSE;
  $sets['grid']      = !empty($sets['grid']);
  $sets['detroy']    = $sets['current_item'] == 'main' && !empty($sets['grid']) && !empty($sets['unsplide']);
  $sets['use_ca']    = $sets['wrapper'] && (empty($sets['grid']) || !empty($variables['content_attributes']));
}

/**
 * Prepares common variables for splide item templates.
 */
function _splide_preprocess_splide_item(&$variables) {
  $element = $variables['element'];
  foreach (['attributes', 'content_attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? 0 : [];
    $variables[$key] = $element["#$key"] ?? $default;
  }

  $sets = &$variables['settings'];
  $sets['wrapper'] = $sets['current_item'] != 'grid';

  // Because Twig prepends new classes, not appends as expected by BEM.
  $attributes = &$variables['attributes'];
  $classes = empty($attributes['class']) ? [] : (array) $attributes['class'];
  $attributes['class'] = array_merge(['splide__slide', 'slide'], $classes);

  // Shortcuts for easy calls.
  $variables['blazies'] = $sets['blazies']->storage();
  $variables['splides'] = $sets['splides']->storage();
}

/**
 * Prepares variables for splide-minimal.html.twig templates.
 */
function template_preprocess_splide_minimal(&$variables) {
  _splide_preprocess_splide_item($variables);
}
