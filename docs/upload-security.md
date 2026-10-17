# File upload security

Only authenticated profile image uploads are supported. Other web routes reject file attachments, and the profile accepts one avatar and one cover only. Laravel CSRF protection remains enabled. Profile writes are limited to 20 requests per minute per authenticated user.

The server validates image content, allowlists JPEG/PNG/WebP, checks the client extension against the detected image type, rejects double extensions and unsafe names, and limits each file to 2 MiB, 4096 pixels per dimension and 12 million pixels. Client names never become stored paths: Laravel generates a random 40-character name under the owner's private directory.

Files remain outside the web root. Generic local-disk serving is disabled. The image controller checks the exact owner directory and generated filename, rejects symlinks and path escapes, allowlists response MIME types, and sends nosniff and restrictive CSP headers. Owner-authorized replacement/removal never accepts a client-supplied storage path.

Regression tests cover SVG, executable/double extensions, MIME/extension mismatch, size limits, nested and unexpected file fields, authentication, throttling, owner isolation, tampered database paths and symbolic links. Fixtures are inert and tests run against temporary local storage.

## Limits

These checks are not antivirus scanning. No antivirus daemon or image decode/re-encode pipeline has been verified on the production host. Original accepted raster bytes can include metadata or trailing data; private storage and controlled image responses prevent treating them as application code, but do not establish that the bytes are malware-free. Do not add downloads, documents, archives or other file formats without a separate validation and scanning design. Keep PHP, framework and image-handling libraries patched. Absolute protection from all exploits cannot be guaranteed.

Trusted deployment archives use a separate encrypted, expiring authenticated installer, which rejects traversal and symbolic-link archive entries. They are not accepted through user-facing upload routes.
