<?php

namespace Drupal\graphql_signed_s3_upload;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\s3fs\StreamWrapper\S3fsStream;
use Drupal\Core\Entity\EntityTypeManagerInterface;


class CreateMediaImageEntityManager
{
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
     * The entityTypeManager service.
     *
     * @var \Drupal\Core\Entity\EntityTypeManager
     */
    protected $entityTypeManager;

    /**
     * Constructs a MediaImageUtilities object.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     *   The plugin implemented entityTypeManager
     * @param S3fsStream $s3fsStream
     *   The S3fsStream object used for syncing
     * @param AccountProxyInterface $currentUser
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, S3fsStream $s3fsStream, AccountProxyInterface $currentUser) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->s3fsStream = $s3fsStream;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(EntityTypeManagerInterface $entityTypeManager, S3fsStream $s3fsStream, AccountProxyInterface $currentUser) {
        return new static($entityTypeManager, $s3fsStream, $currentUser);
    }

    /**
     * Create File and Media Entities from S3 uploaded files - think decoupled uploader.
     *
     * @param $files
     *   An array of files.
     * @return array
     * @throws
     */
    public function createFileAndMediaEntitiesFromS3UploadedFiles($files){
        $mediaEntities = [];

        foreach ($files as $file) {

            $fileEntity = $this->createFileEntity($file);

            $this->s3fsStream->writeUriToCache($fileEntity->getFileUri());

            $imageMediaEntity = $this->createImageMediaEntity($fileEntity);
            $mediaEntities[] = $imageMediaEntity;
        }
        return $mediaEntities;
    }

    /**
     * Create a file entity from an extremely optimistic url
     *
     * @param $file array
     * @return \Drupal\Core\Entity\EntityInterface
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function createFileEntity($file) {

        $uri = 'public://' . $file['url'];

        /**
         * TODO: I'd love to be able to check if the file exists on S3 first, but can't figure out how...
         *
        $success = \Drupal::service('stream_wrapper_manager')->getViaScheme('s3')->waitUntilFileExists($uri);
        try {
            if (!file_exists($uri)) {
                throw new \Exception(dt('Source file not found :( '));
            }
        }catch(\Exception $e){
            drupal_set_message($this->t('Error: %error.', array('%error' => $e->getMessage())), 'error');
            return false;
        }
        */

        /** @var \Drupal\file\FileInterface $file */
        $fileEntity = $this->entityTypeManager->getStorage('file')->create([
            'uid' => $this->currentUser->id(),
            'status' => 0,
            'filename' => $file['filename'],
            'uri' => $uri,
            'filesize' => $file['filesize'],
            'filemime' => 'image/jpeg',
        ]);

        $fileEntity->setPermanent();
        $fileEntity->save();

        return $fileEntity;
    }

    /**
     * Create an Image Media Entity from a file entity.
     *
     * @param \Drupal\file\FileInterface $fileEntity
     *   The File entity to create the media entity from.
     *
     * @return \Drupal\Core\Entity\EntityInterface
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function createImageMediaEntity($fileEntity){

        //TODO: at some point the exif data should also be loaded and added.
        //$exif = exif_read_data($fileEntity->getFileUri());

        // Save media entity
        $imageMediaEntity = $this->entityTypeManager->getStorage('media')->create([
            'bundle' => 'image',
            'uid' => $this->currentUser->id(),
            'status' => 1,
            'field_media_image' => [
                'target_id' => $fileEntity->id()
            ]
        ]);
        $imageMediaEntity->save();

        return $imageMediaEntity;
    }
}