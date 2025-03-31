<?php

namespace Drupal\splide;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\blazy\BlazyDefault;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class SplideDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  protected static $id = 'splides';

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'optionset'       => 'default',
      'override'        => FALSE,
      'overridables'    => [],
      'skin'            => '',
      'skin_arrows'     => '',
      'skin_dots'       => '',
      'use_theme_field' => FALSE,
      'pagination_pos'  => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function gridSettings() {
    return [
      'preserve_keys' => FALSE,
      'visible_items' => 0,
    ] + parent::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    return [
      'optionset_nav'    => '',
      'skin_nav'         => '',
      'nav_caption'      => '',
      'thumbnail_effect' => '',
      'navpos'           => '',
    ] + self::baseSettings() + parent::imageSettings() + self::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return [
      'thumbnail' => '',
      'pagination_text' => '',
    ] + self::imageSettings()
      + parent::extendedSettings();
  }

  /**
   * Returns filter settings.
   */
  public static function filterSettings() {
    $settings = self::imageSettings();
    $unused = [
      'breakpoints' => [],
      'sizes'       => '',
      'grid_header' => '',
    ];
    foreach ($unused as $key => $value) {
      if (isset($settings[$key])) {
        unset($settings[$key]);
      }
    }
    return $settings + self::gridSettings();
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public static function overridableOptions($option = TRUE): array {
    return [
      'arrows'     => $option ? new TranslatableMarkup('Arrows') : '',
      'pagination' => $option ? new TranslatableMarkup('Pagination') : '',
      'autoplay'   => $option ? new TranslatableMarkup('Autoplay') : '',
      'autoWidth'  => $option ? new TranslatableMarkup('Auto width') : '',
      'autoHeight' => $option ? new TranslatableMarkup('Auto height') : '',
      'drag'       => $option ? new TranslatableMarkup('Drag') : '',
      'wheel'      => $option ? new TranslatableMarkup('Mouse wheel') : '',
      'randomize'  => $option ? new TranslatableMarkup('Randomize') : '',
    ];
  }

  /**
   * Returns valid options for breakpoints.
   */
  public static function validBreakpointOptions(): array {
    return [
      'arrows',
      'direction',
      'rewind',
      'speed',
      'width',
      'height',
      'fixedWidth',
      'fixedHeight',
      'heightRatio',
      'perPage',
      'perMove',
      'clones',
      'focus',
      'gap',
      'padding',
      'pagination',
      'drag',
      'snap',
      'easing',
      'destroy',
    ];
  }

  /**
   * Returns Splide specific settings.
   */
  public static function splides(): array {
    return [
      'autoscroll'     => FALSE,
      'display'        => 'main',
      // 'nav'            => FALSE,
      // 'navpos'         => FALSE,
      'pagination_fx'  => '',
      'pagination_tab' => FALSE,
      'thumbnail_uri'  => '',
      '_unload'        => FALSE,
      'unsplide'       => FALSE,
      'vanilla'        => FALSE,
      'vertical'       => FALSE,
      'vertical_nav'   => FALSE,
    ];
  }

  /**
   * Returns Splide unsplide settings aka resets.
   */
  public static function unsplide(): array {
    return [
      'arrows' => FALSE,
      'autoplay' => FALSE,
      'drag' => FALSE,
      'pagination' => FALSE,
      'perPage' => 1,
      'perMove' => 1,
      'progress' => FALSE,
      'start' => 0,
      'type' => 'fade',
    ];
  }

  /**
   * Returns HTML or layout related settings to shut up notices.
   *
   * @return array
   *   The default settings.
   */
  public static function htmlSettings() {
    return [
      // @todo remove post 2.17:
      // 'splides' => \blazy()->settings(self::values()),
      // @todo remove after migrations.
      'namespace' => 'splide',
      // @todo remove `+ self::splides()`.
    ] + self::splides()
      + self::imageSettings()
      + parent::htmlSettings();
  }

  /**
   * Defines JS options required by theme_splide(), used with optimized option.
   */
  public static function jsSettings(): array {
    return [
      'autoplay'   => FALSE,
      'direction'  => 'ltr',
      'downTarget' => '',
      'downOffset' => 0,
      'pagination' => TRUE,
      'perPage'    => 1,
      'progress'   => FALSE,
    ];
  }

  /**
   * Returns splide theme properties.
   */
  public static function themeProperties() {
    return [
      'attached' => [],
      'attributes' => [],
      'items' => [],
      'options' => [],
      'optionset' => NULL,
      'settings' => [],
    ];
  }

  /**
   * Verify the settings.
   */
  public static function verify(array &$settings, $manager): void {
    $config = $settings['splides'] ?? NULL;
    if (!$config) {
      $settings += self::htmlSettings();
      $config = $settings['splides'];
    }

    if (!$config->get('ui')) {
      $ui = $manager->configMultiple('splide.settings');
      $config->set('ui', $ui);
    }
  }

  /**
   * Reverts the optionset to source.
   */
  public static function import($module, $key) {
    $config_factory = \Drupal::configFactory();
    $config_path = \splide()->getPath('module', $module) . '/config/install/splide.optionset.' . $key . '.yml';
    $data = Yaml::parseFile($config_path);
    $config_factory->getEditable('splide.optionset.' . $key)
      ->setData($data)
      ->save(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected static function values(): array {
    return self::splides();
  }

}
