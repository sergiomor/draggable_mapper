<?php

namespace Drupal\draggable_map_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Draggable Map Entity entity.
 */
class DraggableMapEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\draggable_map_entity\Entity\DraggableMapEntityInterface $entity */

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view draggable map entity');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit draggable map entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete draggable map entity');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add draggable map entity');
  }

}
