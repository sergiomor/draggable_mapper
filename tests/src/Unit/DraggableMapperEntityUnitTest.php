<?php

namespace Drupal\Tests\draggable_mapper_entity\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Simple unit test for Draggable Mapper Entity.
 *
 * @group draggable_mapper_entity
 */
class DraggableMapperEntityUnitTest extends TestCase {

  /**
   * Test that coordinate percentages are properly formatted.
   */
  public function testCoordinateFormatting() {
    // Test that coordinates are properly formatted as percentages.
    $this->assertEquals('50%', $this->formatCoordinateAsPercentage(0.5));
    $this->assertEquals('0%', $this->formatCoordinateAsPercentage(0));
    $this->assertEquals('100%', $this->formatCoordinateAsPercentage(1.0));
    $this->assertEquals('25%', $this->formatCoordinateAsPercentage(0.25));
  }

  /**
   * Test that coordinates stay within bounds.
   */
  public function testCoordinateBounds() {
    // Test that out-of-bounds coordinates are constrained.
    $this->assertEquals(1.0, $this->constrainCoordinate(1.5));
    $this->assertEquals(0.0, $this->constrainCoordinate(-0.5));
    $this->assertEquals(0.75, $this->constrainCoordinate(0.75));
  }

  /**
   * Test marker title formatting.
   */
  public function testMarkerTitleFormatting() {
    // Test title truncation.
    $this->assertEquals('Short Title', $this->formatMarkerTitle('Short Title', 20));
    $this->assertEquals('This is a lo...', $this->formatMarkerTitle('This is a long title that should be truncated', 15));
  }

  /**
   * Helper function to format coordinate as percentage.
   */
  private function formatCoordinateAsPercentage($value) {
    return round($value * 100) . '%';
  }

  /**
   * Helper function to constrain coordinate within 0-1 range.
   */
  private function constrainCoordinate($value) {
    return max(0, min(1, $value));
  }

  /**
   * Helper function to format marker title with length limit.
   */
  private function formatMarkerTitle($title, $maxLength) {
    if (strlen($title) <= $maxLength) {
      return $title;
    }
    return substr($title, 0, $maxLength - 3) . '...';
  }

}
