<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;

/**
 * A Trait common for splide vanilla formatters.
 */
trait SplideVanillaWithNavTrait {

  /**
   * {@inheritdoc}
   */
  protected function withElementOverride(array &$build, array $element): void {
    if ($build['#asnavfor']) {
      if ($build['#vanilla']) {
        // Build media item including custom highres video thumbnail.
        // @todo re-check/ refine for Paragraphs, etc.
        $this->blazyOembed->build($element);
      }

      $this->withElementThumbnail($build, $element);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function withElementThumbnail(array &$build, array $element): void {
    if (!$build['#asnavfor']) {
      return;
    }

    // The settings in $element has updated metadata extracted from media.
    $settings  = $this->formatter->toHashtag($element);
    $entity    = $element['#entity'];
    $delta     = $element['#delta'];
    $item      = $this->formatter->toHashtag($element, 'item', NULL);
    $view_mode = $settings['view_mode'] ?? 'default';
    $_caption  = $settings['nav_caption'] ?? NULL;
    $captions  = [];

    if ($_caption) {
      if ($item && $text = trim($item->{$_caption} ?? '')) {
        $captions = ['#markup' => Xss::filterAdmin($text)];
      }
      else {
        $captions = $this->viewField($entity, $_caption, $view_mode);
      }
    }

    // Thumbnail usages: asNavFor pagers, dot, arrows, lightbox thumbnails.
    $tn = $this->formatter->getThumbnail($settings, $item, $captions);
    $build[static::$navId]['items'][$delta] = $tn;
  }

}
