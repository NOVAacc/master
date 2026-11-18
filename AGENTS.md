# BOSH repository rules

## Production environment (.env) — strict preservation

- The production configuration is `/home/g143326/master-app/shared/.env`. Each release's `current/.env` is a symlink to the shared file.
- **Never overwrite, regenerate, truncate, normalize, replace, remove, or roll back an existing production `.env`.** This includes deployment, rollback, install scripts, code changes, AI assistants, GitHub Actions, and maintenance commands.
- Existing variables, values, comments, formatting, and ordering must remain untouched. New settings may only be **appended** when explicitly required and authorized, without modifying any existing entry. Never append a duplicate variable; report conflicting/absent settings rather than updating them automatically.
- Deployment may create `.env` **only on a truly fresh installation where it does not exist**. All subsequent releases and rollbacks preserve the existing file byte-for-byte.
- Do not place production secrets in the repository, CI logs, or chat. Do not use `cp .env.example .env` against a live installation.
- Any deployment change must retain an integration test asserting byte-for-byte `.env` preservation, including unknown keys and rollbacks.
