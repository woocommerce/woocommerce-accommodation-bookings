#!/bin/bash

# SomewhereWarm Version Update Script
# This script updates version numbers across plugin files

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# In-place sed that works with both GNU sed (Linux/CI) and BSD sed (macOS),
# which disagree on the -i syntax.
sedi() {
    if sed --version >/dev/null 2>&1; then
        sed -i "$@"
    else
        sed -i '' "$@"
    fi
}

# Function to validate version format
validate_version() {
    local version=$1
    if [[ ! $version =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.-]+)?$ ]]; then
        print_error "Invalid version format. Please use format: x.x.x or x.x.x-dev"
        exit 1
    fi
}

validate_core_version() {
    local version=$1
    if [[ ! $version =~ ^[0-9]+\.[0-9]+(-[a-zA-Z0-9.-]+)?$ ]]; then
        print_error "Invalid version format. Please use format: x.x or x.x-dev"
        exit 1
    fi
}

# Function to get main plugin file from directory name
get_main_plugin_file() {
    local dir_name=$(basename "$PWD")
    echo "${dir_name}.php"
}

update_wp_tested_up_to() {
	local old_version=$1
	local new_version=$2
	local main_plugin_file=$(get_main_plugin_file)
	FILES_TO_UPDATE=("readme.txt" "$main_plugin_file")

	for file in "${FILES_TO_UPDATE[@]}"; do
		if [[ -f "$file" ]]; then
			sedi "s/Tested up to: $old_version/Tested up to: $new_version/g" "$file"
			print_success "Updated WP tested up to in $file to $new_version"
		else
			print_warning "$file not found, skipping..."
		fi
	done
}

update_wc_tested_up_to() {
	local old_version=$1
	local new_version=$2
	local main_plugin_file=$(get_main_plugin_file)
	FILES_TO_UPDATE=("readme.txt" "$main_plugin_file")

	for file in "${FILES_TO_UPDATE[@]}"; do
		if [[ -f "$file" ]]; then
			sedi "s/WC tested up to: $old_version/WC tested up to: $new_version/g" "$file"
			print_success "Updated WC tested up to in $file to $new_version"
		else
			print_warning "$file not found, skipping..."
		fi
	done
}

# Function to update version in specific files
update_version_in_files() {
    local old_version=$1
    local new_version=$2

    # Get main plugin file
    local main_plugin_file=$(get_main_plugin_file)
    print_status "Main plugin file: $main_plugin_file"

    # Update package.json — only the "version" field
    if [[ -f "package.json" ]]; then
        sedi "s/\"version\": \"$old_version\"/\"version\": \"$new_version\"/" "package.json"
        print_success "Updated package.json"
    else
        print_warning "package.json not found, skipping..."
    fi

    # Update readme.txt — only the "Stable tag" field
    if [[ -f "readme.txt" ]]; then
        sedi "s/Stable tag: $old_version/Stable tag: $new_version/" "readme.txt"
        print_success "Updated readme.txt"
    else
        print_warning "readme.txt not found, skipping..."
    fi

    # Update main plugin file — header, @version annotation, and version constant
    if [[ -f "$main_plugin_file" ]]; then
        sedi \
            -e "s/^\( *\* Version:\) $old_version/\1 $new_version/" \
            -e "s/^\( \* @version\)  $old_version/\1  $new_version/" \
            -e "s/\(define( 'WC_ACCOMMODATION_BOOKINGS_VERSION', '\)$old_version'/\1$new_version'/" \
            "$main_plugin_file"
        print_success "Updated $main_plugin_file"
    else
        print_warning "$main_plugin_file not found, skipping..."
    fi
}

# Function to find and replace x.x.x patterns in all files
replace_version_patterns() {
    local new_version=$1

    print_status "Replacing x.x.x patterns in all files..."

    # Get only git-tracked files that contain x.x.x pattern
    git ls-files | grep -E '\.(php|js|json|txt|css|scss)$' | while read -r file; do
        # Skip specific files we handle elsewhere
        if [[ "$file" == "package.json" || "$file" == "readme.txt" || "$file" == "changelog.txt" ]]; then
            continue
        fi

        # Check if file contains x.x.x pattern
        if grep -q "x\.x\.x" "$file"; then
            sedi "s/x\.x\.x/$new_version/g" "$file"
            print_status "Updated $file"
        fi
    done

    print_success "x.x.x patterns replaced in all files"
}

# Function to get current version from package.json
get_current_version() {
    if [[ -f "package.json" ]]; then
        grep '"version"' package.json | sed 's/.*"version": "\([^"]*\)".*/\1/'
    else
        print_error "package.json not found. Cannot determine current version."
        exit 1
    fi
}

get_wp_tested_up_to() {
	local main_plugin_file=$(get_main_plugin_file)
	if [[ -f $main_plugin_file ]]; then
		grep 'Tested up to:' $main_plugin_file | head -1 | sed 's/.*Tested up to:[[:space:]]*\([0-9.]*\).*/\1/'
	else
		print_warning "$main_plugin_file not found. Cannot determine WordPress tested up to version."
		echo ""
	fi
}

get_wc_tested_up_to() {
	local main_plugin_file=$(get_main_plugin_file)
	if [[ -f $main_plugin_file ]]; then
		grep 'WC tested up to:' $main_plugin_file | head -1 | sed 's/.*WC tested up to:[[:space:]]*\([0-9.]*\).*/\1/'
	else
		print_warning "$main_plugin_file not found. Cannot determine WooCommerce tested up to version."
		echo ""
	fi
}

# Parse CLI arguments
# Usage: ./update_version.sh <version> [--wp <wp-version>] [--wc <wc-version>]
parse_args() {
    NEW_VERSION=""
    WP_VERSION=""
    WC_VERSION=""

    while [[ $# -gt 0 ]]; do
        case "$1" in
            --wp)
                WP_VERSION="$2"
                shift 2
                ;;
            --wc)
                WC_VERSION="$2"
                shift 2
                ;;
            *)
                if [[ -z "$NEW_VERSION" ]]; then
                    NEW_VERSION="$1"
                else
                    print_error "Unexpected argument: $1"
                    echo "Usage: $0 <version> [--wp <wp-version>] [--wc <wc-version>]"
                    exit 1
                fi
                shift
                ;;
        esac
    done
}

# Main script
main() {
    # Get main plugin file for display
    local main_plugin_file=$(get_main_plugin_file)
    print_status "SomewhereWarm Version Update Script - $main_plugin_file"
    echo

    # Get current version
    CURRENT_VERSION=$(get_current_version)
    CURRENT_WP_VERSION=$(get_wp_tested_up_to)
    CURRENT_WC_VERSION=$(get_wc_tested_up_to)
    print_status "Current version: $CURRENT_VERSION"
    print_status "Current WordPress tested up to: ${CURRENT_WP_VERSION:-N/A}"
    print_status "Current WooCommerce tested up to: ${CURRENT_WC_VERSION:-N/A}"

    # Parse CLI arguments
    parse_args "$@"

    # Fall back to interactive prompts if no version argument was given
    if [[ -z "$NEW_VERSION" ]]; then
        echo
        read -p "Enter new version number (e.g., 8.4.2): " NEW_VERSION
        read -p "[Optional] Enter WordPress version tested up to (e.g., 6.9): " WP_VERSION
        read -p "[Optional] Enter WooCommerce version tested up to (e.g., 10.1): " WC_VERSION
    fi

    # Validate input
    if [[ -z "$NEW_VERSION" ]]; then
        print_error "Version number cannot be empty"
        exit 1
    fi

    validate_version "$NEW_VERSION"

    if [[ -n "$WP_VERSION" ]]; then
        validate_core_version "$WP_VERSION"
    fi

    if [[ -n "$WC_VERSION" ]]; then
        validate_core_version "$WC_VERSION"
    fi

    # Update specific files with version replacement
    print_status "Updating current version to new in specific files..."
    update_version_in_files "$CURRENT_VERSION" "$NEW_VERSION"

    # Replace x.x.x patterns in all other files
    replace_version_patterns "$NEW_VERSION"

    if [[ -n "$WP_VERSION" ]]; then
        update_wp_tested_up_to "$CURRENT_WP_VERSION" "$WP_VERSION"
    fi

    if [[ -n "$WC_VERSION" ]]; then
        update_wc_tested_up_to "$CURRENT_WC_VERSION" "$WC_VERSION"
    fi

    echo
    print_success "Version update completed successfully!"
    print_status "New version: $NEW_VERSION"
    echo
    print_warning "Please review the changes before committing."
}

# Run main function
main "$@"
