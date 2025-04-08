<?php

namespace Drupal\Tests\draggable_mapper\Kernel;

use Drupal\draggable_mapper\Entity\DraggableMapper;

/**
 * Tests CRUD operations and field processing for the draggable mapper entity.
 *
 * @group draggable_mapper
 */
class DraggableMapperCrudTest extends DraggableMapperKernelTestBase {

    /**
     * Tests that a draggable mapper entity can be created, updated, and deleted.
     */
    public function testCrudOperations() {

        // Create a Draggable Mapper entity.
        $title = "Test Crud Mapper";
        $draggable_mapper = $this->CreateEntity($title);


        // Verify that the entity got an ID.
        $this->assertNotEmpty($draggable_mapper->id(), 'Entity created successfully with an ID.');

        // Load the entity and verify the label.
        $loaded = DraggableMapper::load($draggable_mapper->id());
        $this->assertEquals($title, $loaded->label(), 'Loaded entity has the expected name.');

        // Update the entity.
        $newName = $loaded->label(). ' Updated';
        $loaded->set('name', $newName);
        $loaded->save();

        // Verify that the update was applied.
        $updated = DraggableMapper::load($loaded->id());
        $this->assertEquals($newName, $updated->label(), 'Entity updated successfully.');

        // Delete the entity.
        $updated->delete();
        $deleted = DraggableMapper::load($updated->id());
        $this->assertNull($deleted, 'Entity deleted successfully.');
    }

    /**
     * Tests that field processing (e.g., computing marker coordinates)
     * occurs during entity save.
     */
    public function testFieldProcessing() {

        // Create a Draggable Mapper entity.
        $title = "Test Field Processing Mapper";
        $draggable_mapper = $this->CreateEntity($title);

        // Reload the entity to ensure the latest values are present.
        $loaded = DraggableMapper::load($draggable_mapper->id());
            $markers = $loaded->get('field_dme_marker')->getValue();

        // Assert that the marker's coordinate fields remain null, indicating an unmapped state.
        foreach ($markers as $marker) {
        $this->assertNull($marker['field_dme_marker_x'], 'Marker X coordinate is null (unmapped).');
        $this->assertNull($marker['field_dme_marker_y'], 'Marker Y coordinate is null (unmapped).');
        }
    }
}