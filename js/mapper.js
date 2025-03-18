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
        // Process marker title fields
        once('marker-title-handler', '.field--name-field-dme-marker-title input[type="text"]', context).forEach(function(titleInput) {
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
        var title = $titleInput.val() || 'Untitled marker';
        
        // Create or update the marker, but only if there's no icon
        var $marker = $('#dme-marker-' + delta);
        if (!$marker.length || !$marker.hasClass('has-icon')) {
          ensureMarkerExists(delta, title);
        }     
      }
    }
   
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
          $marker.addClass('has-icon').removeClass('has-title');
        } else {
          // We need to create the marker first
          ensureMarkerExists(delta, '');
          // Now update it with the icon
          var $newMarker = $('#dme-marker-' + delta);
          $newMarker.find('.dme-marker-wrapper').empty().html('<img src="' + e.target.result + '" alt="Marker Icon" />');
          $newMarker.addClass('has-icon').removeClass('has-title');

        }
      };
      reader.readAsDataURL($iconInput[0].files[0]);

    }

    /**
     * Ensures a marker with the given delta exists in the map container.
     */
    function ensureMarkerExists(delta, title) {
      var $container = $('.dme-container-wrapper');
      var $marker = $('#dme-marker-' + delta);
      if (!$marker.length) {
        // Create new marker
        var markerHtml = '<div id="dme-marker-' + delta + '" class="dme-marker js-form-wrapper form-wrapper" role="application" aria-label="Interactive component positioning interface" aria-describedby="dme-instructions" aria-live="polite">';
        markerHtml += '<div class="dme-marker-wrapper">';    
        markerHtml += '</div></div>';
        $container.append(markerHtml);
      }
      // Update marker text
      $marker.find('.dme-marker-wrapper').text(title);
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
      $('.dme-marker.has-icon').each(function() {
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
              $('#dme-marker-' + delta).removeClass('has-icon').addClass('has-title');
            }
            
            return false; // Break the loop after finding the matching paragraph
          }
        });
      });
    }
  
  })(jQuery, Drupal, drupalSettings, once);