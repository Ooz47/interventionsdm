<?php

namespace Drupal\splide;

/**
 * Provides an interface defining Splide skins, and asset managements.
 */
interface SplideSkinManagerInterface extends SplideInteropInterface {

  /**
   * Returns cache backend service.
   */
  public function getCache();

  /**
   * Returns app root.
   */
  public function root();

  /**
   * Provides splide skins and libraries.
   *
   * @param array $load
   *   The loaded libraries being modified.
   * @param array $attach
   *   The settings which determine what library to attach.
   * @param object $blazies
   *   The settings.blazies object for convenient, optional for BC.
   */
  public function attach(array &$load, array $attach, $blazies = NULL): void;

  /**
   * Provides core libraries.
   *
   * @param array $load
   *   The loaded libraries being modified.
   * @param array $attach
   *   The settings which determine what library to attach.
   * @param object $blazies
   *   The settings.blazies object for convenient, optional for BC.
   */
  public function attachCore(array &$load, array $attach, $blazies = NULL): void;

  /**
   * Returns splide config shortcut.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = '', $group = 'splide.settings');

  /**
   * Splide module-managed/ builtin library components including library ones.
   */
  public function getComponents(): array;

  /**
   * Returns the supported skins.
   */
  public function getConstantSkins(): array;

  /**
   * Returns splide skins registered via SplideSkin plugin and or defaults.
   */
  public function getSkins(): array;

  /**
   * Returns available splide skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array;

  /**
   * Returns splide library path if available, else NULL.
   */
  public function getSplidePath(
    $base = 'splide',
    $packagist = 'splidejs--splide',
  ): ?string;

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild(): array;

  /**
   * Implements hook_library_info_alter().
   */
  public function libraryInfoAlter(array &$libraries, $extension): void;

  /**
   * Returns an instance of a plugin by given plugin id.
   *
   * @param string $id
   *   The plugin id.
   *
   * @return \Drupal\splide\SplideSkinPluginInterface
   *   Return instance of SplideSkin.
   */
  public function load($id): SplideSkinPluginInterface;

  /**
   * Returns plugin instances.
   */
  public function loadMultiple(): array;

}
