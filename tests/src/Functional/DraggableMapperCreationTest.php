<?php

namespace Drupal\Tests\draggable_mapper\Functional;

/**
 * Tests the basic functionality of the Draggable Mapper module.
 *
 * @group draggable_mapper
 */
class DraggableMapperCreationTest extends DraggableMapperTestBase {

  /**
   * Tests the entity creation process.
   */
  public function testEntityCreation() {

    // Test creating a basic entity.
    $entity_id = $this->createBasicEntity('Test Basic Entity');
    $this->assertNotEmpty($entity_id, 'Entity was created successfully with an ID');

    // Verify we can view the entity.
    if (!empty($entity_id)) {
      $this->drupalGet("draggable-mapper/{$entity_id}");
      $this->assertEquals(200, $this->getSession()->getStatusCode(), 'Entity view page loaded');
      $this->assertSession()->pageTextContains('Test Basic Entity');
    }
  }

  /**
   * Tests basic CRUD operations.
   *
   * Verifies that an entity can be created, viewed, edited, and deleted.
   */
  public function testBasicCrudOperations() {

    // Create an entity.
    $entity_name = 'CRUD Test Entity';
    $entity_id = $this->createBasicEntity($entity_name);
    $this->assertNotEmpty($entity_id, 'Entity was created successfully with an ID');

    // View the entity.
    $this->drupalGet("draggable-mapper/{$entity_id}");
    $this->assertEquals(200, $this->getSession()->getStatusCode(), 'Entity view page loaded');
    $this->assertSession()->pageTextContains($entity_name);

    // Edit the entity.
    $this->drupalGet("admin/structure/draggable-mapper/{$entity_id}/edit");
    $this->assertEquals(200, $this->getSession()->getStatusCode(), 'Entity edit page loaded');

    // Change the name.
    $edit = [
      'name[0][value]' => $entity_name . ' (Updated)',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the update worked.
    $this->drupalGet("draggable-mapper/{$entity_id}");
    $this->assertSession()->pageTextContains($entity_name . ' (Updated)');

    // Delete the entity.
    $this->drupalGet("admin/structure/draggable-mapper/{$entity_id}/delete");
    $this->assertEquals(200, $this->getSession()->getStatusCode(), 'Entity delete page loaded');
    $this->submitForm([], 'Delete');

    // Verify the entity is gone.
    $this->drupalGet('admin/structure/draggable-mapper');
    $this->assertSession()->pageTextNotContains($entity_name . ' (Updated)');

    // Verify accessing the deleted entity returns 404.
    $this->drupalGet("admin/structure/draggable-mapper/{$entity_id}");
    $this->assertEquals(404, $this->getSession()->getStatusCode(), 'Entity no longer exists');
  }

  /**
   * Tests that coordinate fields are properly hidden in the form.
   */
  public function testMarkerCoordinateFieldsAreHidden() {

    // Go to the entity creation form.
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->assertSession()->statusCodeEquals(200);

    // Verify coordinates, width, and height fields exist and are hidden.
    $selectors = [
      'edit-field-dme-marker-0-subform-field-dme-marker-x-0-value',
      'edit-field-dme-marker-0-subform-field-dme-marker-y-0-value',
      'edit-field-dme-marker-0-subform-field-dme-marker-width-0-value',
      'edit-field-dme-marker-0-subform-field-dme-marker-height-0-value',
    ];

    foreach ($selectors as $selector) {

      // Check field exists.
      $this->assertSession()->elementExists('css', "[data-drupal-selector=\"$selector\"]");

      // Verify it's hidden.
      $field = $this->getSession()->getPage()->find('css', "[data-drupal-selector=\"$selector\"]");
      $this->assertEquals('hidden', $field->getAttribute('type'), "Field $selector should be hidden");
    }
  }

}
