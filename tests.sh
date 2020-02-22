#!/bin/bash

# some commands must be executable
composer --version || exit 1
composer help package || exit 1
