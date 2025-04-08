<?php

namespace Drupal\Tests\draggable_mapper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\draggable_mapper\Entity\DraggableMapper;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\file\Entity\File;

/**
 * Base class for draggable mapper kernel tests.
 *
 * @group draggable_mapper
 */
class DraggableMapperKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'image',
    'file',
    'node',
    'inline_entity_form',
    'draggable_mapper',
    'paragraphs',
    'entity_reference_revisions',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    // Set up schema and config.
    parent::setUp();
    $this->installEntitySchema('draggable_mapper');
    $this->installConfig('draggable_mapper');
    $this->installEntitySchema('paragraph');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    // Add field definitions for the paragraph type.
    $this->installEntitySchema('paragraph');
    $this->installConfig(['field', 'draggable_mapper']);

  }

  /**
   * Creates a test image file for use in draggable mapper entities.
   *
   * This helper method:
   * 1. Ensures the public files directory exists
   * 2. Creates a minimal 1x1 transparent PNG image
   * 3. Creates and saves a permanent File entity.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity ready for use in tests.
   */
  protected function createTestImageFile(): File {

    // Prepare directory structure.
    $directory = 'public://';
    $file_system = \Drupal::service('file_system');
    // 1 = FILE_CREATE_DIRECTORY
    $file_system->prepareDirectory($directory, 1);

    // Create a dummy image file with minimal content.
    $image_path = 'public://dummy-map.png';
    file_put_contents($image_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    $file = File::create([
      'uri' => 'public://dummy-map.png',
    ]);
    $file->save();

    return $file;

  }

  /**
   * Creates a basic draggable mapper entity through the UI.
   *
   * @param string $title
   *   Base name used for marker title.
   *
   * @return Drupal\draggable_mapper\Entity\DraggableMapper|null
   *   The entity or NULL if creation failed.
   */
  public function createEntity(string $title) {

    // Create an image for the mapper.
    $file = $this->createTestImageFile();

    // Create a Paragraph entity for the marker.
    $paragraph = Paragraph::create([

      'type' => 'dme_marker',
      // Set the required title field.
      'field_dme_marker_title' => 'Test Marker 1',
      // Empty coordinate fields to test default processing.
      'field_dme_marker_x' => NULL,
      'field_dme_marker_y' => NULL,
    ]);
    $paragraph->save();

    // Create a draggable mapper entity that references the marker paragraph.
    $draggable_mapper = DraggableMapper::create([
      'name' => $title,
      'field_dme_image' => [
        ['target_id' => $file->id()],
      ],
      'field_dme_marker' => [
        ['target_id' => $paragraph->id()],
      ],
    ]);
    $draggable_mapper->save();

    return $draggable_mapper;
  }

}
