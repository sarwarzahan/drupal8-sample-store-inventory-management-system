<?php

namespace Drupal\hackdonalds\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Form handler for the user register forms.
 *
 * @internal
 */
class RegisterForm extends FormBase {

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
    return 'hackdonalds_user_register_form';
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
    // Account information.
    $form['account'] = [
      '#type'   => 'container',
      '#weight' => -10,
    ];

    $form['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#required' => false,
      '#default_value' => '',
    ];

    // Only show name field on registration form or user can change own username.
    $form['account']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['username'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => '',
    ];

    // Display password field only for existing users or when user is allowed to
    // assign a password during registration.
    $form['account']['pass'] = [
      '#type' => 'password_confirm',
      '#size' => 25,
      '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
    ];
    
    if ($this->checkAccess()) {
      $form['account']["group_type"] = array(
        "#type" => "select",
        "#title" => t("Select user group type"),
        '#required' => TRUE,
        "#default_value" => '',
        "#options" => array(
          "head_office" => t("Head office"),
          "store" => t("Store")
        ),
        "#description" => t("Select user group type."),
      );

      $groups = $this->userService->getStoreOwnerGroups();
      $options = [];
      foreach ($groups as $id => $group) {
        $options[$id] = $group->label();
      }
      $form['account']["group"] = array(
        "#type" => "select",
        "#title" => t("Select user group"),
        '#required' => TRUE,
        "#default_value" => '',
        '#states' => [
          'visible' => [
            ['select[name="group_type"]' => ['value' => 'store']]
          ]],
        "#options" => $options,
        "#description" => t("Select store user group."),
      );

      $form['account']["type"] = array(
        "#type" => "radios",
        "#title" => t("Select user type"),
        '#required' => TRUE,
        "#default_value" => 'owner',
        '#states' => [
          'visible' => [
            ['select[name="group_type"]' => ['value' => 'store']]
          ]],
        "#options" => array(
          "owner" => t("Owner"),
          "user" => t("User")
        ),
        "#description" => t("Select store user type."),
      );
    }


    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
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
    
    if ($this->checkAccess()) {
      if ($form_state->getValue('group_type') == 'store' && empty($form_state->getValue('group'))) {
        $form_state->setErrorByName('group', $this->t('Please select a value.'));
      }
    }

    $user = $this->userService->getUserByName($form_state->getValue('name'));
    if ($user) {
      $form_state->setErrorByName('name', $this->t('The user name already exists.'));
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

    $userInfo = [];
    $userInfo['name'] = $form_state->getValue('name');
    $userInfo['mail'] = $form_state->getValue('mail');
    $userInfo['password'] = $form_state->getValue('pass');
    
    if ($this->checkAccess()) {
      $userInfo['storeGroupType'] = $form_state->getValue('type');
      $userInfo['groupType'] = $form_state->getValue('group_type');
      $userInfo['group'] = $form_state->getValue('group');
    }
    else {
      $userInfo['storeGroupType'] = 'user';
      $userInfo['groupType'] = 'store';
      $userInfo['group'] = $this->userService->getCurrentUserGroupId();;
    }

    $this->userService->createUser($userInfo['groupType'], $userInfo['group'], $userInfo);

    $messenger = \Drupal::messenger();
    $messenger->addMessage('User added');

    // Redirect to home
    $form_state->setRedirect('hackdonalds.user_create_form');

  }
  
  private function checkAccess() {
    return $this->userService->isMemberHeadOffice() || $this->userService->isAdministrator();
  }

}
