<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\splide\SplideDefault;

/**
 * Plugin implementation of the 'Splide File' to get image/ SVG from files.
 *
 * This was previously for deprecated VEF, since 1.0.8 re-purposed for SVG, WIP!
 *
 * @FieldFormatter(
 *   id = "splide_file",
 *   label = @Translation("Splide File/SVG"),
 *   field_types = {
 *     "entity_reference",
 *     "file",
 *     "image",
 *     "svg_image_field",
 *   }
 * )
 *
 * @todo remove `image` at 3.x, unless dedicated for SVG (forms and displays).
 */
class SplideFileFormatter extends SplideFileFormatterBase {

  use SplideFormatterTrait {
    pluginSettings as traitPluginSettings;
  }

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  protected static $useOembed = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $useSvg = TRUE;

  /**
   * {@inheritdoc}
   *
   * @todo use BlazyDefault post blazy:2.17 for one swap improvements.
   */
  public static function defaultSettings() {
    return SplideDefault::svgSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove at/by 3.x.
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::buildSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    // @todo use $this->getEntityScopes() post blazy:2.17.
    return [
      'fieldable_form'   => TRUE,
      'multimedia'       => TRUE,
      'no_loading'       => TRUE,
      'no_preload'       => TRUE,
      'responsive_image' => FALSE,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
