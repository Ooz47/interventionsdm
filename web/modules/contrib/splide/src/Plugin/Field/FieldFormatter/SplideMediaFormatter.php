<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'splide media' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_media",
 *   label = @Translation("Splide Media"),
 *   description = @Translation("Display the referenced entities as a Splide slider."),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideMediaFormatter extends SplideEntityReferenceFormatterBase {

  /**
   * Overrides the blazy manager.
   *
   * @todo remove at/by 3.x.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $multiple = $this->isMultiple();

    return [
      'grid_form' => $multiple,
      'style'     => $multiple,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'media';
  }

}
