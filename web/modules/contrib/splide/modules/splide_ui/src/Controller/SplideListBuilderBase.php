<?php

namespace Drupal\splide_ui\Controller;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\blazy\Controller\BlazyListBuilderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Splide optionsets.
 */
abstract class SplideListBuilderBase extends BlazyListBuilderBase {

  /**
   * The splide manager.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );

    $instance->manager = $container->get('splide.manager');
    return $instance;
  }

}
