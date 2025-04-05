<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Base class for JavaScript-based tests of the draggable mapper module.
 */
class DraggableMapperJsTestBase extends WebDriverTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Path to the test image.
   *
   * @var string
   */
  protected $testImagePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Log browser status
    $session = $this->getSession();
    $driver = null !== $session ? $session->getDriver() : null;
  

    // Create article content type if it doesn't exist
    if (!NodeType::load('article')) {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create admin user with appropriate permissions
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'create article content',
      'edit any article content',
      'delete any article content',
      'add draggable mapper',
      'edit draggable mapper',
      'delete draggable mapper',
      'administer draggable mapper',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a test image for later use
    $this->testImagePath = $this->createTestImage();
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

  /**
   * Adds a draggable mapper entity reference field to the article content type.
   *
   * @param string $field_name
   *   The field name to create.
   * @param string $field_label
   *   The field label to create.
   * @param bool $use_ief
   *   Whether to configure the field to use the Inline Entity Form widget.
   *
   * @return string
   *   The name of the created field.
   */
  protected function addMapperFieldToArticle($field_name = 'field_mapper', 
                                           $field_label = 'Map', 
                                           $use_ief = TRUE) {
    // Create the field storage
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'draggable_mapper',
      ],
      'cardinality' => 1,
    ])->save();

    // Create the field instance
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $field_label,
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
      ],
    ])->save();

    // Configure the form display to use Inline Entity Form if requested
    if ($use_ief) {
      $form_display = EntityFormDisplay::load('node.article.default');
      if (!$form_display) {
        $form_display = EntityFormDisplay::create([
          'targetEntityType' => 'node',
          'bundle' => 'article',
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }
      
      $form_display->setComponent($field_name, [
        'type' => 'inline_entity_form_complex',
        'settings' => [
          'form_mode' => 'default',
          'override_labels' => TRUE,
          'label_singular' => 'map',
          'label_plural' => 'maps',
          'allow_new' => TRUE,
          'allow_existing' => TRUE,
          'match_operator' => 'CONTAINS',
          'revision' => FALSE,
          'collapsible' => TRUE,
          'collapsed' => FALSE,
        ],
        'weight' => 1,
      ])
      ->save();
    }
    
    return $field_name;
  }

  /**
   * Creates a test node with embedded draggable mapper entity.
   *
   * @param string $node_title
   *   The title for the node.
   * @param string $field_name
   *   The name of the entity reference field.
   * @param string $mapper_name
   *   (optional) The name for the embedded mapper. Defaults to 'Embedded Test Map'.
   *
   * @return int|null
   *   The node ID if creation was successful, otherwise NULL.
   */
  protected function createNodeWithEmbeddedMapper($node_title, $field_name = 'field_mapper', $mapper_name = 'Embedded Test Map') {
    $this->drupalGet('node/add/article');
    
    // Wait for the page to load
    $this->assertSession()->pageTextContains('Create Article');
    
    // Fill in the node title
    $this->getSession()->getPage()->fillField('title[0][value]', $node_title);
    
    // Click the "Add new field_mapper" button
    $this->getSession()->getPage()->pressButton("Add new {$field_name}");
    
    // Wait for the inline form to appear - use fixed time rather than condition
    $page = $this->getSession()->getPage();
    $this->getSession()->wait(5000); // Just wait 5 seconds without a condition
    
    // Check if we can find the name field
    $field = $page->findField('name[0][value]');
    
    // Fill in the mapper name if field is found
    if ($field) {
      $this->getSession()->getPage()->fillField('name[0][value]', $mapper_name);
    } else {
      $inputs = $page->findAll('css', 'input');
      foreach ($inputs as $index => $input) {
        $name = $input->getAttribute('name');
      }
      // Continue anyway to see what happens
    }
    
    // Upload the test image
    try {
      $image_field = $this->getSession()->getPage()->findField('files[field_dme_image_0]');
      if ($image_field) {
        $image_field->attachFile($this->testImagePath);
      }
    } catch (\Exception $e) {
    }
    
    // Wait for image to be processed - use fixed time rather than condition
    $this->getSession()->wait(5000); // Just wait 5 seconds without a condition
    
    // Add a test marker
    try {
      $this->getSession()->getPage()->fillField('field_dme_marker[0][title]', 'Test Marker Title');
      $this->getSession()->getPage()->fillField('field_dme_marker[0][subtitle]', 'Test Marker Subtitle');
    } catch (\Exception $e) {
    }
    
    // Save the node
    $this->getSession()->getPage()->pressButton('Save');
    
    // The URL of the node should be like "/node/N"
    $current_url = $this->getSession()->getCurrentUrl();
    if (preg_match('|/node/(\d+)$|', $current_url, $matches)) {
      return (int) $matches[1];
    }
    return NULL;
  }

  /**
   * Creates a basic draggable mapper entity.
   *
   * @param string $name
   *   The name for the entity.
   * @param string $marker_title
   *   (optional) The title for the marker. Defaults to 'Test Marker Title'.
   *
   * @return int|null
   *   The entity ID if creation was successful, otherwise NULL.
   */
  protected function createBasicEntity($name, $marker_title = 'Test Marker Title') {
    $this->drupalGet('admin/structure/draggable_mapper/add');
    
    // Fill in the name field
    $this->getSession()->getPage()->fillField('name[0][value]', $name);
    
    // Upload the test image
    $image_field = $this->getSession()->getPage()->findField('files[field_dme_image_0]');
    $image_field->attachFile($this->testImagePath);
    
    // Wait for image to be processed
    $this->getSession()->wait(2000, "jQuery('.image-widget img').length > 0");
    
    // Add a test marker
    $this->getSession()->getPage()->fillField('field_dme_marker[0][title]', $marker_title);
    $this->getSession()->getPage()->fillField('field_dme_marker[0][subtitle]', 'Test Marker Subtitle');
    
    // Save the entity
    $this->getSession()->getPage()->pressButton('Save');
    
    // The URL of the entity should be like "/admin/structure/draggable_mapper/N"
    $current_url = $this->getSession()->getCurrentUrl();
    if (preg_match('|/admin/structure/draggable_mapper/(\d+)$|', $current_url, $matches)) {
      return (int) $matches[1];
    }
    
    return NULL;
  }

}
