# Composer/Satis build a single package

## What it does

Generates an archive and a JSON file for a single package tag/branch.
Does the same as Satis but only for a single package and tag.   

This is using Composer and Satis in the background.

:warning: To run this outside of gitlab-runner make sure you have set the following environment variables

```bash
# Example env file
CI_PROJECT_URL=http://localhost:3001/NAMESPACE/PROJECT
CI_PROJECT_ID=#ID_OF_PROJECT
PRIVATE_TOKEN=YOUR_PRIVATE_TOKEN
```

## Run command

```bash
./gitlab-composer build-local ./satis-example.json --version-to-dump=develop

```

## Development

For development you need [composer](https://getcomposer.org/) installed.

```bash
git clone https://github.com/ochorocho/gitlab-composer.git
cd gitlab-composer/
composer install

```

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

```
