<?php

namespace Drupal\hackdonalds\Store;

use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;

/**
 * Store Functionality
 *
 * @author sarwar
 */
class HackdonaldsStore {

  public function getStoreById($storeId) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'store', 'title' => $storeId]);
    
    return reset($nodes);
  }
  
  public function createStore($storeId, $location) {
    $node = Node::create([
        'type' => 'store',
        'title' => $storeId,
        'field_store_id' => $storeId,
        'field_location' => $location,
        'field_inventory' => 0,
    ]);
    $node->save();
    
    // Now create group for the store and add the store to the group
    $store_owners_group = Group::create(['label' => $storeId, 'type' => 'store']);
    $store_owners_group->save();
    $plugin_id = 'group_node:' . $node->getType();
    $store_owners_group->addContent($node, $plugin_id);
  }

}
