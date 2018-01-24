# graphql_signed_s3_upload


This module generates an array of upload urls from input filenames. There is a dependency on the s3fs module and you must have an s3 bucket created with the appropriate permissions set. 


QUERY 
```$xslt
query {
  signedUploadURL(input:$input)
}
```

example: 
```$xslt
query{
  signedUploadURL(input:{fileNames:["super.jpg", "extra.jpg"]})
}
```


RESULT
```$xslt
{
  "data": {
    "signedUploadURL": [
      "https://xxx.s3.amazonaws.com/test?X-Amz-Content-..."
    ]
  }
}
```

After the file has been uploaded, there is another graphql mutation for adding/syncing the files back to drupal: 

```$xslt
mutation{
  addS3Files(input:{
    files:[
      {filename:"image_1.jpg", filesize:123, url:"uid/node/image_1.jpg"},
      {filename:"image_2.jpg", filesize:123, url:"uid/node/image_2.jpg"}
    ]
  }){
    ... on MediaImage {
      image {
        derivative(style: large){
          url
        }
      }
    }
  }
}
```


RESULT
```$xslt
{
  "data": {
    "addS3Files": [
      {
        "image": {
          "derivative": {
            "url": "http://d8d.loc/s3/files/styles/large/public/uid/node/image_1.jpg?itok=qpThHo7H"
          }
        }
      },
      {
        "image": {
          "derivative": {
            "url": "http://d8d.loc/s3/files/styles/large/public/uid/node/image_2.jpg?itok=UOlnG2yS"
          }
        }
      }
    ]
  }
}
```