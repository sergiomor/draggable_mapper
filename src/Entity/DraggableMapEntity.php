<?php

namespace Drupal\draggable_map_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Draggable Map Entity entity class.
 *
 * @ContentEntityType(
 *   id = "draggable_map_entity",
 *   label = @Translation("Draggable Map Entity"),
 *   label_collection = @Translation("Draggable Map Entities"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\draggable_map_entity\DraggableMapEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\draggable_map_entity\Form\DraggableMapEntityForm",
 *       "add" = "Drupal\draggable_map_entity\Form\DraggableMapEntityForm",
 *       "edit" = "Drupal\draggable_map_entity\Form\DraggableMapEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\draggable_map_entity\DraggableMapEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "draggable_map_entity",
 *   admin_permission = "administer draggable map entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/draggable_map_entity/{draggable_map_entity}",
 *     "add-form" = "/admin/structure/draggable_map_entity/add",
 *     "edit-form" = "/admin/structure/draggable_map_entity/{draggable_map_entity}/edit",
 *     "delete-form" = "/admin/structure/draggable_map_entity/{draggable_map_entity}/delete",
 *     "collection" = "/admin/structure/draggable_map_entity",
 *   },
 *   field_ui_base_route = "entity.draggable_map_entity.collection",
 * )
 */
class DraggableMapEntity extends ContentEntityBase implements ContentEntityInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Draggable Map Entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
