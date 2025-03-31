<?php

namespace Drupal\splide\Entity;

use Drupal\Component\Serialization\Json;

/**
 * Defines the Splide configuration entity.
 *
 * @ConfigEntityType(
 *   id = "splide",
 *   label = @Translation("Splide optionset"),
 *   list_path = "admin/config/media/splide",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "weight",
 *     "label",
 *     "group",
 *     "skin",
 *     "breakpoint",
 *     "optimized",
 *     "options",
 *   }
 * )
 */
class Splide extends SplideBase implements SplideInterface {

  /**
   * The number of breakpoints for the optionset.
   *
   * @var int
   */
  protected $breakpoint = 0;

  /**
   * The optionset group for easy selections.
   *
   * @var string
   */
  protected $group = '';

  /**
   * The flag indicating to optimize the stored options by removing defaults.
   *
   * @var bool
   */
  protected $optimized = FALSE;

  /**
   * The skin name for the optionset.
   *
   * @var string
   */
  protected $skin = '';

  /**
   * {@inheritdoc}
   */
  public function getBreakpoint(): int {
    return $this->breakpoint ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): string {
    return $this->group ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSkin(): string {
    return $this->skin ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function optimized(): bool {
    return $this->optimized ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponsiveOptions(): array {
    $options = [];
    if (empty($this->breakpoint)) {
      return $options;
    }

    if (isset($this->options['breakpoints']) && $breakpoints = $this->options['breakpoints']) {
      foreach ($breakpoints as $delta => $breakpoint) {
        if (empty($breakpoint['breakpoint'])) {
          continue;
        }
        if (!empty($breakpoint)) {
          $options[$delta] = $breakpoint;
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings'): self {
    $this->options['breakpoints'][$delta][$key] = $values;
    return $this;
  }

  /**
   * Turns casts to defaults to prevent errors.
   */
  public function toDefault(array &$js, array $defaults = []): void {
    if (empty($js)) {
      return;
    }

    $defaults = $defaults ?: self::defaultSettings();
    foreach (self::getBooleans() as $key => $value) {
      if (isset($js[$key])) {
        $printed = $js[$key];
        if ($key == 'focus') {
          if (is_numeric($printed)) {
            $js[$key] = (string) $printed;
          }
        }

        if ($printed == '') {
          $js[$key] = $value;
        }
        if (is_numeric($printed) || is_bool($printed)) {
          $js[$key] = ($printed || $printed == 1 || $printed == '1') ? 'true' : 'false';
        }
      }
    }

    foreach (self::getNumerics() as $key => $value) {
      if (isset($js[$key]) && $js[$key] == '') {
        $js[$key] = $value;
      }
    }

    foreach ($js as $key => $value) {
      if (is_object($value)) {
        $value = Json::encode($value);
      }

      $type = isset($defaults[$key]) ? gettype($defaults[$key]) : gettype($value);
      settype($js[$key], $type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toJson(array $js): array {
    $config   = [];
    $defaults = self::typecast(self::defaultSettings(), FALSE);

    foreach (self::getObjects() as $key) {
      unset($defaults[$key]);
    }

    // Remove wasted dependent options if disabled, empty or not.
    $js = self::typecast($js);
    if (!$this->optimized) {
      $this->removeWastedDependentOptions($js);
    }

    $config = array_diff_assoc($js, $defaults);

    // Remove useless JS stuffs.
    foreach (['downTarget', 'downOffset', 'progress'] as $key) {
      unset($config[$key]);
    }

    // Clean up responsive options if similar to defaults.
    if ($breakpoints = $this->getResponsiveOptions()) {
      $cleaned = [];
      foreach ($breakpoints as $key => $responsive) {
        $point = $breakpoints[$key]['breakpoint'];

        // Destroy responsive splide if so configured.
        if (!empty($breakpoints[$key]['unsplide'])) {
          $cleaned[$point]['destroy'] = TRUE;
        }
        else {
          // Remove wasted dependent options if disabled, empty or not.
          $settings = &$breakpoints[$key]['settings'];

          self::typecast($settings);

          // @fixme figure out where it fails and remove this.
          foreach (['arrows', 'pagination'] as $sk) {
            if (isset($settings[$sk])) {
              $settings[$sk] = self::toBoolOrString($settings[$sk]);
            }
          }

          if (!$this->optimized) {
            $this->removeWastedDependentOptions($settings);
          }

          $cleaned[$point] = (object) array_diff_assoc($settings, $defaults);
        }
      }
      $config['breakpoints'] = (object) $cleaned;
    }

    return array_filter($config, '\Drupal\splide\Entity\Splide::filterEmpty');
  }

  /**
   * {@inheritdoc}
   */
  public function removeWastedDependentOptions(array &$js): void {
    foreach (self::getDependentOptions() as $key => $option) {
      if (isset($js[$key]) && empty($js[$key])) {
        foreach ($option as $dependent) {
          unset($js[$dependent]);
        }
      }
    }
  }

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions(): array {
    $down = ['downTarget', 'downOffset'];
    return [
      'arrows'     => ['arrowPath', 'down'] + $down,
      // @todo 'clones' => ['cloneStatus'],
      'down'       => $down,
      'drag'       => ['noDrag', 'dragMinThreshold', 'snap'],
      'pagination' => ['omitEnd', 'paginationKeyboard', 'paginationDirection'],
      'rewind'     => ['rewindSpeed', 'rewindByDrag'],
      'slideFocus' => ['focusableNodes'],
      'wheel'      => ['releaseWheel', 'wheelMinThreshold', 'wheelSleep'],
      'progress'   => ['resetProgress'],
      'autoplay'   => [
        'pauseOnHover',
        'pauseOnFocus',
        'interval',
      ],
    ];
  }

  /**
   * Typecast hybrid (boolean|number|string|object) options to the correct type.
   */
  public static function typecast(array $config, $filter = TRUE): array {
    if (empty($config)) {
      return [];
    }

    // @todo recheck other stupid numbers.
    foreach (self::getNumerics() as $key => $default) {
      if (isset($config[$key])) {
        $type = gettype($config[$key]);
        $float = (is_string($config[$key]) && strpos($config[$key], ".") !== FALSE);
        if ($float || $type == 'double' || $type == 'float') {
          $config[$key] = (float) $config[$key];
        }
        elseif (is_numeric($config[$key])) {
          $config[$key] = (int) $config[$key];
        }
      }
    }

    foreach (self::getBooleans() as $key => $default) {
      $value = $config[$key] ?? $default;
      if (is_string($value)) {
        $value = trim($value);
        if ($key == 'focus') {
          if (is_numeric($value)) {
            $config[$key] = (int) $value;
          }
        }
        else {
          $config[$key] = self::toBoolOrString($value);
        }
      }
    }

    foreach (self::getObjects() as $key) {
      if (!empty($config[$key])) {
        $config[$key] = self::toObject($config, $key);
      }
    }

    if ($filter) {
      unset($config['easingOverride']);
      $config = array_filter($config, '\Drupal\splide\Entity\Splide::filterEmpty');
    }

    return $config;
  }

  /**
   * Returns valid JSON object|numeric|string.
   */
  public static function toObject(array $config, $key) {
    $results = [];
    foreach (self::getObjects() as $item) {
      if (!empty($config[$item])) {
        $value = &$config[$item];
        try {
          if (is_string($value) && strpos($value, "{") !== FALSE) {
            $results[$item] = (object) Json::decode(str_replace("'", '"', trim($value)));
          }
          else {
            $results[$item] = is_numeric($value) ? (int) $value : $value;
          }
        }
        catch (\Exception $ignored) {
          // No need to be chatty with incorrect objects, already warned about.
        }
      }
    }
    return empty($results[$key]) ? '' : $results[$key];
  }

  /**
   * Filters out empty string value to avoid JSON.parse error.
   */
  public static function filterEmpty($config): bool {
    return ($config !== NULL && $config !== '');
  }

  /**
   * Returns hybrid/ mixed casts with boolean|number|string.
   */
  private static function getBooleans(): array {
    return [
      'focus' => '0',
      'arrows' => 'true',
      'drag' => 'true',
      'pagination' => 'true',
      'keyboard' => 'true',
      'trimSpace' => 'true',
    ];
  }

  /**
   * Returns hybrid/ mixed casts with object|number|string.
   */
  private static function getNumerics(): array {
    return [
      'width' => '0',
      'height' => '0',
      'heightRatio' => '0',
      'fixedWidth' => '0',
      'fixedHeight' => '0',
      'flickVelocityThreshold' => 0.6,
      'gap' => '0',
      'focus' => '0',
      'padding' => '',
    ];
  }

  /**
   * Returns hybrid/ mixed casts with object|number|string.
   */
  public static function getObjects(): array {
    return array_merge(self::getObjectsAsBool(), [
      'breakpoints',
      'padding',
      'classes',
      'i18n',
    ]);
  }

  /**
   * Returns hybrid/ mixed casts with object|number|string with known FALSE.
   */
  public static function getObjectsAsBool(): array {
    return [
      'autoScroll',
      'intersection',
      'video',
      'zoom',
    ];
  }

  /**
   * Returns bool|string.
   */
  public static function toBoolOrString($value) {
    if ($value == 'true' || $value == 'false' || is_numeric($value)) {
      return ($value == "true" || $value == "1") ? TRUE : FALSE;
    }
    return $value;
  }

  /**
   * Deprecated in splide:1.0.8.
   *
   * Since blazy:2.17, sliders lazyloads are deprecated to avoid complication.
   *
   * @deprecated in splide:1.0.8 and is removed from splide:2.0.0. Use
   * none instead.
   * @see https://www.drupal.org/node/3367291
   */
  public function whichLazy(array &$settings): void {
    // Do nothing.
  }

}
