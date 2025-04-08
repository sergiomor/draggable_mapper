<?php

namespace Drupal\Tests\draggable_mapper\Kernel;

use Drupal\draggable_mapper\Entity\DraggableMapper;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\Entity\ParagraphsType;
use \Drupal\draggable_mapper\PreprocessHooks\DraggableMapperPreprocessHook;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Tests the Draggable Mapper preprocess hook.
 *
 * @group draggable_mapper
 */
class DraggableMapperPreprocessHookTest extends DraggableMapperKernelTestBase {

  /**
   * Tests that the preprocess hook sets expected variables.
   */
  public function testPreprocessHook() {

    // Create a dummy draggable mapper entity.
    $mapper = DraggableMapper::create([
      'name' => 'Test Mapper',
      // We deliberately leave fields like field_dme_image and field_dme_marker empty.
    ]);
    $mapper->save();

    // Build a simulated "elements" array like what would be passed to a Twig template.
    $variables = [];

    // Make sure the preprocess hook can get the entity via #draggable_mapper.
    $variables['elements'] = [
      '#draggable_mapper' => $mapper,
      '#view_mode' => 'full',

      // Simulate that there are child elements. The preprocess hook uses Element::children().
      'child1' => ['#markup' => 'Some child markup'],
    ];

    // Optionally, prepare empty attributes array.
    $variables['attributes'] = [];
    
    // Now call the preprocess hook.
    DraggableMapperPreprocessHook::preprocessDraggableMapper($variables);

    // Verify that your hook set the entity as a variable.
    $this->assertEquals($mapper->label(), $variables['title'], 'The title is set correctly.');
    $this->assertEquals($mapper->label(), $variables['label'], 'The label is set correctly.');
    $this->assertEquals('full', $variables['view_mode'], 'The view mode is passed correctly.');
    $this->assertNotEmpty($variables['draggable_mapper'], 'The draggable_mapper variable is set.');
    $this->assertEquals($mapper->id(), $variables['draggable_mapper']->id(), 'The correct entity is in draggable_mapper.');

    // Verify that the attributes array is populated with proper classes.
    $this->assertArrayHasKey('class', $variables['attributes'], 'Attributes include classes.');
    $this->assertContains('draggable-mapper', $variables['attributes']['class'], 'draggable-mapper class is present.');
    $this->assertContains('draggable-mapper--view-mode-full', $variables['attributes']['class'], 'View mode class is present.');
    $this->assertContains('draggable-mapper--id-' . $mapper->id(), $variables['attributes']['class'], 'Entity ID class is present.');

    // Verify that the content array is populated from the children of elements.
    $children = Element::children($variables['elements']);
    $this->assertNotEmpty($children, 'There are children in elements.');
    $this->assertArrayHasKey($children[0], $variables['content'], 'Content array is populated.');

    // Since no map image was provided, map_image_url should be empty.
    $this->assertEmpty($variables['map_image_url'], 'Map image URL is empty.');

    // Since no marker entities were provided via field_dme_marker, markers should be empty.
    $this->assertEmpty($variables['markers'], 'Markers array is empty.');
  }

    /**
     * Tests map image being included in variables.
    */
    public function testMapImageIsIncludedInVariables() {
    // Create a test file entity.
    $file = $this->createTestImageFile();

    // Create a draggable mapper entity with image field.
    $entity = DraggableMapper::create([
        'type' => 'default',
        'title' => 'Test Map',
        'field_dme_image' => [
        'target_id' => $file->id(),
        'alt' => 'Alt text',
        ],
    ]);
    $entity->save();

    // Build renderable elements array like what would be in the theme layer.
    $elements = [
        '#draggable_mapper' => $entity,
        '#view_mode' => 'full',
    ];

    $variables = [
        'elements' => $elements,
        'attributes' => [],
    ];

    // Run the preprocess function.
    DraggableMapperPreprocessHook::preprocessDraggableMapper($variables);

    // Assert image url and alt text are included in the variables.
    $this->assertNotEmpty($variables['map_image_url'], 'Map image URL is set.');
    $this->assertEquals('Alt text', $variables['map_alt'] ?? NULL, 'Alt text is set correctly.');
    }

    /**
     * Tests a marker with a title gets included.
     */
    public function testMarkerWithTitleIsIncluded() {

        // Create the paragraph type first
        if (!ParagraphsType::load('dme_marker')) {
            ParagraphsType::create([
                'id' => 'dme_marker',
                'label' => 'Marker',
            ])->save();
        }

        // Create a marker paragraph.
        $paragraph = Paragraph::create([
        'type' => 'dme_marker',
        'field_dme_marker_title' => 'My Marker',
        'field_dme_marker_x' => 0.5,
        'field_dme_marker_y' => 0.5,
        'field_dme_marker_width' => 0.2,
        'field_dme_marker_height' => 0.2,
        ]);
        $paragraph->save();

        // Create the draggable mapper entity with one marker.
        $entity = DraggableMapper::create([
        'type' => 'default',
        'title' => 'Map With Marker',
        'field_dme_marker' => [$paragraph],
        ]);
        $entity->save();

        $elements = [
        '#draggable_mapper' => $entity,
        '#view_mode' => 'full',
        ];

        $variables = [
        'elements' => $elements,
        'attributes' => [],
        ];

        DraggableMapperPreprocessHook::preprocessDraggableMapper($variables);

        // The markers array should include our mapped marker since we provided valid coordinates.
        $this->assertNotEmpty($variables['markers'], 'Markers array is not empty.');
        $marker = reset($variables['markers']);
        $this->assertEquals('My Marker', $marker['title'], 'Marker title is set correctly.');
        $this->assertIsNumeric($marker['x'], 'Marker X coordinate is numeric.');
        $this->assertIsNumeric($marker['y'], 'Marker Y coordinate is numeric.');
        $this->assertIsNumeric($marker['width'], 'Marker width is numeric.');
        $this->assertIsNumeric($marker['height'], 'Marker height is numeric.');
    }

}