#!/usr/bin/env python3
"""Encrypt a private release and generate an expiring fixed-operation installer."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import time

ROOT = Path(__file__).resolve().parent


def php_literal(value):
    if isinstance(value, dict):
        return '[' + ','.join(php_literal(k) + '=>' + php_literal(v) for k, v in value.items()) + ']'
    if isinstance(value, bool):
        return 'true' if value else 'false'
    if isinstance(value, int):
        return str(value)
    return "'" + str(value).replace('\\', '\\\\').replace("'", "\\'") + "'"


def prepare(args):
    if not re.fullmatch(r'[a-f0-9]{40}', args.revision):
        raise ValueError('Expected a Git commit SHA')
    if not re.fullmatch(r'[a-f0-9]{40}-[0-9]+-[0-9]+', args.release):
        raise ValueError('Invalid release ID')
    password = os.environ.get('MASTER_DB_PASSWORD', '')
    if not password:
        raise ValueError('MASTER_DB_PASSWORD must be configured')
    args.output.mkdir(parents=True, exist_ok=True, mode=0o700)
    token = secrets.token_hex(32)
    upload = 'master-upload-' + secrets.token_hex(16) + '.bin'
    installer = 'master-install-' + secrets.token_hex(16) + '.php'
    encrypted = args.output / upload
    # The encryption key only leaves this process in the HTTPS request file.
    program = '''$key=random_bytes(32);$iv=random_bytes(12);
$data=openssl_encrypt(file_get_contents($argv[1]),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
if($data===false)exit(1);file_put_contents($argv[2],$data);
echo json_encode(['key'=>base64_encode($key),'iv'=>base64_encode($iv),'tag'=>base64_encode($tag)]);'''
    result = subprocess.run([args.php, '-r', program, str(args.archive.resolve()), str(encrypted.resolve())],
                            check=True, capture_output=True, text=True)
    crypto = json.loads(result.stdout)
    config = {'root': str(args.site_root), 'base': str(args.private_root), 'revision': args.revision,
              'release': args.release, 'url': args.url, 'db_host': args.db_host, 'db_port': args.db_port,
              'db_name': args.db_name, 'db_user': args.db_user, 'secure_cookies': args.url.startswith('https://'),
              'expires': int(time.time()) + 900, 'token_hash': hashlib.sha256(token.encode()).hexdigest(),
              'upload': upload, 'sha256': hashlib.sha256(encrypted.read_bytes()).hexdigest()}
    template = (ROOT / 'installer.php.template').read_text()
    (args.output / installer).write_text(template.replace('__DEPLOY_CONFIG__', php_literal(config)))
    payload = {'action': 'install', **crypto, 'db_password': password}
    # This is a short-lived, chmod 0600 local HTTPS request, not part of the release archive.
    # GitHub Actions injects this secret from the nikita_api repository secret.
    otp_key = os.environ.get('NIKITA_OTP_API_KEY', '')
    if otp_key:
        payload['nikita_otp_api_key'] = otp_key
    smtp_host = os.environ.get('BOSH_SMTP_HOST', '').strip()
    smtp_password = os.environ.get('BOSH_SMTP_PASSWORD', '')
    if smtp_password and not smtp_host:
        raise ValueError('BOSH_SMTP_HOST is required when BOSH_SMTP_PASSWORD is set')
    # Allow deployments before the mailbox password is configured. Never enable
    # logging or dummy SMTP as a fallback for verification/reset messages.
    if smtp_password:
        payload['smtp'] = {'host': smtp_host, 'password': smtp_password}

    (args.output / 'request.json').write_text(json.dumps(payload))
    (args.output / 'rollback.json').write_text('{"action":"rollback"}')
    (args.output / 'auth-header.txt').write_text('X-Master-Deploy: ' + token + '\n')
    (args.output / 'names.json').write_text(json.dumps({'installer': installer, 'upload': upload}))
    for file in args.output.iterdir():
        file.chmod(0o600)
    print(json.dumps({'installer': installer, 'upload': upload, 'expires_in_seconds': 900}))


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('archive', type=Path)
    p.add_argument('output', type=Path)
    p.add_argument('--revision', required=True)
    p.add_argument('--release', required=True)
    p.add_argument('--php', default='php')
    p.add_argument('--site-root', type=Path, default=Path('/home/n66843/public_html/edu.china.kg'))
    p.add_argument('--private-root', type=Path, default=Path('/home/n66843/master-app'))
    p.add_argument('--url', default='https://edu.china.kg')
    p.add_argument('--db-host', default='localhost')
    p.add_argument('--db-port', type=int, default=3306)
    p.add_argument('--db-name', default='n66843_master')
    p.add_argument('--db-user', default='n66843_masteruser')
    prepare(p.parse_args())
