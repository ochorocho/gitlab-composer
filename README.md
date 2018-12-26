# Composer/Satis build a single package

## What it does

Generates an archive and a JSON file for a single package tag/branch.
Does the same as Satis but only for a single package and tag.   

This is using Composer and Satis in the background.

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