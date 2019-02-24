<?php

namespace Drupal\hackdonalds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;

Class HackDonaldsController extends ControllerBase {
  
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  public function storelist() {
    $nids = \Drupal::entityQuery('node')->condition('type','store')->accessCheck(FALSE)->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    $stores = [];
    foreach ($nodes as $node) {
      $data['id'] = $node->id();
      $data['title'] = $node->getTitle();
      $data['store_id'] = $node->get('field_store_id')->value;
      $data['location'] = $node->get('field_location')->value;
      $data['inventory'] = $node->get('field_inventory')->value;
      $stores[] = $data;
    }
    
    return array(
      '#theme' => 'store_list',
      '#stores' => $stores,
      '#attached' => ['library' => ['core/drupal.dialog.ajax']]
    );
  }
  
  /**
   * Callback for opening the modal form.
   */
  public function openModalForm($node) {
    // Save value in session for selected store
    $tempstore = \Drupal::service('user.private_tempstore')->get('hackdonalds');
    $tempstore->set('selectedStore', $node->id());
    
    $response = new AjaxResponse();
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\hackdonalds\Form\ModalForm');
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand('Confirm', $modal_form, ['width' => '200']));

    return $response;
  }

}
