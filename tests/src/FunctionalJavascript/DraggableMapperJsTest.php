<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

/**
 * Tests the draggable mapper integration with Inline Entity Form.
 *
 * @group draggable_mapper
 */
class DraggableMapperJsTest extends DraggableMapperJsTestBase {

  /**
   * Tests the entity creation process.
   */
  public function testEntityCreation() {
        // Test creating a basic entity
        $entity_id = $this->createDraggableMapperEntity('Test Basic Entity');
        $this->assertNotEmpty($entity_id, 'Entity was created successfully with an ID');
        //print "CURRENT ENTITY ID: " . $entity_id . "\n";
        // Verify we can view the entity
        if (!empty($entity_id)) {
            $this->drupalGet("draggable-mapper/{$entity_id}");
            $this->assertSession()->pageTextContains('Test Basic Entity');
        }
  }

  
}
