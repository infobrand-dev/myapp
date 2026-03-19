# AGENTS.md - Universal Working Rules

## Purpose
- This file should stay generic.
- Keep it focused on technical working rules that remain useful across the app.
- Do not store feature inventory, module lists, route maps, credentials, or product-specific behavior here.

## Source of truth
- Check `README.md` for setup and runtime commands.
- Check `MODULES.md` for module catalog and module-specific notes.
- Check `ARCHITECTURE.md` for app-specific architecture, boundaries, and implementation patterns.
- When a module is involved, read its `module.json` before changing code.

## General engineering rules
- Prefer changes that are small, reversible, and easy to review.
- Do not hardcode assumptions that belong in configuration, manifests, or database state.
- Keep documentation aligned with the current codebase; if behavior changes, update the relevant `.md` file in the same work.
- Prefer extending existing patterns over inventing a parallel structure.

## Boundaries
- Keep framework or app-shell code separate from optional or feature-specific code.
- Avoid spreading one feature across unrelated layers when it can stay localized.
- If a feature has its own manifest, config, routes, views, migrations, or assets, keep those concerns close to the owning feature.
- Shared code should stay generic; feature-specific integration logic should stay near the feature that needs it.

## Configuration and secrets
- Never commit secrets, local credentials, `.env`, keys, session artifacts, or generated runtime caches.
- Do not document fixed default passwords as if they are guaranteed production behavior.
- Prefer environment variables or persistent settings over hardcoded local URLs or credentials.

## Data and seeders
- Seeders and demo data should avoid depending on fragile hardcoded identities where possible.
- If seed data needs an acting user, resolve it using a durable rule rather than a magic email or ID.
- Keep seeders idempotent when practical.

## UI and frontend
- Reuse shared UI components before adding new duplicates.
- Keep forms, labels, and select options human-readable.
- Preserve the existing frontend stack and styling conventions unless the task explicitly changes them.
- Do not mix unrelated UI patterns in the same flow without a reason.

## Background work and integrations
- Verify queues, jobs, realtime, and external integrations against actual config before changing behavior.
- Keep adapter code near the integration that owns it.
- Separate transport-specific logic from normalized application behavior when possible.

## Documentation hygiene
- `AGENTS.md` should remain universal.
- Move app-specific detail to focused docs such as `ARCHITECTURE.md`, `MODULES.md`, or other dedicated notes.
- If a section starts reading like product documentation, it likely does not belong here.

## File hygiene
- Keep generated dependencies, build output, local runtime state, and temporary artifacts out of version control.
- Be careful with local caches and machine-specific files.
