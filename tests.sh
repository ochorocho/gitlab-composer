#!/bin/bash

# some commands must be executable
satis --version || exit 1
satis publish-gitlab --help || exit 1
composer --version || exit 1
