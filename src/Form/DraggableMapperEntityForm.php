<?php

namespace Drupal\draggable_mapper_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Draggable Map Entity forms.
 */
class DraggableMapperEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New draggable map entity %label has been created.', $message_arguments));
      $this->logger('draggable_mapper_entity')->notice('Created new draggable map entity %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The draggable map entity %label has been updated.', $message_arguments));
      $this->logger('draggable_mapper_entity')->notice('Updated draggable map entity %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.draggable_mapper_entity.canonical', ['draggable_mapper_entity' => $entity->id()]);
  }

}
