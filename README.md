# Composer/Satis build a single package

## What it does

Generates an archive and a JSON file for a single package tag/branch
using Composer and a composer plugin `gitlab/composer-plugin` to package and publish to Gitlab.
It uses the `composer archive` command the build a package of the given Branch/Tag.


## Run command in Docker Container

```bash
docker run --rm -it --entrypoint "ash" ochorocho/gitlab-composer:3.0.0
composer package
composer publish http://<GITLAB IP>:3000/ <project id> <private token>
```

## Development

For development you need [composer](https://getcomposer.org/) installed.

### Install

```bash
git clone https://github.com/ochorocho/gitlab-composer.git
cd gitlab-composer/
composer install
export COMPOSER_HOME=/path-to/gitlab-composer/ # If not set, plugin is not loaded
```

### Structure

```
gitlab-composer
└── composerPlugins             
    └── gitlab-plugin      # Composer Plugin
        └── src
            ├── Command    # Contains Additional commands
            └── Publisher  # Code for `composer publish` command
```

### Run it

```bash
vendor/bin/composer package # create package in build folder
vendor/bin/composer publish http://<GITLAB IP>:3000/ <project id> <private token> # publish package to gitlab instance
```

### Example `.gitlab-ci.yml`

Runners `$CI_JOB_TOKEN` variable is used to authenticate against Gitlab API.

```yaml
image: ochorocho/gitlab-composer:3.0.0
build:
  stage: build
  when: always
  script:
    - composer package
    - composer publish $CI_PROJECT_URL $CI_PROJECT_ID
```
