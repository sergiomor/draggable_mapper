<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

/**
 * Tests the draggable mapper preview mapper.
 *
 * @group draggable_mapper
 */
class DraggableMapperJsMapPreviewTest extends DraggableMapperJsTestBase {

  /**
   * Tests the map preview process.
   */
  public function testMapPreview() {

     // 1. Start filling entity creation form
    $name = 'Preview Test Map';
    $marker1 = 'Test Marker 1';
    $marker2 = 'Test Marker 2';
    $this->drupalGet('admin/structure/draggable-mapper/add');
    $this->fillsBaseFields($name);

    // Wait for the image preview to appear
    $this->assertSession()->waitForElement('css', '.dme-image-wrapper img[alt="' . $name . '"]');

    $this->assertSession()->elementAttributeContains(
      'css', 
      '.dme-image-wrapper img',
      'alt',
      'Map Image'
    );

    $this->addTextMarker($marker1);
    // Wait for text marker
    $this->getSession()->wait(1000);
    $this->addIconMarker($marker2, 'Marker Icon');
    // Verify text marker preview
    $this->assertSession()->waitForElement('css', '.dme-marker:nth-child(1)');
    $this->assertSession()->elementContains('css', '.dme-marker:nth-child(1)', $marker1);

    // Verify icon marker preview
    $this->assertSession()->waitForElement('css', '.dme-marker:nth-child(2) img');
    $this->assertSession()->elementExists(
      'css',
      '.dme-unmapped-wrapper .dme-marker:nth-child(2) img[alt*="Marker Icon"]'
    );

    $page = $this->getSession()->getPage();
$html = $page->getContent();
//print($html);

    // Final count verification
    $this->assertSession()->elementsCount(
      'css',
      '.dme-unmapped-wrapper .dme-marker',
      2
    );
  }
}
