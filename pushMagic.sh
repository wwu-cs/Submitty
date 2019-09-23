#!/bin/bash

git add .
git status

if [ "$1" != "" ]; then
    git commit -m "$1"
else
    git commit -m "autosave"
fi

git push
