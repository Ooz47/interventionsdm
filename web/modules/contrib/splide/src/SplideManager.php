<?php

namespace Drupal\splide;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerBase;
use Drupal\splide\Entity\Splide;

/**
 * Provides splide manager.
 */
class SplideManager extends BlazyManagerBase implements SplideManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'splide';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * The splide skin manager service.
   *
   * @var \Drupal\splide\SplideSkinManagerInterface
   */
  protected $skinManager;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSplide', 'preRenderSplideWrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function skinManager(): SplideSkinManagerInterface {
    return $this->skinManager;
  }

  /**
   * Sets splide skin manager service.
   */
  public function setSkinManager(SplideSkinManagerInterface $skin_manager) {
    $this->skinManager = $skin_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build): array {
    foreach (SplideDefault::themeProperties() as $key => $default) {
      $k = $key == 'items' ? $key : "#$key";
      $build[$k] = $this->toHashtag($build, $key, $default);
    }

    $splide = [
      '#theme'      => 'splide_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSplideWrapper']],
      // Satisfy CTools blocks as per 2017/04/06: 2804165.
      'items'       => [],
    ];

    $this->moduleHandler->alter('splide_build', $splide, $build['#settings']);
    return empty($build['items']) ? [] : $splide;
  }

  /**
   * {@inheritdoc}
   */
  public function buildGrid(array $items, array &$settings): array {
    $this->verifySafely($settings);

    $blazies = $settings['blazies'];
    $config  = $settings['splides'];
    $count   = $blazies->get('count', 0);
    $grids   = [];

    $blazies->set('is.grid_nested', TRUE);

    // Enforces unsplide with less items.
    if (!$config->is('unsplide')) {
      $settings['unsplide'] = $unplide = $count < $settings['visible_items'];
      $config->set('is.unplide', $unplide);
    }

    // Display all items if unsplide is enforced for plain grid to lightbox.
    // Or when the total is less than visible_items.
    // @todo recheck and revert $count 1 to 2 if any side issues.
    if ($config->is('unplide')) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = $count = 1;

      // Requests to refresh grid and re-attach libraries when destroyed.
      $blazies->set('count', $count)
        ->set('is.grid', TRUE)
        ->set('is.grid_refresh', TRUE);

      $grids[0] = $this->buildGridItem($items, $settings);
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = $settings['preserve_keys'] ?? FALSE;
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = $count = count($grid_items);

      $blazies->set('count', $count);
      foreach ($grid_items as $grid_item) {
        $grids[] = $this->buildGridItem($grid_item, $settings);
      }
    }

    return $grids;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnimationTypes(): array {
    $types = [];
    $this->moduleHandler->alter('splide_animation_types', $types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionTypes(): array {
    $types = [
      'slide' => 'Slide',
      'loop'  => 'Loop',
      'fade'  => 'Fade',
    ];
    $this->moduleHandler->alter('splide_transition_types', $types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderSplide(array $element): array {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = &$build['#settings'];

    $this->verifySafely($settings);
    Splide::verifyOptionset($build, $settings['optionset']);

    $options   = &$build['#options'];
    $optionset = &$build['#optionset'];
    $blazies   = $settings['blazies'];
    $config    = $settings['splides'];
    $wheel     = $options['wheel'] ?? $optionset->getSetting('wheel');

    // @todo move here anything unique to each displays.
    $data = [
      'animation'  => $options['animation'] ?? $optionset->getSetting('animation'),
      'transition' => $options['type'] ?? $optionset->getSetting('type'),
      'wheel'      => $wheel,
    ];

    foreach ($data as $key => $value) {
      // @todo remove settings after migration.
      $settings[$key] = $value;
      $config->set(is_bool($value) ? 'is.' . $key : $key, $value);
    }

    if ($settings['display'] == 'main') {
      // Build the Splide grid if provided.
      $blazies->set('is.grid_nested', TRUE);
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = $this->buildGrid($build['items'], $settings);
      }
      $blazies->set('is.grid_nested', FALSE);
    }

    $build['#attributes'] = $this->prepareAttributes($build);

    $this->moduleHandler->alter('splide_optionset', $optionset, $settings, $options);

    foreach (SplideDefault::themeProperties() as $key => $default) {
      $element["#$key"] = $this->toHashtag($build, $key, $default);
    }

    unset($build);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSafely($name): Splide {
    return Splide::loadSafely($name);
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderSplideWrapper($element): array {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare settings and assets.
    $settings = $this->prepareSettings($element, $build);

    // Checks if we have thumbnail navigation.
    $navs    = $build['nav']['items'] ?? [];
    $blazies = $settings['blazies'];
    $config  = $settings['splides'];

    // Prevents unused thumb going through the main display.
    unset($build['nav']);

    // Build the main Splide.
    $splide[0] = $this->splide($build);

    // Build the thumbnail Splide.
    if ($blazies->is('nav') && $navs) {
      $splide[1] = $this->buildNavigation($build, $navs);
    }

    // Reverse splides if thumbnail position is provided to get CSS float work.
    if ($config->get('navpos')) {
      $splide = array_reverse($splide);
    }

    // Collect the splide instances.
    $element['#items'] = $splide;
    $this->setAttachments($element, $settings);

    unset($build);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function toBlazy(array &$data, array &$captions, $delta): void {
    $settings = $this->toHashtag($data);
    $this->verifySafely($settings);

    $blazies = $settings['blazies'];
    $skin    = $settings['skin'] ?? '';
    $prefix  = 'slide';

    $blazies->set('item.id', $prefix)
      ->set('item.prefix', $prefix);

    // Only if it has captions.
    if ($captions) {
      $data['#media_attributes']['class'][] = $prefix . '__media';
      if (strpos($skin, 'full') !== FALSE) {
        $data['#caption_wrapper_attributes']['class'][] = $prefix . '__constrained';
      }
    }

    // Grid already has grid__content wrapper, skip.
    $attrs = $blazies->get('item.wrapper_attributes', []);
    if (empty($settings['grid']) || $attrs) {
      $data['#wrapper_attributes']['class'][] = $prefix . '__content';
      if ($attrs) {
        $data['#wrapper_attributes'] = $this->merge($data['#wrapper_attributes'], $attrs);
      }
    }

    parent::toBlazy($data, $captions, $delta);
  }

  /**
   * {@inheritdoc}
   */
  public function verifySafely(array &$settings, $key = 'blazies', array $defaults = []) {
    SplideDefault::verify($settings, $this);

    return parent::verifySafely($settings, $key, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  protected function attachments(array &$load, array $attach, $blazies): void {
    parent::attachments($load, $attach, $blazies);
    $this->verifySafely($attach);

    $this->skinManager->attach($load, $attach, $blazies);

    $this->moduleHandler->alter('splide_attach', $load, $attach, $blazies);
  }

  /**
   * Returns grid items.
   */
  protected function buildGridItem(array $items, array $settings): array {
    $config  = $settings['splides'];
    $output  = $this->generateGridItem($items, $settings);
    $result  = $this->toGrid($output, $settings);
    $unplide = $config->is('unplide');

    $result['#attributes']['class'][] = $unplide ? 'splide__grid' : 'slide__content';

    $build = ['slide' => $result, '#settings' => $settings];

    $this->moduleHandler->alter('splide_grid_item', $build, $settings);
    return $build;
  }

  /**
   * Returns splide navigation with structured array similar to main display.
   */
  protected function buildNavigation(array &$build, array $items): array {
    $settings  = $this->toHashtag($build);
    $optionset = $this->toHashtag($build, 'optionset_nav');
    $options   = $optionset->getSettings(TRUE);
    $skin      = $settings['skin_nav'] ?? NULL;

    // Only designed for main display, not thumbnails.
    unset($settings['skin_arrows'], $settings['skin_dots']);

    // Programmatic options via skin definitions.
    if ($skin && $skins = $this->skinManager->getSkinsByGroup('nav')) {
      if ($programs = $skins[$skin]['options'] ?? []) {
        $options = $this->merge($programs, $options);
      }
    }

    $settings['optionset'] = $settings['optionset_nav'];
    $settings['skin']      = $skin;
    $settings['display']   = 'nav';
    $data['items']         = $items;
    $data['#optionset']    = $optionset;
    $data['#options']      = $options;
    $data['#settings']     = $settings;

    // Disabled irrelevant options when lacking of slides.
    $this->unsplide($options, $settings);

    // The  navigation has the same structure as the main one.
    unset($build['#optionset_nav']);
    return $this->splide($data);
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  protected function prepareAttributes(array $build = []): array {
    $settings   = $this->toHashtag($build);
    $attributes = $this->toHashtag($build, 'attributes');

    if ($settings['display'] == 'main') {
      Blazy::containerAttributes($attributes, $settings);
    }
    return $attributes;
  }

  /**
   * Prepares js-related options.
   */
  protected function prepareOptions(Splide &$optionset, array &$options, array &$settings): void {
    $blazies = $settings['blazies'];

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Splide without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    $skin = $settings['skin'] ?? NULL;
    if ($skin && $skins = $this->skinManager->getSkinsByGroup('main')) {
      if ($programs = $skins[$skin]['options'] ?? []) {
        $options = $this->merge($programs, $options);
      }
    }

    if ($settings['display'] == 'main') {
      $options['pagination'] = $pagination = Splide::toBoolOrString($optionset->getSetting('pagination'));

      // Overrides common options to re-use an optionset.
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $options[$key] = $key == $override ? TRUE : FALSE;

          // Retain the original optionset specific for pagination.
          if ($key == 'pagination' && $options[$key]) {
            // @todo recheck why double equal signs fails in PHP8.
            if ($pagination === '.splide__arrows') {
              $options[$key] = '.splide__arrows';
            }
          }

          // Supports FIFO hook_splide_overridable_options_info_alter.
          // Makes no sense, but the cheap way without another option for now.
          foreach (['slide', 'loop', 'fade'] as $k) {
            if (isset($options[$k])) {
              if ($options[$k] == $key) {
                $options['type'] = $k;
              }
              unset($options[$k]);
            }
          }
        }
      }
    }

    // Disable draggable for Layout Builder UI to not conflict with UI sortable.
    $count = $blazies->get('count');
    $options['count'] = $count;

    if (strpos($blazies->get('route_name', ''), 'layout_builder.') === 0
      || $blazies->is('sandboxed')) {
      $options['drag'] = FALSE;
    }

    $this->moduleHandler->alter('splide_options', $options, $settings, $optionset);
    // Disabled irrelevant options when lacking of slides.
    $this->unsplide($options, $settings);
  }

  /**
   * Prepare settings for the known module features, not necessarily users'.
   */
  protected function prepareSettings(array &$element, array &$build): array {
    $this->hashtag($build);
    $this->hashtag($build, 'options');

    $settings = &$build['#settings'];
    $this->verifySafely($settings);

    $options   = &$build['#options'];
    $blazies   = $settings['blazies'];
    $config    = $settings['splides'];
    $id        = $blazies->get('css.id', $settings['id'] ?? NULL);
    $id        = $settings['id'] = $this->getHtmlId('splide', $id);
    $optionset = Splide::verifyOptionset($build, $settings['optionset']);

    // Additional settings, Splide supports nav for Vanilla, unlike Slick.
    $count  = $blazies->get('count') ?: $settings['count'] ?? 0;
    $total  = count($build['items']);
    $count  = $count ?: $total;
    $navpos = $settings['navpos'] ?? NULL;
    $nav    = $blazies->is('nav', !empty($settings['nav']));

    // Make it work with ElevateZoomPlus.
    if (!$blazies->is('nav_overridden')) {
      $nav = $nav || (!empty($settings['optionset_nav'])
        && isset($build['items'][1]));
    }

    // Few dups are generic and needed by Blazy to interop Slick and Splide.
    // The total is the original unmodified count, tricked at grids.
    $blazies->set('css.id', $id)
      ->set('count', $count)
      ->set('total', $total)
      ->set('is.nav', $nav);

    $options['count'] = $count;
    $options['total'] = $total;

    // Affected by formatter Override main optionset.
    $this->prepareOptions($optionset, $options, $settings);

    // Removes pagination thumbnail effect if has no thumbnails.
    $wheel      = $options['wheel'] ?? $optionset->getSetting('wheel');
    $pagination = $options['pagination'] ?? $optionset->getSetting('pagination');
    $pagination = Splide::toBoolOrString($pagination);
    $vertical   = $optionset->getSetting('vertical');
    $fx         = $pagination
      && (!empty($settings['thumbnail_style'])
      || !empty($settings['thumbnail']));

    // Currently only concerns for those affecting libraries here.
    $data = [
      'count'          => $count,
      'total'          => $total,
      'down'           => $optionset->getSetting('down'),
      'nav'            => $nav,
      'navpos'         => ($nav && $navpos) ? $navpos : '',
      'pagination_fx'  => $fx ? $settings['thumbnail_effect'] : '',
      'pagination_tab' => $pagination && !empty($settings['pagination_texts']),
      'animation'      => $options['animation'] ?? $optionset->getSetting('animation'),
      'transition'     => $options['type'] ?? $optionset->getSetting('type'),
      'vertical'       => ($options['direction'] ?? FALSE) == 'ttb' || $vertical,
      'autoplay'       => $options['autoplay'] ?? $optionset->getSetting('autoplay'),
      'autoscroll'     => $optionset->getSetting('autoScroll'),
      'intersection'   => $optionset->getSetting('intersection'),
      'wheel'          => $wheel,
    ];

    foreach ($data as $key => $value) {
      // @todo remove settings after migration.
      $settings[$key] = $value;
      $config->set(is_bool($value) ? 'is.' . $key : $key, $value);
    }

    if ($blazies->is('nav')) {
      $optionset_nav = $build['#optionset_nav'] = $this->loadSafely($settings['optionset_nav']);

      $data = [
        'vertical_nav' => $optionset_nav->getSetting('direction') == 'ttb',
        'wheel' => $options['wheel'] ?? $optionset_nav->getSetting('wheel'),
      ];

      foreach ($data as $key => $value) {
        // @todo remove settings after migration.
        $settings[$key] = $value;
        $config->set(is_bool($value) ? 'is.' . $key : $key, $value);
      }
    }
    else {
      // Pass extra attributes such as those from Commerce product variations to
      // theme_splide() since we have no asNavFor wrapper here.
      if ($attributes = $element['#attributes'] ?? []) {
        $attrs = $this->toHashtag($build, 'attributes');
        $build['#attributes'] = $this->merge($attributes, $attrs);
      }
    }

    // Supports Blazy multi-breakpoint or lightbox images if provided.
    // Cases: Blazy within Views gallery, or references without direct image.
    if ($data = $blazies->get('first.data')) {
      if (is_array($data)) {
        $this->isBlazy($settings, $data);
      }
    }

    foreach ($this->skinManager->getComponents() as $key) {
      $config->set('libs.' . $key, !empty($settings[$key]));
    }

    $element['#settings'] = $settings;
    return $settings;
  }

  /**
   * Returns a cacheable renderable array of a single splide instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of splide contents: text, image or media.
   *   - #options: An array of key:value pairs of custom JS overrides.
   *   - #optionset: The cached optionset object to avoid multiple invocations.
   *   - #settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a splide instance, or empty array.
   */
  protected function splide(array $build): array {
    foreach (SplideDefault::themeProperties() as $key => $default) {
      $k = $key == 'items' ? $key : "#$key";
      $build[$k] = $this->toHashtag($build, $key, $default);
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'splide',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSplide']],
    ];
  }

  /**
   * Generates items as a grid item display.
   */
  private function generateGridItem(array $items, array $settings): \Generator {
    $blazies = $settings['blazies'];
    $config  = $settings['splides'];

    foreach ($items as $delta => $item) {
      if (!is_array($item)) {
        continue;
      }

      $sets = $this->toHashtag($item);
      $sets += $settings;
      $attrs = $this->toHashtag($item, 'attributes');
      $content_attrs = $this->toHashtag($item, 'content_attributes');
      $sets['current_item'] = 'grid';
      $sets['delta'] = $delta;

      $blazy = $sets['blazies']->reset($sets);
      $blazy->set('delta', $delta);

      // @todo remove after migrations.
      unset(
        $item['settings'],
        $item['attributes'],
        $item['content_attributes'],
        $item['item_attributes']
      );
      if (!$config->is('unplide')) {
        $attrs['class'][] = 'slide__grid';
      }

      $attrs['class'][] = 'grid--' . $delta;

      // Listens to signaled attributes via hook_alters.
      $this->gridCheckAttributes($attrs, $content_attrs, $blazies, FALSE);

      $theme = empty($settings['vanilla']) ? 'slide' : 'minimal';
      $content = [
        '#theme'    => 'splide_' . $theme,
        '#item'     => $item,
        '#delta'    => $delta,
        '#settings' => $sets,
      ];

      $slide = [
        'content' => $content,
        '#attributes' => $attrs,
        '#content_attributes' => $content_attrs,
        '#settings' => $sets,
      ];

      yield $slide;
    }
  }

  /**
   * Disabled irrelevant options when lacking of slides, unsplide softly.
   *
   * Unlike `settings.unsplide`, this doesn't destroy the markups so that
   * `settings.unsplide` can be overriden as needed unless being forced.
   */
  private function unsplide(array &$options, array $settings): void {
    $blazies = $settings['blazies'];
    if ($blazies->get('count', 0) < 2) {
      $options = array_merge($options, SplideDefault::unsplide());
    }
  }

}
