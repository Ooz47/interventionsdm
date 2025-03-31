<?php

namespace Drupal\Tests\splide\Traits;

/**
 * A Trait common for Splide tests.
 */
trait SplideKernelTrait {

  /**
   * The splide admin service.
   *
   * @var \Drupal\splide\Form\SplideAdminInterface
   */
  protected $splideAdmin;

  /**
   * The splide formatter service.
   *
   * @var \Drupal\splide\SplideFormatterInterface
   */
  protected $splideFormatter;

  /**
   * The splide manager service.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $splideManager;

  /**
   * The splide settings form.
   *
   * @var \Drupal\splide_ui\Form\SplideForm
   */
  protected $splideForm;

  /**
   * The splide manager service.
   *
   * @var \Drupal\splide\SplideSkinManagerInterface
   */
  protected $splideSkinManager;

}
