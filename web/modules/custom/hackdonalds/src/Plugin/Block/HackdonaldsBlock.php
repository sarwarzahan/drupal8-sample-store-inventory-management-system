<?php

namespace Drupal\hackdonalds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Hackdonalds' Block.
 *
 * @Block(
 *   id = "hackdonalds_block",
 *   admin_label = @Translation("Hackdonalds block"),
 *   category = @Translation("Hackdonalds"),
 * )
 */
class HackdonaldsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::service('hackdonalds.user');
    $userRouteList = array (
      'hackdonalds.user_create_form' => t('Create user'),
      'hackdonalds.store_create_form' => t('Create store'),
      'hackdonalds.inventory_form' => t('Inventory'),
      'hackdonalds.store_list' => t('Store list')
    );
    $routeListHtml = '';
    foreach ($userRouteList as $route => $routeText) {
      if ($user->checkUserRoute($route)) {
        $routeListHtml = '<li>' . $this->generateUrlFromRoute($route, $routeText) . '</li>' . $routeListHtml;
      }
    }
    return array(
      '#markup' => '<ul>' . $routeListHtml . '</ul>',
    );
  }
  
  private function generateUrlFromRoute($routeName, $linkTitle) {
    $url = Url::fromRoute($routeName);
    $project_link = Link::fromTextAndUrl($linkTitle, $url);
    $project_link = $project_link->toRenderable();
    
    return render($project_link);
  }
  
  /**
     * {@inheritdoc}
     */
  public function getCacheMaxAge() {
    return 0;
  }

}
