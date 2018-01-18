<?php

namespace Drupal\graphql_signed_s3_upload;

use Aws\S3\S3ClientInterface;

class SigningUtilities {

  /**
   * Returns the generated URL
   *
   * @param String $filename
   * @param \Aws\S3\S3ClientInterface $s3Client
   *   Client object.
   *
   * @return array
   *   The presigned URLs
   */
  static function generateUrls(Array $filenames, S3ClientInterface $s3Client){

    $presignedUrls = [];
    $bucket = \Drupal::config('s3fs.settings')->get('bucket');

    foreach ($filenames as $filename) {
      
      $cmd = $s3Client->getCommand('PutObject', [
        'Bucket' => $bucket,
        'Key' => $filename,
        'ContentType' => 'image/jpeg',
        'Body'        => '',
      ]);

      $request = $s3Client->createPresignedRequest($cmd, '+5 minutes');
      $presignedUrls[] =(string) $request->getUri();

    }

    return $presignedUrls;
  }
}