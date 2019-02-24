<?php

namespace Drupal\hackdonalds\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form handler for the user register forms.
 *
 * @internal
 */
class InventoryForm extends FormBase {

  private $userService;

  public function __construct() {
    $this->userService = \Drupal::service('hackdonalds.user');
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'hackdonalds_inventory_form';
  }
  
  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Store information.
    $form['store'] = [
      '#type'   => 'container',
      '#weight' => -10,
    ];
    
    $form['store']['current_inventory'] = [
      '#type' => 'item',
      '#title' => $this->t('Current: ') . $this->userService->getGroupInventory(),
      '#description' => '',
    ];

    if ($this->userService->isStoreOwner()) {
      $form['store']['inventory'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Inventory'),
        '#attributes' => array(
          ' type' => 'number', // insert space before attribute name :)
        ),
        '#description' => $this->t('Enter updated inventory'),
        '#required' => true,
        '#maxlength' => 9,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      // Add a submit button that handles the submission of the form.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }

    return $form;

  }
  
  /**
   * Validate the title and the checkbox of the form
   * 
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $inventory= $form_state->getValue('inventory');
    $this->userService->updateGroupInventory($inventory);
    
    $messenger = \Drupal::messenger();
    $messenger->addMessage('Inventory updated');

    // Redirect to form
    $form_state->setRedirect('hackdonalds.inventory_form');

  }

}
