<?php

namespace Drupal\draggable_mapper_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for draggable mapper entity.
 */
interface DraggableMapperEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the draggable mapper entity name.
   *
   * @return string
   *   Name of the draggable mapper entity.
   */
  public function getName();

  /**
   * Sets the draggable mapper entity name.
   *
   * @param string $name
   *   The draggable mapper entity name.
   *
   * @return \Drupal\draggable_mapper_entity\Entity\DraggableMapperEntityInterface
   *   The called draggable mapper entity entity.
   */
  public function setName($name);

  /**
   * Gets the draggable mapper entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the draggable mapper entity.
   */
  public function getCreatedTime();

  /**
   * Sets the draggable mapper entity creation timestamp.
   *
   * @param int $timestamp
   *   The draggable mapper entity creation timestamp.
   *
   * @return \Drupal\draggable_mapper_entity\Entity\DraggableMapperEntityInterface
   *   The called draggable mapper entity entity.
   */
  public function setCreatedTime($timestamp);

}
