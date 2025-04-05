<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use \Drupal\Core\Entity\Entity\EntityViewDisplay;
use \Drupal\Core\File\FileSystemInterface;

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
    'node',
    'field',
    'field_ui',
    'file',
    'image',
    'inline_entity_form',
    'user',
    'system',
    'filter',
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

    // Create admin user with necessary permissions
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
    // Create a test image for later use
    $this->testImagePath = $this->createTestImage();
  }

  /**
   * Fills the draggable mapper entity form.
   *
   * @param string $name
   *   The name for the entity.
   * @param array $values
   *   Additional values for the entity (optional).
   *
   * @return int|string|null
   *   The entity ID or NULL if creation failed.
   */
/*   protected function fillsEntityForm($name, array $values = []) {
    // Create a test image to use for the map
    $image_path = $this->createTestImage();
    $this->assertFileExists($image_path);
    
    // Go to the entity creation form
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->assertSession()->pageTextContains('The name of the Draggable Mapper.');
    
  // Fill in the basic information
    $this->getSession()->getPage()->findField('name[0][value]')->setValue($name);
    $this->getSession()->getPage()->attachFileToField('files[field_dme_image_0]', $image_path);
    
    // After the image is uploaded, complete the form
    $this->assertSession()->waitForField('field_dme_image[0][alt]');
    $this->getSession()->getPage()->fillField('field_dme_image[0][alt]', 'Map image for ' . $name);
    $this->getSession()->getPage()->fillField('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'Test Marker');
    
    // Add second marker
    $this->pressButton('Add Map Marker');
    $this->getSession()->getPage()->fillField('field_dme_marker[1][subform][field_dme_marker_title][0][value]', 'Test Marker 2');
    // with icon
    $this->getSession()->getPage()->attachFileToField('files[field_dme_marker_1_subform_field_dme_marker_icon_0]', $image_path);
    $this->assertSession()->waitForField('field_dme_image[1][alt]');
    $this->getSession()->getPage()->fillField('field_dme_image[1][alt]', 'Map image for ' . $name);

    // Save the entity
    $this->pressButton('Save');
    
    // Verify save was successful
    $this->assertSession()->pageTextContains('The map ' . $name . ' has been saved.');

    // Extract the entity ID 
    $query = \Drupal::entityQuery('draggable_mapper')
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);
    $entity_id = $query->execute();
    //print "CURRENT URL: " . $entity_id . "\n";
    return !empty($entity_id) ? reset($entity_id) : NULL;
  }
 */

/**
 * Fills basic entity fields common to all draggable mapper test scenarios.
 *
 * @param string $name
 *   The human-readable name for the mapper entity
 *
 * @throws \Behat\Mink\Exception\ElementNotFoundException
 *   If required form elements are missing
 */
  protected function fillsBaseFields($name) {
    // Create a test image to use for the map
    $image_path = $this->createTestImage();
    $this->assertFileExists($image_path);
    
    // Go to the entity creation form
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->assertSession()->pageTextContains('The name of the Draggable Mapper.');
    
  // Fill in the basic information
    $this->getSession()->getPage()->findField('name[0][value]')->setValue($name);
    $this->getSession()->getPage()->attachFileToField('files[field_dme_image_0]', $image_path);
    
    // After the image is uploaded, complete the form
    $this->assertSession()->waitForField('field_dme_image[0][alt]');
    $this->getSession()->getPage()->fillField('field_dme_image[0][alt]', 'Map image for ' . $name);
  }

/**
 * Adds marker entries to the draggable mapper entity form.
 * 
 * @param string $name
 *   Base name used for image alt text attributes
 *
 * @throws \Behat\Mink\Exception\ElementNotFoundException
 *   If marker form elements are missing
 * @throws \RuntimeException
 *   If image upload fails
 */
  protected function addMarkers($name) {
    // Create a test image to use for the marker
    $image_path = $this->createTestImage();
    $this->assertFileExists($image_path);

    // Add title to first marker
    $this->getSession()->getPage()->fillField('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'Test Marker');
    
    // Add second marker
    $this->pressButton('Add Map Marker');
    $this->assertSession()->waitForField('field_dme_marker[1][subform][field_dme_marker_title][0][value]');
    $this->getSession()->getPage()->fillField('field_dme_marker[1][subform][field_dme_marker_title][0][value]', 'Test Marker 2');
    // with icon
    $this->getSession()->getPage()->attachFileToField('files[field_dme_marker_1_subform_field_dme_marker_icon_0]', $image_path);
    $this->assertSession()->waitForField('field_dme_marker[1][subform][field_dme_marker_icon][0][alt]');
    $this->getSession()->getPage()->fillField('field_dme_marker[1][subform][field_dme_marker_icon][0][alt]', 'Map image for ' . $name);
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
    // Extract the entity ID 
    $query = \Drupal::entityQuery('draggable_mapper')
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);
    $entity_id = $query->execute();
    //print "CURRENT URL: " . $entity_id . "\n";
    return !empty($entity_id) ? reset($entity_id) : NULL;
  }

/**
 * Generates a test image file for form uploads in browser tests.
 *
 * @param string $filename
 *   (Optional) Target filename in public filesystem
 *
 * @return string
 *   Full system path to generated image file
 *
 * @throws \RuntimeException
 *   If image creation or file write fails
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
 *   Button text or submit value attribute
 *
 * @throws \LogicException
 *   If button cannot be found or becomes stuck in invisible state
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
 * Complete entity creation workflow facade method.
 *
 * Combines navigation, form filling, and save operations into a single
 * reusable workflow for test cases.
 *
 * @param string $name
 *   Human-readable name for the new mapper entity
 *
 * @return int|string
 *   Created entity ID
 *
 * @throws \RuntimeException
 *   If any step in the creation workflow fails
 */
  protected function createDraggableMapperEntity(string $name): int {
    // Form filling logic
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->fillsBaseFields($name);
    $this->addMarkers($name);
    $this->pressButton('Save');
    return $this->getCreatedEntityId();
  }
}
