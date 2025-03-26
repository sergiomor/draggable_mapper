/**
 * @file
 * JavaScript behaviors for the Draggable Mapper Entity (View Mode).
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for Draggable Mapper view mode.
   */
  Drupal.behaviors.draggableMapperView = {
    attach: function (context, settings) {
      // Initialize the map container once
      once('dme-container', '.dme-container', context).forEach(function(container) {
        initializeMapContainer($(container));
      });
    }
  };

  /**
   * Initialize the entire map container
   */
  function initializeMapContainer($container) {
    // Find all markers within this container
    const $markers = $container.find('.dme-marker');
    
    // Initialize markers with descriptions
    $markers.filter('[data-has-description="true"]').each(function() {
      initializeModalForMarker($(this));
    });
    
    // Initialize other markers
    $markers.not('[data-has-description="true"]').each(function() {
      initializeMarkerClickBehavior($(this));
    });
  }

  /**
   * Initialize modal functionality for a marker.
   */
  function initializeModalForMarker($marker) {
    const markerId = $marker.attr('data-marker-id');
    const $modal = $('#dme-marker-modal-' + markerId);
    
    if ($modal.length === 0) {
      console.error('Modal not found for marker ID:', markerId);
      return;
    }
    
    // Add click handler to open modal
    $marker.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      // Close any open modals
      $('.dme-marker-modal').hide();
      
      // Show the modal
      $modal.show();
      
      // Add keyboard event listener for escape key
      $(document).on('keydown.dme-modal', function(e) {
        if (e.key === 'Escape') {
          closeAllModals();
        }
      });
      
      console.log('Modal opened for marker ID:', markerId);
    });
    
    // Add close button handler
    $modal.find('.dme-modal-close').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeAllModals();
    });
    
    // Close when clicking outside the modal
    $(document).on('click.dme-modal-outside', function(e) {
      if (!$(e.target).closest('.dme-marker-modal').length && 
          !$(e.target).closest('.dme-marker').length) {
        closeAllModals();
      }
    });
  }
  
  /**
   * Close all modals and remove event listeners
   */
  function closeAllModals() {
    $('.dme-marker-modal').hide();
    $(document).off('keydown.dme-modal');
    $(document).off('click.dme-modal-outside');
  }
  
  /**
   * Initialize click behavior for markers without modals
   */
  function initializeMarkerClickBehavior($marker) {
    $marker.on('click', function(e) {
      e.preventDefault();
      // Toggle active state for the marker
      $marker.toggleClass('dme-marker--active');
    });
  }

})(jQuery, Drupal, drupalSettings, once);
