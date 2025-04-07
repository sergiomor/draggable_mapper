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
    
    // Initialize font sizes for all markers
    $markers.each(function() {
      initializeMarkerFontSize($(this));
    });
    
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
   * Initialize font size for a marker based on its dimensions
   * @param {Object} $marker - jQuery object for the marker
   */
  function initializeMarkerFontSize($marker) {
    // Get marker dimensions
    var width = $marker.width();
    var height = $marker.height();
             
    // Base calculation on width for tall markers to prevent text overflow
    var fontSize;
    var aspectRatio = width / height;
                
    if (aspectRatio < 3) {
      // For tall markers, base font size on width instead of height
      fontSize = width * 0.1;
    } else {
      // For square or wide markers, use smallest dimension
      var smallestDimension = Math.min(width, height);
      fontSize = smallestDimension * 0.25;
    }
    
    // Set a minimum readable font size
    fontSize = Math.max(fontSize, 12);            
    $($marker).css('font-size', fontSize + 'px');
    $($marker).attr('data-font-size', fontSize);
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
      $modal.show().addClass('opened');
      
      // Add keyboard event listener for escape key
      $(document).on('keydown.dme-modal', function(e) {
        if (e.key === 'Escape') {
          closeAllModals();
        }
      });
    });
    
    // Add close button handler
    $modal.find('.dme-modal-close').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $modal.hide().removeClass('opened');
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
    $('.dme-marker-modal').hide().removeClass('opened');;
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