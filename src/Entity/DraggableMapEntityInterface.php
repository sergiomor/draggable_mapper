<?php

namespace Drupal\draggable_map_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for draggable map entity.
 */
interface DraggableMapEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the draggable map entity name.
   *
   * @return string
   *   Name of the draggable map entity.
   */
  public function getName();

  /**
   * Sets the draggable map entity name.
   *
   * @param string $name
   *   The draggable map entity name.
   *
   * @return \Drupal\draggable_map_entity\Entity\DraggableMapEntityInterface
   *   The called draggable map entity entity.
   */
  public function setName($name);

  /**
   * Gets the draggable map entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the draggable map entity.
   */
  public function getCreatedTime();

  /**
   * Sets the draggable map entity creation timestamp.
   *
   * @param int $timestamp
   *   The draggable map entity creation timestamp.
   *
   * @return \Drupal\draggable_map_entity\Entity\DraggableMapEntityInterface
   *   The called draggable map entity entity.
   */
  public function setCreatedTime($timestamp);

}
