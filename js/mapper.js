/**
 * @file
 * JavaScript behaviors for the Draggable Mapper Entity.
 */
(function ($, Drupal, drupalSettings, once) {
    'use strict';
  
    /**
     * Behavior for Draggable Mapper Entity.
     */
    Drupal.behaviors.filePreview = {
        attach: function (context, settings) {
          once('file-preview', 'input[type="file"]', context).forEach(function(fileInput) {
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
                  $('.dme-image-preview').html(previewMarkup);
                };
                
                // Read the selected file as a data URL
                reader.readAsDataURL(this.files[0]);
              }
            });
          });
        }
      };
  
  })(jQuery, Drupal, drupalSettings, once);