<?php

namespace Drupal\group_solr\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Defines an "Item URL" property.
 *
 * @see \Drupal\group_solr\Plugin\search_api\processor\AccessControl
 */
class AddAccessControlProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'absolute' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => new FormattableMarkup(
              "<p>This field is determined access control with 
                    <a href='https://www.drupal.org/project/group' target='_blank'>Group</a> 
                    for an indexed item to be public or private for annonymous users</p>
                <p>Field's values to be indexed to Solr: </p>
                <ul>
                    <li>Public: <strong>200</strong></li>
                    <li>Private: <strong>403</strong></li>
                    
                </ul>
                ",
              []),
    ];

    return $form;
  }

}
