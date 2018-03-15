<?php

namespace Drupal\Tests\graphql_signed_s3_upload\Kernel\Extension;

use Drupal\Tests\graphql_core\Kernel\GraphQLContentTestBase;

/**
 * Test a simple mutation.
 *
 * @group graphql
 */
class AddS3FilesMutationTest extends GraphQLContentTestBase {
  public static $modules = [
    'file',
    'image',
    'taxonomy',
    'media',
    's3fs',
    'graphql_signed_s3_upload',
  ];

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheTags() {
    return array_merge([
      'config:field.storage.media.field_media_image',
    ], parent::defaultCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['file', 'image', 'media', 'graphql_signed_s3_upload']);
  }

  /**
   * {@inheritdoc}
   *
   * Add the 'access content' permission to the mocked account.
   */
  protected function userPermissions() {
    $perms = parent::userPermissions();
    $perms[] = 'access content';
    $perms[] = 'create media';
    $perms[] = 'create image media';
    $perms[] = 'execute graphql requests';
    return $perms;
  }

  /**
   * Test if the media is created properly.
   */
  public function testAddS3FilesMutation() {
    $query = $this->getQueryFromFile('addS3Files.gql');
    $variables = [
      'input' => [
        'files' => [
          [
            'filename' => "image-name.jpg",
            'filesize' => 61870,
            'url' => "username/000-000-000/image-name.jpg"
          ]
        ]
      ]
    ];
    $expected = [
      'addS3Files' => [
        "mid" => 1,
        "images"=> [
          "derivative" => [
            "url" => "http://d8d.loc/s3/files/styles/medium/public/username/000-000-000/image-name.jpg"
          ]
        ]
      ]
    ];

    $this->assertResults($query, $variables, $expected, $this->defaultMutationCacheMetaData());
  }
//
//  /**
//   * Test if the media is NOT created properly.
//   */
//  public function testCreateArticleFailureMutation() {
//    $query = $this->getQueryFromFile('createArticle.gql');
//    $variables = [
//      'input' => [
//        'title' => 'Hey',
//        'some-non-existent-field' => "Ho"
//      ]
//    ];
//    $expected = [
//      "Variable \"\$input\" got invalid value {\"title\":\"Hey\",\"some-non-existent-field\":\"Ho\"}.
//In field \"some-non-existent-field\": Unknown field."
//    ];
//    $this->assertErrors($query, $variables, $expected, $this->defaultMutationCacheMetaData());
//  }


}
