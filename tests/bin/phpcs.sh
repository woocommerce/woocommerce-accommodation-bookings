#!/usr/bin/env bash

COMMIT_RANGE="${1} ${2}"
CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $COMMIT_RANGE | grep '\.php' | awk '{print}' ORS=' '`
IGNORE="tests/,vendor/,dist/"

if [ "$CHANGED_FILES" != "" ]; then
	echo "Changed files: $CHANGED_FILES"
	echo "Running Code Sniffer."

	./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p --report-full ${CHANGED_FILES}
else
	echo "No changes found. Skipping PHPCS run."
fi
