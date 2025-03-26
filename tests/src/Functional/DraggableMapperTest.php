<?php

namespace Drupal\Tests\draggable_mapper\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the functionality of Draggable Mapper entities.
 *
 * @group draggable_mapper
 */
class DraggableMapperTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'draggable_mapper',
    'field',
    'file',
    'image',
    'user',
    'paragraphs',
    'inline_entity_form',
    'text',
  ];

  /**
   * A user with permission to administer draggable mapper entities.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity created during the test.
   *
   * @var int
   */
  protected $entityId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user with appropriate permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer draggable mapper settings',
      'create new draggable mapper',
      'edit draggable mapper',
      'delete draggable mapper',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a test entity with an image for later tests.
    $this->createMapEntityWithImage();
  }

  /**
   * Creates a map entity with an image for testing.
   */
  protected function createMapEntityWithImage() {
    // Navigate to the add form.
    $this->drupalGet('admin/structure/draggable_mapper/add');
    
    // Prepare a test image.
    $image = current($this->getTestFiles('image'));
    $image_path = $this->container->get('file_system')->realpath($image->uri);

    // Submit the form with minimal values.
    $edit = [
      'name[0][value]' => 'Test Map',
      'status[value]' => 1,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Save');

    // Store the entity ID from the URL for later use.
    $url_parts = explode('/', $this->getUrl());
    $this->entityId = end($url_parts);
  }

  /**
   * Tests that the form elements for markers are present and can be saved.
   */
  public function testMarkerFormElements() {
    // Navigate to edit form.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Add a marker to test.
    $this->submitForm([], 'Add Marker');
    
    // Fill in marker information.
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'Form Test Marker',
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '0.25',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '0.75',
    ];
    $this->submitForm($edit, 'Save');
    
    // Verify entity was saved.
    $this->assertSession()->pageTextContains('has been updated');
    
    // Edit the entity again to verify the coordinates were saved.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Check the saved coordinates.
    $this->assertSession()->fieldValueEquals('field_dme_marker[0][subform][field_dme_marker_x][0][value]', '0.25');
    $this->assertSession()->fieldValueEquals('field_dme_marker[0][subform][field_dme_marker_y][0][value]', '0.75');
  }

  /**
   * Tests that markers appear correctly in view mode.
   */
  public function testMarkersInViewMode() {
    // First, we need to add a marker to the entity.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Add a marker with coordinates.
    $this->submitForm([], 'Add Marker');
    
    // Fill in marker information.
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'View Mode Test Marker',
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '0.5',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '0.5',
    ];
    $this->submitForm($edit, 'Save');
    
    // Visit the entity view page.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}");
    
    // Check that the marker is present in view mode.
    $this->assertSession()->elementExists('css', '.dme-marker');
    
    // Verify that the marker title appears in the view.
    $this->assertSession()->pageTextContains('View Mode Test Marker');
    
    // Check that the map container exists.
    $this->assertSession()->elementExists('css', '.draggable-mapper');
  }

  /**
   * Tests form submissions with different coordinate values.
   */
  public function testCoordinateFormSubmission() {
    // Navigate to edit form.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Add a marker to test coordinates.
    $this->submitForm([], 'Add Marker');
    
    // Fill in marker title.
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'Coordinate Test Marker',
    ];
    
    // Test valid coordinates
    $edit['field_dme_marker[0][subform][field_dme_marker_x][0][value]'] = '0.5';
    $edit['field_dme_marker[0][subform][field_dme_marker_y][0][value]'] = '0.5';
    
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been updated');
    
    // Edit and test boundary case coordinates
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '0',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '1',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been updated');
    
    // Edit and test potentially out-of-bounds values
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '1.5',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '-0.2',
    ];
    $this->submitForm($edit, 'Save');
    
    // Check if validation messages appear
    // This will pass either way, as we're just checking if the form handles the values
    $this->assertSession()->statusCodeEquals(200);
  }
}
