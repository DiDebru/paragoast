<?php

namespace Drupal\paragoast;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class Paragoast.
 * Provides service to deliver Pargraph fields to Yoast
 *
 * @package Drupal\paragoast
 */
class Paragoast {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $field_manager;

  /**
   * @param $field_manager
   *   The entity FieldManager
   *
   * Constructor.
   */
  public function __construct(EntityFieldManager $field_manager) {
    $this->field_manager = $field_manager;
  }

  /**
   * @param $form_after_build
   *    The form from YoastSeoFieldManager->setFieldsConfiguration().
   * @param $form_state
   *    The form_state from YoastSeoFieldManager->setFieldsConfiguration().
   *
   * @return array
   *    Returns an array of text fields.
   *
   * A Function that filters Paragraph text fields out of a specific entity.
   *
   */
  public function filterTextFields($form_after_build, FormStateInterface $form_state) {
    // Attach Paragraph fields.
    $build_info = $form_state->getBuildInfo();
    $form_entity = $build_info['callback_object'];
    $entity = $form_entity->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $fields = $this->field_manager->getFieldDefinitions($entity_type, $bundle);
    $paragraph_field_types = ['entity_reference_revisions'];
    $extended_text_field_types = ['text_with_summary', 'text_long', 'text'];
    $paragraph_text_fields = [];
    $paragraph_fields = [];

    foreach ($fields as $field_name => $field) {
      // Check If $field is of type entity_reference_revisions.
      if ($field->getType() == 'entity_reference_revisions') {
        $field_settings = $field->getSettings();
        $field_target_bundle = array_keys($field_settings['handler_settings']['target_bundles']);
        // Get all fields from the paragraph bundle.
        $paragraph_fields = $this->field_manager->getFieldDefinitions('paragraph', $field_target_bundle[0]);
        $paragraph_text_fields = $this->searchForEntityReferenceRevisionFields($paragraph_fields, $extended_text_field_types);
      }
    }

    return $paragraph_text_fields;
  }

  /**
   * @param $paragraph_field
   *
   * @return array
   *    Array of Paragraph fields with reference revision.
   */
  public function getEntityReferenceRevisionFields($paragraph_field) {
    $field_settings = $paragraph_field->getSettings();
    $field_target_bundle = array_keys($field_settings['handler_settings']['target_bundles']);
    $paragraph_fields = $this->field_manager->getFieldDefinitions('paragraph', $field_target_bundle[0]);

    return $paragraph_fields;
  }

  public function searchForEntityReferenceRevisionFields($paragraph_fields, $field_types) {
    $paragraph_text_fields = [];
    foreach ($paragraph_fields as $paragraph_field_name => $paragraph_field) {
      if (in_array($paragraph_field->getType(), $field_types)) {
        return $paragraph_text_fields[$paragraph_field->id()] = $paragraph_field;
      }
      if ($paragraph_field->getType() == 'entity_reference_revisions') {
        $paragraph_fields = $this->getEntityReferenceRevisionFields($paragraph_field);
        $this->searchForEntityReferenceRevisionFields($paragraph_fields, $field_types);
      }
    }
  }

}

