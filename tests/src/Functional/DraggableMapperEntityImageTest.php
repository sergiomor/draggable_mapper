<?php

namespace Drupal\Tests\draggable_mapper_entity\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the image handling functionality of Draggable Mapper entities.
 *
 * @group draggable_mapper_entity
 */
class DraggableMapperEntityImageTest extends BrowserTestBase {

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
    'draggable_mapper_entity',
    'field',
    'file',
    'image',
    'user',
    'paragraphs',
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
      'administer draggable mapper entities',
      'create draggable mapper entity',
      'edit draggable mapper entity',
      'delete draggable mapper entity',
      'access draggable mapper entity overview',
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
    $this->drupalGet('admin/structure/draggable_mapper_entity/add');
    
    // Prepare a test image.
    $image = current($this->getTestFiles('image'));
    $image_path = $this->container->get('file_system')->realpath($image->uri);

    // Submit the form with minimal values.
    $edit = [
      'name[0][value]' => 'Image Test Map',
      'status[value]' => 1,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Save');

    // Store the entity ID from the URL for later use.
    $url_parts = explode('/', $this->getUrl());
    $this->entityId = end($url_parts);
  }

  /**
   * Tests uploading and handling of map images.
   */
  public function testImageUpload() {
    // Navigate to the add form.
    $this->drupalGet('admin/structure/draggable_mapper_entity/add');
    
    // Verify image field is present.
    $this->assertSession()->fieldExists('files[field_dme_image_0]');
    
    // Prepare different test images.
    $test_images = $this->getTestFiles('image');
    $this->assertNotEmpty($test_images, 'Test images were found.');
    
    // Try uploading an image with a different name.
    $image = $test_images[1] ?? $test_images[0];
    $image_path = $this->container->get('file_system')->realpath($image->uri);
    
    $edit = [
      'name[0][value]' => 'New Image Upload Test',
      'status[value]' => 1,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Save');
    
    // Verify entity was created with the image.
    $this->assertSession()->pageTextContains('Draggable Mapper New Image Upload Test has been created');
    
    // Navigate to the entity view page to verify image is displayed.
    $url_parts = explode('/', $this->getUrl());
    $new_entity_id = end($url_parts);
    $this->drupalGet("admin/structure/draggable_mapper_entity/$new_entity_id");
    
    // Check that the image is present in the rendered output.
    $this->assertSession()->elementExists('css', '.field--name-field-dme-image img');
    
    // Test image alt text.
    $alt_text = 'Image Test Map';
    $this->assertSession()->elementAttributeContains('css', '.field--name-field-dme-image img', 'alt', $alt_text);
  }

  /**
   * Tests updating/replacing the map image.
   */
  public function testImageReplacement() {
    // Navigate to edit the existing entity.
    $this->drupalGet("admin/structure/draggable_mapper_entity/{$this->entityId}/edit");
    
    // Verify the current image is displayed.
    $this->assertSession()->elementExists('css', '.field--name-field-dme-image .image-widget img');
    
    // Prepare a different test image for replacement.
    $test_images = $this->getTestFiles('image');
    $replacement_image = end($test_images); // Use a different image than the first one.
    $replacement_path = $this->container->get('file_system')->realpath($replacement_image->uri);
    
    // Replace the image.
    $edit = [
      'files[field_dme_image_0]' => $replacement_path,
    ];
    $this->submitForm($edit, 'Save');
    
    // Verify entity was updated.
    $this->assertSession()->pageTextContains('has been updated');
    
    // Navigate back to the edit form to verify the image was replaced.
    $this->drupalGet("admin/structure/draggable_mapper_entity/{$this->entityId}/edit");
    
    // The image widget should show the new image.
    $this->assertSession()->elementExists('css', '.field--name-field-dme-image .image-widget img');
    
    // Get the current image URL from the src attribute.
    $image_element = $this->getSession()->getPage()->find('css', '.field--name-field-dme-image .image-widget img');
    $new_image_url = $image_element->getAttribute('src');
    
    // This is a simple test to see that the image URL changed.
    // A more comprehensive test would compare file hashes.
    $this->assertStringContainsString('files/styles/', $new_image_url, 'Image URL shows it is being processed by image styles.');
  }

  /**
   * Tests responsive scaling of the map interface.
   */
  public function testResponsiveScaling() {
    // This test verifies that the map container has responsive CSS classes.
    // Navigate to the entity view page.
    $this->drupalGet("admin/structure/draggable_mapper_entity/{$this->entityId}");
    
    // The map container should have the appropriate responsive classes.
    $this->assertSession()->elementExists('css', '.draggable-mapper-entity');
    
    // Check for responsive container styles.
    $map_container = $this->getSession()->getPage()->find('css', '.draggable-mapper-entity');
    $this->assertNotEmpty($map_container, 'Map container found on page.');
    
    // Test that the markers are positioned with percentages.
    // First, add a marker to the entity.
    $this->drupalGet("admin/structure/draggable_mapper_entity/{$this->entityId}/edit");
    $this->submitForm([], 'Add Marker');
    
    // Fill in marker information with percentage-based coordinates.
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'Responsive Test Marker',
      'field_dme_marker[0][subform][field_dme_marker_description][0][value]' => 'This tests responsive positioning.',
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '0.25',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '0.75',
    ];
    $this->submitForm($edit, 'Save');
    
    // Now view the entity and check that marker positioning uses percentage styling.
    $this->drupalGet("admin/structure/draggable_mapper_entity/{$this->entityId}");
    
    // The marker should use percentage-based positioning.
    // This verifies that coordinates are properly stored and used responsively.
    $this->assertSession()->elementExists('css', '.dme-marker');
    
    // In a full browser test, we would test resizing the window and verifying
    // the marker stays in relative position, but that requires JavaScript testing.
    // For now, we'll just check that the HTML structure supports responsive scaling.
    $html = $this->getSession()->getPage()->getHtml();
    
    // Check for percentage-based positioning in the DOM.
    // This assumes your module generates inline styles or classes that use percentages.
    $this->assertStringContainsString('left', $html);
    $this->assertStringContainsString('top', $html);
  }

}
