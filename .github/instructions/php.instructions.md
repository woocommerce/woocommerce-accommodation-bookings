---
applyTo: "**/*.php"
---

# PHP conventions

## DocBlock versioning tags

- Every class, trait, and interface DocBlock has exactly one `@version` tag with the release that last changed the file.
- When a PR modifies a file that declares a class, trait, or interface, set that DocBlock's `@version` to `x.x.x`. The `check-version.yml` workflow fails the PR otherwise.
- Do not replace `x.x.x` with a guessed version. The release bump replaces every `x.x.x` with the version being released.
- Flag a modified class, trait, or interface whose `@version` is not `x.x.x`. Do not flag `x.x.x` itself as a placeholder.
- Ignore `@since` tags and method-level `@version` tags. Do not comment on missing, incorrect, or placeholder `@since` tags.
