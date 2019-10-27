# Composer/Satis build a single package

## What it does

Generates an archive and a JSON file for a single package tag/branch.
Does the same as Satis but only for a single package and tag.   

This is using Composer and Satis.

## Run command

```bash
./vendor/bin/satis build --versions-only=master
```

## Development

For development you need [composer](https://getcomposer.org/) installed.

```bash
git clone https://github.com/ochorocho/gitlab-composer.git
cd gitlab-composer/
composer install
```

Run it

```bash
vendor/bin/satis publish-gitlab $CI_PROJECT_URL $CI_PROJECT_ID $PRIVATE_TOKEN
```

vendor/bin/satis publish-gitlab $CI_PROJECT_URL $CI_PROJECT_ID $PRIVATE_TOKEN
## Example config

Configure your project build based on [Satis Schema](https://github.com/composer/satis/blob/master/res/satis-schema.json).
You can work with all config options available in Satis 

```json

{
  "name": "vendor/project",
  "homepage": "http://www.example.com",
  "repositories": [
    {
      "type": "vcs",
      "url": "./"
    }
  ],
  "providers": false,
  "output-dir": "build",
  "output-html": false,
  "archive": {
    "format": "tar",
    "directory": "build",
    "absolute-directory": "build"
  }
}


```

### Example `.gitlab-ci.yml`

```yaml
image: ochorocho/gitlab-composer:latest

build:
  stage: build
  script:
    - git checkout $CI_COMMIT_REF_NAME
    - git pull
    - satis build --versions-only=$CI_COMMIT_REF_NAME
    - satis publish-gitlab --project-url=$CI_PROJECT_URL --project-id=$CI_PROJECT_ID
    - satis publish-gitlab $CI_PROJECT_URL $CI_PROJECT_ID
```
