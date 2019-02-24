<?php

namespace Drupal\hackdonalds\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

class CustomAccessCheck implements AccessInterface {
  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $user = \Drupal::service('hackdonalds.user');
    $hasAccess = $user->checkUserRoute($route_name);
    if ($hasAccess) {
      return AccessResult::allowed();
    } else {
      return AccessResult::forbidden();
    }
  }

}
