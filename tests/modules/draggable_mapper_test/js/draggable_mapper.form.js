/**
 * @file
 * JavaScript for the draggable mapper form functionality.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Attaches the behavior for the draggable marker.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.draggableMapper = {
    attach: function (context, settings) {
      once('draggable-mapper', '.draggable-mapper-container', context).forEach(function (container) {
        // Simplified version for testing purposes
        const $marker = $(container).find('.draggable-marker');
        const $fieldX = $(container).find('.field-dme-marker-x input');
        const $fieldY = $(container).find('.field-dme-marker-y input');
        
        // Basic functionality to update fields when marker is moved
        if ($marker.length && $fieldX.length && $fieldY.length) {
          // Test implementation would go here
          // This is a minimal implementation for tests
        }
      });
    }
  };
})(jQuery, Drupal, once);
