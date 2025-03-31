<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\blazy\Field\BlazyEntityReferenceBase;
use Drupal\splide\SplideDefault;

/**
 * Base class for splide entity reference formatters with field details.
 *
 * @see \Drupal\splide_media\Plugin\Field\FieldFormatter
 * @see \Drupal\splide_paragraphs\Plugin\Field\FieldFormatter
 */
abstract class SplideEntityReferenceFormatterBase extends BlazyEntityReferenceBase {

  use SplideVanillaWithNavTrait;
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
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::extendedSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $bundles     = $this->getAvailableBundles();
    $text_fields = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts       = $admin->getFieldOptions($bundles, $text_fields, $target_type);
    $texts2      = $admin->getValidFieldOptions($bundles, $target_type);

    if ($bundles) {
      // @todo figure out to not hard-code stock bundle image.
      if (in_array('image', $bundles)) {
        $texts['title'] = $this->t('Image Title');
        $texts['alt'] = $this->t('Image Alt');
      }
    }

    return [
      'thumb_captions'   => $texts,
      'thumb_positions'  => TRUE,
      'nav'              => TRUE,
      'pagination_texts' => array_merge($texts, $texts2),
    ] + parent::getPluginScopes();
  }

}
