#!/bin/bash

# some commands must be executable
vendor/bin/satis --version || exit 1
vendor/bin/composer --version || exit 1