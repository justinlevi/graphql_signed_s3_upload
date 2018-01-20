<?php

namespace Drupal\graphql_signed_s3_upload;

use Aws\S3\S3ClientInterface;

class SigningUtilities {

  /**
   * Returns the generated URL
   *
   * @param array $filenames
   * @param \Aws\S3\S3ClientInterface $s3Client
   *   Client object.
   *
   * @return array
   *   The presigned URLs
   */
  static function generateUrls(Array $filenames, S3ClientInterface $s3Client){

    $presignedUrls = [];
    $config = \Drupal::config('s3fs.settings');
    $bucket = $config->get('bucket');
    $public_config = $config->get('public_folder');
    $private_config = $config->get('private_folder');

    $public_folder = !empty($public_config) ? $public_config : 's3fs-public';
    $private_folder = !empty($private_config) ? $private_config : 's3fs-private';

    foreach ($filenames as $filename) {

      $cmd = $s3Client->getCommand('PutObject', [
        'Bucket'      => $bucket,
        'Key'         => $public_folder . '/' . $filename,
        'ContentType' => 'image/jpeg',
        'Body'        => '',
        'ACL'         => 'public-read',
      ]);

      $request = $s3Client->createPresignedRequest($cmd, '+5 minutes');
      $presignedUrls[] =(string) $request->getUri();

    }

    return $presignedUrls;
  }
}