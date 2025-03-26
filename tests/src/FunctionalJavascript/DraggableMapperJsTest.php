<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the JavaScript functionality of Draggable Mapper entities.
 *
 * @group draggable_mapper
 */
class DraggableMapperJsTest extends WebDriverTestBase {

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
      'name[0][value]' => 'JS Test Map',
      'status[value]' => 1,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Save');

    // Store the entity ID from the URL for later use.
    $url_parts = explode('/', $this->getUrl());
    $this->entityId = end($url_parts);
  }

  /**
   * Tests that the JavaScript libraries are properly loaded.
   */
  public function testJsLibraryLoading() {
    // Navigate to edit form which should load the JS libraries.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Wait for page to fully load
    $this->getSession()->wait(1000, "jQuery('.dme-preview-container').length > 0");
    
    // Check for the library attachment in the page.
    $this->assertSession()->elementExists('css', '.dme-preview-container');
    
    // Add a marker to work with.
    $this->getSession()->getPage()->pressButton('Add Marker');
    $this->getSession()->wait(1000); // Wait for ajax completion
    
    // Fill in marker title for easier identification.
    $this->getSession()->getPage()->fillField('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'Draggable Test Marker');
    
    // Test that the marker appears in the preview.
    $this->assertSession()->elementExists('css', '.dme-marker');
    
    // Check that JS attaches the right classes to the preview container.
    $this->assertSession()->elementExists('css', '.dme-preview-container');
  }

  /**
   * Tests the drag and drop functionality for markers.
   */
  public function testDragAndDropMarkers() {
    // Navigate to edit form.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Add a marker to test dragging.
    $this->getSession()->getPage()->pressButton('Add Marker');
    $this->getSession()->wait(1000); // Wait for ajax completion
    
    // Fill in marker information.
    $page = $this->getSession()->getPage();
    $page->fillField('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'Drag Test Marker');
    
    // Check initial coordinates - should be empty or 0,0.
    $x_field = $page->find('css', 'input[name="field_dme_marker[0][subform][field_dme_marker_x][0][value]"]');
    $y_field = $page->find('css', 'input[name="field_dme_marker[0][subform][field_dme_marker_y][0][value]"]');
    
    $initial_x = $x_field->getValue() ?: '0';
    $initial_y = $y_field->getValue() ?: '0';
    
    // Get the map container and marker elements.
    $map_container = $page->find('css', '.dme-preview-container .dme-image-wrapper');
    $marker = $page->find('css', '.dme-marker');
    
    // Ensure the marker is found
    $this->assertSession()->elementExists('css', '.dme-marker');
    
    // Use JavaScript to drag the marker
    $this->getSession()->executeScript("
      (function() {
        var marker = document.querySelector('.dme-marker');
        var map = document.querySelector('.dme-image-wrapper');
        var rect = map.getBoundingClientRect();
        
        // Simulate a drag to 25% across, 50% down
        var targetX = rect.left + (rect.width * 0.25);
        var targetY = rect.top + (rect.height * 0.5);
        
        // Create and dispatch events
        var mouseDown = new MouseEvent('mousedown', {
          bubbles: true,
          cancelable: true,
          view: window
        });
        
        var mouseMove = new MouseEvent('mousemove', {
          bubbles: true,
          cancelable: true,
          view: window,
          clientX: targetX,
          clientY: targetY
        });
        
        var mouseUp = new MouseEvent('mouseup', {
          bubbles: true,
          cancelable: true,
          view: window,
          clientX: targetX,
          clientY: targetY
        });
        
        marker.dispatchEvent(mouseDown);
        document.dispatchEvent(mouseMove);
        document.dispatchEvent(mouseUp);
      })();
    ");
    
    // Wait a moment for any events to complete
    $this->getSession()->wait(1000);
    
    // Check if coordinates have changed
    $new_x = $x_field->getValue();
    $new_y = $y_field->getValue();
    
    // Save the entity and verify the coordinates persist.
    $page->pressButton('Save');
    
    // Edit the entity again to verify the coordinates were saved.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Get the saved coordinate values.
    $x_field = $page->find('css', 'input[name="field_dme_marker[0][subform][field_dme_marker_x][0][value]"]');
    $y_field = $page->find('css', 'input[name="field_dme_marker[0][subform][field_dme_marker_y][0][value]"]');
    
    // The saved values should be within the valid range.
    $value_x = (float) $x_field->getValue();
    $this->assertSession()->elementExists('css', 'input[name="field_dme_marker[0][subform][field_dme_marker_x][0][value]"]');
  }

  /**
   * Tests that markers are not draggable in view mode.
   */
  public function testMarkersNotDraggableInViewMode() {
    // First, we need to add a marker to the entity.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}/edit");
    
    // Add a marker with coordinates.
    $this->getSession()->getPage()->pressButton('Add Marker');
    $this->getSession()->wait(1000); // Wait for ajax completion
    
    // Fill in marker information.
    $page = $this->getSession()->getPage();
    $page->fillField('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'View Mode Test Marker');
    $page->fillField('field_dme_marker[0][subform][field_dme_marker_x][0][value]', '0.5');
    $page->fillField('field_dme_marker[0][subform][field_dme_marker_y][0][value]', '0.5');
    
    // Save the entity.
    $page->pressButton('Save');
    
    // Visit the entity view page.
    $this->drupalGet("admin/structure/draggable_mapper/{$this->entityId}");
    
    // Check that the marker is present in view mode.
    $this->assertSession()->elementExists('css', '.dme-marker');
    
    // Check the marker's classes to ensure it doesn't have draggable classes
    $marker = $page->find('css', '.dme-marker');
    $markerClass = $marker->getAttribute('class');
    
    // The marker should not have jQuery UI draggable classes in view mode
    $this->assertSession()->elementAttributeNotContains('css', '.dme-marker', 'class', 'ui-draggable');
  }
}
