<?php

namespace Drupal\splide_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Form\BlazyEntityFormBase;
use Drupal\blazy\Traits\EasingTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base form for a splide instance configuration form.
 */
abstract class SplideFormBase extends BlazyEntityFormBase {

  use EasingTrait;

  /**
   * The splide admin service.
   *
   * @var \Drupal\splide\Form\SplideAdminInterface
   */
  protected $admin;

  /**
   * The splide manager.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $manager;

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

  /**
   * The JS easing options.
   *
   * @var array
   */
  protected $jsEasingOptions;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->admin = $container->get('splide.admin');
    $instance->manager = $container->get('splide.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $admin_css = $this->manager->config('admin_css', 'blazy.settings');

    // Attach Splide admin library.
    if ($admin_css) {
      $form['#attached']['library'][] = 'splide_ui/admin.vtabs';
    }

    return parent::form($form, $form_state);
  }

}
