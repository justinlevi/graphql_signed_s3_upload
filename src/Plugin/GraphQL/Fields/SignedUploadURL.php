<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\graphql_signed_s3_upload\SigningUtilities;
use Drupal\graphql\Plugin\GraphQL\PluggableSchemaBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;
use Aws\S3\S3Client;

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
   * The signing utility service class instance.
   *
   * @var \Drupal\graphql_signed_s3_upload\SigningUtilities
   */
  protected $signingUtilities;


  /**
   * The AWS SDK for PHP S3Client object.
   *
   * @var \Aws\S3\S3Client
   */
  protected $s3 = NULL;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $result = $this->signingUtilities->generateUrls($args['input']['fileNames'], $this->s3);
    foreach ($result as $signedUrl) {
      yield $signedUrl;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $config = \Drupal::config('s3fs.settings')->get();
    $client_config['credentials'] = [
      'key' => $config['access_key'],
      'secret' => $config['secret_key'],
    ];

    if (!empty($config['region'])) {
      $client_config['region'] = $config['region'];
      // Signature v4 is only required in the Beijing and Frankfurt regions.
      // Also, setting it will throw an exception if a region hasn't been set.
      $client_config['signature_version'] = 'v4';
      $client_config['version'] = '2006-03-01';
    }


    return new static(
        $configuration,
        $pluginId,
        $pluginDefinition,
        $container->get('graphql_signed_s3_upload.signing_utilities'),
        new S3Client($client_config));
  }

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param $pluginId
   * @param $pluginDefinition
   * @param \Drupal\graphql_signed_s3_upload\SigningUtilities $signingUtilities
   * @param \Aws\S3\S3Client $s3
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SigningUtilities $signingUtilities, S3Client $s3) {
    $this->s3 = $s3;
    $this->signingUtilities = $signingUtilities;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

}
