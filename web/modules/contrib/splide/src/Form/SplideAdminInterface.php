<?php

namespace Drupal\splide\Form;

use Drupal\blazy\Form\BlazyAdminInteropInterface;

/**
 * Provides resusable admin functions or form elements.
 *
 * @todo recheck if to extend BlazyAdminInterface. The reason it never extends
 * it is to avoid blocking Blazy adjustments due to still recognizing similar
 * features across sub-modules to DRY.
 */
interface SplideAdminInterface extends BlazyAdminInteropInterface {

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions(): array;

  /**
   * Returns available splide optionsets by group.
   */
  public function getOptionsetsByGroupOptions($group = ''): array;

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions(): array;

  /**
   * Returns available splide skins for select options.
   */
  public function getSkinsByGroupOptions($group = ''): array;

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary(array $definition = []): array;

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
  ): array;

  /**
   * Get list of valid label text fields that appear on ALL possible bundles.
   *
   * @todo THIS SHOULD PROBABLY BE REPLACED with $admin->getFieldOptions() if it
   * is possible to narrow the list to include only fields that are present on
   * every bundle.
   */
  public function getValidFieldOptions(
    array $bundles,
    string $target_type,
    array $valid_field_types = [
      'string',
      'text',
    ],
  ): array;

}
