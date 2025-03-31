<?php

namespace Drupal\splide\Entity;

/**
 * Provides an interface defining a Splide entity.
 */
interface SplideInterface extends SplideBaseInterface {

  /**
   * Returns the number of breakpoints.
   *
   * @return int
   *   The number of the provided breakpoints.
   */
  public function getBreakpoint(): int;

  /**
   * Returns the group this optioset instance belongs to for easy selections.
   *
   * @return string
   *   The name of the optionset group.
   */
  public function getGroup(): string;

  /**
   * Returns the Splide skin.
   *
   * @return string
   *   The name of the Splide skin.
   */
  public function getSkin(): string;

  /**
   * Returns whether to optimize the stored options, or not.
   *
   * @return bool
   *   If true, the stored options will be cleaned out from defaults.
   */
  public function optimized(): bool;

  /**
   * Returns the Splide responsive settings.
   *
   * @return array
   *   The responsive options.
   */
  public function getResponsiveOptions(): array;

  /**
   * Sets the Splide responsive settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings'): self;

  /**
   * Turns casts to defaults to prevent errors.
   */
  public function toDefault(array &$js, array $defaults = []): void;

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @param array $js
   *   The source options.
   *
   * @return array
   *   The cleaned out settings.
   */
  public function toJson(array $js): array;

  /**
   * Removes wasted dependent options, even if not empty.
   */
  public function removeWastedDependentOptions(array &$js): void;

}
