<?php

namespace Drupal\draggable_mapper\PreprocessHooks;

use Drupal\file\Entity\File;
use Drupal\Core\Render\Element;

/**
 * Preprocess hooks for the Draggable Mapper templates.
 */
class DraggableMapperPreprocessHook {

  /**
   * Prepares variables for Draggable Mapper templates.
   *
   * Default template: draggable-mapper.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing rendered fields.
   *   - attributes: HTML attributes for the containing element.
   */
  public static function preprocessDraggableMapper(array &$variables) {
    /** @var \Drupal\draggable_mapper\Entity\DraggableMapperEntity $entity */
    $entity = $variables['elements']['#draggable_mapper'];
    $variables['draggable_mapper'] = $entity;

    // Add the entity title explicitly.
    $variables['title'] = $entity->label();

    // Add entity label as a separate variable (Drupal convention)
    $variables['label'] = $entity->label();

    // Add the entity ID and bundle as classes.
    $variables['attributes']['class'][] = 'draggable-mapper';
    $variables['attributes']['class'][] = 'draggable-mapper--id-' . $entity->id();

    // Add view mode class.
    $variables['view_mode'] = $variables['elements']['#view_mode'];
    $variables['attributes']['class'][] = 'draggable-mapper--view-mode-' . $variables['view_mode'];

    // Process the content.
    $variables['content'] = [];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }

    // Get the map image URL.
    $variables['map_image_url'] = '';
    if (method_exists($entity, 'get') && !$entity->get('field_dme_image')->isEmpty()) {
      $image_file = File::load($entity->get('field_dme_image')->target_id);
      if ($image_file) {
        $variables['map_image_url'] = \Drupal::service('file_url_generator')->generateString($image_file->getFileUri());
        // Get alt text from image field configuration.
        $map_alt = $entity->get('field_dme_image')->alt ?? 'Map image';
        $variables['map_alt'] = !empty($map_alt) ? $map_alt : 'Map image';
      }
    }

    // Get the markers.
    $variables['markers'] = [];
    if (method_exists($entity, 'get') && !$entity->get('field_dme_marker')->isEmpty()) {
      foreach ($entity->get('field_dme_marker')->referencedEntities() as $index => $paragraph) {

        // Bypass unmapped markers (with null or empty coordinates)
        if (method_exists($paragraph, 'get') && ($paragraph->get('field_dme_marker_x')->isEmpty() ||
            $paragraph->get('field_dme_marker_y')->isEmpty() ||
            $paragraph->get('field_dme_marker_x')->value === NULL ||
            $paragraph->get('field_dme_marker_y')->value === NULL)) {
          // Skip this marker.
          continue;
        }

        // Skip width/height check if fields don't exist.
        $hasWidth = method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_width');
        $hasHeight = method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_height');

        // Check width if the field exists.
        if ($hasWidth &&
            (method_exists($paragraph, 'get') && ($paragraph->get('field_dme_marker_width')->isEmpty() ||
             $paragraph->get('field_dme_marker_width')->value === NULL))) {
          // Skip this marker.
          continue;
        }

        // Check height if the field exists.
        if ($hasHeight &&
            (method_exists($paragraph, 'get') && ($paragraph->get('field_dme_marker_height')->isEmpty() ||
             $paragraph->get('field_dme_marker_height')->value === NULL))) {
          // Skip this marker.
          continue;
        }

        $marker = [
          'x' => $paragraph->get('field_dme_marker_x')->value * 100,
          'y' => $paragraph->get('field_dme_marker_y')->value * 100,
        ];

        // Add width if the field exists.
        if ($hasWidth) {
          $marker['width'] = $paragraph->get('field_dme_marker_width')->value * 100;
        }

        // Add height if the field exists.
        if ($hasHeight) {
          $marker['height'] = $paragraph->get('field_dme_marker_height')->value * 100;
        }

        // Add title/description if available.
        if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_title') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_title')->isEmpty()) {
          $marker['title'] = $paragraph->get('field_dme_marker_title')->value;
        }
        elseif (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_title') && method_exists($paragraph, 'get') && !$paragraph->get('field_title')->isEmpty()) {
          // Try an alternate field name.
          $marker['title'] = $paragraph->get('field_title')->value;
        }
        else {
          // Use a default if no title field is found.
          $marker['title'] = 'Marker ' . ($index + 1);
        }

        if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_description') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_description')->isEmpty()) {
          $marker['description'] = [
            '#type' => 'processed_text',
            '#text' => $paragraph->get('field_dme_marker_description')->value,
            '#format' => $paragraph->get('field_dme_marker_description')->format,
          ];
        }

        // Add icon if available.
        if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_icon') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_icon')->isEmpty()) {
          $icon_file = File::load($paragraph->get('field_dme_marker_icon')->target_id);
          if ($icon_file) {
            $marker['icon_url'] = \Drupal::service('file_url_generator')->generateString($icon_file->getFileUri());

            // Get alt text from image field configuration.
            $icon_alt = $paragraph->get('field_dme_marker_icon')->alt ?? 'Marker icon';
            $marker['icon_alt'] = !empty($icon_alt) ? $icon_alt : 'Marker icon';
          }
        }

        $variables['markers'][] = $marker;
      }
    }

    // Add library for viewing.
    $variables['#attached']['library'][] = 'draggable_mapper/draggable_mapper.view';
  }

}
