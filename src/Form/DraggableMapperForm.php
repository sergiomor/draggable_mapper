<?php

namespace Drupal\draggable_mapper\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the draggable mapper entity forms.
 */
class DraggableMapperForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    
    // Set the entity as a form property to match the structure expected by mapper.js
    $form['#entity'] = $this->entity;
    $form['#entity_type'] = 'draggable_mapper';
    
    // Attach the mapper library
    $form['#attached']['library'][] = 'draggable_mapper/draggable_mapper.form';
    
    // Add the preview container using the same helper function as the inline entity form
    module_load_include('module', 'draggable_mapper');
    _draggable_mapper_add_preview_container($form, $form_state);
    
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
}