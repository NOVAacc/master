# BOSH: Email sender and password recovery (Fornex)

## Create mailbox
In Fornex cPanel → Email Accounts → Create, select **bosh.kg** and create **no-reply@bosh.kg** with a unique strong generated password. Ensure bosh.kg is added as a mail domain to the hosting account. If domain/mail records are hosted elsewhere, configure MX, SPF, DKIM and preferably DMARC based on Fornex cPanel's Email Deliverability recommendations.

See [Fornex cPanel mailbox](https://ru.fornex.com/help/creating-mail-cpanel/) and [secure client settings](https://fornex.com/ru/help/connecting-mail-cpanel/).

## Add GitHub Actions secrets
Repository → Settings → Secrets and variables → Actions → New repository secret:

- `BOSH_SMTP_PASSWORD`: password of the existing `no-reply@bosh.kg` mailbox. Set this secret privately; never paste it into chat or commit it.

The outgoing SMTP host is already set to `mail.bosh.kg` directly in the deploy workflow based on the mailbox settings from Fornex. The username is `no-reply@bosh.kg`, SMTP port 465, SMTPS. IMAP/POP settings are not used for outgoing verification and recovery emails.

No password is stored in tracked files. Production deployment transmits these secrets over the existing authenticated, short-lived HTTPS installer request and writes them to the private `/home/n66843/master-app/shared/.env` with 0600 mode. The installer only activates the SMTP transport if both settings are supplied; old mail settings are preserved on later deploys if secrets are absent. Only the password secret must be configured; the host is already fixed in the workflow. The installer refuses to enable SMTP without a password.

The deployed config uses SMTP over TLS on port 465 with `MAIL_SCHEME=smtps`, `MAIL_USERNAME=no-reply@bosh.kg`, and `MAIL_FROM_ADDRESS=no-reply@bosh.kg`.

## Verify
1. Deploy via the existing GitHub Actions Build server release → Deploy Master application workflow.
2. In the master dashboard, save an email address, resend a verification link, and inspect inbox/spam.
3. Confirm the email, then use Forgot password; verify reset email arrives, link expires, and the reset updates the login password.
4. Check headers for SPF/DKIM alignment and ensure mail secrets and reset tokens never appear in public logs.

Do **not** set `MAIL_MAILER=log` in production when verification/recovery is expected; account security endpoints explicitly refuse log/array mailers.
