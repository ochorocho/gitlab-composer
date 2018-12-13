#!/bin/bash

# some commands must be executable
bin/satis --version || exit 1
vendor/bin/composer --version || exit 1