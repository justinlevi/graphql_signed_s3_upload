<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\Mutations;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql_signed_s3_upload\MediaImageUtilities;
use Drupal\file\Entity\File;
use Drupal\media_entity_image\Plugin\MediaEntity\Type\Image;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\s3fs\StreamWrapper\S3fsStream;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;

/**
 * A S3 Synchronization file mutation.
 *
 * @GraphQLMutation(
 *   id = "add_s3_files",
 *   secure = "false",
 *   name = "addS3Files",
 *   type = "MediaImage",
 *   multi = true,
 *   entity_type = "file",
 *   entity_bundle = "file",
 *   arguments = {
 *     "input" = "S3FilesInput"
 *   }
 * )
 *
 * The mutation would look something like this:
 *
 * mutation{
 *  addS3Files(input:{
 *      files:[{filename:"TEST", filesize:123, url:"test.jpg"}]
 *    }){
 *    entityId
 *   }
 * }
 */
class AddS3Files extends CreateEntityBase {

  /**
   * The Media Image utility service class instance.
   *
   * @var \Drupal\graphql_signed_s3_upload\MediaImageUtilities
   */
  protected $mediaImageUtilities;


  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The plugin implemented entityTypeManager
   * @param \Drupal\graphql_signed_s3_upload\MediaImageUtilities $mediaImageUtilities
   *   The media image utility class used for creating file and media entities.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, MediaImageUtilities $mediaImageUtilities) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mediaImageUtilities = $mediaImageUtilities;

    parent::__construct($configuration, $pluginId, $pluginDefinition, $entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('graphql_signed_s3_upload.media_image_utilities')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {
      $files = $args[input];
      $mediaEntities = $this->mediaImageUtilities->createFileAndMediaEntitiesFromS3UploadedFiles($files);
      return $mediaEntities;
  }


  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput(array $inputArgs, InputObjectType $inputType, ResolveInfo $info) {
    return [];
  }

}
