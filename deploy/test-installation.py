#!/usr/bin/env python3
"""Integration-test encrypted installation, upgrades and rollback against a disposable MySQL DB."""
import argparse
import http.client
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
import zipfile

ROOT = Path(__file__).resolve().parents[1]


def run(args):
    with tempfile.TemporaryDirectory(prefix='master-deploy-test-') as tmp:
        base = Path(tmp)
        site = base / 'site'
        private = base / 'private'
        site.mkdir()
        (site / 'index.html').write_text('Original Master demo')
        (site / '.htaccess').write_text('# original PHP handler\n')
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        url = f'http://127.0.0.1:{port}'
        server = subprocess.Popen([args.php, '-S', f'127.0.0.1:{port}', '-t', str(site)],
                                  cwd=site, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        try:
            for attempt in range(50):
                try:
                    urllib.request.urlopen(url, timeout=1).read()
                    break
                except (urllib.error.URLError, ConnectionError, http.client.HTTPException):
                    time.sleep(.1)
            else:
                raise RuntimeError('Test server did not start')

            def prepare(attempt):
                out = base / f'request-{attempt}'
                cmd = ['python3', str(ROOT / 'deploy/prepare-install.py'), str(args.archive.resolve()), str(out),
                       '--revision', args.revision, '--release', f'{args.revision}-999-{attempt}',
                       '--php', args.php, '--site-root', str(site), '--private-root', str(private), '--url', url,
                       '--db-host', args.db_host, '--db-name', args.db_name, '--db-user', args.db_user]
                subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL,
                               env={**os.environ, 'NIKITA_OTP_API_KEY': 'inert-test-otp-key-1234567890',
                                    'BOSH_SMTP_HOST': 'host-test.fornex.org',
                                    'BOSH_SMTP_PASSWORD': 'test-password-not-real-123456'})
                names = json.loads((out / 'names.json').read_text())
                for name in names.values():
                    shutil.copy2(out / name, site / name)
                token = (out / 'auth-header.txt').read_text().strip().split(': ', 1)[1]
                return out, names, token

            def request(names, token, payload):
                req = urllib.request.Request(url + '/' + names['installer'], data=json.dumps(payload).encode(),
                                             headers={'X-Master-Deploy': token, 'Content-Type': 'application/json'})
                try:
                    with urllib.request.urlopen(req, timeout=90) as response:
                        return response.status, json.load(response)
                except urllib.error.HTTPError as error:
                    return error.code, json.loads(error.read())

            out, names, token = prepare(1)
            payload = json.loads((out / 'request.json').read_text())
            assert request(names, 'invalid-token', payload)[0] == 403
            assert not private.exists(), 'Unauthorized request wrote to disk'
            code, result = request(names, token, payload)
            assert code == 200 and result.get('status') == 'activated', (result, (private / 'deployments/errors.log').read_text() if (private / 'deployments/errors.log').exists() else '')
            assert not (site / 'index.html').exists()
            assert (private / 'current').is_symlink()
            first = (private / 'current').resolve()
            first_env = (private / 'shared/.env').read_text()
            assert 'APP_KEY=' in first_env and 'APP_DEBUG="false"' in first_env
            assert 'NIKITA_OTP_API_KEY="inert-test-otp-key-1234567890"' in first_env
            assert 'MAIL_MAILER="smtp"' in first_env
            assert 'MAIL_HOST="host-test.fornex.org"' in first_env
            assert 'MAIL_PORT="465"' in first_env
            assert 'MAIL_SCHEME="smtps"' in first_env
            assert 'MAIL_USERNAME="no-reply@bosh.kg"' in first_env
            assert 'MAIL_FROM_ADDRESS="no-reply@bosh.kg"' in first_env
            assert 'MAIL_PASSWORD="test-password-not-real-123456"' in first_env

            assert (private / 'shared/.env').stat().st_mode & 0o777 == 0o600
            assert request(names, token, payload)[0] == 500, 'Repeated install was accepted'
            with urllib.request.urlopen(url + '/index.php/login') as response:
                page = response.read().decode()
                assert '/build/assets/' in page and 'Auth' in page
            for asset in ['brand/bosh-mark.svg', 'brand/bosh-social.png', 'brand/bosh-icon-192.png',
                          'brand/bosh-icon-512.png', 'favicon.svg', 'favicon.ico', 'favicon-16.png',
                          'favicon-32.png', 'apple-touch-icon.png', 'site.webmanifest']:
                with urllib.request.urlopen(url + '/' + asset) as response:
                    assert response.status == 200
                    assert response.read() == (first / 'public' / asset).read_bytes(), asset
            assert not (site / '.env').exists() and not (site / 'vendor').exists()
            assert list((private / 'backups').glob('*/database.sql'))

            # An upgrade preserves the application key and persistent storage, and can roll back to its predecessor.
            (private / 'shared/storage/app/private/retained.txt').write_text('persistent data')
            # Inert test values: never contact Meta or use real credentials during installation tests.
            first_env = first_env.replace('WHATSAPP_TOKEN=""', 'WHATSAPP_TOKEN="test-token-keep-private"')
            first_env = first_env.replace('WHATSAPP_SENDER_NUMBER="996550512233"', 'WHATSAPP_SENDER_NUMBER="996555000001"')
            first_env = first_env.replace('WHATSAPP_CLIENT_TEMPLATE="bosh_booking_client"', 'WHATSAPP_CLIENT_TEMPLATE="custom_booking_client"')
            (private / 'shared/.env').write_text(first_env)
            out2, names2, token2 = prepare(2)
            code, result = request(names2, token2, json.loads((out2 / 'request.json').read_text()))
            assert code == 200 and result.get('status') == 'activated', (result, (private / 'deployments/errors.log').read_text() if (private / 'deployments/errors.log').exists() else '')
            assert (private / 'current').resolve() != first
            assert (private / 'shared/.env').read_text() == first_env
            assert (private / 'shared/storage/app/private/retained.txt').read_text() == 'persistent data'
            assert request(names2, token2, {'action': 'rollback'})[1]['status'] == 'rolled_back'
            assert (private / 'current').resolve() == first

            # Corrupted ciphertext cannot change the active release.
            out3, names3, token3 = prepare(3)
            (site / names3['upload']).write_bytes(b'corrupt')
            code, result = request(names3, token3, json.loads((out3 / 'request.json').read_text()))
            assert code == 500 and result['error'] == 'upload_checksum_failed', result
            assert (private / 'current').resolve() == first

            # First-release rollback restores the original static site. Schema/data are deliberately not rolled back.
            assert request(names, token, {'action': 'rollback'})[1]['status'] == 'rolled_back'
            assert (site / 'index.html').read_text() == 'Original Master demo'
            assert (site / '.htaccess').read_text() == '# original PHP handler\n'
            assert not (site / 'index.php').exists()
            print('Installer checks passed: auth, encrypted archive, migration, upgrade, storage/key preservation, corruption rejection and rollback')
        finally:
            server.terminate()
            server.wait(timeout=10)


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('archive', type=Path)
    p.add_argument('--revision', required=True)
    p.add_argument('--php', default='php')
    p.add_argument('--db-host', default='127.0.0.1')
    p.add_argument('--db-name', default='master_installer_testing')
    p.add_argument('--db-user', default='root')
    run(p.parse_args())
