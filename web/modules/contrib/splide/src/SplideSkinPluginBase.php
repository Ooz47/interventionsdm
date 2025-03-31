<?php

namespace Drupal\splide;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base class for all splide skins.
 */
abstract class SplideSkinPluginBase extends PluginBase implements SplideSkinPluginInterface {

  /**
   * The splide main/thumbnail skin definitions.
   *
   * @var array
   */
  protected $skins;

  /**
   * The splide arrow skin definitions.
   *
   * @var array
   */
  protected $arrows;

  /**
   * The splide dot skin definitions.
   *
   * @var array
   */
  protected $dots;

  /**
   * The manager service.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->manager = $container->get('splide.manager');
    $instance->skins = $instance->setSkins();
    $instance->arrows = $instance->setArrows();
    $instance->dots = $instance->setDots();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function arrows() {
    return $this->arrows;
  }

  /**
   * {@inheritdoc}
   */
  public function dots() {
    return $this->dots;
  }

  /**
   * Alias for BlazyInterface::getPath().
   */
  protected function getPath($type, $name, $absolute = TRUE): ?string {
    return $this->manager->getPath($type, $name, $absolute);
  }

  /**
   * Sets the required plugin main/thumbnail skins.
   */
  abstract protected function setSkins();

  /**
   * Sets the optional/ empty plugin arrow skins.
   */
  protected function setArrows() {
    return [];
  }

  /**
   * Sets the optional/ empty plugin dot skins.
   */
  protected function setDots() {
    return [];
  }

}
