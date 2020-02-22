#!/bin/bash

docker build --no-cache --pull . -f Dockerfile -t ochorocho/gitlab-composer:latest && docker run --rm -it -v `pwd`/tests.sh:/tmp/test.sh --entrypoint "ash" ochorocho/gitlab-composer:latest /tmp/test.sh
#docker images | grep gitlab-composer
