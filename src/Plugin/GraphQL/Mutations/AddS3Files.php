<?php

namespace Drupal\graphql_signed_s3_upload\Plugin\GraphQL\Mutations;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql_core\Plugin\GraphQL\Mutations\Entity\CreateEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaBundle;
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
 */
class AddS3Files extends CreateEntityBase {


  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The S3fs service.
   *
   * @var \Drupal\s3fs\StreamWrapper\S3fsStream
   */
  protected $s3fsStream;



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
   * @param \Drupal\s3fs\StreamWrapper\S3fsStream $s3fsStream
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, S3fsStream $s3fsStream) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = \Drupal::currentUser();
    $this->s3fsStream = $s3fsStream;

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
      $container->get('stream_wrapper.s3fs')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function createFile($uri, $filename, $filesize, AccountProxyInterface $user) {

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->create([
      'uid' => $user->id(),
      'status' => 0,
      'filename' => $filename,
      'uri' => $uri,
      'filesize' => $filesize,
      'filemime' => 'image/jpeg',
    ]);

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {

    $file_entities = [];
    $media_entities = [];

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    foreach ($args['input'] as $file) {
      // Todo: check if file exists on S3 First
      //if (file_exists($file->getPathname())) {

      $uri = 'public://' . $file[0]['url'];

      $this->s3fsStream->writeUriToCache($uri);

      $entity = $this->createFile(
        $uri,
        $file[0]['filename'],
        $file[0]['filesize'],
        $this->currentUser
      );
      $entity->setPermanent();
      $entity->save();

      // Save media entity
      $media = [
        'bundle' => 'image',
        'uid' => $this->currentUser->id(),
        'status' => 1,
        'image' => [
          'target_id' => $entity->id(),
          'alt' => t(basename($file[0]['filename'])),
          'title' => t(basename($file[0]['filename'])),
        ],
      ];
      $image = $this->entityTypeManager->getStorage('media')->create($media);
      $image->save();

      $media_entities[] = $image;
      $file_entities[] = $entity;
      //}
    }

    return $media_entities;

  }


  /**
   * {@inheritdoc}
   */
  protected function extractEntityInput(array $inputArgs, InputObjectType $inputType, ResolveInfo $info) {
    return [];
  }

}
