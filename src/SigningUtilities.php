<?php

namespace Drupal\graphql_signed_s3_upload;

use \Aws\S3\S3ClientInterface;
use \Aws\S3\S3Client;

class SigningUtilities {

    /**
     * The AWS SDK for PHP S3Client object.
     *
     * @var \Aws\S3\S3ClientInterface
     */
    protected $s3Client = NULL;

    /**
     *
     */
    protected $s3fsSettings = NULL;

    /**
     * Constructs a MediaImageUtilities object.
     *
     * @param \Aws\S3\S3ClientInterface $s3client
     *   The s3 Client.
     */
    public function __construct() {
        $this->s3fsSettings = $config = \Drupal::config('s3fs.settings')->get();
        $this->s3Client = new S3Client($this->getClientConfig());
    }

    /**
     * Returns the generated URL
     *
     * @param array $filenames
     *   The fullpath/filenames that we need presigned urls for.
     *
     * @return array
     *   The presigned URLs
     */
    public function generateUrls(Array $filenames){
        $presignedUrls = [];

        $bucket = $this->s3fsSettings->get('bucket');
        $public_config = $this->s3fsSettings->get('public_folder');
        $private_config = $this->s3fsSettings->get('private_folder');

        $public_folder = !empty($public_config) ? $public_config : 's3fs-public';

        //TODO: handle private folders
        $private_folder = !empty($private_config) ? $private_config : 's3fs-private';

        foreach ($filenames as $filename) {

            $cmd = $this->s3Client->getCommand('PutObject', [
                'Bucket'      => $bucket,
                'Key'         => $public_folder . '/' . $filename,
                'ContentType' => 'image/jpeg',
                'ACL'         => 'public-read',
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, '+5 minutes');
            $presignedUrls[] =(string) $request->getUri();
        }

        return $presignedUrls;
    }

    /**
     * Returns the S3fs S3 configuration
     *
     * @return array
     */
    protected function getClientConfig(){

        $clientConfig['credentials'] = [
            'key' => $this->s3fsSettings['access_key'],
            'secret' => $this->s3fsSettings['secret_key'],
        ];

        if (!empty($this->s3fsSettings['region'])) {
            $clientConfig['region'] = $this->s3fsSettings['region'];
            // Signature v4 is only required in the Beijing and Frankfurt regions.
            // Also, setting it will throw an exception if a region hasn't been set.
            $clientConfig['signature_version'] = 'v4';
            $clientConfig['version'] = '2006-03-01';
        }
        return $clientConfig;
    }
}