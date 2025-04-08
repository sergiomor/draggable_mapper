<?php

namespace Drupal\Tests\draggable_mapper\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for utility functions and services.
 *
 * @group draggable_mapper
 * @coversDefaultClass \Drupal\draggable_mapper\PreprocessHooks\DraggableMapperPreprocessHook
 */
class DraggableMapperUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the Drupal service container.
    $container = new ContainerBuilder();

    // Mock the file_url_generator service.
    $file_url_generator = $this->createMock('\Drupal\Core\File\FileUrlGeneratorInterface');
    $file_url_generator->method('generateString')->willReturnCallback(
      function ($uri) {
        return '/test/path/' . basename($uri);
      }
    );
    $container->set('file_url_generator', $file_url_generator);

    // Mock the string_translation service.
    $string_translation = $this->getStringTranslationStub();
    $container->set('string_translation', $string_translation);

    \Drupal::setContainer($container);
  }

  /**
   * Tests coordinate conversion from decimal to percentage.
   */
  public function testCoordinateDecimalToPercentage() {
    // Test converting decimal coordinates (0-1 range)
    // to percentage values (0-100)
    $decimal_x = 0.25;
    $decimal_y = 0.75;

    // Expected percentage values (multiply by 100)
    $expected_x_percentage = 25;
    $expected_y_percentage = 75;

    // Call our utility method to test.
    $x_percentage = $this->decimalToPercentage($decimal_x);
    $y_percentage = $this->decimalToPercentage($decimal_y);

    $this->assertEquals($expected_x_percentage, $x_percentage, 'X coordinate decimal to percentage conversion is correct');
    $this->assertEquals($expected_y_percentage, $y_percentage, 'Y coordinate decimal to percentage conversion is correct');
  }

  /**
   * Tests marker size calculation.
   */
  public function testMarkerSizeCalculation() {
    // Test container size.
    $container_width = 800;
    $container_height = 600;

    // Test pixel dimensions.
    // 20% of container width.
    $marker_width_px = 160;
    // 20% of container height
    $marker_height_px = 120;

    // Expected decimal values.
    $expected_width_decimal = 0.2;
    $expected_height_decimal = 0.2;

    // Call our utility method to test.
    $width_decimal = $this->pixelToDecimal($marker_width_px, $container_width);
    $height_decimal = $this->pixelToDecimal($marker_height_px, $container_height);

    $this->assertEquals($expected_width_decimal, $width_decimal, 'Width pixel to decimal conversion is correct');
    $this->assertEquals($expected_height_decimal, $height_decimal, 'Height pixel to decimal conversion is correct');
  }

  /**
   * Tests font size calculation.
   */
  public function testFontSizeCalculation() {
    // Test different marker sizes and expected font sizes.
    $test_cases = [
      // Wide marker (width > height, aspect ratio > 3)
      [
        'width' => 300,
        'height' => 50,
    // 25% of smallest dimension
        'expected_font_size' => 12.5,
      ],
      // Square marker (aspect ratio = 1)
      [
        'width' => 100,
        'height' => 100,
      // 10% of width
        'expected_font_size' => 10,
      ],
      // Tall marker (height > width, aspect ratio < 1)
      [
        'width' => 50,
        'height' => 150,
      // 10% of width
        'expected_font_size' => 5,
      ],
    ];

    foreach ($test_cases as $test_case) {
      $calculated_font_size = $this->calculateFontSize(
        $test_case['width'],
        $test_case['height']
      );

      $this->assertEquals(
        $test_case['expected_font_size'],
        $calculated_font_size,
        sprintf(
          'Font size calculation is correct for %dx%d marker',
          $test_case['width'],
          $test_case['height']
        )
      );
    }
  }

  /**
   * Tests marker data extraction.
   */
  public function testMarkerDataExtraction() {
    // Create a mock paragraph with test data.
    $paragraph = $this->getMockParagraph([
      'field_dme_marker_x' => 0.25,
      'field_dme_marker_y' => 0.5,
      'field_dme_marker_width' => 0.2,
      'field_dme_marker_height' => 0.1,
      'field_dme_marker_title' => 'Test Marker',
    ]);

    // Create test variables array.
    $variables = ['markers' => []];

    // Process the paragraph into marker data.
    $marker_data = $this->extractMarkerData($paragraph, 1);

    // Assert marker data is extracted correctly.
    $this->assertEquals(25, $marker_data['x'], 'X coordinate extracted correctly');
    $this->assertEquals(50, $marker_data['y'], 'Y coordinate extracted correctly');
    $this->assertEquals(20, $marker_data['width'], 'Width extracted correctly');
    $this->assertEquals(10, $marker_data['height'], 'Height extracted correctly');
    $this->assertEquals('Test Marker', $marker_data['title'], 'Title extracted correctly');
  }

  /**
   * Tests marker validation.
   */
  public function testMarkerValidation() {
    // Test valid marker (has all required fields)
    $valid_marker = $this->getMockParagraph([
      'field_dme_marker_x' => 0.25,
      'field_dme_marker_y' => 0.5,
      'field_dme_marker_title' => 'Valid Marker',
    ]);

    // Test marker with missing coordinates.
    $invalid_marker_missing_coords = $this->getMockParagraph([
      'field_dme_marker_x' => NULL,
      'field_dme_marker_y' => 0.5,
      'field_dme_marker_title' => 'Invalid Marker',
    ]);

    // Test marker with out-of-range coordinates.
    $invalid_marker_out_of_range = $this->getMockParagraph([
    // > 1.0 is out of range
      'field_dme_marker_x' => 1.5,
      'field_dme_marker_y' => 0.5,
      'field_dme_marker_title' => 'Invalid Marker',
    ]);

    // Test validation functions.
    $this->assertTrue(
      $this->isMarkerValid($valid_marker),
      'Valid marker passes validation'
    );

    $this->assertFalse(
      $this->isMarkerValid($invalid_marker_missing_coords),
      'Marker with missing coordinates fails validation'
    );

    $this->assertFalse(
      $this->isMarkerValid($invalid_marker_out_of_range),
      'Marker with out-of-range coordinates fails validation'
    );
  }

  /**
   * Utility method to convert decimal to percentage.
   */
  private function decimalToPercentage($decimal) {
    return $decimal * 100;
  }

  /**
   * Utility method to convert pixel dimensions to decimal (0-1 range).
   */
  private function pixelToDecimal($pixels, $container_size) {
    return $pixels / $container_size;
  }

  /**
   * Utility method to calculate font size based on marker dimensions.
   */
  private function calculateFontSize($width, $height) {
    $aspectRatio = $width / $height;

    if ($aspectRatio < 3) {
      // For markers with normal aspect ratio, use 10% of width.
      return $width * 0.1;
    }
    else {
      // For wide markers, use 25% of smallest dimension.
      $smallestDimension = min($width, $height);
      return $smallestDimension * 0.25;
    }
  }

  /**
   * Extracts marker data from a paragraph entity.
   */
  private function extractMarkerData($paragraph, $index) {
    // Get values using proper Drupal field access.
    $marker = [
      'x' => (float) ($paragraph->get('field_dme_marker_x')->getValue()[0]['value'] ?? 0) * 100,
      'y' => (float) ($paragraph->get('field_dme_marker_y')->getValue()[0]['value'] ?? 0) * 100,
    // Fixed title access.
      'title' => $paragraph->get('field_dme_marker_title')->getValue()[0]['value'] ?? '',
    ];

    // Update width/height access similarly.
    if ($paragraph->hasField('field_dme_marker_width') && !$paragraph->get('field_dme_marker_width')->isEmpty()) {
      $marker['width'] = (float) ($paragraph->get('field_dme_marker_width')->getValue()[0]['value'] ?? 0) * 100;
    }

    if ($paragraph->hasField('field_dme_marker_height') && !$paragraph->get('field_dme_marker_height')->isEmpty()) {
      $marker['height'] = (float) ($paragraph->get('field_dme_marker_height')->getValue()[0]['value'] ?? 0) * 100;
    }

    return $marker;
  }

  /**
   * Validates a marker paragraph entity.
   */
  private function isMarkerValid($paragraph) {
    // Get values using proper Drupal field access.
    $x = $paragraph->get('field_dme_marker_x')->getValue()[0]['value'] ?? NULL;
    $y = $paragraph->get('field_dme_marker_y')->getValue()[0]['value'] ?? NULL;

    // Rest of validation logic remains the same.
    if ($x === NULL || $y === NULL ||
        $x < 0 || $x > 1 ||
        $y < 0 || $y > 1) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Creates a mock paragraph with the given field values.
   */
  private function getMockParagraph($field_values) {
    // Create paragraph mock that doesn't care about parameter verification.
    $paragraph = $this->getMockBuilder('\Drupal\paragraphs\Entity\Paragraph')
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->getMock();

    // Configure hasField and isEmpty methods.
    $paragraph->method('hasField')->willReturnCallback(function ($field_name) use ($field_values) {
      return isset($field_values[$field_name]);
    });

    // Configure get method to return properly structured field items.
    $paragraph->method('get')->willReturnCallback(function ($field_name) use ($field_values) {
      // Create field list mock.
      $field_list = $this->getMockBuilder('\Drupal\Core\Field\FieldItemList')
        ->disableOriginalConstructor()
        ->getMock();

      // Configure field list behavior.
      if (isset($field_values[$field_name])) {
        $value = $field_values[$field_name];

        // Configure isEmpty method.
        $field_list->method('isEmpty')->willReturn($value === NULL);

        if ($value !== NULL) {
          // Store value directly on field list as property.
          $field_list->value = $value;

          // Set getValue method to return proper structure.
          $field_list->method('getValue')->willReturn([['value' => $value]]);
        }
      }
      else {
        $field_list->method('isEmpty')->willReturn(TRUE);
      }

      return $field_list;
    });

    return $paragraph;
  }

}
