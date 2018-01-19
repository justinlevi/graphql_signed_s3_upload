<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * The S3FileInput GraphQL input type.
 *
 * @GraphQLInputType(
 *   id = "s3_file_input",
 *   name = "S3FileInput",
 *   fields = {
 *     "filename" = "String",
 *     "filesize" = "Int",
 *     "url" = "String"
 *   }
 * )
 */
class S3FileInput extends InputTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function parseValue($value) {
    return empty($value['name'])
      ? parent::parseValue($value)
      : \Drupal::service('request_stack')->getCurrentRequest()->files->get($value['name']);
  }

}
