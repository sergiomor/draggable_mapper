<?php

namespace Drupal\draggable_map_entity\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

// /**
//  * Drush commands for the Draggable Map Entity module.
//  */
// class DraggableMapEntityCommands extends DrushCommands {

//   /**
//    * The database connection.
//    *
//    * @var \Drupal\Core\Database\Connection
//    */
//   protected $database;

//   /**
//    * Constructs a new DraggableMapEntityCommands object.
//    *
//    * @param \Drupal\Core\Database\Connection $database
//    *   The database connection.
//    */
//   public function __construct(Connection $database) {
//     parent::__construct();
//     $this->database = $database;
//   }

//   /**
//    * Force removes the draggable_map_entity module by cleaning up database entries.
//    *
//    * @command draggable-map-entity:force-uninstall
//    * @aliases dme-force-uninstall
//    * @usage draggable-map-entity:force-uninstall
//    *   Forcibly removes all database entries related to the Draggable Map Entity module.
//    */
//   public function forceUninstall() {
//     // Remove the module from system table
//     $this->database->update('system')
//       ->fields(['status' => 0])
//       ->condition('name', 'draggable_map_entity')
//       ->execute();
    
//     // Remove the entity type from key_value tables
//     if ($this->database->schema()->tableExists('key_value')) {
//       $this->database->delete('key_value')
//         ->condition('collection', 'entity.definitions.installed')
//         ->condition('name', 'draggable_map_entity.entity_type')
//         ->execute();
      
//       $this->database->delete('key_value')
//         ->condition('collection', 'entity.storage_schema.sql')
//         ->condition('name', 'draggable_map_entity.entity_schema_data')
//         ->execute();
      
//       // Remove field definitions related to our entity
//       $field_delete_query = $this->database->delete('key_value')
//         ->condition('collection', 'entity.definitions.installed');
//       $field_delete_or = $field_delete_query->orConditionGroup()
//         ->condition('name', 'field.field.draggable_map_entity.%', 'LIKE')
//         ->condition('name', 'field.storage.draggable_map_entity.%', 'LIKE');
//       $field_delete_query->condition($field_delete_or);
//       $field_delete_query->execute();
//     }

//     // Remove config entries
//     if ($this->database->schema()->tableExists('config')) {
//       $config_delete_query = $this->database->delete('config');
//       $config_delete_or = $config_delete_query->orConditionGroup()
//         ->condition('name', 'field.field.draggable_map_entity.%', 'LIKE')
//         ->condition('name', 'field.storage.draggable_map_entity.%', 'LIKE')
//         ->condition('name', 'core.entity_form_display.draggable_map_entity.%', 'LIKE')
//         ->condition('name', 'core.entity_view_display.draggable_map_entity.%', 'LIKE');
//       $config_delete_query->condition($config_delete_or);
//       $config_delete_query->execute();
//     }

//     // Remove from core.extension
//     if ($this->database->schema()->tableExists('config')) {
//       // Get core.extension config
//       $extension_record = $this->database->select('config', 'c')
//         ->fields('c', ['data'])
//         ->condition('name', 'core.extension')
//         ->execute()
//         ->fetchField();
      
//       if ($extension_record) {
//         $extension_data = unserialize($extension_record);
//         if (isset($extension_data['module']['draggable_map_entity'])) {
//           // Remove our module
//           unset($extension_data['module']['draggable_map_entity']);
          
//           // Save back to config
//           $this->database->update('config')
//             ->fields(['data' => serialize($extension_data)])
//             ->condition('name', 'core.extension')
//             ->execute();
//         }
//       }
//     }
    
//     // Clear all caches
//     drupal_flush_all_caches();
    
//     $this->io()->success("Draggable Map Entity module has been forcibly uninstalled.");
//     $this->io()->note("You should rebuild your Drupal container with: drush cr");
//   }

// }
