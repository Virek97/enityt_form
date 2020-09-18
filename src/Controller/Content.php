<?php


namespace Drupal\content_entity_example\Controller;

use Drupal\content_entity_example\Entity\Contact;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class Content
 *
 * @package Drupal\content_entity_example\Controller
 */
class Content extends ControllerBase {

  /**
   * The method gets a form for adding comments and all comments from the database and displays them on the comments page.
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function get_data() {

    Global $base_url;

    $entity = Contact::create();
    $comment_form = \Drupal::service('entity.form_builder')->getForm($entity, 'add');

    $info = [];
    $storage = \Drupal::entityTypeManager()->getStorage('content_entity_example_contact');
    $query = $storage->getQuery()
      ->pager(2)
      ->sort('id' , 'DESC');
    $result = $query->execute();
    $nodes = $storage->loadMultiple($result);


    $i = 0;
    foreach ($nodes as $node) {
      array_push($info, [
        'id' => $node->id->value,
        'name' => $node->name->value,
        'email' => $node->email->value,
        'phone_number' => $node->phone_number->value,
        'info_text' => $node->info_text->value,
        'managed' => $node->toLink('Managed'),
      ]);

//      If the avatar was set, then record it, and if not, then set the default
      if ($node->avatar->entity != NULL) {
        $avatar = $node->avatar->entity;
        $info[$i]['avatar'] = file_url_transform_relative(file_create_url($avatar->getFileUri()));
      } else {
        $info[$i]['avatar'] = 'default_avatar.png';
      }

      if ($node->image->entity != NULL) {
        $image = $node->image->entity;
        $info[$i]['image'] = file_url_transform_relative(file_create_url($image->getFileUri()));
      }

      $i++;
    }

    $data = [
      'info' => $info,
    ];

    $render[] = [
      '#theme' => 'content_entity_example_list_theme',
      '#data' => $data,
      '#comment_form' => $comment_form,
      '#base_url' => $base_url,
    ];

//    Pagination is added to the page
    $render['custom_pager'] = [
      '#type' => 'pager',
    ];

    return $render;

  }

}
