<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'Splide Entity Reference' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_entityreference",
 *   label = @Translation("Splide Entity Reference"),
 *   description = @Translation("Display the entity reference (revisions) as a Splide Slider."),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SplideEntityReferenceFormatter extends SplideEntityVanillaFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'no_thumb_effects' => TRUE,
      'vanilla' => FALSE,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    parent::pluginSettings($blazies, $settings);

    $blazies->set('is.vanilla', TRUE);
    $settings['vanilla'] = TRUE;
  }

}
