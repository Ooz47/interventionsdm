<?php

namespace Drupal\Tests\splide\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\splide\Entity\Splide;
use Drupal\splide\SplideDefault;

/**
 * @coversDefaultClass \Drupal\splide\Entity\Splide
 *
 * @group splide
 */
class SplideUnitTest extends UnitTestCase {

  /**
   * Tests for splide entity methods.
   *
   * @covers \Drupal\splide\SplideDefault::jsSettings
   * @covers ::getDependentOptions
   */
  public function testSplideEntity() {
    $js_settings = SplideDefault::jsSettings();
    $this->assertArrayHasKey('perPage', $js_settings);

    $dependent_options = Splide::getDependentOptions();
    $this->assertArrayHasKey('arrows', $dependent_options);
  }

}
