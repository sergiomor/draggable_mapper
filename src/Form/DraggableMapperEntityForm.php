<?php

namespace Drupal\draggable_mapper_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Form controller for the draggable mapper entity forms.
 */
class DraggableMapperEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Create a container for the image and markers
    $form['dme_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dme-container'],
        'id' => 'dme-container',
      ],
      '#weight' => 1.5,
    ];

    // Move the image field into the container
    if (isset($form['field_dme_image'])) {
      $form['field_dme_image']['#ajax'] = [
        'callback' => '::updateImagePreview',
        'event' => 'change',
        'wrapper' => 'dme-container',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating image preview...'),
        ],
      ];
      
      // Store original image field for processing
      $form['dme_container']['field_dme_image'] = $form['field_dme_image'];
      unset($form['field_dme_image']);
      
      // Add image preview area
      $form['dme_container']['image_preview'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['dme-image-preview'],
          'id' => 'dme-image-preview',
        ],
      ];
      
      // If we have an existing image, display it
      if (!$entity->isNew() && !empty($entity->field_dme_image->entity)) {
        $file = $entity->field_dme_image->entity;
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        
        $form['dme_container']['image_preview']['#markup'] = '<div class="dme-image-wrapper"><img src="' . $image_url . '" alt="Map Image" /></div>';
        
        // Add markers if we have any
        if (!empty($entity->field_dme_marker) && $entity->field_dme_marker->count() > 0) {
          $this->addMarkers($form, $entity);
        }
      }
    }
    
    // Add custom styling
    $form['#attached']['library'][] = 'draggable_mapper_entity/mapper';

    return $form;
  }

  /**
   * Ajax callback to update the image preview.
   */
  public function updateImagePreview(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    // Get the file ID from the form state
    $file_ids = $form_state->getValue(['field_dme_image', 0, 'fids']);
    
    if (!empty($file_ids)) {
      $file = File::load(reset($file_ids));
      if ($file) {
        // Generate image URL
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        
        // Create image markup
        $image_markup = '<div class="dme-image-wrapper"><img src="' . $image_url . '" alt="Map Image" /></div>';
        
        // Add markers from the existing values
        $marker_values = $form_state->getValue('field_dme_marker');
        $markers_markup = '';
        
        if (!empty($marker_values)) {
          foreach ($marker_values as $delta => $item) {
            if (isset($item['subform']) && 
                isset($item['subform']['field_dme_marker_x'][0]['value']) && 
                isset($item['subform']['field_dme_marker_y'][0]['value'])) {
              
              $x = floatval($item['subform']['field_dme_marker_x'][0]['value']) * 100;
              $y = floatval($item['subform']['field_dme_marker_y'][0]['value']) * 100;
              
              $title = '';
              if (isset($item['subform']['field_dme_marker_title'][0]['value'])) {
                $title = $item['subform']['field_dme_marker_title'][0]['value'];
              }
              
              $icon_markup = '<div class="dme-marker-default"></div>';
              if (isset($item['subform']['field_dme_marker_icon'][0]['fids']) && 
                  !empty($item['subform']['field_dme_marker_icon'][0]['fids'])) {
                $icon_file = File::load(reset($item['subform']['field_dme_marker_icon'][0]['fids']));
                if ($icon_file) {
                  $icon_url = \Drupal::service('file_url_generator')->generateAbsoluteString($icon_file->getFileUri());
                  $icon_markup = '<img src="' . $icon_url . '" alt="Marker Icon" />';
                }
              }
              
              $markers_markup .= '<div class="dme-marker" style="left: ' . $x . '%; top: ' . $y . '%; position: absolute;" title="' . $title . '">' . $icon_markup . '</div>';
            }
          }
        }
        
        // Replace the image preview with the new image and markers
        $response->addCommand(new HtmlCommand('#dme-image-preview', $image_markup . $markers_markup));
      }
    }
    else {
      // No image selected, clear the preview
      $response->addCommand(new HtmlCommand('#dme-image-preview', ''));
    }
    
    return $response;
  }

  /**
   * Helper function to add markers to the form.
   */
  protected function addMarkers(array &$form, $entity) {
    $markers_markup = '';
    
    foreach ($entity->field_dme_marker as $delta => $marker_item) {
      $marker = $marker_item->entity;
      
      if ($marker && 
          $marker->hasField('field_dme_marker_x') && 
          $marker->hasField('field_dme_marker_y')) {
        
        $x = floatval($marker->get('field_dme_marker_x')->value) * 100;
        $y = floatval($marker->get('field_dme_marker_y')->value) * 100;
        
        $title = '';
        if ($marker->hasField('field_dme_marker_title') && !$marker->get('field_dme_marker_title')->isEmpty()) {
          $title = $marker->get('field_dme_marker_title')->value;
        }
        
        $icon_markup = '<div class="dme-marker-default"></div>';
        if ($marker->hasField('field_dme_marker_icon') && 
            !$marker->get('field_dme_marker_icon')->isEmpty()) {
          $icon_file = $marker->get('field_dme_marker_icon')->entity;
          if ($icon_file) {
            $icon_url = \Drupal::service('file_url_generator')->generateAbsoluteString($icon_file->getFileUri());
            $icon_markup = '<img src="' . $icon_url . '" alt="Marker Icon" />';
          }
        }
        
        $markers_markup .= '<div class="dme-marker" style="left: ' . $x . '%; top: ' . $y . '%; position: absolute;" title="' . $title . '">' . $icon_markup . '</div>';
      }
    }
    
    if (!empty($markers_markup)) {
      $form['dme_container']['image_preview']['#markup'] .= $markers_markup;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    $entity_link = $entity->toLink($this->t('View'))->toString();
    $context = ['%title' => $entity->label(), 'link' => $entity_link];
    $t_args = ['%title' => $entity->label()];

    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the %title Draggable Mapper Entity.', $t_args));
      $this->logger('draggable_mapper_entity')->notice('Created new draggable mapper entity %title.', $context);
    }
    else {
      $this->messenger()->addStatus($this->t('Saved the %title Draggable Mapper Entity.', $t_args));
      $this->logger('draggable_mapper_entity')->notice('Updated draggable mapper entity %title.', $context);
    }

    $form_state->setRedirect('entity.draggable_mapper_entity.canonical', ['draggable_mapper_entity' => $entity->id()]);
    
    return $status;
  }

}
