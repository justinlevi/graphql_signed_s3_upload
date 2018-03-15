<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\Mutations;

use Symfony\Component\DependencyInjection\ContainerInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql_signed_s3_upload\CreateMediaImageEntityManager;

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
   * @var \Drupal\graphql_signed_s3_upload\CreateMediaImageEntityManager
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
   * @param \Drupal\graphql_signed_s3_upload\CreateMediaImageEntityManager $mediaImageUtilities
   *   The media image utility class used for creating file and media entities.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, CreateMediaImageEntityManager $mediaImageUtilities) {
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
      $container->get('graphql_signed_s3_upload.create_media_image_entity_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveContext $context, ResolveInfo $info) {
      $files = $args['input']['files'];
      $mediaEntities = $this->mediaImageUtilities->createFileAndMediaEntitiesFromS3UploadedFiles($files);
      return $mediaEntities;
  }


  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput($value, array $args, ResolveContext $context, ResolveInfo $info) {
    return [];
  }

}
