#!/bin/bash

# some commands must be executable
composer --version || exit 1
composer help package | grep "options" || exit 1
composer help publish | grep "<project-url>" || exit 1
