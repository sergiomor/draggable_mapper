<?php

namespace Drupal\draggable_mapper\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Draggable Mapper class.
 *
 * @ContentEntityType(
 *   id = "draggable_mapper",
 *   label = @Translation("Draggable Mapper"),
 *   label_collection = @Translation("Draggable Mapper"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\draggable_mapper\DraggableMapperListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\draggable_mapper\Form\DraggableMapperForm",
 *       "add" = "Drupal\draggable_mapper\Form\DraggableMapperForm",
 *       "edit" = "Drupal\draggable_mapper\Form\DraggableMapperForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\draggable_mapper\DraggableMapperAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "draggable_mapper",
 *   admin_permission = "administer draggable mapper",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/draggable-mapper/{draggable_mapper}",
 *     "add-form" = "/admin/structure/draggable-mapper/add",
 *     "edit-form" = "/admin/structure/draggable-mapper/{draggable_mapper}/edit",
 *     "delete-form" = "/admin/structure/draggable-mapper/{draggable_mapper}/delete",
 *     "collection" = "/admin/structure/draggable-mapper",
 *   },
 *   field_ui_base_route = "entity.draggable_mapper.collection",
 * )
 */
class DraggableMapper extends ContentEntityBase implements ContentEntityInterface, EntityOwnerInterface {

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
      ->setDescription(t('The name of the Draggable Mapper.'))
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
