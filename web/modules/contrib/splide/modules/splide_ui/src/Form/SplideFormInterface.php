<?php

namespace Drupal\splide_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for splide form.
 */
interface SplideFormInterface {

  /**
   * Typecast the setting values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public function typecastOptionset(array &$settings): void;

  /**
   * Handles switching the breakpoint based on the input value.
   */
  public function addBreakpoint($form, FormStateInterface $form_state);

}
