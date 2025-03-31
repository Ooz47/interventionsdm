<?php

namespace Drupal\splide_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\splide\Entity\Splide;
use Drupal\splide\Entity\SplideInterface;
use Drupal\splide\SplideDefault;

/**
 * Extends base form for splide instance configuration form.
 */
class SplideForm extends SplideFormBase implements SplideFormInterface {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // $form = parent::form($form, $form_state);
    $path   = $this->manager->getPath('module', 'splide');
    $splide = $this->entity;

    // Satisfy phpstan.
    if (!($splide instanceof SplideInterface)) {
      return parent::form($form, $form_state);
    }

    $options   = $splide->getOptions() ?: [];
    $tooltip   = ['class' => ['is-tooltip']];
    $route     = ['name' => 'splide_ui'];
    $is_help   = $this->manager()->moduleExists('help');
    $readme    = $is_help ? Url::fromRoute('help.page', $route)->toString() : Url::fromUri('base:' . $path . '/docs/README.md')->toString();
    $admin_css = $this->manager->config('admin_css', 'blazy.settings');
    $defaults  = Splide::defaultSettings();
    $_default  = $splide->id() == 'default';

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $splide->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the Splide optionset."),
      '#attributes'    => $tooltip,
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $splide->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\splide\Entity\Splide::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => ($_default || !$splide->isNew()) && $this->operation != 'duplicate',
    ];

    $form['skin'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Skin'),
      '#options'       => $this->admin->getSkinsByGroupOptions(),
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $splide->getSkin(),
      '#description'   => $this->t('Skins allow swappable layouts like next/prev links, split image and caption, etc. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. See main <a href="@url">README</a> for details on Skins. Only useful for custom work, and ignored/overridden by splide formatters or sub-modules. If you are using Splidebox, this is the only option to change its skin at the Splidebox optionset.', ['@url' => $readme]),
      '#attributes'    => $tooltip,
      '#wrapper_attributes' => ['class' => ['form-item--tooltip-wide']],
    ];

    $form['group'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Group'),
      '#options'       => [
        'main'     => $this->t('Main'),
        'nav'      => $this->t('Navigation'),
        'overlay'  => $this->t('Overlay'),
        'lightbox' => $this->t('Lightbox'),
      ],
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $splide->getGroup(),
      '#description'   => $this->t('Group this optionset to avoid confusion for optionset selections. Leave empty to make it available for all.'),
      '#attributes'    => $tooltip,
    ];

    $form['breakpoint'] = [
      '#title'         => $this->t('Breakpoint'),
      '#type'          => 'textfield',
      '#default_value' => $form_state->hasValue('breakpoint') ? $form_state->getValue('breakpoint') : $splide->getBreakpoint(),
      '#description'   => $this->t('The number of breakpoints added to Responsive display, max 9. This is not Breakpoint Width (480px, etc).'),
      '#ajax' => [
        'callback' => '::addBreakpoint',
        'wrapper'  => 'edit-breakpoint-ajax-wrapper',
        'event'    => 'change',
        'progress' => ['type' => 'fullscreen'],
        'effect'   => 'fade',
        'speed'    => 'fast',
      ],
      '#attributes' => $tooltip,
      '#maxlength'  => 1,
    ];

    $form['optimized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Optimized'),
      '#default_value' => $splide->optimized(),
      '#description'   => $this->t('Check to optimize the stored options. Anything similar to defaults will not be stored, except those required by sub-modules and theme_splide(). Like you hand-code/ cherry-pick the needed options, and are smart enough to not repeat defaults, and free up memory. The rest are taken care of by JS. Uncheck only if theme_splide() can not satisfy the needs, and more hand-coded preprocess is needed which is less likely in most cases.'),
      '#access'        => $splide->id() != 'default',
      '#attributes'    => $tooltip,
      '#wrapper_attributes' => ['class' => ['form-item--tooltip-wide']],
    ];

    if ($admin_css) {
      $form['optimized']['#title_display'] = 'before';

      $form['skin']['#prefix'] = '<div class="b-nativegrid b-nativegrid--form b-tooltip is-b-narrow is-b-gapless">';
      if ($_default) {
        $form['breakpoint']['#suffix'] = '</div>';
      }
      else {
        $form['optimized']['#suffix'] = '</div>';
      }

      foreach (['skin', 'group', 'breakpoint', 'optimized'] as $key) {
        $attrs = &$form[$key]['#wrapper_attributes'];
        $attrs['class'][] = 'grid';
        $attrs['class'][] = 'b-tooltip__bottom';
        $attrs['data-b-w'] = 3;
      }
    }

    // Options.
    $form['options'] = [
      '#type'    => 'vertical_tabs',
      '#tree'    => TRUE,
      '#parents' => ['options'],
    ];

    // Main JS options.
    $form['settings'] = [
      '#type'       => 'details',
      '#tree'       => TRUE,
      '#title'      => $this->t('Settings'),
      '#attributes' => ['class' => ['details--settings', 'b-tooltip']],
      '#group'      => 'options',
      '#parents'    => ['options', 'settings'],
    ];

    // Common options to be attached into each form element.
    $elementsFormOptions = [
      'type',
      'options',
      'empty_option',
      'field_suffix',
      'states',
      'step',
    ];

    foreach ($this->getFormElements() as $name => $element) {
      $element['default'] = $element['default'] ?? '';
      $default_value = (NULL !== $splide->getSetting($name)) ? $splide->getSetting($name) : $element['default'];
      $element_type = $element['type'] ?? '';

      // In case more useful stupidity gets in the way.
      if ($element_type == 'textfield' || $element_type == 'textarea') {
        $default_value = strip_tags($default_value);
      }

      $form['settings'][$name] = [
        '#title'         => $element['title'] ?? '',
        '#default_value' => $default_value,
      ];

      $formsets = &$form['settings'][$name];
      if (in_array($name, $this->tooltipBottom())) {
        $formsets['#wrapper_attributes']['class'][] = 'form-item--tooltip-bottom';
      }

      foreach ($elementsFormOptions as $option) {
        if (isset($element[$option])) {
          $formsets["#$option"] = $element[$option];
        }
      }
      if ($element_type) {
        if ($admin_css && $element_type == 'checkbox') {
          $formsets['#title_display'] = 'before';
        }

        if ($element_type != 'hidden') {
          $formsets['#attributes'] = $tooltip;
        }
        else {
          // Ensures hidden element doesn't screw up the states.
          unset($element['states']);
        }

        if ($element_type == 'textfield') {
          $formsets['#size'] = 20;
          $formsets['#maxlength'] = 255;
        }
      }

      if (isset($element['description'])) {
        $formsets['#description'] = $element['description'];
      }

      if (is_int($element['default'])) {
        $formsets['#maxlength'] = 60;
        $formsets['#attributes']['class'][] = 'form-text--int';
      }

      if (in_array($name, ['classes', 'i18n', 'intersection', 'autoScroll'])) {
        $formsets['#wrapper_attributes']['class'][] = 'form-item--tooltip-wide';
        $formsets['#wrapper_attributes']['data-b-h'] = 3;
      }
    }

    // Responsive JS options.
    // https://github.com/Splidejs/splideissues/951
    $form['breakpoints'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Responsive display'),
      '#open'        => TRUE,
      '#tree'        => TRUE,
      '#group'       => 'options',
      '#parents'     => ['options', 'breakpoints'],
      '#description' => $this->t('Containing breakpoints and settings objects. Settings set at a given breakpoint/screen width is self-contained and does not inherit the main settings, but defaults. Be sure to set Breakpoint option above.'),
    ];

    $form['breakpoints']['responsive'] = [
      '#type'       => 'container',
      '#title'      => $this->t('Responsive'),
      '#group'      => 'breakpoints',
      '#parents'    => ['options', 'breakpoints'],
      '#prefix'     => '<div id="edit-breakpoint-ajax-wrapper">',
      '#suffix'     => '</div>',
      '#attributes' => ['class' => ['b-tooltip', 'details--responsive--ajax']],
    ];

    // Add some information to the form state for easier form altering.
    $form_state->set('breakpoint_count', 0);
    $breakpoint_count = $form_state->hasValue('breakpoint') ? $form_state->getValue('breakpoint') : $splide->getBreakpoint();

    if (!$form_state->hasValue('breakpoint_count')) {
      $form_state->setValue('breakpoint_count', $breakpoint_count);
    }

    $user_input = $form_state->getUserInput();
    $breakpoint_input = (int) ($user_input['breakpoint'] ?? $breakpoint_count);

    if ($breakpoint_input && ($breakpoint_input != $breakpoint_count)) {
      $form_state->setValue('breakpoint_count', $breakpoint_input);
    }

    if ($form_state->getValue('breakpoint_count') > 0) {
      $breakpoint_forms = $this->getResponsiveFormElements($form_state->getValue('breakpoint_count'));

      foreach ($breakpoint_forms as $i => $breakpoints) {
        // Individual breakpoint details depends on the breakpoint amount.
        $form['breakpoints']['responsive'][$i] = [
          '#type'       => $breakpoints['type'],
          '#title'      => $breakpoints['title'],
          '#open'       => FALSE,
          '#group'      => 'breakpoints',
          '#attributes' => [
            'class' => [
              'details--responsive',
              'details--breakpoint-' . $i,
              'b-tooltip',
            ],
          ],
        ];

        unset($breakpoints['title'], $breakpoints['type']);
        foreach ($breakpoints as $key => $responsive) {
          switch ($key) {
            case 'breakpoint':
            case 'unsplide':
              $form['breakpoints']['responsive'][$i][$key] = [
                '#type'          => $responsive['type'],
                '#title'         => $responsive['title'],
                '#default_value' => $options['breakpoints'][$i][$key] ?? $responsive['default'],
                '#description'   => $responsive['description'],
                '#attributes'    => $tooltip,
              ];

              $detroyable = &$form['breakpoints']['responsive'][$i][$key];
              $attrs = &$detroyable['#wrapper_attributes'];
              if ($responsive['type'] == 'textfield') {
                $detroyable['#size'] = 20;
                $detroyable['#maxlength'] = 255;
              }

              if (is_int($responsive['default'])) {
                $detroyable['#maxlength'] = 60;
              }

              if (isset($responsive['field_suffix'])) {
                $detroyable['#field_suffix'] = $responsive['field_suffix'];
              }

              if ($admin_css && $responsive['type'] == 'checkbox') {
                $detroyable['#title_display'] = 'before';
              }

              $attrs['class'][] = 'grid';
              $attrs['class'][] = 'form-item--tooltip-bottom';
              if ($key == 'breakpoint') {
                $detroyable['#prefix'] = '<div class="b-nativegrid b-nativegrid--auto b-nativegrid--form b-tooltip">';
              }
              else {
                $detroyable['#suffix'] = '</div>';
              }
              break;

            case 'settings':
              $form['breakpoints']['responsive'][$i][$key] = [
                '#type'       => $responsive['type'],
                '#title'      => $responsive['title'],
                '#open'       => TRUE,
                '#group'      => $i,
                '#states'     => ['visible' => [':input[name*="options[breakpoints][' . $i . '][unsplide]"]' => ['checked' => FALSE]]],
                '#attributes' => [
                  'class' => [
                    'details--settings',
                    'details--breakpoint-' . $i,
                    'b-tooltip',
                  ],
                ],
              ];

              unset($responsive['title'], $responsive['type']);

              // @fixme, boolean default is ignored at index 0 only.
              foreach ($responsive as $k => $item) {
                $default = $defaults[$k] ?? '';
                $item['default'] = $item['default'] ?? $default;

                $form['breakpoints']['responsive'][$i][$key][$k] = [
                  '#title'         => $item['title'] ?? '',
                  '#default_value' => $options['breakpoints'][$i][$key][$k] ?? $item['default'],
                  '#description'   => $item['description'] ?? '',
                  '#attributes'    => $tooltip,
                ];

                $subsets = &$form['breakpoints']['responsive'][$i][$key][$k];
                foreach (['type', 'options', 'empty_option', 'field_suffix', 'step'] as $option) {
                  if (isset($item[$option])) {
                    $subsets["#$option"] = $item[$option];
                  }
                }

                if ($admin_css && ($item['type'] ?? NULL) == 'checkbox') {
                  $subsets['#title_display'] = 'before';
                }

                if (in_array($k, $this->tooltipBottom())) {
                  $subsets['#wrapper_attributes']['class'][] = 'form-item--tooltip-bottom';
                }
              }
              break;

            default:
              break;
          }
        }
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function typecastOptionset(array &$settings): void {
    if (empty($settings)) {
      return;
    }

    $defaults = Splide::defaultSettings();

    foreach ($defaults as $name => $value) {
      if (isset($settings[$name])) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($defaults[$name]);
        $type = $type == 'double' ? 'float' : $type;

        settype($settings[$name], $type);
      }
    }
  }

  /**
   * Handles switching the breakpoint based on the input value.
   */
  public function addBreakpoint($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('breakpoint')) {
      $form_state->setValue('breakpoint_count', $form_state->getValue('breakpoint'));
      if ($form_state->getValue('breakpoint') >= 6) {
        $message = $this->t('You are trying to load too many Breakpoints. Try reducing it to reasonable numbers say, between 1 to 5.');
        $this->messenger()->addMessage($message, 'warning');
      }
    }

    return $form['breakpoints']['responsive'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Update CSS Bezier version.
    $override = $form_state->getValue(['options', 'settings', 'easingOverride']);
    if ($override) {
      $override = $this->getBezier($override);
      // Update cssEaseBezier value based on easingOverride.
      $form_state->setValue(['options', 'settings', 'easing'], $override);
    }

    // @todo Prevents hybrid casts from screwing up.
    $num = $form_state->getValue('breakpoint');
    $breakpoints = $form_state->getValue(['options', 'breakpoints']);
    if (!empty($breakpoints)) {
      foreach ($breakpoints as $key => $breakpoint) {
        if (empty($breakpoint['breakpoint'])) {
          $form_state->unsetValue(['options', 'breakpoints', $key]);
          $num -= 1;
          $form_state->setValue('breakpoint', $num);
        }
      }
    }

    if ($form_state->getValue(['options', 'options__active_tab'])) {
      $form_state->unsetValue(['options', 'options__active_tab']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Optimized if so configured.
    $splide = $this->entity;

    // Satisfy phpstan.
    if (!($splide instanceof SplideInterface)) {
      return;
    }

    $default = $splide->id() == 'default';
    if (!$default && !$form_state->isValueEmpty('optimized')) {
      $defaults = Splide::defaultSettings();
      $required = $this->getOptionsRequiredByTemplate();
      $main     = array_diff_assoc($defaults, $required);
      $settings = $form_state->getValue(['options', 'settings']);

      // Cast the values.
      $this->typecastOptionset($settings);

      // Remove wasted dependent options if disabled, empty or not.
      $splide->removeWastedDependentOptions($settings);

      $main_settings = array_diff_assoc($settings, $main);
      $splide->setSettings($main_settings);

      $responsive_options = ['options', 'breakpoints'];
      if ($breakpoints = $form_state->getValue($responsive_options)) {
        foreach ($breakpoints as $delta => &$responsive) {
          if (!empty($responsive['unsplide'])) {
            $splide->setResponsiveSettings([], $delta);
          }
          else {
            $this->typecastOptionset($responsive['settings']);
            $splide->removeWastedDependentOptions($responsive['settings']);

            $responsive_settings = array_diff_assoc($responsive['settings'], $defaults);
            $splide->setResponsiveSettings($responsive_settings, $delta);
          }
        }
      }
    }
  }

  /**
   * Defines available options for the main and responsive settings.
   *
   * @return array
   *   All available Splide options.
   *
   * @see https://github.com/Splidejs/splide
   */
  protected function getFormElements() {
    if (!isset($this->formElements)) {
      $elements = [];

      $transitions = $this->getDescriptionsAndOptions($this->manager->getTransitionTypes());
      $types = $transitions['types'];
      $text = $transitions['text'];

      $elements['type'] = [
        'type'        => 'select',
        'title'       => $this->t('Type'),
        'description' => $this->t('Determine a slider type, accepting: @text Be warned! Only <b>slide</b> and <b>loop</b> make sense for thumbnail navigation.', ['@text' => new FormattableMarkup($text, [])]),
        'options'     => $types,
      ];

      $animations = $this->getDescriptionsAndOptions($this->manager->getAnimationTypes());
      $types = $animations['types'];
      $text = $animations['text'];

      $elements['animation'] = [
        'type'         => 'select',
        'title'        => $this->t('Animation'),
        'description'  => $this->t('Determine a slider animation, accepting: @text Be warned! Requires elaborate custom works managed by the provider.', [
          '@text' => $text ? new FormattableMarkup($text, []) : '',
        ]),
        'options'      => $types,
        'access'       => count($types) > 0,
        'empty_option' => $this->t('- None -'),
      ];

      $elements['isNavigation'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Is navigation'),
        'description' => $this->t('Determine if a slider is navigation for another. Requires `slideFocus`.'),
      ];

      $media_queries = ['min', 'max'];
      $elements['mediaQuery'] = [
        'type'         => 'select',
        'title'        => $this->t('Media query'),
        'options'      => array_combine($media_queries, $media_queries),
        'description'  => $this->t('If min, the media query for breakpoints will be min-width, or otherwise max-width.'),
      ];

      $directions = ['ltr', 'rtl', 'ttb'];
      $elements['direction'] = [
        'type'        => 'select',
        'title'       => $this->t('Direction'),
        'options'     => array_combine($directions, $directions),
        'description' => $this->t("Slider direction. <ol><li><b>ltr</b>: Left to right.</li><li><b>rtl</b>: Right to left.</li><li><b>ttb</b>: Top to bottom aka Vertical, requires `height` or `heightRatio` option. For flexible, fixed amount, non-moving TTB like vertical tabs, try: <em><br>height: auto <br>heightRatio: 0 <br>fixedHeight: 64, or any number</em></li></ol>"),
      ];

      $arrows = ['false', 'true'];
      $elements['arrows'] = [
        'type'         => 'select',
        'title'        => $this->t('Arrows'),
        'options'      => array_combine($arrows, $arrows),
        'description'  => $this->t("Whether to append arrows. True or false. The `slider` option was removed in v4."),
      ];

      $elements['arrowPath'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Arrow path'),
        'description'  => $this->t("Change the arrow SVG path. The arrow SVG path like 'm7.61 0.807-2.12…'. SVG size must be 40×40."),
      ];

      $elements['down'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Use arrow down'),
        'description' => $this->t('Arrow down to scroll down into a certain page section. Be sure to provide its target selector.'),
      ];

      $elements['downTarget'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Arrow down target'),
        'description' => $this->t('Valid CSS selector to scroll to, e.g.: #main, or #content.'),
      ];

      $elements['downOffset'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Arrow down offset'),
        'description'  => $this->t('Offset when scrolled down from the top.'),
        'field_suffix' => 'px',
      ];

      $elements['pagination'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Pagination'),
        'description' => $this->t('True, false, or .splide__arrows. The `slider` option was removed in v4. The module supports <strong>.splide__arrows</strong> to achieve this style: <br />&lt; o o o o o o o &gt;<br />Be sure to enable Arrows in such a case.'),
      ];

      $elements['omitEnd'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Omit end'),
        'description' => $this->t('If true, omits some pagination dots which just changes the active slide and do not move a carousel. Also, disables a next arrow when a carousel reaches the last page. To use this option: <ul><li>The <strong>type</strong> must be <strong>slide</strong> (or undefined)</li><li>The focus option must be specified.</li></ul>'),
      ];

      $elements['paginationKeyboard'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pagination keyboard'),
        'description' => $this->t('Determines whether to enable keyboard shortcuts for pagination when it contains focus. This is required for <a href="@url">W3C Carousel Design Pattern</a>.', [
          '@url' => 'https://splidejs.com/guides/accessibility/#keyboard-interaction',
        ]),
      ];

      $dirs = ['ltr', 'rtl', 'ttb'];
      $elements['paginationDirection'] = [
        'type'         => 'select',
        'title'        => $this->t('Pagination direction'),
        'options'      => array_combine($dirs, $dirs),
        'description'  => $this->t("Explicitly sets the pagination direction that does not only affect appearance but also shortcuts and ARIA attributes. The default value is determined by the carousel direction."),
      ];

      $elements['width'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Width'),
        'description'  => $this->t('Defines the slider max width, accepting the CSS format such as 10em, 80vw, 80%, etc. Applied to <strong>.splide</strong> element as `max-width`. Only useful if you are not CSS guy.'),
      ];

      $elements['height'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Height'),
        'description'  => $this->t('Defines the slider height, accepting the CSS format except for %. Required for Vertical aka Direction TTB, see Direction for details. Applied to <strong>.splide__track</strong> element. Only useful if you are not CSS guy.'),
      ];

      $elements['fixedWidth'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Fixed width'),
        'description'  => $this->t('Fixes width of slides, accepting the CSS format. The slider will ignore the perPage option if you provide this value. Applied to <strong>.splide__slide</strong> element. Only useful if you are not CSS guy.'),
      ];

      $elements['fixedHeight'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Fixed height'),
        'description'  => $this->t('Fixes height of slides, accepting the CSS format except for %. The slider will ignore height and heightRatio options if you provide this value. Applied to <strong>.splide__slide</strong> element. Only useful if you are not CSS guy.'),
      ];

      $elements['heightRatio'] = [
        'type'         => 'number',
        'step'         => '0.01',
        'title'        => $this->t('Height ratio'),
        'description'  => $this->t('Determines height of slides by the ratio to the slider width. For example, when the slider width is 1000 and the ratio is 0.3, the height will be 300.'),
      ];

      $elements['autoWidth'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Auto width'),
        'description' => $this->t('If true, the width of slides are determined by their width. The perPage and perMove options should be 1.'),
      ];

      $elements['autoHeight'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Auto height'),
        'description' => $this->t('If true, the height of slides are determined by their height. The perPage and perMove options should be 1.'),
      ];

      $elements['gap'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Gap'),
        'description'  => $this->t('Gap between slides. CSS format is allowed such as 1em.'),
      ];

      $elements['padding'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Padding'),
        'description'  => $this->t("Set padding-left/right in horizontal mode or padding-top/bottom in vertical one. E.g.: <code>{ 'left' : 0, 'right': '2rem' }</code>, including braces _only for multiple values. Must be a valid JSON object. Or just <code>10</code> for single value without braces, meaning padding left/right(top/bottom) will be 10px."),
      ];

      $elements['perPage'] = [
        'type'         => 'number',
        'title'        => $this->t('Per page'),
        'description'  => $this->t('Determine how many slides should be displayed per page.'),
      ];

      $elements['perMove'] = [
        'type'         => 'number',
        'title'        => $this->t('Per move'),
        'description'  => $this->t('Determine how many slides should be moved when a slider goes to next or previous page. To display all indicator dots, set focus to 0.'),
      ];

      $elements['clones'] = [
        'type'         => 'number',
        'title'        => $this->t('Clones'),
        'description'  => $this->t('Related to perPage > 1. Manually determine how many clones should be generated on one slide. The total number of clones will be twice of this for both sides. Simply said, if perPage 7, put 3 (half of perPage), etc. Since v4, you have to modify this value, else empty LHS slides.'),
      ];

      $elements['cloneStatus'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Clone status'),
        'description' => $this->t('Determines whether to add is-active class to clones or not. Disable, or it may look ugly when having less slides with multiple is-active clones at once.'),
      ];

      $elements['start'] = [
        'type'         => 'number',
        'title'        => $this->t('Start index'),
        'description'  => $this->t('The initial slide, 0-based.'),
      ];

      // @todo change into select option: false, true, pause?
      $elements['autoplay'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Autoplay'),
        'description' => $this->t('Enables autoplay. With the Intersection extension, you can enable autoplay only when the carousel is in the viewport.'),
      ];

      $elements['interval'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Autoplay interval'),
        'description' => $this->t('Autoplay interval in milliseconds.'),
      ];

      $elements['pauseOnHover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on hover'),
        'description' => $this->t('Whether to pause autoplay when a slider is hovered. The pauseOnHover option should be false (unchecked) if a slider has a pause button since autoplay will be stopped before clicking the button.'),
      ];

      $elements['pauseOnFocus'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on focus'),
        'description' => $this->t('Whether to pause autoplay when elements inside a slider are focused. Checked (true) is recommended for accessibility.'),
      ];

      $elements['progress'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Show progress bar'),
        'description' => $this->t('Whether to show progress indicator of the autoplay timer. By default, Splide rewinds the progress once it is interrupted. You can keep the elapsed time by setting the `resetProgress` option to false.'),
      ];

      $elements['resetProgress'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Reset progress'),
        'description' => $this->t('Whether to reset progress of the autoplay timer when resumed. Uncheck to keep the elapsed time.'),
      ];

      $elements['rewind'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Rewind'),
        'description' => $this->t('Whether to rewind a slider before the first slide or after the last one. This does not work in the loop mode.'),
      ];

      $elements['rewindByDrag'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Rewind by drag'),
        'description' => $this->t('Allows users to rewind a carousel by drag. The rewind option must be true.'),
      ];

      $elements['rewindSpeed'] = [
        'type'         => 'number',
        'title'        => $this->t('Rewind speed'),
        'description'  => $this->t('Transition speed on rewind in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['speed'] = [
        'type'         => 'number',
        'title'        => $this->t('Speed'),
        'description'  => $this->t('Transition speed in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['waitForTransition'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Wait for transition'),
        'description' => $this->t('Whether to prevent any actions while a slider is transitioning.'),
      ];

      $elements['focus'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Focus'),
        'description'  => $this->t('Determine which slide should be focused if there are multiple slides in a page. `center` is acceptable for centering an active slide. If you are not comfortable with empty spaces before the first slide and after the last one, enable <b>trimSpace</b> option (it is true as default).'),
      ];

      $elements['easing'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Easing'),
        'description' => $this->t('Animation timing function for CSS transition. CSS3 animation easing. <a href="@ceaser">Learn</a> <a href="@bezier">more</a>. Ignored if <strong>CSS ease override</strong> is provided.', [
          '@ceaser' => '//matthewlein.com/ceaser/',
          '@bezier' => '//cubic-bezier.com',
        ]),
      ];

      $elements['easingOverride'] = [
        'title'        => $this->t('Easing override'),
        'type'         => 'select',
        'options'      => $this->getCssEasingOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t('If provided, this will override the Easing with the pre-defined CSS easings based on <a href="@ceaser">CSS Easing Animation Tool</a>. Leave it empty to use your own CSS ease.', ['@ceaser' => 'https://matthewlein.com/ceaser/']),
      ];

      $elements['randomize'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Randomize'),
        'description' => $this->t('Randomize the slide display, useful to manipulate cached blocks.'),
      ];

      $keyboards = ['false', 'true', 'global', 'focused'];
      $elements['keyboard'] = [
        'type'        => 'select',
        'title'       => $this->t('Keyboard'),
        'options'     => array_combine($keyboards, $keyboards),
        'description' => $this->t("Whether to control a slider via keyboard. <br><b>true</b>: Enables shortcuts only when a carousel contains focus. <br><b>global</b>:	Enables shortcuts globally, listening to the window keydown event (discouraged). <br><b>focused</b>: Same with true. <br><b>false</b>: Disable keyboard shortcuts."),
      ];

      $drags = ['false', 'true', 'free'];
      $elements['drag'] = [
        'type'        => 'select',
        'title'       => $this->t('Drag'),
        'options'     => array_combine($drags, $drags),
        'description' => $this->t('Whether to allow mouse drag and touch swipe.'),
      ];

      $elements['snap'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Snap'),
        'description' => $this->t('Snaps the closest slide on drag free mode.'),
      ];

      $elements['noDrag'] = [
        'type'        => 'textfield',
        'title'       => $this->t('No drag selectors'),
        'description' => $this->t('The selector for nodes that cannot be dragged, e.g.: <strong>input, textarea, .no-drag</strong>.'),
      ];

      $elements['dragMinThreshold'] = [
        'type'        => 'number',
        'title'       => $this->t('Drag min threshold'),
        'description' => $this->t('The required distance to start moving the slider by the touch action. Other pointing devices will ignore this value.'),
      ];

      $elements['flickVelocityThreshold'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Flick velocity threshold'),
        'description' => $this->t('Velocity threshold for determining if a drag action is `flick` or `swipe`. Around 0.5 is recommended.'),
      ];

      $elements['flickPower'] = [
        'type'        => 'number',
        'title'       => $this->t('Flick power'),
        'description' => $this->t('Determine power of flick. The larger number this is, the farther a slider runs by flick.'),
      ];

      $elements['flickMaxPages'] = [
        'type'        => 'number',
        'title'       => $this->t('Flick max pages'),
        'description' => $this->t('Limit a number of pages to move by flick.'),
      ];

      $elements['cover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Cover'),
        'description' => $this->t('Set img src to background-image of its parent element. Images with various sizes can be displayed as same dimension without troublesome cropping work. height, fixedHeight or heightRatio is required. This option does not support the srcset aka Responsive image. Overriden by formatters, use CSS background option for more advanced usages like Responsive image, etc. instead.'),
      ];

      $elements['slideFocus'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Slide focus'),
        'description' => $this->t('Must be checked/ true if `isNavigation` checked, or unchecked/ false for normal carousels. As a result of other accessibility improvements, slides in a normal carousel do not need to be focusable (not 100% sure), whereas slides in a thumbnail carousel with isNavigation still needs to, since <a href=":url">clickable elements must be focusable</a>.', [':url' => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/Understanding_WCAG/Keyboard#clickable_elements_must_be_focusable_and_should_have_interactive_semantics']),
      ];

      $elements['focusableNodes'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Focusable nodes'),
        'description' => $this->t('The selector to find focusable elements inside slides. When a slide gets hidden (has aria-hidden="true"), Splide assigns tabindex="-1" to its descendant focusable elements for accessibility.'),
      ];

      $trims = ['false', 'true', 'move'];
      $elements['trimSpace'] = [
        'type'        => 'select',
        'title'       => $this->t('Trim space'),
        'options'     => array_combine($trims, $trims),
        'description' => $this->t("Whether to trim spaces before the first slide or after the last one. <br><b>false</b>: Allow spaces. <br><b>true</b>: Default. Remove spaces but sometimes the slider doesn’t move even when the active index is updated. <br><b>move</b>: Remove spaces and the slider always move when the active index is updated. This mode is not compatible with pagination(indicator dots)."),
      ];

      $elements['live'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Live'),
        'description' => $this->t("Enables a <a href=':url'>live region</a>. If isNavigation is enabled, Splide won't activate it.", [
          ':url' => 'https://splidejs.com/guides/accessibility/#live-region',
        ]),
      ];

      $elements['updateOnMove'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Update on move'),
        'description' => $this->t("The <code>is-active</code> class is added after transition is completed (the “moved” event) by default. If checked, it will be added before transition. <br>This doesn’t perfectly work when a slide and its clone are shown at the same time. For example, when number of slides is less than perPage or using fixedWidth with a few slides, you will see 2 (or more) active slides while transitioning."),
      ];

      // Module features, already in/ managed by library since v3.
      $elements['wheel'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Mouse wheel'),
        'description' => $this->t('Requires <code>waitForTransition</code> set to true and/or give <code>wheelSleep</code> duration, otherwise your carousel will immediately reach the end. Will use native mouse wheel event, see <a href=":url">Mouse wheel</a>.', [
          ':url' => 'https://developer.mozilla.org/en-US/docs/Web/API/WheelEvent',
        ]),
      ];

      $elements['releaseWheel'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Release wheel'),
        'description' => $this->t('Determines whether to release the wheel event when the carousel reaches the first or last slide so that the user can scroll the page continuously.'),
      ];

      $elements['wheelSleep'] = [
        'type'        => 'number',
        'title'       => $this->t('Wheel sleep'),
        'description' => $this->t('The sleep duration in milliseconds until accepting next wheel. The timer starts when the transition begins.'),
      ];

      $elements['wheelMinThreshold'] = [
        'type'        => 'number',
        'title'       => $this->t('Wheel min threshold'),
        'description' => $this->t('The threshold to cut off the small delta produced by inertia scroll.'),
      ];

      $elements['classes'] = [
        'type'        => 'textarea',
        'title'       => $this->t('Classes'),
        'description' => $this->t("Collection of class names. To add your own classes to arrows or pagination buttons, provide them with original classes like this: <b><br><br>{ <br>'arrows': 'splide__arrows your-class-arrows', <br>'arrow': 'splide__arrow your-class-arrow', <br>'prev': 'splide__arrow--prev your-class-prev', <br>'next': 'splide__arrow--next your-class-next', <br>'pagination': 'splide__pagination your-class-pagination', <br>'page': 'splide__pagination__page your-class-page' <br>}</b><br><br>Including braces. Must be a valid JSON object like the sample. Be sure the original class (the first one) is always included to avoid broken displays. <strong>Warning!</strong> Trailing commas break JSON."),
      ];

      $elements['i18n'] = [
        'type'        => 'textarea',
        'title'       => $this->t('i18n'),
        'description' => $this->t("Here is a list of default texts. `%s` will be replaced by a slide or page number: <b><br><br>{ <br>'prev': 'Previous slide', <br>'next': 'Next slide', <br>'first': 'Go to first slide', <br>'last': 'Go to last slide', <br>'slideX': 'Go to slide %s', <br>'pageX': 'Go to page %s', <br>'play': 'Start autoplay', <br>'pause': 'Pause autoplay' <br>}</b><br><br>Including braces. Must be a valid JSON object like the sample. <strong>Warning!</strong> Trailing commas break JSON."),
      ];

      $elements['autoScroll'] = [
        'type'        => 'textarea',
        'title'       => $this->t('Auto Scroll'),
        'description' => $this->t("Enable AutoScroll plugin and set options. Example: <b><br><br>{ <br>'speed': 1, <br>'autoStart': true, <br>'rewind': false, <br>'pauseOnHover': true,<br>'pauseOnFocus': true<br>}</b><br><br>Including braces. Must be a valid JSON object like the sample. Best with: <code>type: 'loop', drag: 'free', focus: 'center', perPage: 3</code>. Requires <br><code>/libraries/splidejs--splide-extension-auto-scroll/dist/js/splide-extension-auto-scroll.min.js</code> or <br><code>/libraries/splide-extension-auto-scroll/dist/js/splide-extension-auto-scroll.min.js</code> <a href=':url'>here</a>. <strong>Warning!</strong> Trailing commas break JSON.", [':url' => 'https://github.com/Splidejs/splide-extension-auto-scroll']),
      ];

      $elements['intersection'] = [
        'type'        => 'textarea',
        'title'       => $this->t('Intersection'),
        'description' => $this->t("Enable Intersection plugin and set options. Example: <b><br><br>{ <br>'inView': { 'autoplay': true, 'autoScroll': true }, <br>'outView': { 'autoplay': false, 'autoScroll': false }<br>}</b><br><br>Including braces. Must be a valid JSON object like the sample. Best to trigger: <code>autoplay autoScroll keyboard</code> when being intersected in viewport. Requires <br><code>/libraries/splidejs--splide-extension-intersection/dist/js/splide-extension-intersection.min.js</code> or <br><code>/libraries/splide-extension-intersection/dist/js/splide-extension-intersection.min.js</code> <a href=':url'>here</a>. <strong>Warning!</strong> Trailing commas break JSON.", [':url' => 'https://github.com/Splidejs/splide-extension-intersection/']),
      ];

      // Defines the default values if available.
      $defaults = Splide::defaultSettings();
      foreach ($elements as $name => $element) {
        $checkbox = $element['type'] == 'checkbox';
        $default  = $checkbox ? FALSE : '';
        $value    = $defaults[$name] ?? $default;
        $value    = is_string($value) ? strip_tags($value) : $value;

        $elements[$name]['default'] = $value;

        if (isset($elements[$name]['description'])) {
          $elements[$name]['description'] .= $this->getDefaultValue($value, $checkbox);
        }
      }

      foreach (Splide::getDependentOptions() as $parent => $items) {
        foreach ($items as $name) {
          if (isset($elements[$name])) {
            // Arrows, drag is not a checkbox, can be boolean or string.
            if (in_array($parent, ['arrows', 'drag'])) {
              $states = ['visible' => ['select[name*="options[settings][' . $parent . ']"]' => ['!value' => 'false']]];
            }
            elseif (in_array($parent, ['pagination'])) {
              $states = ['visible' => ['input[name*="options[settings][' . $parent . ']"]' => ['!value' => 'false']]];
            }
            else {
              $states = ['visible' => [':input[name*="options[settings][' . $parent . ']"]' => ['checked' => TRUE]]];
            }

            if (!isset($elements[$name]['states'])) {
              $elements[$name]['states'] = $states;
            }
            else {
              $elements[$name]['states'] = array_merge($elements[$name]['states'], $states);
            }
          }
        }
      }

      $this->formElements = $elements;
    }

    return $this->formElements;
  }

  /**
   * Defines available options for the responsive Splide.
   *
   * @param int $count
   *   The number of breakpoints.
   *
   * @return array
   *   An array of Splide responsive options.
   */
  protected function getResponsiveFormElements($count = 0) {
    $elements = [];
    $range = range(0, ($count - 1));
    $breakpoints = array_combine($range, $range);

    foreach ($breakpoints as $key => $breakpoint) {
      $elements[$key] = [
        'type'  => 'details',
        'title' => $this->t('Breakpoint #@key', ['@key' => ($key + 1)]),
      ];

      $elements[$key]['breakpoint'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Breakpoint'),
        'description'  => $this->t('Breakpoint width in pixel.'),
        'default'      => '',
        'field_suffix' => 'px',
      ];

      $elements[$key]['unsplide'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Destroy'),
        'description' => $this->t("Disable Splide at a given breakpoint. Note, you can't window shrink this, once you destroy, you are destroyed."),
        'default'     => FALSE,
      ];

      $elements[$key]['settings'] = [
        'type'  => 'details',
        'title' => $this->t('Settings'),
      ];

      // Duplicate relevant main settings.
      $valid = SplideDefault::validBreakpointOptions();
      $valid = array_combine($valid, $valid);
      foreach ($this->getFormElements() as $name => $responsive) {
        if (!isset($valid[$name])) {
          continue;
        }
        $elements[$key]['settings'][$name] = $responsive;
      }
    }
    return $elements;
  }

  /**
   * Defines options required by theme_splide(), used with optimized option.
   */
  protected function getOptionsRequiredByTemplate() {
    $options = [
      'perPage' => 0,
    ];

    $this->manager->moduleHandler()->alter('splide_options_required_by_template', $options);
    return $options;
  }

  /**
   * Returns default value.
   */
  private function getDefaultValue($value, $checkbox): string {
    $empty = !$checkbox && empty($value) && $value != '0';
    $value = var_export($value, TRUE);

    if ($empty) {
      $value = $this->t('None');
    }

    return '<br><em>' . $this->t('Default: @value', [
      '@value' => $value,
    ]) . '</em>';
  }

  /**
   * Returns form items to have tooltip bottom.
   */
  private function tooltipBottom(): array {
    return [
      'animation',
      'type',
      'mediaQuery',
      'direction',
      'isNavigation',
      'pagination',
      'arrows',
      'rewind',
      'speed',
      'width',
    ];
  }

  /**
   * Returns descriptions and options.
   */
  private function getDescriptionsAndOptions(array $features): array {
    $types = [];
    $text = '';
    if ($features) {
      $text = '<ol>';
      foreach ($features as $key => $type) {
        $label = $type;
        $desc = '';

        if (is_array($type)) {
          $label = $type['label'] ?? 'x';
          $desc = $type['description'] ?? '';

          if ($provider = $type['provider'] ?? '') {
            $desc .= '<br>' . $this->t('Provider: @name.', [
              '@name' => $provider,
            ]);
          }
        }

        $types[$key] = $label;
        $info = $desc;
        $text .= "<li><b>{$label}</b>{$info}</li>";
      }
      $text .= '</ol>';
    }
    return ['text' => $text, 'types' => $types];
  }

  /**
   * Returns modifiable lazyload options.
   *
   * @todo remove post recommended release 2.0.1.
   */
  protected function getLazyloadOptions() {
    return [];
  }

}
