<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * The S3FilesInput GraphQL input type.
 *
 * @GraphQLInputType(
 *   id = "s3_files_input",
 *   name = "S3FilesInput",
 *   fields = {
 *     "files"  = {
 *        "type" = "S3FileInput",
 *        "multi" = "TRUE"
 *     }
 *   }
 * )
 */
class S3FilesInput extends InputTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function parseValue($value) {
    return empty($value['name'])
      ? parent::parseValue($value)
      : \Drupal::service('request_stack')->getCurrentRequest()->files->get($value['name']);
  }

}
