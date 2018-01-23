<?php

namespace Drupal\graphql_signed_s3_upload;

use Drupal\s3fs\StreamWrapper\S3fsStream;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaBundle;
use Drupal\file\Entity\File;
use Drupal\media_entity_image\Plugin\MediaEntity\Type\Image;
use Drupal\Core\Session\AccountProxyInterface;


class MediaImageUtilities
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
     * @param S3fsStream $entityTypeManager
     *   The plugin implemented entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, S3fsStream $s3fsStream) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = \Drupal::currentUser();
        $this->s3fsStream = $s3fsStream;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(EntityTypeManagerInterface $entityTypeManager, S3fsStream $s3fsStream) {
        return new static($entityTypeManager, $s3fsStream);
    }


    /**
     * Create File and Media Entities from S3 uploaded files - think decoupled uploader.
     *
     * @param $files
     *   An array of files.
     * @return array
     */
    public function createFileAndMediaEntitiesFromS3UploadedFiles($files){
        $mediaEntities = [];
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        foreach ($files as $file) {

            $fileEntity = $this->createFileEntity($file);

            $this->s3fsStream->writeUriToCache($fileEntity->getFileUri());

            $imageMediaEntity = $this->createImageMediaEntity($fileEntity);
            $mediaEntities[] = $imageMediaEntity;
        }
        return $mediaEntities;
    }


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



    public function createImageMediaEntity($fileEntity){

        //TODO: at some point the exif data should also be loaded and added.
        //$exif = exif_read_data($fileEntity->getFileUri());

        // Save media entity
        $imageMediaEntity = $this->entityTypeManager->getStorage('media')->create([
            'bundle' => 'image',
            'uid' => $this->currentUser->id(),
            'status' => 1,
            'image' => [
                'target_id' => $fileEntity->id()
            ]
        ]);
        $imageMediaEntity->save();

        return $imageMediaEntity;
    }
}