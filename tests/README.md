# Draggable Mapper Module Test Plan

## Overview

This test plan follows Drupal standards to ensure the Draggable Mapper module functions correctly across all features, with special focus on the enhanced marker functionality. The plan includes unit, kernel, functional, and JavaScript tests to validate all aspects of the module.

## Required Test Files

### 1. Base Test Classes

#### `DraggableMapperTestBase.php` (Existing)
- Purpose: Provides common setup and utility methods for all functional tests
- Content:
  - Module dependencies setup
  - Admin user creation
  - Test image generation
  - Entity creation utilities

#### `DraggableMapperJavascriptTestBase.php` (New)
- Purpose: Base class for JavaScript tests
- Location: `tests/src/FunctionalJavascript/`
- Content:
  - Extends `DrupalJavascriptTestBase`
  - Shared JavaScript test configurations
  - Wait conditions for AJAX operations
  - Additional assertions for JavaScript functionality

### 2. Functional Tests

#### `DraggableMapperTest.php` (Existing)
- Purpose: Tests basic mapper functionality and hidden fields
- Updates needed:
  - Ensure tests for hidden coordinate fields (X/Y) work properly
  - Maintain tests for hidden dimension fields (width/height)

#### `DraggableMapperCreationTest.php` (Existing)
- Purpose: Tests entity creation and basic CRUD operations
- No modifications needed

#### `DraggableMapperMarkerTest.php` (New)
- Purpose: Tests enhanced marker functionality
- Location: `tests/src/Functional/`
- Content:
  - Test marker creation with different types (text vs. icon)
  - Validate hidden fields behavior
  - Test for presence of required JavaScript

### 3. JavaScript Tests

#### `DraggableMapperResizableTest.php` (New)
- Purpose: Tests marker resizing behavior with JavaScript interaction
- Location: `tests/src/FunctionalJavascript/`
- Content:
  - Test icon marker resizing with aspect ratio maintained
  - Test text marker free resizing
  - Test containment boundaries
  - Test height auto-adjustment

#### `DraggableMapperInlineEntityFormTest.php` (New)
- Purpose: Tests integration with Inline Entity Form module
- Location: `tests/src/FunctionalJavascript/`
- Content:
  - Test simple widget integration
  - Test complex widget integration
  - Test marker addition within IEF context
  - Validate coordinates and dimensions are properly saved

### 4. Unit and Kernel Tests

#### `DraggableMapperUnitTest.php` (New)
- Purpose: Unit tests for utility functions and services
- Location: `tests/src/Unit/`
- Content:
  - Test coordinate calculation methods
  - Test marker positioning utilities

#### `DraggableMapperEntityTest.php` (New)
- Purpose: Kernel tests for entity structure and fields
- Location: `tests/src/Kernel/`
- Content:
  - Test entity field definitions
  - Test field storage
  - Test entity save/load operations

### 5. Test Module

#### `draggable_mapper_test.module` (Existing)
- Purpose: Support module containing test configurations
- Location: `tests/modules/draggable_mapper_test/`
- Content:
  - Content type definitions
  - Entity reference field configurations
  - Inline Entity Form configurations
  - Test templates

#### `draggable_mapper_test.info.yml` (Existing)
- Purpose: Test module definition
- Content:
  - Dependencies on draggable_mapper and inline_entity_form

## Test Scenarios

### Entity Creation and Management

1. **Basic Entity Creation**
   - Create draggable mapper entity with image
   - Verify fields are properly saved
   - Test entity edit and delete operations

2. **Marker Management**
   - Add markers to entity
   - Verify marker position storage (hidden X/Y fields)
   - Test marker removal

3. **Integration with Nodes**
   - Create node with entity reference to draggable mapper
   - Test entity reference display modes
   - Verify marker rendering in node context

### Enhanced Marker Functionality

1. **Marker Resizing Behavior**
   - Test icon markers maintain aspect ratio
   - Test text markers can be freely resized
   - Verify containment within boundaries works
   - Test height management for different marker types

2. **Marker Styling and Display**
   - Test marker CSS classes are correctly applied
   - Test font size calculation based on dimensions
   - Verify marker positioning is correct

### Inline Entity Form Integration

1. **Simple Widget**
   - Test creating draggable mapper within node form
   - Verify marker addition works within IEF context
   - Test saving and loading entity values

2. **Complex Widget**
   - Test multiple entities in IEF complex widget
   - Verify markers maintain positions between saves
   - Test removal and re-addition of markers

### JavaScript Functionality

1. **UI Interaction**
   - Test dragging markers
   - Test resizing with mouse
   - Verify AJAX operations for marker addition/removal

2. **Event Handling**
   - Test marker position update events
   - Test coordinate calculation during resize
   - Verify containment boundaries are enforced

## Implementation Notes

1. **File Modifications**
   - Existing test files should be refactored to extend base classes
   - New test files should be created following Drupal coding standards
   - Test module should be properly namespaced

2. **Test Dependencies**
   - Add inline_entity_form as a test dependency
   - Ensure jQuery UI libraries are available for testing

3. **Test Data**
   - Create a central repository for test images
   - Standardize entity and field names across tests

## Key Feature Testing: Enhanced Marker Functionality

The tests should focus particularly on validating the enhanced marker functionality:

1. **Proper Resizable Behavior**
   - Markers should be properly resizable as soon as they're created
   - Icon markers must maintain aspect ratio during resizing
   - Text markers should allow free resizing without aspect ratio constraints

2. **Containment**
   - Markers cannot be resized beyond container boundaries
   - Test jQuery UI's containment option for resizing operations

3. **Height Management**
   - Icon markers use height:auto to adapt to content
   - Text markers have appropriate minimum height
   - Font size calculation based on marker dimensions is correct

4. **Coordinate Field Handling**
   - X/Y coordinate fields (field_dme_marker_x and field_dme_marker_y) should be hidden in the form
   - Width/height fields should also be hidden
   - Values should still be correctly saved to the database
