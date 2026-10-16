# Architecture

Domain: edu.china.kg (temporary)
Database: n66843_master
Database username: n66843_masteruser

Stack proposal: Laravel, Inertia, React and MySQL. Do not store credentials in the repository.

Required preflight for JAZ shared hosting: PHP version/extensions, web root, database host, FTP/SFTP, SSH/Composer, scheduled tasks and HTTPS.

MVP models: users, master_profiles, services, working_hours, availability_blocks, appointments, clients.

An appointment must reserve the whole service duration; overlapping active appointments are rejected at transaction time. Public booking needs rate limiting and personal-data notice.

Deployment is not configured yet.
