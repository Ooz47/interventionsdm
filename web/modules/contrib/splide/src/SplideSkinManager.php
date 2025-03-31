<?php

namespace Drupal\splide;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\splide\Entity\Splide;

/**
 * Provides Splide skin manager.
 */
class SplideSkinManager extends DefaultPluginManager implements SplideSkinManagerInterface, MapperInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Static cache for the skins by group.
   *
   * @var array
   */
  protected $skinsByGroup;

  /**
   * The library info definition.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * The easing library path.
   *
   * @var string
   */
  protected $easingPath;

  /**
   * The splide library path.
   *
   * @var string
   */
  protected $splidePath;

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'splide';

  /**
   * {@inheritdoc}
   */
  protected static $path = 'Plugin/splide';

  /**
   * {@inheritdoc}
   */
  protected static $interface = 'Drupal\splide\SplideSkinPluginInterface';

  /**
   * {@inheritdoc}
   */
  protected static $annotation = 'Drupal\splide\Annotation\SplideSkin';

  /**
   * {@inheritdoc}
   */
  protected static $key = 'splide_skin';

  /**
   * {@inheritdoc}
   */
  protected static $methods = ['skins', 'arrows', 'dots'];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    $root,
    ConfigFactoryInterface $config,
  ) {
    // parent::__construct(
    // 'Plugin/splide',
    // $namespaces,
    // $module_handler,
    // SplideSkinPluginInterface::class,
    // 'Drupal\splide\Annotation\SplideSkin'
    // );.
    parent::__construct(static::$path, $namespaces, $module_handler, static::$interface, static::$annotation);

    $this->root = $root;
    $this->config = $config;

    $this->alterInfo('splide_skin_info');
    $this->setCacheBackend($cache_backend, 'splide_skin_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getCache() {
    return $this->cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function root() {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$load, array $attach, $blazies = NULL): void {
    $this->attachCore($load, $attach, $blazies);
    $load['drupalSettings']['splide'] = $this->getSafeSettings(Splide::defaultSettings());

    if (!empty($attach['pagination_tab'])) {
      $load['library'][] = 'splide/pagination.tab';

      // @todo move it into [data-splide] to support multiple instances on page.
      $load['drupalSettings']['splide']['paginationTexts'] = $attach['pagination_texts'] ?? [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachCore(array &$load, array $attach, $blazies = NULL): void {
    // @todo remove BC at 2.x with non-optional $blazies.
    $blazies = $blazies ?: $attach['blazies'] ?? NULL;
    if ($this->config('splide_css')) {
      $load['library'][] = 'splide/splide.css';
    }

    if ($blazies && !$blazies->is('unlazy')) {
      $load['library'][] = 'blazy/loading';
    }

    if ($blazies->get('libs.media')) {
      $attach['media'] = TRUE;
    }

    if ($blazies->is('blazy')) {
      $attach['blazy'] = TRUE;
    }

    foreach ($this->getComponents() as $key) {
      if (!empty($attach[$key])) {
        $load['library'][] = 'splide/' . $key;
      }
    }

    $load['library'][] = 'splide/load';
    if (!empty($attach['_vanilla'])) {
      $load['library'][] = 'splide/vanilla';
    }

    $load['library'][] = 'splide/nav';

    if (!empty($attach['skin'])) {
      $this->attachSkin($load, $attach, $blazies);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function config($key = '', $group = 'splide.settings') {
    return $this->config->get($group)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstantSkins(): array {
    return [
      'browser',
      'lightbox',
      'overlay',
      'main',
      'nav',
      'arrows',
      'dots',
      'widget',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getComponents(): array {
    return array_merge($this->getModuleComponents(), [
      'autoscroll',
      'intersection',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins(): array {
    if (!isset($this->skinDefinition)) {
      $cid = 'splide_skins_data';
      $cache = $this->cacheBackend->get($cid);

      if ($cache && $data = $cache->data) {
        $this->skinDefinition = $data;
      }
      else {
        $methods = static::$methods;
        $skins = $items = [];
        foreach ($this->loadMultiple() as $skin) {
          foreach ($methods as $method) {
            $items[$method] = $skin->{$method}();
          }
          $skins = NestedArray::mergeDeep($skins, $items);
        }

        $count = isset($items['skins']) ? count($items['skins']) : count($items);
        $tags = Cache::buildTags($cid, ['count:' . $count]);
        $this->cacheBackend->set($cid, $skins, Cache::PERMANENT, $tags);

        $this->skinDefinition = $skins;
      }
    }
    return $this->skinDefinition ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array {
    if (!isset($this->skinsByGroup[$group])) {
      $skins         = $groups = $ungroups = [];
      $nav_skins     = in_array($group, ['arrows', 'dots']);
      $defined_skins = $nav_skins ? $this->getSkins()[$group] : $this->getSkins()['skins'];

      foreach ($defined_skins as $skin => $properties) {
        $item = $option ? strip_tags($properties['name']) : $properties;
        if (!empty($group)) {
          if (isset($properties['group'])) {
            if ($properties['group'] != $group) {
              continue;
            }
            $groups[$skin] = $item;
          }
          elseif (!$nav_skins) {
            $ungroups[$skin] = $item;
          }
        }
        $skins[$skin] = $item;
      }
      $this->skinsByGroup[$group] = $group ? array_merge($ungroups, $groups) : $skins;
    }
    return $this->skinsByGroup[$group] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSplidePath(
    $base = 'splide',
    $packagist = 'splidejs--splide',
  ): ?string {
    if (!isset($this->splidePath[$base])) {
      if ($manager = self::service('splide.manager')) {
        $libs = [$packagist, $base];
        $this->splidePath[$base] = $manager->getLibrariesPath($libs);
      }
    }
    return $this->splidePath[$base];
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoBuild(): array {
    if (!isset($this->libraryInfoBuild)) {
      $this->libraryInfoBuild = $this->getSkinLibraries();
    }
    return $this->libraryInfoBuild;
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoAlter(array &$libraries, $extension): void {
    if ($path = $this->getSplidePath()) {
      $js = [
        '/' . $path . '/dist/js/splide.min.js' => [
          'weight' => -3,
          'minified' => TRUE,
        ],
      ];
      $base = ['/' . $path . '/dist/css/splide-core.min.css' => []];
      $theme = ['/' . $path . '/dist/css/splide.min.css' => []];

      $libraries['splide']['js'] = $js;
      $libraries['splide']['css']['base'] = $base;
      $libraries['splide.css']['css']['base'] = $theme;
    }

    $plugins = [
      'autoscroll' => 'auto-scroll',
      'intersection' => 'intersection',
    ];

    foreach ($plugins as $key => $value) {
      $base = 'splide-extension-' . $value;
      if ($path = $this->getSplidePath($base, 'splidejs--' . $base)) {
        $js = [
          '/' . $path . '/dist/js/' . $base . '.min.js' => [
            'weight' => -2.9,
            'minified' => TRUE,
          ],
        ];
        $libraries[$key]['js'] = $js;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($plugin_id): SplideSkinPluginInterface {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(): array {
    $skins = [];
    foreach ($this->getDefinitions() as $definition) {
      array_push($skins, $this->createInstance($definition['id']));
    }
    return $skins;
  }

  /**
   * Provides skins only if required.
   *
   * @param array $load
   *   The loaded libraries being modified.
   * @param array $attach
   *   The settings which determine what library to attach.
   * @param object $blazies
   *   The settings.blazies object for convenient, optional for BC.
   */
  protected function attachSkin(array &$load, array $attach, $blazies = NULL): void {
    if ($this->config('module_css', 'splide.settings')) {
      $load['library'][] = 'splide/theme';
    }

    if ($pagination_fx = $attach['pagination_fx'] ?? NULL) {
      $load['library'][] = 'splide/pagination.' . $pagination_fx;
    }

    if (!empty($attach['down'])) {
      $load['library'][] = 'splide/arrow.down';
    }

    if (!empty($attach['autoplay'])) {
      $load['library'][] = 'splide/autoplay';
    }

    foreach ($this->getConstantSkins() as $group) {
      $skin = $group == 'main' ? $attach['skin'] : ($attach['skin_' . $group] ?? '');
      if (!empty($skin)) {
        $skins = $this->getSkinsByGroup($group);
        $provider = $skins[$skin]['provider'] ?? 'splide';
        $load['library'][] = 'splide/' . $provider . '.' . $group . '.' . $skin;
      }
    }
  }

  /**
   * Returns typecast settings.
   */
  protected function getSafeSettings(array $settings): array {
    // Attach default JS settings to allow responsive displays have a lookup,
    // excluding wasted/trouble options, e.g.: PHP string vs JS object.
    $excludes = explode(' ', 'breakpoints classes i18n padding easingOverride downTarget downOffset');
    $excludes = array_combine($excludes, $excludes);
    $settings = Splide::typecast($settings, FALSE);

    // The library assumes some object FALSE explicitly by default.
    // @todo recheck others like breakpoints classes i18n padding, etc.
    foreach (Splide::getObjectsAsBool() as $key) {
      $settings[$key] = FALSE;
    }

    $breakpoints = SplideDefault::validBreakpointOptions();
    $breakpoints = array_combine($breakpoints, $breakpoints);
    $extras = ['destroy' => FALSE];

    foreach ($settings as $key => $value) {
      if (isset($breakpoints[$key])) {
        $extras[$key] = $value;
      }
    }

    $settings['destroy'] = FALSE;
    $config = ['defaults' => array_diff_key($settings, $excludes)];
    $config['extras'] = Splide::typecast($extras, FALSE);
    $config['resets'] = SplideDefault::unsplide();

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSkinLibraries(): array {
    $libraries = [];
    $libraries['splide.css'] = [
      'dependencies' => ['splide/splide'],
      'css' => [
        'base' => ['/' . $this->getSplidePath() . '/dist/css/splide.min.css' => []],
      ],
    ];

    $fullscreen = ['css/components/splide.fullscreen.css' => []];
    foreach ($this->getModuleComponents() as $key) {
      $libraries[$key] = [
        'dependencies' => ['splide/base'],
        'js' => [
          'js/components/splide.' . $key . '.min.js' => [
            'minified' => TRUE,
            'weight' => -0.03,
          ],
        ],
      ];
      if ($key == 'fullscreen') {
        $libraries[$key]['css']['component'] = $fullscreen;
      }
    }

    $libraries['colorbox']['dependencies'][] = 'blazy/colorbox';
    $libraries['media']['dependencies'][] = 'splide/blazy';
    $libraries['zoom']['dependencies'][] = 'splide/swipedetect';

    foreach ($this->getConstantSkins() as $group) {
      if ($skins = $this->getSkinsByGroup($group)) {
        foreach ($skins as $key => $skin) {
          $provider = $skin['provider'] ?? 'splide';
          $id = $provider . '.' . $group . '.' . $key;

          $libraries[$id]['dependencies'] = [];
          foreach (['css', 'js', 'dependencies'] as $property) {
            if (isset($skin[$property]) && is_array($skin[$property])) {
              $libraries[$id][$property] = $skin[$property];
            }
          }

          $libraries[$id]['version'] = 'VERSION';

          if ($dependencies = $this->getDependencies()) {
            $libraries[$id]['dependencies'] = array_merge(
              $libraries[$id]['dependencies'],
              $dependencies
            );
          }
        }
      }
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDependencies(): array {
    return ['blazy/dblazy'];
  }

  /**
   * Splide module-managed/ builtin library components.
   */
  private function getModuleComponents(): array {
    return [
      'blazy',
      'colorbox',
      'fullscreen',
      'media',
      'swipedetect',
      'zoom',
    ];
  }

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  private static function service($service) {
    return \Drupal::hasService($service) ? \Drupal::service($service) : NULL;
  }

}
