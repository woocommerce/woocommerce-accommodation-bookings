#!/usr/bin/env bash
#
# Packages the plugin as deploy/<package.json name>.zip with one root folder, config.sww.zip.root
# (default: the package.json name).
#
# Usage: bash bin/build-zip.sh [<commit>]
#   Without a commit: runs the config.sww.zip.build package script (default `build`; empty for
#   none), then packages the current checkout, uncommitted and untracked files included.
#   With a commit: packages the files committed at it, for plugins without a build step.
#
# Steps: copy the source into a staging folder (never .git, node_modules, deploy or vendor),
# install production Composer dependencies there when config.sww.zip.composerProduction is true,
# run bin/hooks/build-zip-stage.sh <staging folder> when the repository has it, then zip the
# staging folder without the paths that the exclude list (config.sww.zip.exclude, default
# exclude.lst) names. The exclude list has one `zip -x` wildcard per line, matched against
# `<root>/<path>`; a leading `/` anchors a line at the plugin root, and `#` starts a comment.
set -euo pipefail

cd "$(dirname "$0")/.."
root=$PWD

config=$(bash bin/sww-config.sh)
name=$(jq -r .name <<< "$config")
plugin_dir=$(jq -r .zip.root <<< "$config")
exclude=$(jq -r .zip.exclude <<< "$config")
build=$(jq -r '.zip.build // ""' <<< "$config")
composer_production=$(jq -r .zip.composerProduction <<< "$config")
ref=${1:-}

work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT

source_dir=$root
if [[ -n "$ref" ]]; then
	if [[ -n "$build" ]]; then
		echo "Packaging a commit needs a plugin without a build step (config.sww.zip.build is \"$build\"). Check the commit out and run bash bin/build-zip.sh." >&2
		exit 1
	fi
	commit=$(git rev-parse --verify --end-of-options "$ref^{commit}")
	git diff --quiet "$commit" -- package.json || { echo "package.json differs from $commit; check the commit out instead." >&2; exit 1; }
	source_dir=$work/source
	mkdir "$source_dir"
	git archive --format=tar "$commit" | tar -x -C "$source_dir"
elif [[ -n "$build" ]]; then
	pnpm run "$build"
fi

if [[ ! -f "$source_dir/$exclude" ]]; then
	echo "Missing exclude list: $exclude" >&2
	exit 1
fi

stage=$work/stage/$plugin_dir
mkdir -p "$stage"
rsync -a --exclude=/.git --exclude=/node_modules --exclude=/deploy --exclude=/vendor "$source_dir/" "$stage/"

if [[ "$composer_production" == true ]]; then
	# Composer reads the package's Git version and revision through this pointer.
	if git_dir=$(git -C "$root" rev-parse --absolute-git-dir 2>/dev/null); then
		printf 'gitdir: %s\n' "$git_dir" > "$stage/.git"
	fi
	(cd "$stage" && composer install --no-dev --prefer-dist --no-progress --no-interaction)
	rm -f "$stage/.git"
fi

if [[ -f bin/hooks/build-zip-stage.sh ]]; then
	bash bin/hooks/build-zip-stage.sh "$stage"
fi

# Anchor `/` lines at the plugin folder and drop comments and blank lines.
patterns=$work/exclude.lst
while IFS= read -r line || [[ -n "$line" ]]; do
	line=${line%$'\r'}
	[[ -z "$line" || "$line" == \#* ]] && continue
	if [[ "$line" == /* ]]; then
		line="$plugin_dir$line"
	fi
	printf '%s\n' "$line"
done < "$source_dir/$exclude" > "$patterns"

(cd "$work/stage" && zip -qr "$work/plugin.zip" "$plugin_dir" -x "@$patterns")

mkdir -p deploy
mv "$work/plugin.zip" "deploy/$name.zip"
echo "Built deploy/$name.zip"
