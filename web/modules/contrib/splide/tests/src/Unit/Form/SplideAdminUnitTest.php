<?php

namespace Drupal\Tests\splide\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\splide\Form\SplideAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Splide admin form.
 *
 * @coversDefaultClass \Drupal\splide\Form\SplideAdmin
 * @group splide
 */
class SplideAdminUnitTest extends UnitTestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The splide admin service.
   *
   * @var \Drupal\splide\Form\SplideAdminInterface
   */
  protected $splideAdmin;

  /**
   * The splide manager service.
   *
   * @var \Drupal\splide\SplideManagerInterface
   */
  protected $splideManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityFieldManager = $this->createMock('\Drupal\Core\Entity\EntityFieldManagerInterface');
    $this->blazyAdmin = $this->createMock('\Drupal\blazy\Form\BlazyAdminInterface');
    $this->splideManager = $this->createMock('\Drupal\splide\SplideManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::blazyAdmin
   * @covers ::manager
   */
  public function testBlazyAdminCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['entity_field.manager', $exception, $this->entityFieldManager],
      ['blazy.admin.formatter', $exception, $this->blazyAdmin],
      ['splide.manager', $exception, $this->splideManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $splideAdmin = SplideAdmin::create($container);
    $this->assertInstanceOf(SplideAdmin::class, $splideAdmin);
    $this->assertInstanceOf('\Drupal\blazy\Form\BlazyAdminInterface', $splideAdmin->blazyAdmin());
    $this->assertInstanceOf('\Drupal\splide\SplideManagerInterface', $splideAdmin->manager());
  }

}
