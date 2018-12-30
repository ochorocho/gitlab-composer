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
./gitlab-composer build-local ./satis-example.json --version-to-dump=dev-develop

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
  "repositories": [
    {
      "type": "vcs",
      "url": "./"
    }
  ],
  "archive": {
    "format": "tar",
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
    - echo $CI_COMMIT_REF_NAME 
    - git checkout -B "$CI_BUILD_REF_NAME" "$CI_BUILD_REF"     # Workaround detached head causing confusion in satis, see https://gitlab.com/gitlab-org/gitlab-ce/issues/19421
    - /gitlab-composer/gitlab-composer build-local ./satis.json --version-to-dump=$CI_COMMIT_REF_NAME
    - /gitlab-composer/gitlab-composer publish ./satis.json

```
