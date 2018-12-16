# Composer/Satis build a single package

## What it does

Generates  

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
      "url": "./" // Path to repository
    }
  ],
  "require-all": true,
  "archive": {
    "directory": "composer/packages", // Path in which archives are stored
    "format": "tar",
    "prefix-url": "https://gitlab.knallimall.org", // Path to your gitlab instance
    "skip-dev": false
  },
  "output-dir": "build"
}
```