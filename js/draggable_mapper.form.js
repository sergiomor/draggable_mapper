/**
 * @file
 * JavaScript behaviors for the Draggable Mapper Entity.
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
  * Behavior for Draggable Mapper Entity file preview.
  */
  Drupal.behaviors.filePreview = {
      attach: function (context, settings) {
        once('file-preview', '.field--name-field-dme-image input[type="file"]', context).forEach(function(fileInput) {
          $(fileInput).on('change', function() {
            if (this.files && this.files[0]) {
              // Create a file reader to generate a preview from the selected file
              var reader = new FileReader();
              
              reader.onload = function(e) {
                // Generate preview markup client-side
                var previewMarkup = '<div class="dme-image-wrapper">' +
                  '<img src="' + e.target.result + '" alt="Map Image" />' +
                  '</div>';
                
                // Update both containers with the new client-side generated preview
                $('.dme-image').html(previewMarkup);
              };
              
              // Read the selected file as a data URL
              reader.readAsDataURL(this.files[0]);
            }
          });
        });
      }
    };

  /**
   * Behavior for Draggable Mapper Entity marker handling.
   */
  Drupal.behaviors.markerHandler = {
    attach: function (context, settings) {

      // Make markers draggable
      initDraggableMarkers(context);

      // Initialize the Container as Droppable
      $('.dme-container-wrapper').droppable({
        accept: '.dme-marker'
      });

      // Check for existing uploaded marker icons on page load
      once('check-existing-icons', 'body', context).forEach(function() {
        initializeExistingMarkerIcons();
      });

      // Process marker title fields
      once('marker-title-handler', '.field--name-field-dme-marker-title input[type="text"]', context).forEach(function(titleInput) {
        // Initialize the marker with current value or default
        updateMarker($(titleInput));
        $(titleInput).on('input', function() {
          updateMarker($(this));
        });
      });
      
      // Process marker icon fields
      once('marker-icon-handler', '.field--name-field-dme-marker-icon input[type="file"]', context).forEach(function(iconInput) { 
        $(iconInput).on('change', function() {
          updateMarkerIcon($(this));
        });
      });

      once('paragraph-operations', '.field--name-field-dme-marker', context).forEach(function(paragraphField) {
        // Set up a MutationObserver to detect when paragraphs are removed
        var observer = new MutationObserver(function(mutations) {
          // Check if we need to remove markers
          checkAndRemoveOrphanedMarkers();
        });
        
        // Observe the paragraph container for changes to its children
        observer.observe(paragraphField, {
          childList: true,
          subtree: true
        });
      });
      
      // Listen for AJAX events to capture file removal
      $(document).ajaxSuccess(function(event, xhr, settings) {
        // Check if this is a file removal AJAX call
        if (settings.url && settings.url.indexOf('field_dme_marker_icon') !== -1) { 
          // Give a moment for the DOM to update
          setTimeout(checkAndUpdateMarkers, 500);
        }

        // Check if this might be a paragraph add/update operation
        if (settings.url && (
          settings.url.indexOf('field_dme_marker') !== -1 || 
          settings.url.indexOf('paragraphs') !== -1 ||
          settings.url.indexOf('ajax_form') !== -1)) {
            // Give a moment for the DOM to update
            setTimeout(function() {
              // Process all title inputs, including newly added ones
              $('.field--name-field-dme-marker-title input[type="text"]').each(function() {
                var $input = $(this);
                // Update the marker
                updateMarker($input);
              });
            }, 500);
          }
      });
    }
  };

  /**
   * Behavior to manage the unmapped wrapper visibility based on image presence.
   */
  Drupal.behaviors.draggableMapperWrapperManager = {
    attach: function (context, settings) {
      $(once('wrapper-manager', 'body', context)).each(function() {
        function updateWrapperVisibility() {
          // Target the unmapped wrapper container
          const $wrapper = $('#dme-unmapped-wrapper');
          // Target the unmapped wrapper description
          const $description = $('.form-item__description');
          
          // Check if image exists using multiple methods
          const imgElements = $('.field--name-field-dme-image img');
          const imgInputs = $('input[name^="field_dme_image"][name$="[fids]"][value!=""]');
          const hasImage = imgElements.length > 0 || imgInputs.length > 0;
          
          // Update wrapper visibility based on image presence
          if (hasImage) {
            $wrapper.removeClass('empty-container');
            $description.removeClass('empty-container');
          } 
        }
        
        // Run immediately and after any AJAX completes
        updateWrapperVisibility();
        
        $(document).ajaxComplete(function() {
          updateWrapperVisibility();
        });
      });
    }
  };

  /**
   * Behavior to make Mapped Marker resizable.
   */
  Drupal.behaviors.resizableMarker = {
    attach: function (context, settings) {

      once('resizable-marker', '.dme-mapped-marker', context).forEach(function(marker) {
        
        // Make the marker resizable
        makeMarkerResizable($(marker));

        // Resize marker font
        resizeFont($(marker));
        
      });
      
      // Ensure resizable property is maintained after drag operations
      $(document).on('dragstop', '.dme-mapped-marker', function() {

        // Re-initialize resizable if needed
        if (!$(this).hasClass('ui-resizable')) {
          
          $(this).resizable({
            aspectRatio: $(this).hasClass('dme-marker-icon') ? true : false,
            handles: 'all',
            containment: '.dme-container-wrapper',
            minWidth: 100
          });
        }
      });
    }
  };

  /**
   * Updates a marker based on its title input field.
   */
  function updateMarker($titleInput) {
    // Find the paragraph item containing this title field
    var $paragraphItem = $titleInput.closest('.paragraph-type--dme-marker');
    if (!$paragraphItem.length) {
      $paragraphItem = $titleInput.closest('.paragraphs-subform');
    }
    
    if ($paragraphItem.length) {
      // Get paragraph delta (index)
      var delta = getDeltaFromParagraph($paragraphItem);
      // Check if this paragraph already has an icon uploaded or if marker already has an icon
      var hasUploadedFile = $paragraphItem.find('.field--name-field-dme-marker-icon .file').length > 0;
      var $marker = $('#dme-marker-' + delta);
      var hasIconClass = $marker.length && $marker.hasClass('dme-marker-icon');
      
      // If there's an uploaded file or the marker already has an icon class, don't update with the title
      if (hasUploadedFile || hasIconClass) {
        return;
      }        
      // Get the input value, but use a default for the marker if empty
      var inputValue = $.trim($titleInput.val());
      var displayTitle = inputValue || Drupal.t('Untitled marker');

      // Create or update the marker, but only if there's no icon
      if (!$marker.length) {
        ensureMarkerExists(delta, displayTitle);
        // Mark it explicitly as a title marker
        $('#dme-marker-' + delta).addClass('has-title');
      } else if (!$marker.hasClass('dme-marker-icon')) {
        // Update the title if it's not an icon marker
        $marker.find('.dme-marker-wrapper').html('<span class="marker-text">' + displayTitle + '</span>');
        $marker.addClass('has-title');
      }
    }
  }

  /**
 * Set the height of unmapped markers to equal to mapped markers
 */
/*   function setUnmappedMarkerHeight() {
    var $containerWrapper = $('.dme-container-wrapper');
    if ($containerWrapper.length) {
      var containerHeight = $containerWrapper.height();
      var markerHeight = containerHeight * 0.1; // 10% of container height
      $('.dme-unmapped-marker, .dme-no-markers-message').css('height', markerHeight + 'px');
    }
  }  */
 
  /**
   * Updates a marker icon based on the file input.
   */
  function updateMarkerIcon($iconInput) {
    // Find the paragraph item containing this icon field
    var $paragraphItem = $iconInput.closest('.paragraph-type--dme-marker');
    if (!$paragraphItem.length) {
      $paragraphItem = $iconInput.closest('.paragraphs-subform');
    }
    
    if (!$paragraphItem.length) {
      return;
    }
    
    // Get paragraph delta (index)
    var delta = getDeltaFromParagraph($paragraphItem);
    if (delta === null) {
      return;
    }
    
    var reader = new FileReader();
    
    // Update marker with image
    reader.onload = function(e) {
      // Update marker with image
      var $marker = $('#dme-marker-' + delta);
      if ($marker.length) {
        // First empty the wrapper completely
        var $wrapper = $marker.find('.dme-marker-wrapper');
        $wrapper.empty();
        // Then add only the image
        $wrapper.html('<img src="' + e.target.result + '" alt="Marker Icon" />');
        // Add a class to indicate this marker has an icon
        $marker.addClass('dme-marker-icon').removeClass('has-title');
        // Set height to auto for the marker
        $marker.css('height', 'auto');
        if ($marker.hasClass('dme-mapped-marker')) {
          $marker.resizable({
            aspectRatio: true,
            handles: 'all',
            minWidth: 100,
            containment: '.dme-container-wrapper',
          });
        }
      } else {
        // We need to create the marker first
        ensureMarkerExists(delta, '');
        // Now update it with the icon
        var $newMarker = $('#dme-marker-' + delta);
        $newMarker.find('.dme-marker-wrapper').empty().html('<img src="' + e.target.result + '" alt="Marker Icon" />');
        $newMarker.addClass('dme-marker-icon').removeClass('has-title');
      }     
    };
    reader.readAsDataURL($iconInput[0].files[0]);

  }

  /**
   * Ensures a marker with the given delta exists in the map container.
   */
  function ensureMarkerExists(delta, title) {
    var $container = $('.dme-unmapped-wrapper');
    var $marker = $('#dme-marker-' + delta);
    if (!$marker.length) {
      // Create new marker
      var markerHtml = '<div id="dme-marker-' + delta + '" class="dme-marker dme-unmapped-marker js-form-wrapper form-wrapper" role="application" aria-label="Interactive component positioning interface" aria-describedby="dme-instructions" aria-live="polite">';
      markerHtml += '<div class="dme-marker-wrapper">' + title + '</div></div>';
      $container.append(markerHtml);
      // Check if we need to hide the no markers message
      checkAndHideNoMarkersMessage();
    }
    // Update marker text
    $marker.find('.dme-marker-wrapper').text(title);
  }

  /**
   * Check if there are markers in the unmapped wrapper and hide the no markers message if needed
   */
  function checkAndHideNoMarkersMessage() {
    var $container = $('.dme-unmapped-wrapper');
    if ($container.find('.dme-marker').length > 0) {
      // Find the message if it exists
      var $message = $container.find('.dme-no-markers-message');
      if ($message.length > 0) {
        // Fade out the message before removing it
        $message.fadeOut(400, function() {
          $(this).remove();
        });
      }
    } else {
      // If no markers are present and the message doesn't exist, add it with fade in
      if ($container.find('.dme-no-markers-message').length === 0) {
        var $message = $('<div class="dme-no-markers-message" style="display: none;">' + 
                       Drupal.t('Add new markers to be mapped') + '</div>');
        $container.append($message);
        $message.fadeIn(400);
      }
    }
  }

  /**
   * Check for and remove markers that no longer have a corresponding paragraph
   */
  function checkAndRemoveOrphanedMarkers() {
    // Collect all deltas from paragraphs
    var validDeltas = [];
    
    // Find all marker paragraphs
    $('.paragraph-type--dme-marker .paragraphs-subform').each(function() {
      var delta = getDeltaFromParagraph($(this));
      if (delta !== null && delta !== undefined) {
        validDeltas.push(delta);
      }
    });
    
    // Check for orphaned markers and remove them
    $('.dme-marker').each(function() {
      var markerId = $(this).attr('id');
      if (!markerId) return;
      
      // Extract delta from marker ID
      var delta = parseInt(markerId.replace('dme-marker-', ''), 10);
      
      // If this delta is not in our valid deltas list, remove it
      if (validDeltas.indexOf(delta) === -1) {
        $(this).remove();
      }
    });
    // Check if we need to show or hide the no markers message
    checkAndHideNoMarkersMessage();
  }
  
  /**
   * Gets the delta (index) from a paragraph item.
   */
  function getDeltaFromParagraph($paragraphItem) {  
    // Check for any element with field-dme-marker-X in its ID
    var $anyElement = $paragraphItem.find('[id*="field-dme-marker-"], [data-drupal-selector*="field-dme-marker-"]').first();
    if ($anyElement.length) {
      var attrValue = $anyElement.attr('id') || $anyElement.attr('data-drupal-selector');
      var elemMatches = attrValue.match(/field-dme-marker-(\d+)/);
      if (elemMatches && elemMatches[1]) {
        return parseInt(elemMatches[1], 10);
      }
    }
    return 0;
  }

  /**
   * Checks for markers that need to revert to text after a file has been removed.
   */
  function checkAndUpdateMarkers() {
    // Find all markers and check which ones need to revert to text
    $('.dme-marker.dme-marker-icon').each(function() {
      var markerId = $(this).attr('id');
      if (!markerId) return;
      
      // Extract delta from marker ID
      var delta = markerId.replace('dme-marker-', '');
      
      // Find the corresponding paragraph by matching the delta
      var foundParagraph = false;
      $('.paragraph-type--dme-marker, .paragraphs-subform').each(function() {
        var paragraphDelta = getDeltaFromParagraph($(this));
        if (paragraphDelta == delta) {
          foundParagraph = true;
          var $paragraphItem = $(this);
          
          // Check if there's a file in the file input
          var $iconInput = $paragraphItem.find('.field--name-field-dme-marker-icon input[type="file"]');
          var hasFile = $iconInput.length && $iconInput[0].files && $iconInput[0].files.length > 0;
          
          // Also check if there's already an uploaded file
          var hasUploadedFile = $paragraphItem.find('.field--name-field-dme-marker-icon .file').length > 0;
          
          if (!hasFile && !hasUploadedFile) {
            // No file, revert to text
            var $titleInput = $paragraphItem.find('input[name*="field_dme_marker_title"]');
            var title = $titleInput.length ? $titleInput.val() : 'Marker ' + delta;
            
            var $wrapper = $('#dme-marker-' + delta).find('.dme-marker-wrapper');
            $wrapper.empty();
            $wrapper.text(title || 'Marker ' + delta);
            
            // Update classes
            $('#dme-marker-' + delta).removeClass('dme-marker-icon').addClass('has-text');
            // Reset the fixed height when reverting to text
            $('#dme-marker-' + delta).css('height', '');
            // Make the marker resizable
            $('#dme-marker-' + delta).resizable({
              aspectRatio: false,
              handles: 'all',
              minHeight: 50,
              minWidth: 100,
              containment: '.dme-container-wrapper',
              resize: function(event, ui) {
                // Update marker font size in real-time
                resizeFont($(this));
              },
            });
          }
          
          return false; // Break the loop after finding the matching paragraph
        }
      });
    });
  }

  /**
   * Initialize font size for a marker based on its dimensions
   */
  function resizeFont($marker) {

    // Update marker font size in real-time
    var width = $marker.width(); 
    var height = $marker.height();
                
    // Base calculation on width for tall markers
    var fontSize;
    var aspectRatio = width / height;                       
    if (aspectRatio < 2.5) {
      fontSize = width * 0.1;
    } else {
      var smallestDimension = Math.min(width, height);
      fontSize = smallestDimension * 0.25;
    }
    
    fontSize = Math.max(fontSize, 12);            
    $($marker).css('font-size', fontSize + 'px');
    $($marker).attr('data-font-size', fontSize);

  }

  /**
   * Make a marker resizable with appropriate options based on marker type
   * @param {Object} $marker - jQuery object for the marker element
   */
  function makeMarkerResizable($marker) {
    // Wait until jQuery UI's resizable method is available
    if (typeof $.fn.resizable !== 'function') {
      // Retry after a short delay
      setTimeout(function () {
        makeMarkerResizable($marker);
      }, 100);
      return;
    }
    // If marker is already resizable, destroy it first
    if ($marker.hasClass('ui-resizable')) {
      $marker.resizable('destroy');
    }
    $marker.resizable({
      //aspectRatio: true,
      aspectRatio: $marker.hasClass('dme-marker-icon') ? true : false,
      handles: 'all',
      minWidth: 100,
      minHeight:  $marker.hasClass('dme-marker-icon') ? '' : 50,
      containment: '.dme-container-wrapper',
      start: function(event, ui) {
        $(this).addClass('dme-marker-resizing');
      },
      resize: function(event, ui) {
        // Update marker font size in real-time
        if ($(this).hasClass('has-title')) {
          resizeFont($(this));
        }
      },
      stop: function(event, ui) {
        $(this).removeClass('dme-marker-resizing');
      
          // Get marker data
          var markerId = $(this).attr('id');
          var delta = markerId.replace('dme-marker-', '');
          
          // Update hidden fields with new size information
          if (delta) {
            // Calculate size as percentage of container
            var containerWidth = $('.dme-container-wrapper').width();
            var containerHeight = $('.dme-container-wrapper').height();
            var markerWidth = ui.size.width;
            var markerHeight = ui.size.height;
            
            // Convert width to decimal between 0 and 1
            var widthDecimal = (markerWidth / containerWidth).toFixed(6);
            
            // Convert height to decimal between 0 and 1
            var heightDecimal = (markerHeight / containerHeight).toFixed(6);
            
            // Find and update the corresponding width input field
            $('input[name*="field_dme_marker_width"][name*="[' + delta + ']"]').val(widthDecimal);
            
            // Find and update the corresponding height input field
            $('input[name*="field_dme_marker_height"][name*="[' + delta + ']"]').val(heightDecimal);
            // Store size information in custom data attributes for persistence
            $(this).attr('data-size-width', markerWidth);
            $(this).attr('data-size-height', markerHeight);
        }
      }
    });
  }

  /**
   * Checks for existing uploaded marker icons on page load and updates markers accordingly
   */
  function initializeExistingMarkerIcons() {
    // Find all marker paragraphs with uploaded icons
    $('.paragraph-type--dme-marker .field--name-field-dme-marker-icon .file, .paragraphs-subform .field--name-field-dme-marker-icon .file').each(function() {
      // Get the paragraph delta
      var $paragraphItem = $(this).closest('.paragraph-type--dme-marker');
      if (!$paragraphItem.length) {
        $paragraphItem = $(this).closest('.paragraphs-subform');
      }
      
      if (!$paragraphItem.length) {
        return;
      }
      
      var delta = getDeltaFromParagraph($paragraphItem);
      if (delta === null) {
        return;
      }
    
      // Find the image source - look for img or a.href for file URLs
      var imgSrc = $(this).find('img').attr('src');
      if (!imgSrc) {
        // Try finding href in case it's a file link
        imgSrc = $(this).find('a').attr('href');
      }

      if (!imgSrc) {
        return;
      }
      
      // Update marker with this icon
      var $marker = $('#dme-marker-' + delta);
      if ($marker.length) {
        // First empty the wrapper completely
        var $wrapper = $marker.find('.dme-marker-wrapper');
        $wrapper.empty();
        // Then add only the image
        $wrapper.html('<img src="' + imgSrc + '" alt="Marker Icon" />');
        // Add a class to indicate this marker has an icon
        $marker.addClass('dme-marker-icon').removeClass('has-title');
      } else {
        // We need to create the marker first
        ensureMarkerExists(delta, '');
        // Now update it with the icon
        var $newMarker = $('#dme-marker-' + delta);
        $newMarker.find('.dme-marker-wrapper').empty().html('<img src="' + imgSrc + '" alt="Marker Icon" />');
        $newMarker.addClass('dme-marker-icon').removeClass('has-title');
      }
    });
    
    // Check and hide the no markers message if needed
    checkAndHideNoMarkersMessage();
  }

  /**
   * Initialize draggability for marker elements
   */
  function initDraggableMarkers(context) {
    // Wait until jQuery UI's draggable method is available
    if (typeof $.fn.draggable !== 'function') {
      // Retry after a short delay
      setTimeout(function () {
        initDraggableMarkers(context);
      }, 100);
      return;
    }
    // Get all markers and make them draggable
    once('draggable', '.dme-marker', context).forEach(function(marker) {
      var $marker = $(marker);
      // Fade in the marker with animation
      $marker.addClass('visible');
      $(marker).draggable({
        // Create a clone for dragging to make transitions smoother
        helper: function() {
          // Create a clone that maintains fixed dimensions
          var $clone = $(this).clone();
          var currentWidth = $(this).outerWidth();
          var currentHeight = $(this).outerHeight();
          
          // Force the helper to maintain the current marker dimensions
          $clone.css({
            'width': currentWidth,
            'height': currentHeight,
            'box-sizing': 'border-box'
          });
          
          return $clone;
        },
        appendTo: 'body', // Attach the helper to the body to avoid containment issues during drag
        zIndex: 1000, // Ensure the dragged item appears above other elements
        opacity: 0.7, // Slightly transparent while dragging
        cursor: 'move',
        
        // When drag starts
        start: function(event, ui) {
          // Store original position for revert if needed
          $(this).data('originalPosition', $(this).position());
          $(this).data('originalParent', $(this).parent());
          
          // Hide the original element while dragging the clone
          $(this).css('opacity', '0.3');
        },
        
       // During drag
        drag: function(event, ui) {
          // Check if we're over the target container
          var $container = $('.dme-container-wrapper');
          var containerOffset = $container.offset();
          
          if (containerOffset && 
              ui.position.left >= containerOffset.left && 
              ui.position.right <= containerOffset.left + $container.width() &&
              ui.position.top >= containerOffset.top && 
              ui.position.bottom <= containerOffset.top + $container.height()) {
            // Add a visual indicator that we're over the drop target
            $container.addClass('dme-drop-hover');
          } else {
            $container.removeClass('dme-drop-hover');
          }
        },
        
        // When drag stops
        stop: function(event, ui) {
          // Get marker ID from the element
          var markerId = $(this).attr('id');
          var delta = parseInt(markerId.replace('dme-marker-', ''), 10);
          var $container = $('.dme-container-wrapper');
          var $marker = $(this);
          
          // Reset opacity of original
          $marker.css('opacity', '1');
          
          // Check if we've dropped on the target container
          var containerOffset = $container.offset();
          var markerWidth = $(ui.helper).outerWidth();
          var markerHeight = $(ui.helper).outerHeight();
          var safetyMargin = 2; // 1px safety margin.
          
          // Calculate right and bottom edges
          // Use ui.offset and include the safety margin on right and bottom.
          if (containerOffset &&
            ui.offset.left >= containerOffset.left &&
            (ui.offset.left + markerWidth) <= (containerOffset.left + $container.width() + safetyMargin) &&
            ui.offset.top >= containerOffset.top &&
            (ui.offset.top + markerHeight) <= (containerOffset.top + $container.height() + safetyMargin)) {
            // Calculate position within the target container
            var relativeX = ui.offset.left - containerOffset.left;
            var relativeY = ui.offset.top - containerOffset.top;
            
            // Move the original marker to the container at the right position
            $marker.detach().appendTo($container);
            $marker.css({
              position: 'absolute',
              left: relativeX + 'px',
              top: relativeY + 'px'
            });
                                         
            // Calculate position as percentage of container size for responsive behavior
            var containerWidth = $container.width();
            var containerHeight = $container.height();
            
            var posX = (relativeX / containerWidth).toFixed(6);
            var posY = (relativeY / containerHeight).toFixed(6);

            // Update the hidden form fields with the new coordinates
            var $paragraphItem = $('.paragraph-type--dme-marker[data-delta="' + delta + '"], .paragraphs-subform[data-delta="' + delta + '"]');
            if (!$paragraphItem.length) {
              // Try alternative selectors as fallback
              $paragraphItem = $('#' + delta).closest('.paragraph-type--dme-marker, .paragraphs-subform');
              if (!$paragraphItem.length) {
                $paragraphItem = $('.paragraph-type--dme-marker, .paragraphs-subform').filter(function() {
                  return getDeltaFromParagraph($(this)) === delta;
                });
              }
            }
            
            if ($paragraphItem.length) {
              var $xField = $paragraphItem.find('input[name*="field_dme_marker_x"]');
              var $yField = $paragraphItem.find('input[name*="field_dme_marker_y"]');
              
              if ($xField.length && $yField.length) {
                // Update the form field values with the new coordinates
                $xField.val(posX);
                $yField.val(posY);
                // Trigger change event to ensure Drupal recognizes the change
                $xField.trigger('change');
                $yField.trigger('change');
              }
            }
                        
            // Mark as mapped
            $marker.removeClass('dme-unmapped-marker').addClass('dme-mapped-marker');

            // Apply resizable behavior immediately after drop
            makeMarkerResizable($marker);

            // Check if this was the last marker in the unmapped wrapper
            checkForEmptyUnmappedWrapper();
          }
          // Remove any hover effects
          $container.removeClass('dme-drop-hover');
        }
      });
    });
  }
  /**
   * Check if the unmapped wrapper is empty and show the "no markers" message with fade-in effect if it is
   */
  function checkForEmptyUnmappedWrapper() {
    var $unmappedContainer = $('.dme-unmapped-wrapper');
    // If there are no more markers in the unmapped wrapper
    if ($unmappedContainer.find('.dme-marker').length === 0) {
      // If the message doesn't exist, add it with fade in effect
      if ($unmappedContainer.find('.dme-no-markers-message').length === 0) {
        var $message = $('<div class="dme-no-markers-message" style="display: none;">' +
                      Drupal.t('Add new markers to be mapped') + '</div>');
        $unmappedContainer.append($message);
        $message.fadeIn(400);
        //setUnmappedMarkerHeight();
      }
    }
  }

})(jQuery, Drupal, drupalSettings, once);
