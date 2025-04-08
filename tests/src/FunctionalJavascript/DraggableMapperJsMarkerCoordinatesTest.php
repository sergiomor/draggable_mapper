<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

/**
 * Tests that marker coordinates are saved via dragging.
 *
 * @group draggable_mapper
 */
class DraggableMapperJsMarkerCoordinatesTest extends DraggableMapperJsTestBase {

  /**
   * Test saving text marker coordinates via simulated drag-and-drop.
   */
  public function testTextMarkerCoordinates() {
    $name = 'Markers Coordinates Test';
    $marker = 'Text Marker';

    // Open the add form and fill in the basic fields.
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->fillsBaseFields($name);
    $this->addTextMarker($marker);

    // Get current marker index.
    $markerIndex =  $this->getCurrentIndex() - 1;

    // Simulate dragging the text-based marker.
    $this->assertSession()->elementExists('css', '#dme-marker-' . $markerIndex);
    $this->assertSession()->elementExists('css', '.dme-container-wrapper');
    $this->assertTrue($this->getSession()->evaluateScript("return typeof jQuery.fn.draggable === 'function'"), 'jQuery UI draggable is available.');
    $this->assertTrue($this->getSession()->evaluateScript("return typeof jQuery.fn.droppable === 'function'"), 'jQuery UI droppable is available.');
    
    // Wait for behaviors to attach and make sure markers are properly initialized
    $this->ensureDrupalBehaviors();

    // Perform simulated drag and drop in the browser.
    $this->simulateMarkerDrag(
        $markerIndex, 
        50,  // x coordinate
        50,  // y coordinate
        5    // offset from element edges
    );

    // Wait ensure AJAX/JS processing is complete.
    $this->getSession()->wait(2000);
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Wait for the marker to appear inside the drop container.
    $this->assertSession()->waitForElement('css', '.dme-container-wrapper #dme-marker-' . $markerIndex, 5000);
    
    // Verify that the marker is rendered inside the drop container.
    $this->assertSession()->elementExists('css', '.dme-container-wrapper #dme-marker-' . $markerIndex);

    // Get coordinates. 
    $coordinates = $this->getRelativeMarkerCoordinates($markerIndex);

    // Save the entity.
    $this->pressButton('Save');

    // Retrieve the created entity ID.
    $entity_id = $this->getCreatedEntityId();
    $this->assertNotEmpty($entity_id, 'Entity was created successfully.');
    
    // Reload the entity view page.
    $this->drupalGet('draggable-mapper/' . $entity_id);

    // Get saved coordinates.
    $savedCoordinates = $this->getSavedMarkerCoordinates($entity_id, $markerIndex);

    // Assert saved coordinates are equal to map preview coordinates.
    $this->assertCoordinatesMatch(
      $coordinates,
      $savedCoordinates,
      0.005
    );
   }


  /**
   * Test saving icon marker coordinates via simulated drag-and-drop.
   */
  public function testIconMarkerCoordinates() {
    $name = 'Markers Coordinates Test';
    $marker = 'Icon Marker';

    // Open the add form and fill in the basic fields.
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->fillsBaseFields($name);
    $this->addIconMarker($marker, 'Icon alt text');

    // Get current marker indexs.
    $markerIndex =  $this->getCurrentIndex() - 1;

    // Simulate dragging the icon-based marker.
    $this->assertSession()->elementExists('css', '#dme-marker-' . $markerIndex);
    $this->assertSession()->elementExists('css', '.dme-container-wrapper');
    $this->assertTrue($this->getSession()->evaluateScript("return typeof jQuery.fn.draggable === 'function'"), 'jQuery UI draggable is available.');
    $this->assertTrue($this->getSession()->evaluateScript("return typeof jQuery.fn.droppable === 'function'"), 'jQuery UI droppable is available.');
    
    // Wait for behaviors to attach and make sure markers are properly initialized.
    $this->ensureDrupalBehaviors();

    // Perform simulated drag and drop in the browser.
    $this->simulateMarkerDrag(
        $markerIndex, 
        50,  // x coordinate
        50,  // y coordinate
        5    // offset from element edges
    );

    // Wait ensure AJAX/JS processing is complete.
    $this->getSession()->wait(2000);
    $this->assertSession()->assertWaitOnAjaxRequest();
    
    // Wait for the marker to appear inside the drop container.
    $this->assertSession()->waitForElement('css', '.dme-container-wrapper #dme-marker-' . $markerIndex, 5000);
    
    // Verify that the marker is rendered inside the drop container.
    $this->assertSession()->elementExists('css', '.dme-container-wrapper #dme-marker-' . $markerIndex);

    // Get coordinates.
    $coordinates = $this->getRelativeMarkerCoordinates($markerIndex);

    // Save the entity.
    $this->pressButton('Save');

    // Retrieve the created entity ID.
    $entity_id = $this->getCreatedEntityId();
    $this->assertNotEmpty($entity_id, 'Entity was created successfully.');
    
    // Reload the entity view page.
    $this->drupalGet('draggable-mapper/' . $entity_id);

    // Get saved coordinates
    $savedCoordinates = $this->getSavedMarkerCoordinates($entity_id, $markerIndex);

    // Assert saved coordinates are equal to map preview coordinates.
    $this->assertCoordinatesMatch(
      $coordinates,
      $savedCoordinates,
      0.005
    );
    
    // Verify icon marker has an image.
    $this->assertSession()->waitForElement('css', '.dme-marker img');
    $this->assertSession()->elementExists(
      'css',
      '.dme-marker img[alt*="Icon alt text"]'
    );
   }

}