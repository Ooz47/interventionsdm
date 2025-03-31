<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyTextFormatter;
use Drupal\splide\SplideDefault;

/**
 * Plugin implementation of the 'Splide Text' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_text",
 *   label = @Translation("Splide Text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SplideTextFormatter extends BlazyTextFormatter {

  use SplideFormatterTrait {
    pluginSettings as traitPluginSettings;
  }

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
  protected static $fieldType = 'text';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SplideDefault::baseSettings() + SplideDefault::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    $this->traitPluginSettings($blazies, $settings);

    $blazies->set('is.navless', TRUE)
      ->set('is.text', TRUE)
      ->set('is.vanilla', TRUE);

    // @todo remove.
    $settings['navless'] = TRUE;
    $settings['vanilla'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginScopes(): array {
    return [
      'no_thumb_effects' => TRUE,
    ] + parent::getPluginScopes();
  }

}
