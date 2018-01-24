<?php

namespace Drupal\graphql_signed_s3_upload;

use \Aws\S3\S3Client;
use Drupal\Core\Config\ConfigFactoryInterface;


class SignedUploadURLManager {

    /**
     * The AWS SDK for PHP S3Client object.
     *
     * @var \Aws\S3\S3ClientInterface
     */
    protected $s3Client = NULL;

    /**
     * @var \Drupal\Core\Config\ImmutableConfig $s3fsConfig
     */
    protected $s3fsConfig = NULL;

    /**
     * Constructs a SigningUtility object.
     *
     * @param ConfigFactoryInterface $configFactory
     *   The s3 Client.
     */
    public function __construct($configFactory) {
        $this->s3fsConfig = $configFactory->get('s3fs.settings');
        $this->s3Client = new S3Client($this->getClientConfig($this->s3fsConfig));
    }

    /**
     * {@inheritdoc}
     */
    public static function create($configFactory) {
        return new static($configFactory);
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

        $bucket = $this->s3fsConfig->get('bucket');
        $public_config = $this->s3fsConfig->get('public_folder');
        $private_config = $this->s3fsConfig->get('private_folder');

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
     * @param array $s3fsConfig
     *   The container.
     *
     * @return array
     */
    public static function getClientConfig($s3fsConfig){

        $clientConfig['credentials'] = [
            'key' => $s3fsConfig->get('access_key'),
            'secret' => $s3fsConfig->get('secret_key'),
        ];

        if (!empty($s3fsConfig->get('region'))) {
            $clientConfig['region'] = $s3fsConfig->get('region');
            // Signature v4 is only required in the Beijing and Frankfurt regions.
            // Also, setting it will throw an exception if a region hasn't been set.
            $clientConfig['signature_version'] = 'v4';
            $clientConfig['version'] = '2006-03-01';
        }
        return $clientConfig;
    }

}