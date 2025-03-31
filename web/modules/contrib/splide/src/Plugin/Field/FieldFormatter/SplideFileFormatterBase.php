<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatterBase;
use Drupal\splide\SplideDefault;

/**
 * Base class for splide image and file ER formatters.
 *
 * @todo extends BlazyFileSvgFormatterBase post blazy:2.17, or split.
 */
abstract class SplideFileFormatterBase extends BlazyFileFormatterBase {

  use SplideFormatterTrait;

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
  protected static $captionId = 'caption';

  /**
   * {@inheritdoc}
   */
  protected static $navId = 'nav';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::imageSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function withElementOverride(array &$build, array $element): void {
    if (!$build['#asnavfor']) {
      return;
    }

    // The settings in $element has updated metadata extracted from media.
    $settings = $element['#settings'];
    $item     = $element['#item'] ?? NULL;
    $_caption = $settings['nav_caption'] ?? NULL;
    $caption  = [];

    if ($_caption && $item && $text = $item->{$_caption} ?? NULL) {
      $caption = ['#markup' => Xss::filterAdmin($text)];
    }

    // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
    $tn = $this->formatter->getThumbnail($settings, $item, $caption);
    $build[static::$navId]['items'][] = $tn;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $captions = ['title' => $this->t('Title'), 'alt' => $this->t('Alt')];

    return [
      'namespace'       => 'splide',
      'nav'             => TRUE,
      'thumb_captions'  => $captions,
      'thumb_positions' => TRUE,
    ] + parent::getPluginScopes();
  }

}
