<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for JavaScript-based tests of the draggable mapper module.
 */
abstract class DraggableMapperJsTestBase extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'draggable_mapper',
    'draggable_mapper_test',
    'node',
    'field',
    'field_ui',
    'file',
    'image',
    'inline_entity_form',
    'user',
    'system',
    'filter',
    'jquery_ui',
    'jquery_ui_draggable',
    'jquery_ui_droppable',
    'jquery_ui_resizable',
  ];

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer draggable mapper',
      'add draggable mapper',
      'edit draggable mapper',
      'delete draggable mapper',
      'view draggable mapper',
      'access content',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
    // Create a test image for later use.
    $this->testImagePath = $this->createTestImage();
  }

  /**
   * Fills basic entity fields common to all draggable mapper test scenarios.
   *
   * @param string $name
   *   The human-readable name for the mapper entity.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If required form elements are missing.
   */
  protected function fillsBaseFields($name) {
    // Create a test image to use for the map.
    $image_path = $this->createTestImage();
    $this->assertFileExists($image_path);

    // Fill in the basic information.
    $this->getSession()->getPage()->findField('name[0][value]')->setValue($name);
    $this->getSession()->getPage()->attachFileToField('files[field_dme_image_0]', $image_path);

    // After the image is uploaded, complete the form.
    $this->assertSession()->waitForField('field_dme_image[0][alt]');
    $this->getSession()->getPage()->fillField('field_dme_image[0][alt]', $name);
  }

  /**
   * Counts valid markers by checking filled title fields.
   *
   * Scans the form for marker title fields
   * and counts those with non-empty values.
   * This ensures we only consider actively used markers in our test logic.
   *
   * @return int
   *   Number of markers with non-empty titles
   */
  private function getMarkerCount(): int {
    $count = 0;
    $fields = $this->getSession()->getPage()->findAll(
        'css',
        '[name^="field_dme_marker["][name*="][subform][field_dme_marker_title][0][value]"]'
    );

    foreach ($fields as $field) {
      if (!empty(trim($field->getValue()))) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Gets the highest index of filled markers.
   *
   * Calculates the maximum index based on filled markers to determine where
   * new markers should be added. Returns 0 when no valid markers exist.
   *
   * @return int
   *   Highest index of filled markers, or 0 if none
   */
  protected function getCurrentIndex(): int {
    $count = $this->getMarkerCount();
    return $count;
  }

  /**
   * Adds new marker fields.
   *
   * Presses the "Add Map Marker" button .
   *
   * @param int $index
   *   Target marker index to ensure it is nor the default marker.
   */
  private function pressButtonIfNeeded(int $index) {
    if ($index > 0) {
      $this->pressButton('Add Map Marker');
      $this->assertSession()->waitForField(
            "field_dme_marker[' . $index . '][subform][field_dme_marker_title][0][value]"
        );
    }
  }

  /**
   * Adds text marker to the draggable mapper entity form.
   *
   * @param string $title
   *   Base name used for marker title.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If marker form elements are missing.
   */
  protected function addTextMarker(string $title) {
    $index = $this->getCurrentIndex();
    $this->pressButtonIfNeeded($index);
    $this->getSession()->getPage()->fillField(
      'field_dme_marker[' . $index . '][subform][field_dme_marker_title][0][value]',
      $title
    );
  }

  /**
   * Adds icon marker to the draggable mapper entity form.
   *
   * @param string $title
   *   Base name used for marker title.
   * @param string $alt_text
   *   Base name used for image alt text attributes.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If marker form elements are missing.
   *   If image upload fails.
   */
  protected function addIconMarker(string $title, string $alt_text) {
    $index = $this->getCurrentIndex();
    $this->pressButtonIfNeeded($index);

    $this->assertSession()->waitForField(
        'field_dme_marker[' . $index . '][subform][field_dme_marker_title][0][value]'
    );
    $this->getSession()->getPage()->fillField(
        'field_dme_marker[' . $index . '][subform][field_dme_marker_title][0][value]',
        $title
    );
    $this->getSession()->getPage()->attachFileToField(
        'files[field_dme_marker_' . $index . '_subform_field_dme_marker_icon_0]',
        $this->createTestImage()
    );
    $this->assertSession()->waitForField(
        'field_dme_marker[' . $index . '][subform][field_dme_marker_icon][0][alt]'
    );
    $this->getSession()->getPage()->fillField(
        'field_dme_marker[' . $index . '][subform][field_dme_marker_icon][0][alt]', $alt_text
    );

    // Wait for final AJAX completion.
    $this->assertSession()->waitForElementRemoved('css', '.ajax-progress');
  }

  /**
   * Retrieves the ID of the most recently created draggable mapper entity.
   *
   * @return int|string|null
   *   The entity ID, or NULL if no matching entity exists
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getCreatedEntityId() {

    // Extract the entity ID.
    $query = \Drupal::entityQuery('draggable_mapper')
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);
    $entity_id = $query->execute();
    return !empty($entity_id) ? reset($entity_id) : NULL;
  }

  /**
   * Generates a test image file for form uploads in browser tests.
   *
   * @param string $filename
   *   (Optional) Target filename in public filesystem.
   *
   * @return string
   *   Full system path to generated image file.
   *
   * @throws \RuntimeException
   *   If image creation or file write fails.
   */
  protected function createTestImage(string $filename = 'test_image.png'): string {

    // Create image.
    $image = imagecreatetruecolor(100, 100);
    $bg_color = imagecolorallocate($image, 220, 220, 220);
    imagefill($image, 0, 0, $bg_color);

    // Save to a temp directory accessible to both test process and WebDriver.
    $public_files_path = \Drupal::service('file_system')->realpath('public://');
    $file_path = $public_files_path . '/' . $filename;
    imagepng($image, $file_path);

    // Clean up memory only (NOT the file).
    imagedestroy($image);

    return $file_path;
  }

  /**
   * Robust button interaction handler for JavaScript-enhanced forms.
   *
   * @param string $label
   *   Button text or submit value attribute.
   *
   * @throws \LogicException
   *   If button cannot be found or becomes stuck in invisible state.
   */
  protected function pressButton(string $label): void {
    $session = $this->getSession();
    $page = $session->getPage();

    // Wait up to 5 seconds until the button is attached to the DOM.
    $session->wait(5000, sprintf(
      'document.querySelector("input[type=submit][value=\'%s\']") !== null',
      addslashes($label)
    ));

    $button = $page->findButton($label);
    $this->assertNotNull($button, sprintf('Button "%s" is present on the page.', $label));
    $this->assertTrue($button->isVisible(), sprintf('Button "%s" is visible.', $label));

    // Now safely click it.
    $button->click();
  }

  /**
   * Ensures Drupal behaviors are attached and waits for initialization.
   *
   * Use this when testing JavaScript interactions
   * that depend on Drupal.behaviors
   * being properly initialized. Provides error checking and debug logging.
   */
  protected function ensureDrupalBehaviors() {
    // Check Drupal object existence.
    $this->assertTrue($this->getSession()->evaluateScript(
        "return typeof Drupal !== 'undefined'"
    ), 'Drupal object exists');

    // Force behaviors attachment.
    $this->getSession()->executeScript(<<<JS
            (function() {
                console.log('Attaching Drupal behaviors programmatically');
                if (Drupal.behaviors) {
                    Drupal.attachBehaviors(document, drupalSettings);
                }
            })();
        JS);

    // Wait for behaviors to complete.
    $this->getSession()->wait(1000);
  }

  /**
   * Simulates dragging a marker to specified coordinates.
   *
   * @param int $markerIndex
   *   Zero-based index of the marker to drag.
   * @param int $x
   *   X coordinate relative to container.
   * @param int $y
   *   Y coordinate relative to container.
   * @param int $offset
   *   Optional pixel offset from exact coordinates.
   */
  protected function simulateMarkerDrag(int $markerIndex, int $x, int $y, int $offset = 5): void {
    $script = <<<JS
            (function(markerIndex, x, y, offset) {
                const marker = document.getElementById(`dme-marker-\${markerIndex}`);
                const container = document.getElementById('dme-container-wrapper');
                
                if (!marker || !container) {
                    console.error('Marker or container not found');
                    return false;
                }

                // Calculate positions.
                const markerRect = marker.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                
                // Create and dispatch events.
                const dragStart = new MouseEvent('mousedown', {
                    bubbles: true,
                    clientX: markerRect.left + offset,
                    clientY: markerRect.top + offset
                });
                marker.dispatchEvent(dragStart);

                const dragOver = new MouseEvent('mousemove', {
                    bubbles: true,
                    clientX: containerRect.left + x + offset,
                    clientY: containerRect.top + y + offset
                });
                document.dispatchEvent(dragOver);

                const drop = new MouseEvent('mouseup', {
                    bubbles: true,
                    clientX: containerRect.left + x + offset,
                    clientY: containerRect.top + y + offset
                });
                container.dispatchEvent(drop);
                
                return true;
            })($markerIndex, $x, $y, $offset);
        JS;

    $success = $this->getSession()->evaluateScript($script);
    $this->assertTrue($success, "Drag simulation for marker $markerIndex failed");

    // Wait for coordinates to update.
    $this->assertSession()->waitForElement(
        'css',
        "[name='field_dme_marker[{$markerIndex}][subform][field_dme_marker_coordinates][0][value]']"
    );
  }

  /**
   * Gets the relative coordinates of a marker within its container.
   *
   * @param int $markerIndex
   *   Zero-based index of the marker.
   * @param int $precision
   *   Decimal precision for coordinates (default: 4)
   *
   * @return array
   *   Associative array with keys 'x' and 'y' containing decimal coordinates
   */
  protected function getRelativeMarkerCoordinates(int $markerIndex, int $precision = 4): array {
    $script = <<<JS
            (function(markerIndex) {
                const container = document.getElementById('dme-container-wrapper');
                const marker = document.getElementById(`dme-marker-\${markerIndex}`);
                
                if (!container || !marker) {
                    console.error('Container or marker not found');
                    return [0, 0, 0, 0];
                }

                const containerRect = container.getBoundingClientRect();
                const markerRect = marker.getBoundingClientRect();
                
                return [
                    containerRect.width,
                    containerRect.height,
                    markerRect.left - containerRect.left,
                    markerRect.top - containerRect.top
                ];
            })($markerIndex);
        JS;

    [$width, $height, $x, $y] = $this->getSession()->evaluateScript($script);

    return [
      'x' => round($x / $width, $precision),
      'y' => round($y / $height, $precision),
    ];
  }

  /**
   * Loads and verifies marker coordinates for a draggable mapper entity.
   *
   * @param int $entityId
   *   The entity ID to load.
   * @param int $markerIndex
   *   Zero-based index of the marker to check.
   *
   * @return array
   *   Associative array with 'x' and 'y' coordinate values
   */
  protected function getSavedMarkerCoordinates(int $entityId, int $markerIndex = 0): array {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('draggable_mapper')
      ->load($entityId);

    $this->assertNotEmpty($entity, "Entity $entityId loaded successfully");

    $markers = $entity->get('field_dme_marker')->referencedEntities();
    $this->assertNotEmpty(
        $markers,
        "No markers found on entity $entityId"
    );
    $this->assertArrayHasKey(
        $markerIndex,
        $markers,
        "Marker index $markerIndex not found"
    );

    $marker = $markers[$markerIndex];
    $x = $marker->get('field_dme_marker_x')->value;
    $y = $marker->get('field_dme_marker_y')->value;

    $this->assertNotEmpty($x, "X coordinate missing for marker $markerIndex");
    $this->assertNotEmpty($y, "Y coordinate missing for marker $markerIndex");

    return [
      'x' => (float) $x,
      'y' => (float) $y,
    ];
  }

  /**
   * Asserts that actual coordinates match expected values within a delta.
   *
   * @param array $expected
   *   Associative array with 'x' and 'y' keys.
   * @param array $actual
   *   Associative array with 'x' and 'y' keys.
   * @param float $delta
   *   Allowed margin of error.
   */
  protected function assertCoordinatesMatch(
    array $expected,
    array $actual,
    float $delta = 0.005,
  ): void {
    $this->assertEqualsWithDelta(
        $expected['x'],
        $actual['x'],
        $delta,
        'X coordinate matches expected value'
    );

    $this->assertEqualsWithDelta(
        $expected['y'],
        $actual['y'],
        $delta,
        'Y coordinate matches expected value'
    );
  }

}
