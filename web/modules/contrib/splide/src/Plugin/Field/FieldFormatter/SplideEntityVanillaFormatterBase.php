<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Field\BlazyEntityVanillaBase;
use Drupal\splide\SplideDefault;

/**
 * Base class for splide entity reference formatters without field details.
 *
 * @see \Drupal\splide\Plugin\Field\FieldFormatter\SplideEntityReferenceFormatterBase
 * @see \Drupal\splide\Plugin\Field\FieldFormatter\SplideParagraphsFormatter
 */
abstract class SplideEntityVanillaFormatterBase extends BlazyEntityVanillaBase {

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
   * Returns the blazy manager.
   *
   * @todo remove at/by 3.x.
   */
  public function blazyManager() {
    return $this->formatter;
  }

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
    $texts       = $this->getFieldOptions($text_fields);
    $texts2      = $admin->getValidFieldOptions($bundles, $target_type);

    return [
      'fieldable_form'   => TRUE,
      'image_style_form' => TRUE,
      'images'           => $this->getFieldOptions(['image']),
      'thumb_captions'   => $texts,
      'thumb_positions'  => TRUE,
      'thumbnail_style'  => TRUE,
      'nav'              => TRUE,
      'nav_state'        => TRUE,
      'no_thumb_effects' => TRUE,
      'pagination_texts' => $texts2,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple();
  }

}
