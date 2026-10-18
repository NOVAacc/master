#!/usr/bin/env python3
"""Verify release pruning preserves the active release, rollback and shared data."""
import hashlib
import json
from pathlib import Path
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

def main():
    template = Path(__file__).with_name('prune-releases.php.template').read_text()
    with tempfile.TemporaryDirectory(prefix='bosh-prune-test-') as tmp:
        root = Path(tmp)
        base = root / 'private'
        releases = base / 'releases'
        releases.mkdir(parents=True)
        (base / '.master-managed').write_text('test-site')
        (base / 'deployments').mkdir()
        (base / 'shared').mkdir()
        shared = base / 'shared' / 'retained.txt'
        shared.write_text('keep shared data')
        names = [str(i) * 40 + '-123-1' for i in [1, 2, 3]]
        for name in names:
            (releases / name).mkdir()
            (releases / name / 'artisan').write_text('generated code')
            (releases / name / 'storage').symlink_to(base / 'shared', target_is_directory=True)
        (base / 'current').symlink_to(releases / names[0], target_is_directory=True)
        state = base / 'deployments' / (names[0] + '.json')
        state.write_text(json.dumps({'status': 'activated', 'previous': str(releases / names[1])}))
        # Unknown directories and symlinks must not be followed or removed.
        (releases / 'unrelated').mkdir()
        (releases / ('4' * 40 + '-123-1')).symlink_to(base / 'shared', target_is_directory=True)
        token = 'inert-test-token'
        site = root / 'site'
        site.mkdir()
        script = template.replace('__BASE__', str(base)).replace('__TOKEN_HASH__', hashlib.sha256(token.encode()).hexdigest()).replace('__EXPIRES__', str(int(time.time()) + 120))
        (site / 'prune.php').write_text(script)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        proc = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(site)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        def request(action, auth=token):
            req = urllib.request.Request(f'http://127.0.0.1:{port}/prune.php', data=json.dumps({'action': action}).encode(), headers={'X-Bosh-Cleanup': auth, 'Content-Type': 'application/json'})
            try:
                with urllib.request.urlopen(req, timeout=5) as response:
                    return response.status, json.load(response)
            except urllib.error.HTTPError as error:
                return error.code, json.loads(error.read())
        try:
            for _ in range(30):
                try:
                    code, result = request('inspect', 'wrong')
                    break
                except urllib.error.URLError:
                    time.sleep(.1)
            else:
                raise AssertionError('PHP test server failed to start')
            assert code == 403
            code, result = request('inspect')
            assert code == 200 and result['kept'] == names[:2], result
            assert [x['name'] for x in result['candidates']] == [names[2]], result
            assert (releases / names[2]).exists()
            code, result = request('prune')
            assert code == 200 and result['removed'] == [names[2]], result
            assert not (releases / names[2]).exists()
            assert all((releases / name).is_dir() for name in names[:2])
            assert shared.read_text() == 'keep shared data'
            assert (releases / 'unrelated').is_dir()
            assert (releases / ('4' * 40 + '-123-1')).is_symlink()
            assert request('prune')[1]['removed_count'] == 0
            (releases / names[2]).mkdir()
            state.unlink()
            assert request('prune')[0] == 500
            assert (releases / names[2]).exists()
            print('Release cleanup passed: auth, dry-run, active/rollback protection, shared symlink safety, repeatability and fail-closed metadata')
        finally:
            proc.terminate()
            proc.wait(timeout=10)

if __name__ == '__main__':
    main()
