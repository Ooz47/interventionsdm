<?php

/**
 * @file
 * Post update hooks for Splide.
 */

use Drupal\splide\SplideDefault;

/**
 * Upgrade to Splide v4 with some added options, revert few into source, etc.
 */
function splide_post_update_v4_upgrades() {
  $config_factory = \Drupal::configFactory();

  // Changes: keyboard waitForTransition slideFocus arrows pagination, etc.
  // Revert old default optionsets to source.
  SplideDefault::import('splide', 'default');

  // Revert old sample optionsets, only pick the most obvious and left the rest
  // to config_update module for manual updates/ reverts in case overriden.
  $samples = [
    // 'x_fullscreen',
    // 'x_grid',
    // 'x_splide_for',
    'x_splide_nav',
    // 'x_vtabs',
    // 'x_carousel',
    // 'x_overlay',
    // 'x_split',
  ];

  if (splide()->moduleExists('splide_x')) {
    foreach ($samples as $key) {
      SplideDefault::import('splide_x', $key);
    }
  }

  // Updates the rest of user optionsets.
  $prefix = 'splide.optionset.';
  foreach ($config_factory->listAll($prefix) as $name) {
    $storage = $config_factory->getEditable($name);
    $settings = $storage->get('options.settings');

    // With optimized, might be just empty.
    // Skip factory optionsets, already updated above, unless overriden.
    if (empty($settings) || $name == 'default' || in_array($name, $samples)) {
      continue;
    }

    // If isNavigation enabled, update the new default slideFocus false to true.
    $is_navigation = $settings['isNavigation'] ?? FALSE;
    if ($is_navigation) {
      $storage->set('options.settings.slideFocus', TRUE);
    }

    foreach (['lazyLoad', 'preloadPages'] as $key) {
      $storage->clear('options.settings.' . $key);
    }

    // Update deprecated settings.
    $dep_settings = [
      'arrows',
      'pagination',
    ];

    foreach ($dep_settings as $key) {
      if ($value = $settings[$key] ?? NULL) {

        // The 'slider' value is no longer available, changed to default true.
        if ($value == 'slider') {
          // No need to set since default is TRUE:
          $storage->clear('options.settings.' . $key);
        }
      }
    }

    // The perPage > 1 requires explicit clones.
    if ($value = $settings['perPage'] ?? NULL) {
      $value = (int) $value;
      $clones = $settings['clones'] ?? NULL;

      // Without clones set, slides will have empty space on LHS.
      if ($value > 1 && (!$clones || is_float($clones))) {
        // 7 visible slides requires half of it: 3.
        $clone = (int) round($value / 2, 0);
        $storage->set('options.settings.clones', $clone);
      }
    }

    // Finally save it.
    $storage->save(TRUE);
  }
}

/**
 * Added few Splide v4 missing options, revert default into source, etc.
 */
function splide_post_update_v4_upgrade_misses() {
  // Changes: keyboard, wheel, cloneStatus, etc.
  // Revert old default optionsets to source.
  SplideDefault::import('splide', 'default');
}
