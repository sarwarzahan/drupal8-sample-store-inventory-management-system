<?php

namespace Drupal\hackdonalds\User;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\user\Entity\User;
use Drupal\group\Entity\Group;


/**
 * User functionality
 *
 * @author sarwar
 */
class HackdonaldsUser {

  private $currentUser;
  
  public function __construct(AccountInterface $account) {
    $this->currentUser = User::load($account->id());
  }
  
  public function getUserByName($userName) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $userName]);
    
    return reset($users);
  }
  
  public function createUser($groupType, $group, $userInfo) {
    $user = User::create([
        'name' => $userInfo['name'],
        'mail' => $userInfo['mail'],
        'roles' => array(),
        'pass' => $userInfo['password'],
        'status' => 1,
    ]);
    $user->save();
    
    $user_group_type = $groupType;
    if ($user_group_type == 'head_office') {
      $groups = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['type' => 'head_office']);
      foreach($groups as $group) {
        $group->addMember($user);
      }
    }
    $store_group = $group;
    if ($user_group_type == 'store') {
      $groups = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['id' => $store_group, 'type' => 'store']);
      foreach($groups as $group) {
        if ($userInfo['storeGroupType'] == 'owner') {
          $group->addMember($user, array('group_roles' => array('store-owner')));
        }
        if ($userInfo['storeGroupType'] == 'user') {
          $group->addMember($user, array('group_roles' => array('store-user')));
        }
      }
    }
  }
  
  public function getStoreOwnerGroups() {
    return \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['type' => 'store']);
  }
  
  public function getCurrentUser() {
    return $this->currentUser;
  }
  
  public function getGroupInventory() {
    $group = $this->getCurrentUserGroup();
    if ($group) {
      $plugin_id = 'group_node:store';
      $store = $group->getContentEntities($plugin_id);
      if (isset($store[0])) {
        return $store[0]->get('field_inventory')->getString();
      }
    }
  }
  
  public function updateGroupInventory($inventory) {
    $group = $this->getCurrentUserGroup();
    if ($group) {
      $plugin_id = 'group_node:store';
      $store = $group->getContentEntities($plugin_id);
      if (isset($store[0])) {
        $store[0]->set('field_inventory', $inventory);
        $store[0]->save();
      }
    }
  }

  public function isMemberOfStore() {
    $group = $this->getCurrentUserGroup();
    $isMemberOfStore = false;
    if ($group) {
      if ($group->getGroupType()->id() == 'store') {
        $isMemberOfStore = true;
      }
    }
    return $isMemberOfStore;
  }
  
  public function isStoreOwner() {
    $group = $this->getCurrentUserGroup('store-owner');
    $isStoreOwner = false;
    if ($group) {
      if ($group->getGroupType()->id() == 'store') {
        $isStoreOwner = true;
      }
    }
    return $isStoreOwner;
  }
  
  public function isStoreUser() {
    $group = $this->getCurrentUserGroup('store-user');
    $isStoreUser = false;
    if ($group) {
      if ($group->getGroupType()->id() == 'store') {
        $isStoreUser = true;
      }
    }
    
    return $isStoreUser;
  }
  
  public function isAdministrator() {
    $roles = $this->currentUser->getRoles();
    if (in_array('administrator', $roles)) {
      return true;
    }
    
    return false;
  }
  
  public function isMemberHeadOffice() {
    $group = $this->getCurrentUserGroup();
    if ($group) {
      if ($group->getGroupType()->id() == 'head_office') {
        return true;
      }
    }
    return false;
  }
  
  public function getCurrentUserGroupId($roles = NULL) {
    return $this->getCurrentUserGroup($roles)->id();
  }

  private function getCurrentUserGroup($roles = NULL) {
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($this->currentUser, $roles);
    if (isset($grps[0])) {
      $group = $grps[0]->getGroup();
      return $group;
    }
  }
  
  public function getGroupsFromContent($contentId) {
    $ids = \Drupal::entityQuery('group_content')
      ->condition('entity_id', $contentId)
      ->execute();
    $relations = \Drupal\group\Entity\GroupContent::loadMultiple($ids);
    $gids = [];
    foreach ($relations as $rel) {
      if ($rel->getEntity()->getEntityTypeId() == 'node') {
        $gids[] = $rel->getGroup()->id();
      }
    }
    
    return $gids;
  }
  
  public function deleteGroups($gids) {
    foreach ($gids as $gid) {
      $group_contents = \Drupal::entityTypeManager()
        ->getStorage('group_content')
        ->loadByProperties([
        'gid' => $gid,
      ]);

      $goupUserId = [];
      $groupStoreContentId = [];
      foreach ($group_contents as $item) {
        if ($item->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_node:store') {
          $groupStoreContentId[] = $item->getEntity()->id();
        }
        if ($item->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_membership') {
          $goupUserId[] = $item->getEntity()->id();
        }
        $item->delete();
      }

      foreach ($groupStoreContentId as $storeId) {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $node = $node_storage->load($storeId);
        $node_storage->delete([$node]);
      }
      foreach ($goupUserId as $userId) {
        $user_storage = \Drupal::entityTypeManager()->getStorage('user');
        $user = $user_storage->load($userId);
        $user_storage->delete([$user]);
      }
      
      // Delete the group
      $group_storage = \Drupal::entityTypeManager()->getStorage('group');
      $group = $group_storage->load($gid);
      $group_storage->delete([$group]);
    }
  }
  
  public function checkUserRoute($route_name) {
    if ($route_name == 'hackdonalds.store_create_form' && ($this->isMemberHeadOffice() || $this->isAdministrator())) {
      // Return 403 Access Denied page.  
      return true;
    }

    if ($route_name == 'hackdonalds.user_create_form' && ($this->isStoreUser() || $this->isMemberHeadOffice() || $this->isAdministrator())) {
      // Return 403 Access Denied page.  
      return true;
    }

    if ($route_name == 'hackdonalds.inventory_form' && $this->isMemberOfStore()) {
      // Return 403 Access Denied page.  
      return true;
    }

    if ($route_name == 'hackdonalds.store_list' && ($this->isMemberHeadOffice() || $this->isAdministrator())) {
      // Return 403 Access Denied page.  
      return true;
    }

    if ($route_name == 'hackdonalds.store_delete' && ($this->isMemberHeadOffice() || $this->isAdministrator())) {
      // Return 403 Access Denied page.  
      return true;
    }

    return false;
  }

}
