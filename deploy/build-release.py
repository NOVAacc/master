#!/usr/bin/env python3
"""Build a private release archive. Never collect .env, live storage or test data."""
import argparse
import hashlib
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[1]
DIRECTORIES = ('app', 'bootstrap', 'config', 'database/migrations', 'public', 'resources/views', 'routes', 'vendor')
FILES = ('artisan', 'composer.json', 'composer.lock', '.env.example')
EMPTY_DIRS = ('bootstrap/cache', 'storage/app/private', 'storage/app/public',
              'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs')


def allowed(relative: Path) -> bool:
    parts = relative.parts
    return (not any(p in ('.env', '.git', 'node_modules', 'tests', '.DS_Store', '.gitignore', 'hot') for p in parts)
            and not str(relative).startswith(('bootstrap/cache/', 'public/storage/')))


def build(destination: Path, revision: str) -> dict:
    if not (ROOT / 'vendor/autoload.php').is_file():
        raise RuntimeError('Run composer install --no-dev first')
    if not (ROOT / 'public/build/manifest.json').is_file():
        raise RuntimeError('Run npm ci && npm run build first')
    installed = json.loads((ROOT / 'vendor/composer/installed.json').read_text())
    if installed.get('dev', True):
        raise RuntimeError('Production release must exclude Composer dev dependencies')
    destination.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(destination, 'w', zipfile.ZIP_DEFLATED) as archive:
        for directory in DIRECTORIES:
            for source in sorted((ROOT / directory).rglob('*')):
                relative = source.relative_to(ROOT)
                if source.is_file() and not source.is_symlink() and allowed(relative):
                    archive.write(source, relative.as_posix())
        for name in FILES:
            archive.write(ROOT / name, name)
        for name in EMPTY_DIRS:
            info = zipfile.ZipInfo(name + '/')
            info.external_attr = 0o40750 << 16
            archive.writestr(info, b'')
        archive.writestr('release.json', json.dumps({'project': 'Master', 'revision': revision}))
    digest = hashlib.sha256(destination.read_bytes()).hexdigest()
    destination.with_suffix(destination.suffix + '.sha256').write_text(f'{digest}  {destination.name}\n')
    return {'archive': destination.name, 'sha256': digest, 'bytes': destination.stat().st_size}


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('destination', type=Path)
    parser.add_argument('--revision', required=True)
    args = parser.parse_args()
    print(json.dumps(build(args.destination.resolve(), args.revision), indent=2))
