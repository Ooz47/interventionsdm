<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Deprecated in splide:1.0.8, and is removed from splide:2.0.0.
 *
 * @deprecated in splide:1.0.8 and is removed from splide:2.0.0. Use
 *   SplideEntityVanillaFormatterBase methods instead.
 * @see https://www.drupal.org/node/3103018
 */
abstract class SplideEntityFormatterBase extends SplideEntityVanillaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    @trigger_error('SplideEntityFormatterBase is deprecated in splide:1.0.8 and is removed from splide:2.0.0. Use SplideEntityVanillaFormatterBase instead. See https://www.drupal.org/node/3103018', E_USER_DEPRECATED);
    return parent::viewElements($items, $langcode);
  }

}
