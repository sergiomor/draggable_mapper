<?php

namespace Drupal\Tests\draggable_mapper\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Base class for draggable mapper tests.
 */
abstract class DraggableMapperTestBase extends BrowserTestBase {

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
   * Path to a test image.
   *
   * @var string
   */
  protected $testImagePath;

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
   * Creates a basic draggable mapper entity through the UI.
   *
   * @param string $name
   *   The name for the entity.
   * @param array $values
   *   Additional values for the entity (optional).
   *
   * @return int|string|null
   *   The entity ID or NULL if creation failed.
   */
  protected function createBasicEntity($name, array $values = []) {
    // Create a test image to use for the map
    $image_path = $this->createTestImage();
    
    // Go to the entity creation form
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->assertSession()->statusCodeEquals(200);
    
    // Fill in the basic information
    $edit = [
      'name[0][value]' => $name,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Upload');
    
    // After the image is uploaded, complete the form
    $edit = [
      'field_dme_image[0][alt]' => 'Map image for ' . $name,
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'Test Marker',
    ];
    
    // Save the entity
    $this->submitForm($edit, 'Save');
    
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

  /**
   * Creates a test image and returns its path.
   */
  protected function createTestImage() {
    $path = 'public://test-image.png';
    if (!file_exists($path)) {
      $image = imagecreatetruecolor(50, 50);
      $background = imagecolorallocate($image, 255, 255, 255);
      imagefill($image, 0, 0, $background);
      imagepng($image, \Drupal::service('file_system')->realpath($path));
      imagedestroy($image);
    }
    return \Drupal::service('file_system')->realpath($path);
  }

}