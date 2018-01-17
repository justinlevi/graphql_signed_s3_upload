<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * Signed Upload input type.
 *
 * @GraphQLInputType(
 *   id = "signed_upload_input",
 *   name = "SignedUploadInput",
 *   fields = {
 *     "fileNames"  = {
 *        "type" = "String",
 *        "multi" = "TRUE"
 *     }
 *   }
 * )
 */
class SignedUploadInput extends InputTypePluginBase {

}
