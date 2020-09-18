<?php


namespace Drupal\content_entity_example\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Forms controller for the content_entity_example entity forms.
 *
 * @ingroup content_entity_example
 */
class ContactForm extends ContentEntityForm {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'content_entity_example_contact';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\content_entity_example\Entity\Contact */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#language' => Language::STATE_ALL,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    parent::actions($form, $form_state);

//    The ajax callback for the form is set
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'event' => 'click',
      ],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

//    Regular expression to check the phone number
    $phone_pattern = "/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){12}(\s*)?$/";

    $str = $this->cleanFormText($form_state->getValue('info_text')[0]['value']);
    if (iconv_strlen($str) < 10) {
      $form_state->setErrorByName('info_text',
        $this->t('Comment must be longer than 10 characters!'));
    }
    if (!filter_var($form_state->getValue('email')[0]['value'], FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email',
        $this->t('Invalid email format!'));
    }
    if (!preg_match($phone_pattern, $form_state->getValue('phone_number')[0]['value'])) {
      $form_state->setErrorByName('phone_number',
        $this->t("Wrong phone format! The phone number should be in the following format: +380(67)777-7-777"));
    }
  }

  /**
   * Clears the comment text of unwanted characters.
   *
   * @param string $value
   *
   * @return string
   *    Clean text.
   */
  public function cleanFormText($value = "") {
    $value = trim($value);
    $value = stripslashes($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value);

    return $value;
  }

  /**
   * Ajax callback to display errors, or add a comment and refresh the page.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
      '#status_headings' => [
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages = \Drupal::service('renderer')->render($message);

//    Checks which page we are on and displays the appropriate message.
    $node = \Drupal::routeMatch()->getParameter('content_entity_example_contact');
    if ($node != NULL) {
      $nid = $node->id();
      $linkPattern = "content_entity_example_contact/{$nid}/edit";
      $linkPatternResult = strstr($_SERVER['REQUEST_URI'], $linkPattern);
    } else {
      $linkPatternResult = FALSE;
    }

    if ($form_state->hasAnyErrors()) {
//      If there are errors of validation of the form we deduce them through Ajax
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    } else {
//      We get the address of the current page and reload it.
      $url = Url::fromRoute('entity.content_entity_example_contact.custom_node_list');
      $command = new RedirectCommand($url->toString());
      $ajax_response->addCommand($command);

      if ($linkPatternResult == FALSE) {
        \Drupal::messenger()->addMessage("Thanks for your comment!", 'status');
      } else {
        \Drupal::messenger()->addMessage("Comment edited!", 'status');
      }
    }

    return $ajax_response;
  }

}
