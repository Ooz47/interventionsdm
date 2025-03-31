<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'Splide Paragraphs Media' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_paragraphs_media",
 *   label = @Translation("Splide Paragraphs Media"),
 *   description = @Translation("Display the rich paragraph as a Splide Slider."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideParagraphsFormatter extends SplideMediaFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $type   = $this->getFieldSetting('target_type');
    $media  = $this->getFieldOptions(['entity_reference'], $type, 'media', FALSE);
    $types  = ['image', 'entity_reference'];
    $stages = $this->getFieldOptions($types);

    return [
      'images'   => $stages,
      'overlays' => $stages + $media,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    // Excludes host, prevents complication with multiple nested paragraphs.
    $paragraph = $storage->getTargetEntityTypeId() === 'paragraph';
    return $paragraph && $storage->isMultiple() && $storage->getSetting('target_type') === 'paragraph';
  }

}
