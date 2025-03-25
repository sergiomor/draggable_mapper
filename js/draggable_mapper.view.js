/**
 * @file
 * JavaScript behaviors for the Draggable Mapper Entity (View Mode).
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Draggable Mapper Entity in view mode.
   */
  Drupal.behaviors.draggableMapperView = {
    attach: function (context, settings) {
      once('mapper-view', '.draggable-mapper-entity', context).forEach(function(mapContainer) {
        initViewMarkers(context);
      });
    }
  };
  
  /**
   * Initialize markers in view mode (not draggable).
   */
  function initViewMarkers(context) {
    // Get all view-mode markers in this context
    const $markers = $(context).find('.draggable-mapper-entity--view-mode-default .dme-marker');
    
    if ($markers.length === 0) {
      return;
    }
    
    // Initialize each marker
    $markers.each(function() {
      const $marker = $(this);
      // Add hover interactions for tooltips if description exists
      if ($marker.find('.dme-marker-description').length > 0) {
        initializeTooltipBehavior($marker);
      }
      
      // Add click interactions for showing more details
      initializeMarkerClickBehavior($marker);
    });
  }
  
  /**
   * Initialize tooltip behavior for markers with descriptions.
   */
  function initializeTooltipBehavior($marker) {
    const $description = $marker.find('.dme-marker-description');
    
    // Initially hide the description
    $description.hide();
    
    // Show description on hover
    $marker.on('mouseenter', function() {
      $description.fadeIn(200);
    });
    
    // Hide description when mouse leaves
    $marker.on('mouseleave', function() {
      $description.fadeOut(200);
    });
  }
  
  /**
   * Initialize click behavior for markers.
   */
  function initializeMarkerClickBehavior($marker) {
    // Add click event to show more detailed information if needed
    $marker.on('click', function(e) {
      e.preventDefault();
      
      // Toggle active state for the marker
      $marker.toggleClass('dme-marker--active');
      
      // If you have a more detailed view to show (like a modal), you can trigger it here
      // For example:
      // const markerId = $marker.attr('id');
      // showMarkerDetailModal(markerId);
    });
  }
  
})(jQuery, Drupal, drupalSettings, once);
