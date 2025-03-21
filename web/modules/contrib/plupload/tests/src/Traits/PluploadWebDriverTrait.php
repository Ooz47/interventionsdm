<?php

namespace Drupal\Tests\plupload\Traits;

/**
 * Base class for Plupload Web driver functional test base.
 *
 * @group plupload
 */
trait PluploadWebDriverTrait {

  /**
   * Add a predefined file to Plupload upload queue.
   *
   * @param string $path
   *   The path to the file.
   * @param string $selector
   *   The selector of the Plupload widget.
   */
  protected function addFile(string $path, string $selector): void {
    $session = $this->getSession();

    // Add a fake input element.
    $input = <<<JS
      jQuery('$selector').append('<input type=\'file\' name=\'fakefile\'>');
JS;
    $session->evaluateScript($input);

    // Populate the field with uploaded file.
    $session->getPage()->attachFileToField('fakefile', $path);

    // Add to plupload queue.
    $drop = <<<JS
      jQuery('$selector').pluploadQueue().addFile(jQuery('input[name="fakefile"]')[0]);
JS;
    $session->evaluateScript($drop);
  }

  /**
   * Declare the dependence on getSession method.
   *
   * @return \Behat\Mink\Session
   *   The Mink session.
   */
  abstract public function getSession($name = NULL);

}
