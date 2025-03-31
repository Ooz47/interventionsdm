<?php

namespace Drupal\splide_ui\Form;

use Drupal\blazy\Form\BlazyDeleteFormBase;

/**
 * Builds the form to delete a Splide optionset.
 */
abstract class SplideDeleteFormBase extends BlazyDeleteFormBase {

  /**
   * Defines the nice anme.
   *
   * @var string
   */
  protected static $niceName = 'Splide';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'splide';

}
