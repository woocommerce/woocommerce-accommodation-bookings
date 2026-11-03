---
applyTo: "**/*.php"
---

# PHP conventions

## DocBlock versioning tags

- When modifying a PHP file that declares a class, trait, or interface, set its declaration’s DocBlock `@version` to `x.x.x`. Keep exactly one such tag.
- `x.x.x` is intentional: release tooling replaces it with the released version. Do not substitute a guessed version or report the placeholder as unfinished work.
- Report a missing or incorrect class, trait, or interface `@version` when it violates this workflow. Check the repository’s version-validation script and CI configuration.
- Review `@since` and method-level `@version` tags for errors introduced or made misleading by the PR. Leave unrelated historical tag cleanup outside the review.
- Follow the repository’s separate versioning conventions for template files.
