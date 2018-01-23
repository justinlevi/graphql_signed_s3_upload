<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\graphql_signed_s3_upload\SigningUtilities;
use Drupal\graphql\Plugin\GraphQL\PluggableSchemaBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * List everything we've got in our page.
 *
 * @GraphQLField(
 *   id = "signedUploadURL",
 *   secure = true,
 *   name = "signedUploadURL",
 *   type = "String",
 *   multi = true,
 *   response_cache_max_age = 0,
 *   arguments = {
 *      "input" = "SignedUploadInput"
 *   }
 * )
 */
class SignedUploadURL extends FieldPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The page instance.
   *
   * @var \Drupal\graphql_signed_s3_upload\SigningUtilities
   */
  protected $signingUtilities;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $result = $this->signingUtilities->generateUrls($args['input']['fileNames']);
    foreach ($result as $signedUrl) {
      yield $signedUrl;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
        $configuration,
        $pluginId,
        $pluginDefinition,
        $container->get('graphql_signed_s3_upload.signing_utilities')
        );
  }

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param $pluginId
   * @param $pluginDefinition
   * @param \Drupal\graphql_signed_s3_upload\SigningUtilities $signingUtilities
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SigningUtilities $signingUtilities) {
    $this->signingUtilities = $signingUtilities;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

}
