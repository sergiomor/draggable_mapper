<?php

namespace Drupal\draggable_mapper\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Form controller for the draggable mapper entity forms.
 */
class DraggableMapperForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Set the entity as a form property to match
    // the structure expected by mapper.js.
    $form['#entity'] = $this->entity;
    $form['#entity_type'] = 'draggable_mapper';

    // Attach the mapper library.
    $form['#attached']['library'][] = 'draggable_mapper/draggable_mapper.form';

    // Add the preview container using the class method.
    $this->addPreviewContainer($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('The map %label has been saved.', [
      '%label' => $entity->label(),
    ]));

    $form_state->setRedirect('entity.draggable_mapper.collection');

    return $result;
  }

  /**
   * Helper function to add the preview container to a form.
   *
   * @param array $form
   *   The form array to modify.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addPreviewContainer(array &$form, ?FormStateInterface $form_state = NULL) {
    // Set coordinates fields to hidden.
    if (isset($form['field_dme_marker']) && isset($form['field_dme_marker']['widget'])) {
      // Attempt to hide the coordinate fields by making them hidden inputs.
      foreach (Element::children($form['field_dme_marker']['widget']) as $delta) {
        if (isset($form['field_dme_marker']['widget'][$delta]['subform'])) {

          // Handle X coordinate field.
          if (isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_x']) &&
              isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_x']['widget'])) {
            // Hide form element.
            $form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_x']['widget'][0]['value']['#type'] = 'hidden';
          }

          // Handle Y coordinate field.
          if (isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_y']) &&
              isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_y']['widget'])) {
            // Hide form element.
            $form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_y']['widget'][0]['value']['#type'] = 'hidden';

          }

          // Handle width field.
          if (isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_width']) &&
              isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_width']['widget'])) {
            // Hide form element.
            $form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_width']['widget'][0]['value']['#type'] = 'hidden';
          }

          // Handle heigh field.
          if (isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_height']) &&
              isset($form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_height']['widget'])) {
            // Hide form element.
            $form['field_dme_marker']['widget'][$delta]['subform']['field_dme_marker_height']['widget'][0]['value']['#type'] = 'hidden';
          }
        }
      }
    }

    // Get image file URL if available.
    $image_url = '';
    $image_fid = $this->getImageFid($form, $form_state, 'field_dme_image');

    // If we have a file ID, load the file and get URL.
    if ($image_fid) {
      $file = File::load($image_fid);
      if ($file) {
        $file_url_generator = \Drupal::service('file_url_generator');
        $image_url = $file_url_generator->generateString($file->getFileUri());
      }
    }

    // Add the container after the markers fieldset.
    $weight = isset($form['field_dme_image']['#weight']) ? $form['field_dme_marker']['#weight'] + 0.5 : 50;

    $image_container = '<div class="dme-loading"><p>' . new TranslatableMarkup('The map preview will appear here when a map image is added.') . '</p></div>';

    // If we have an image, create the map preview.
    if ($image_url) {
      $image_container = '<div class="dme-image-wrapper">';
      $image_container .= '<img src="' . $image_url . '" alt="Map Image" />';
      $image_container .= '</div>';
    }

    $form['dme_preview_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dme-container'],
        'id' => 'dme-container',
      ],
      '#weight' => $weight,
      'preview' => [
        '#type' => 'container',
        'header' => [
          '#type' => 'container',
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h4',
            '#value' => new TranslatableMarkup('Map Preview'),
            '#attributes' => ['class' => ['form-item__label']],
          ],
          'instructions' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => new TranslatableMarkup('Drag markers to position map elements.'),
            '#attributes' => ['class' => ['form-item__description']],
          ],
        ],
      ],
    ];

    $form['dme_preview_container']['dme_unmapped_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'dme-unmapped-wrapper',
        'class' => ['dme-unmapped-wrapper'],
      ],
    ];

    $form['dme_preview_container']['dme_container_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'dme-container-wrapper',
        'class' => ['dme-container-wrapper'],
      ],
    ];

    $form['dme_preview_container']['dme_container_wrapper']['dme_image'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dme-image'],
        'id' => 'dme-image',
        'role' => 'region',
        'aria-label' => new TranslatableMarkup('Map preview surface'),
      ],
      '#markup' => $image_container,
    ];

    $markers = $this->getMarkerData($form, $form_state, 'field_dme_marker');

    // Add a fallback message when there are no markers
    // at all or no unmapped markers.
    if (empty($markers) || empty(array_filter($markers, function ($marker) {
      return $marker['x'] === NULL || $marker['y'] === NULL;
    }))) {
      $form['dme_preview_container']['dme_unmapped_wrapper']['fallback_message'] = [
        '#type' => 'markup',
        '#markup' => '<div class="dme-no-markers-message">' . new TranslatableMarkup('Add new markers to be mapped') . '</div>',
      ];
    }

    if (!empty($markers)) {
      // Separate markers into mapped and unmapped.
      $markers_mapped = [];
      $markers_unmapped = [];

      foreach ($markers as $marker) {
        if ($marker['x'] === NULL || $marker['y'] === NULL) {
          $markers_unmapped[] = $marker;
        }
        else {
          $markers_mapped[] = $marker;
        }
      }
      // Add markers to the unmapped section.
      foreach ($markers_unmapped as $marker) {
        // Get the icon if it exists.
        $icon_url = NULL;
        $marker_element = '';
        if (!empty($marker['icon_fid'])) {
          // Load the file entity using the file ID.
          $file = File::load($marker['icon_fid']);
          if ($file) {
            // Get the file URL generator service.
            $file_url_generator = \Drupal::service('file_url_generator');
            // Generate a URL for the file that can be used in the browser.
            $icon_url = $file_url_generator->generateString($file->getFileUri());
          }
          // Create an image tag with the icon URL.
          $marker_element = '<img src="' . $icon_url . '" alt="Marker Icon" />';
        }
        else {
          // If no icon is available, use the marker title as fallback.
          $marker_element = $marker['title'];
        }

        $marker_html = '<div class="dme-marker-wrapper">';
        $marker_html .= $marker_element;
        $marker_html .= '</div>';

        // Add to unmapped wrapper.
        $form['dme_preview_container']['dme_unmapped_wrapper']['dme_marker-' . $marker['index']] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['dme-marker', 'dme-unmapped-marker'],
            'id' => 'dme-marker-' . $marker['index'],
            'role' => 'application',
            'aria-label' => new TranslatableMarkup('Interactive component positioning interface'),
            'aria-describedby' => 'dme-instructions',
        // Announces changes to screen readers.
            'aria-live' => 'polite',
          ],
          '#markup' => $marker_html,
        ];
      }

      // Add markers to the mapped section.
      foreach ($markers_mapped as $marker) {
        // Get the icon if it exists.
        $icon_url = NULL;
        $marker_element = '';
        if (!empty($marker['icon_fid'])) {
          // Load the file entity using the file ID.
          $file = File::load($marker['icon_fid']);
          if ($file) {
            // Get the file URL generator service.
            $file_url_generator = \Drupal::service('file_url_generator');
            // Generate a URL for the file that can be used in the browser.
            $icon_url = $file_url_generator->generateString($file->getFileUri());
          }
          // Create an image tag with the icon URL.
          $marker_element = '<img src="' . $icon_url . '" alt="Marker Icon" />';
        }
        else {
          // If no icon is available, use the marker title as fallback.
          $marker_element = $marker['title'];
        }

        $marker_html = '<div class="dme-marker-wrapper">';
        $marker_html .= $marker_element;
        $marker_html .= '</div>';

        // Add to mapped wrapper.
        $form['dme_preview_container']['dme_container_wrapper']['dme_marker-' . $marker['index']] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['dme-marker', 'dme-mapped-marker'],
            'id' => 'dme-marker-' . $marker['index'],
            'role' => 'application',
            'aria-label' => new TranslatableMarkup('Interactive component positioning interface'),
            'aria-describedby' => 'dme-instructions',
            'aria-live' => 'polite',
            'style' => isset($marker['x'], $marker['y']) ?
            'left: ' . ($marker['x'] * 100) . '%; top: ' . ($marker['y'] * 100) . '%;' .
            (isset($marker['width']) ? ' width: ' . ($marker['width'] * 100) . '%;' : '') .
            (isset($marker['height']) ? ' height: ' . ($marker['height'] * 100) . '%;' : '') : '',
          ],
          '#markup' => $marker_html,
        ];
      }
    }

    // Accessible instructions.
    $form['dme_preview_container']['instructions'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'dme-instructions',
    // Hidden visually but available to screen readers.
        'class' => ['visually-hidden'],
      ],
      '#value' => new TranslatableMarkup('Use arrow keys or drag to position elements on the map surface.'),
    ];
  }

  /**
   * Helper function to extract image FID from various form sources.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field_name
   *   The field name to extract the FID from.
   *
   * @return int|null
   *   The file ID if found, NULL otherwise.
   */
  protected function getImageFid($form, $form_state, $field_name = 'field_dme_image') {
    $image_fid = NULL;

    // If the triggering element is from AJAX upload, get directly from it.
    if ($form_state && ($triggering_element = $form_state->getTriggeringElement())) {
      // Check if it's a file upload.
      $is_upload_button = FALSE;
      if (isset($triggering_element['#submit']) && in_array('file_managed_file_submit', $triggering_element['#submit'])) {
        $is_upload_button = TRUE;
      }

      // Get the parents of the upload button to find the file element.
      if ($is_upload_button) {
        $parents = $triggering_element['#array_parents'];
        // Remove the button itself.
        array_pop($parents);
        $element = NestedArray::getValue($form, $parents);

        if (isset($element['fids']['#value'][0])) {
          $image_fid = $element['fids']['#value'][0];
        }
        elseif (isset($element['#file'])) {
          $image_fid = $element['#file']->id();
        }
      }
    }

    // If we didn't get a file ID from the AJAX element, try other methods.
    if (!$image_fid) {
      // Try to get from form state values.
      if ($form_state && $form_state->getValue($field_name)) {
        $values = $form_state->getValue($field_name);
        if (!empty($values[0]['fids'][0])) {
          $image_fid = $values[0]['fids'][0];
        }
      }

      // If still no value, try from default value.
      if (!$image_fid && isset($form[$field_name]['widget'][0]['#default_value']['fids'][0])) {
        $image_fid = $form[$field_name]['widget'][0]['#default_value']['fids'][0];
      }

      // Last resort, check if file is stored in the widget.
      if (!$image_fid && isset($form[$field_name]['widget'][0]['#file'])) {
        $image_fid = $form[$field_name]['widget'][0]['#file']->id();
      }
    }

    return $image_fid;
  }

  /**
   * Helper function to extract marker data from a form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field_name
   *   The field name to extract the marker data from.
   *
   * @return array
   *   Array of marker data.
   */
  protected function getMarkerData($form, $form_state, $field_name = 'field_dme_marker') {
    $markers = [];
    $entity_ids = [];

    // Get the entity from the form.
    $entity = NULL;
    if (isset($form['#entity'])) {
      $entity = $form['#entity'];
    }
    elseif ($form_state->has('entity')) {
      $entity = $form_state->get('entity');
    }

    // Try to get saved X/Y coordinates.
    if ($entity && !$entity->isNew()) {
      // Use field_dme_marker target_ids directly from the entity.
      if (method_exists($entity, 'hasField') && $entity->hasField($field_name) && method_exists($entity, 'get') && !$entity->get($field_name)->isEmpty()) {
        $paragraph_refs = $entity->get($field_name)->getValue();

        // Create a map of index to paragraph ID.
        $entity_ids = [];
        foreach ($paragraph_refs as $index => $ref) {
          if (isset($ref['target_id'])) {
            $entity_ids[$index] = $ref['target_id'];
          }
        }
      }
    }

    // Process form widget data.
    if (isset($form[$field_name]['widget'])) {
      foreach ($form[$field_name]['widget'] as $index => $item) {
        // Skip non-numeric indexes.
        if (!is_numeric($index)) {
          continue;
        }

        $marker_data = [
          'index' => $index,
          'title' => '',
          'icon_fid' => NULL,
          'x' => NULL,
          'y' => NULL,
          'width' => NULL,
          'height' => NULL,
        ];

        // Get title from default value.
        if (isset($item['subform']['field_dme_marker_title']['widget'][0]['value']['#default_value'])) {
          $marker_data['title'] = $item['subform']['field_dme_marker_title']['widget'][0]['value']['#default_value'];
        }
        elseif (isset($item['subform']['field_title']['widget'][0]['value']['#default_value'])) {
          // Try an alternate field name.
          $marker_data['title'] = $item['subform']['field_title']['widget'][0]['value']['#default_value'];
        }
        else {
          // Use a default if no title field is found.
          $marker_data['title'] = 'Marker ' . ($index + 1);
        }

        // Get icon from default value.
        if (isset($item['subform']['field_dme_marker_icon']['widget'][0]['#default_value']['fids'][0])) {
          $marker_data['icon_fid'] = $item['subform']['field_dme_marker_icon']['widget'][0]['#default_value']['fids'][0];
        }

        // If we have paragraph IDs and this is an existing entity.
        if (!empty($entity_ids) && isset($entity_ids[$index])) {
          $paragraph_id = $entity_ids[$index];

          // Load the paragraph entity.
          $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($paragraph_id);

          if ($paragraph instanceof ContentEntityInterface) {
            // Get X coordinate.
            if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_x') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_x')->isEmpty()) {
              $marker_data['x'] = $paragraph->get('field_dme_marker_x')->value;
            }

            // Get Y coordinate.
            if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_y') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_y')->isEmpty()) {
              $marker_data['y'] = $paragraph->get('field_dme_marker_y')->value;
            }
            // Get width if field exists.
            if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_width') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_width')->isEmpty()) {
              $marker_data['width'] = $paragraph->get('field_dme_marker_width')->value;
            }
            // Get height if field exists.
            if (method_exists($paragraph, 'hasField') && $paragraph->hasField('field_dme_marker_height') && method_exists($paragraph, 'get') && !$paragraph->get('field_dme_marker_height')->isEmpty()) {
              $marker_data['height'] = $paragraph->get('field_dme_marker_height')->value;
            }
          }
        }

        $markers[] = $marker_data;
      }
    }

    return $markers;
  }

}
