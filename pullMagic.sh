#!/bin/bash

if [ "$1" != "" ]; then
    git checkout -m "$1"
else
    git checkout master
fi

git status

git pull
