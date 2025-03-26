<?php

namespace Drupal\Tests\draggable_mapper_entity\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the creation and management of Draggable Mapper entities.
 *
 * @group draggable_mapper_entity
 */
class DraggableMapperEntityCreationTest extends BrowserTestBase {

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
  }

  /**
   * Tests creating a draggable mapper entity.
   */
  public function testEntityCreation() {
    // Navigate to the add form.
    $this->drupalGet('admin/structure/draggable_mapper_entity/add');
    $this->assertSession()->statusCodeEquals(200);

    // Prepare a test image.
    $image = current($this->getTestFiles('image'));
    $image_path = $this->container->get('file_system')->realpath($image->uri);

    // Submit the form with minimal values.
    $edit = [
      'name[0][value]' => 'Test Draggable Map',
      'status[value]' => 1,
      'files[field_dme_image_0]' => $image_path,
    ];
    $this->submitForm($edit, 'Save');

    // Check that the entity was saved properly.
    $this->assertSession()->pageTextContains('Draggable Mapper Test Draggable Map has been created.');

    // Check the entity exists in the listing.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->assertSession()->pageTextContains('Test Draggable Map');
  }

  /**
   * Tests validation when creating a draggable mapper entity.
   */
  public function testEntityValidation() {
    // Navigate to the add form.
    $this->drupalGet('admin/structure/draggable_mapper_entity/add');

    // Submit the form without required values.
    $edit = [
      'status[value]' => 1,
      // No name or image.
    ];
    $this->submitForm($edit, 'Save');

    // Check that required field validation works.
    $this->assertSession()->pageTextContains('Name field is required.');
    $this->assertSession()->pageTextContains('Map Image field is required.');
  }

  /**
   * Tests editing a draggable mapper entity.
   */
  public function testEntityEditing() {
    // First create an entity to edit.
    $this->testEntityCreation();

    // Get the entity ID from the listing.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->clickLink('Edit');

    // Change the name.
    $edit = [
      'name[0][value]' => 'Updated Map Name',
    ];
    $this->submitForm($edit, 'Save');

    // Check that the entity was updated properly.
    $this->assertSession()->pageTextContains('Draggable Mapper Updated Map Name has been updated.');

    // Verify the change persisted.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->assertSession()->pageTextContains('Updated Map Name');
  }

  /**
   * Tests deleting a draggable mapper entity.
   */
  public function testEntityDeletion() {
    // First create an entity to delete.
    $this->testEntityCreation();

    // Navigate to delete form.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->submitForm([], 'Delete');

    // Check the entity was deleted.
    $this->assertSession()->pageTextContains('The Draggable Mapper Test Draggable Map has been deleted.');

    // Verify the entity is gone from the listing.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->assertSession()->pageTextNotContains('Test Draggable Map');
  }

  /**
   * Tests adding markers to a draggable mapper entity.
   */
  public function testAddingMarkers() {
    // First create an entity.
    $this->testEntityCreation();

    // Navigate to edit form.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->clickLink('Edit');

    // Add a marker.
    $this->submitForm([], 'Add Marker');

    // Fill in marker information.
    $edit = [
      'field_dme_marker[0][subform][field_dme_marker_title][0][value]' => 'Test Marker',
      'field_dme_marker[0][subform][field_dme_marker_description][0][value]' => 'This is a test marker description.',
      'field_dme_marker[0][subform][field_dme_marker_x][0][value]' => '0.5',
      'field_dme_marker[0][subform][field_dme_marker_y][0][value]' => '0.5',
    ];
    $this->submitForm($edit, 'Save');

    // Check the entity was updated with the marker.
    $this->assertSession()->pageTextContains('Draggable Mapper Test Draggable Map has been updated.');

    // Navigate back to edit form to verify marker exists.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->clickLink('Edit');
    
    // Verify marker fields exist and have the correct values.
    $this->assertSession()->fieldValueEquals('field_dme_marker[0][subform][field_dme_marker_title][0][value]', 'Test Marker');
    $this->assertSession()->fieldValueEquals('field_dme_marker[0][subform][field_dme_marker_x][0][value]', '0.5');
    $this->assertSession()->fieldValueEquals('field_dme_marker[0][subform][field_dme_marker_y][0][value]', '0.5');
  }

  /**
   * Tests that marker coordinates are properly hidden in the form display.
   */
  public function testMarkerCoordinateFieldsHidden() {
    // First create an entity.
    $this->testEntityCreation();

    // Navigate to edit form.
    $this->drupalGet('admin/structure/draggable_mapper_entity');
    $this->clickLink('Edit');

    // Add a marker.
    $this->submitForm([], 'Add Marker');

    // Check for the form display of coordinate fields
    // These fields should be hidden or have a special input styling
    $this->assertSession()->elementExists('css', '.field--name-field-dme-marker-x.visually-hidden, .field--name-field-dme-marker-x input[type="hidden"]');
    $this->assertSession()->elementExists('css', '.field--name-field-dme-marker-y.visually-hidden, .field--name-field-dme-marker-y input[type="hidden"]');
  }
}
