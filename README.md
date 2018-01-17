# graphql_signed_s3_upload


This module generates an array of upload urls from input filenames

QUERY 
```$xslt
query {
  signedUploadURL(input:$input)
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