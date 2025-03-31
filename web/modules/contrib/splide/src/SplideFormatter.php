<?php

namespace Drupal\splide;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\BlazyFormatter;
use Drupal\splide\Entity\Splide;

/**
 * Provides Splide field formatters utilities.
 */
class SplideFormatter extends BlazyFormatter implements SplideFormatterInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'splide';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $this->hashtag($build);

    $settings = &$build['#settings'];

    $this->verifySafely($settings);

    // Splide specific stuffs.
    $settings['_unload'] = FALSE;

    // @todo move it into self::preSettingsData() post Blazy 2.10.
    $optionset = Splide::verifyOptionset($build, $settings['optionset']);

    // Prepare integration with Blazy.
    $blazies = $settings['blazies'];
    $blazies->set('initial', $optionset->getSetting('start'));

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    parent::preBuildElements($build, $items, $entities);

    $this->hashtag($build);

    $settings = &$build['#settings'];
    $this->verifySafely($settings);

    $blazies = $settings['blazies'];
    $config  = $settings['splides'];

    // Only display thumbnail nav if having at least 2 slides. This might be
    // an issue such as for ElevateZoomPlus module, but it should work it out.
    $nav = $blazies->isset('nav') || isset($settings['nav']);
    if (!$nav) {
      $nav = !empty($settings['optionset_nav']) && isset($items[1]);
    }

    // Dups to allow one swap to all sliders as seen at ElevateZoomPlus.
    $settings['nav'] = $nav;
    $blazies->set('is.nav', $nav);
    $config->set('is.nav', $nav);

    // Only trim overridables options if disabled.
    if (empty($settings['override']) && isset($settings['overridables'])) {
      $settings['overridables'] = array_filter($settings['overridables']);
    }

    if ($entities) {
      $this->checkTextPagination($settings, $entities);
    }

    $this->moduleHandler->alter('splide_settings', $build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preElements(array &$build, $items, array $entities = []): void {
    parent::preElements($build, $items, $entities);

    $settings = $build['#settings'];

    $build['#asnavfor'] = $settings['blazies']->is('nav');
    $build['#vanilla'] = !empty($settings['vanilla']);
  }

  /**
   * {@inheritdoc}
   */
  public function verifySafely(array &$settings, $key = 'blazies', array $defaults = []) {
    SplideDefault::verify($settings, $this);

    return parent::verifySafely($settings, $key, $defaults);
  }

  /**
   * If text pagination is configured, pass strings to the JavaScript.
   */
  protected function checkTextPagination(array &$settings, array $entities): void {
    if ($pagination_text = $settings['pagination_text'] ?? NULL) {
      $pagination_texts = [];
      foreach ($entities as $entity) {
        if (!isset($entity->{$pagination_text})) {
          continue;
        }
        if ($field = $entity->get($pagination_text)) {
          $value = $field->getString();
          $value = $value ? Xss::filter($value, SplideDefault::TAGS) : NULL;
          $pagination_texts[] = $value ?: $this->t('Missing navigation label!');
        }
      }

      if ($pagination_texts) {
        $settings['pagination_texts'] = $pagination_texts;
      }
    }
  }

}
