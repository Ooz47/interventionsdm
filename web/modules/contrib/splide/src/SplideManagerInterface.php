<?php

namespace Drupal\splide;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\blazy\BlazyManagerBaseInterface;
use Drupal\splide\Entity\Splide;

/**
 * Defines re-usable services and functions for splide plugins.
 *
 * @todo remove BlazyManagerBaseInterface when phpstand sniffs inheritance.
 */
interface SplideManagerInterface extends BlazyManagerBaseInterface, SplideInteropInterface, TrustedCallbackInterface {

  /**
   * Returns splide skin manager service.
   */
  public function skinManager(): SplideSkinManagerInterface;

  /**
   * Returns a renderable array of both main and thumbnail splide instances.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of splide contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *   - thumb: An associative array of splide thumbnail following the same
   *     structure as the main display: $build['nav']['items'], etc.
   *
   * @return array
   *   The renderable array of both main and thumbnail splide instances.
   */
  public function build(array $build): array;

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items, array &$settings): array;

  /**
   * Provides alterable animations types.
   */
  public function getAnimationTypes(): array;

  /**
   * Provides alterable transition types.
   */
  public function getTransitionTypes(): array;

  /**
   * Load the optionset with a fallback.
   *
   * @param string $name
   *   The optionset name.
   *
   * @return \Drupal\splide\Entity\Splide
   *   The optionset object.
   */
  public function loadSafely($name): Splide;

  /**
   * Builds the Splide instance as a structured array ready for ::renderer().
   */
  public function preRenderSplide(array $element): array;

  /**
   * One splide_theme() to serve multiple displays: main, overlay, thumbnail.
   */
  public function preRenderSplideWrapper($element): array;

}
