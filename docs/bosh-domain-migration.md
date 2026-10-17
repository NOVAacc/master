# BOSH: move to separate Fornex web root without moving MySQL

## Source and destination
- Old site root: `/home/n66843/public_html/edu.china.kg`
- **New site root**: `/home/n66843/public_html/bosh.kg`
- **Unchanged private application**: `/home/n66843/master-app` with existing `current`, `shared/.env`, `shared/storage`.
- **Unchanged MySQL database**: `n66843_master` on `localhost`, user `n66843_masteruser`.
- **Unchanged APP_KEY** and all client/agent data, appointments, services and uploads.

## Required Fornex and GitHub configuration

1. In Fornex cPanel, verify the document root for bosh.kg is **public_html/bosh.kg** and PHP 8.3 is enabled there. The old directory remains untouched.
2. Enable valid HTTPS/AutoSSL for bosh.kg; `https://bosh.kg` must be reachable.
3. Create an **FTP/FTPS account with its home directory exactly set to `public_html/bosh.kg`**. The account's `/` in FTP must map to bosh.kg, **not** edu.china.kg.
4. In GitHub repository Secrets and variables → Actions, set:
   - `BOSH_FTP_USER`: full login of the new FTPS account.
   - `BOSH_FTP_PASSWORD`: password of that FTPS account.
   Keep existing `MASTER_DB_PASSWORD`, `nikita_api`, and other secrets unchanged.

The deploy makes a unique, harmless text-file upload over verified TLS and fetches it from bosh.kg over HTTPS; it additionally asserts the probe is **not** accessible from edu.china.kg. If either check fails, deployment stops *before* installation; the probe is removed. The installer accepts the new site root only when the original master-app installation marker matches the exact old root and the private path and canonical URL match the expected BOSH paths.

## After preflight

The release is installed into the **existing private application and database**, while public PHP launcher, build files and .htaccess are placed inside `public_html/bosh.kg`. The server-side `APP_URL` changes to `https://bosh.kg`. Existing accounts remain, but host-specific sessions must log in again. Automatic rollback restores the previous code and the .env; the database is not overwritten or rolled back.

Keep edu.china.kg active temporarily. Redirect old links to the new domain only after validating bookings, master profiles, Nikita OTP and images on bosh.kg.

## Warning

Do **not** upload a new Laravel application to the bosh.kg directory or initialize a fresh database. The old FTP secrets might be restricted to the edu.china.kg directory. Explicit BOSH-specific FTPS secrets and a successful HTTPS probe are mandatory before the cutover can proceed.
