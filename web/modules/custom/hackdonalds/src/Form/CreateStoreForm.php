<?php

namespace Drupal\hackdonalds\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Form handler for the store form.
 *
 * @internal
 */
class CreateStoreForm extends FormBase {

  private $storeService;

  public function __construct() {
    $this->storeService = \Drupal::service('hackdonalds.store');
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
    return 'hackdonalds_create_store_form';
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

    $form['store']['storeid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store ID'),
      '#description' => $this->t('Enter Store ID'),
      '#required' => true,
      '#default_value' => '',
    ];
    
    $form['store']['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#description' => $this->t('Enter Location'),
      '#required' => true,
      '#default_value' => '',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

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
    
    $storeid = $form_state->getValue('storeid');
    $store = $this->storeService->getStoreById($storeid);
    if($store) {
      $form_state->setErrorByName('storeid', $this->t('The store id with the location exists. Please change it.'));
    }
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
    $storeid = $form_state->getValue('storeid');
    $location = $form_state->getValue('location');
    
    $this->storeService->createStore($storeid, $location);
    
    $messenger = \Drupal::messenger();
    $messenger->addMessage('Store with StoreID: '. $storeid . ' and location: ' . $location . ' added');
    // Redirect to form
    $form_state->setRedirect('hackdonalds.store_create_form');

  }

}
