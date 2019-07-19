#!/bin/bash

# some commands must be executable
#/gitlab-composer/gitlab-composer --version || exit 1
/gitlab-composer/vendor/bin/satis --version || exit 1
/gitlab-composer/vendor/bin/composer --version || exit 1
