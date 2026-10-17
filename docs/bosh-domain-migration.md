# BOSH: canonical domain switch without moving database

## Existing production data remain unchanged
- MySQL database: `n66843_master`; user: `n66843_masteruser`; host: localhost.
- Private application: `/home/n66843/master-app/current` and shared `.env`, `storage`.
- Existing web root and FTP target: `/home/n66843/public_html/edu.china.kg`.
- APP_KEY, logins, appointments, uploaded files and all records stay in place; **no database import, clone or replacement**.
- A change of domain naturally requires users to log in again because browser cookies are host-scoped.

## Required cPanel operation before deploy
In **Fornex cPanel → Domains → bosh.kg → Manage**, set **Document Root** to
`/home/n66843/public_html/edu.china.kg` (or relative `public_html/edu.china.kg`).
It must be exactly the already-running edu.china.kg directory, **not** a fresh `public_html/bosh.kg` folder.
cPanel may restrict changing a primary/alias domain's root; if so, request support or recreate as an addon domain with that shared document root.
Configure valid SSL/AutoSSL for both `bosh.kg` and `www.bosh.kg` if www is enabled. Confirm `https://bosh.kg/login` shows the existing BOSH login screen.

## Protected GitHub cutover
The deploy workflow checks both HTTPS domains return the *same current build* before it does anything.
If BOSH does not share the existing site, the workflow fails **before uploading or changing any files**, preserving the working edu.china.kg version.
Once the check passes, installer runs against the **original document root and original DB** and sets
`APP_URL=https://bosh.kg`. Production smoke tests then validate BOSH /login, /register, /up and deny access to protected files. Automatic rollback retains existing DB.

Keep edu.china.kg available as a fallback during initial DNS propagation, then add a deliberate HTTPS redirect to bosh.kg only after the new host is verified.

## Validation
1. DNS and TLS for bosh.kg; check GET /login and /up.
2. Log in through bosh.kg with an existing master phone/password.
3. Verify same services, appointment history, master slug and public URL on bosh.kg.
4. Test booking, persistence in **same** database and Nikita OTP. Do not request SMS on random customer numbers.
5. Verify private .env cannot be fetched and a prior release can roll back.
