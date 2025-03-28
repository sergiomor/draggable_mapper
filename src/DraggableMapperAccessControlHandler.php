<?php

namespace Drupal\draggable_mapper;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Draggable Map Entity entity.
 */
class DraggableMapperAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\draggable_mapper\Entity\DraggableMapperInterface $entity */

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view draggable mapper');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit draggable mapper');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete draggable mapper');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add draggable mapper');
  }

}
