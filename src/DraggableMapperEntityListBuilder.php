<?php

namespace Drupal\draggable_mapper_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Draggable Map entities.
 */
class DraggableMapperEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\draggable_mapper_entity\Entity\DraggableMapEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.draggable_mapper_entity.edit_form',
      ['draggable_mapper_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
