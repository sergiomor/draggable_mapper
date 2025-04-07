<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\Tests\draggable_mapper\FunctionalJavascript\DraggableMapperJsTestBase;

/**
 * Tests that marker coordinates are saved via dragging.
 *
 * @group draggable_mapper
 */
class DraggableMapperJsModalTest extends DraggableMapperJsTestBase {

  /**
   * Test saving text marker coordinates via simulated drag-and-drop.
   */
  public function testModal() {
    $name = 'Markers Coordinates Test';
    $title = 'Text Marker';
    // Open the add form and fill in the basic fields.
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->fillsBaseFields($name);
    $this->addTextMarker($title);
    $this->getSession()->wait(1000);
    // Get current marker index
    $markerIndex =  $this->getCurrentIndex() - 1;
    // Fill marker description
    $this->getSession()->getPage()->findField('field_dme_marker[' . $markerIndex . '][subform][field_dme_marker_description][0][value]')->setValue('Test description');
    // Drag the marker
    $this->simulateMarkerDrag(
        $markerIndex, 
        50,  // x coordinate
        50,  // y coordinate
        5    // offset from element edges
    );

    $this->assertSession()->waitForElement('css', '.dme-container-wrapper #dme-marker-' . $markerIndex, 5000);
    // Verify that the marker is rendered inside the drop container.
    $this->assertSession()->elementExists('css', '.dme-container-wrapper #dme-marker-' . $markerIndex);

    // Save the entity.
    $this->pressButton('Save');
    // Get the entity page
    $this->drupalGet('draggable-mapper/' . $this->getCreatedEntityId());
    $this->getSession()->wait(1000);
    // Check if the marker exits
    $this->assertSession()->pageTextContains('Text Marker');
    // Get correspondant modal
    $marker= $this->getSession()->getPage()->find('css', '[data-marker-id="' . $markerIndex + 1 . '"]');
    $innerMarker = $marker->find('css', '.dme-marker-wrapper');
    // Click on marker
    $innerMarker->click();

    // Verify corresponding modal opens
    $modalId = 'dme-marker-modal-' . ($markerIndex + 1);
    $this->assertSession()->waitForElementVisible(
        'css',
        "#{$modalId}",
        2000
    );

    // Check for 'opened' class
    $this->assertTrue(
        $this->getSession()->evaluateScript(
            "return document.querySelector('#{$modalId}').classList.contains('opened')"
        ),
        'Modal should have opened class'
    );
        
    // Verify content
    $this->assertSession()->elementTextContains(
        'css',
        "#{$modalId} .dme-modal-body",
        'Test description'
    );
   }

}