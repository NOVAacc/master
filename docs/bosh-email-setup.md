# BOSH: Email sender and password recovery (Fornex)

## Create mailbox
In Fornex cPanel → Email Accounts → Create, select **bosh.kg** and create **no-reply@bosh.kg** with a unique strong generated password. Ensure bosh.kg is added as a mail domain to the hosting account. If domain/mail records are hosted elsewhere, configure MX, SPF, DKIM and preferably DMARC based on Fornex cPanel's Email Deliverability recommendations.

See [Fornex cPanel mailbox](https://ru.fornex.com/help/creating-mail-cpanel/) and [secure client settings](https://fornex.com/ru/help/connecting-mail-cpanel/).

## Add GitHub Actions secrets
Repository → Settings → Secrets and variables → Actions → New repository secret:

- `BOSH_SMTP_HOST`: **exact outgoing server from cPanel → Connect Devices / Set Up Mail Client**; should match certificate, e.g. a `host####.fornex.org` host. Do not assume the website domain or FTPS endpoint is an authenticated SMTP server.
- `BOSH_SMTP_PASSWORD`: password for the no-reply@bosh.kg mailbox.

No password is stored in tracked files. Production deployment transmits these secrets over the existing authenticated, short-lived HTTPS installer request and writes them to the private `/home/n66843/master-app/shared/.env` with 0600 mode. The installer only activates the SMTP transport if both settings are supplied; old mail settings are preserved on later deploys if secrets are absent. Both secrets must be present together to avoid failed deployment.

The deployed config uses SMTP over TLS on port 465 with `MAIL_SCHEME=smtps`, `MAIL_USERNAME=no-reply@bosh.kg`, and `MAIL_FROM_ADDRESS=no-reply@bosh.kg`.

## Verify
1. Deploy via the existing GitHub Actions Build server release → Deploy Master application workflow.
2. In the master dashboard, save an email address, resend a verification link, and inspect inbox/spam.
3. Confirm the email, then use Forgot password; verify reset email arrives, link expires, and the reset updates the login password.
4. Check headers for SPF/DKIM alignment and ensure mail secrets and reset tokens never appear in public logs.

Do **not** set `MAIL_MAILER=log` in production when verification/recovery is expected; account security endpoints explicitly refuse log/array mailers.
