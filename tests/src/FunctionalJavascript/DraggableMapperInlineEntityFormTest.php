<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

/**
 * Tests the draggable mapper integration with Inline Entity Form.
 *
 * @group draggable_mapper
 */
class DraggableMapperInlineEntityFormTest extends DraggableMapperJsTestBase {

  /**
   * Tests creating a node with a draggable mapper field.
   */
  public function testCreateNodeWithMapper() {
    // Create a test node
    $node = $this->createTestNode('Test Article with Draggable Mapper');
    $this->assertNotEmpty($node, 'Node was created successfully');

    // Verify the node exists
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->titleEquals($node->label() . ' | Drupal');
  }
}
