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

    foreach ($filenames as $filename) {

      $cmd = $s3Client->getCommand('GetObject', [
        'Bucket' => \Drupal::config('s3fs.settings')->get('bucket'),
        'Key' => $filename
      ]);

      $request = $s3Client->createPresignedRequest($cmd, '+5 minutes');
      $presignedUrls[] =(string) $request->getUri();

    }

    return $presignedUrls;
  }
}