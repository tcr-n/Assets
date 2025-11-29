#!/usr/bin/env python3
import csv
import os
import sys
import argparse


def gather_missing_logo_paths(root_dir, strip_prefix='https://hexatransit.fr/assets/'):
    missing = []
    files_read = []
    for dirpath, dirnames, filenames in os.walk(root_dir):
        for fn in filenames:
            if fn == 'lines_picto.csv':
                full = os.path.join(dirpath, fn)
                files_read.append(full)
                try:
                    with open(full, encoding='utf-8') as f:
                        r = csv.DictReader(f, delimiter=';')
                        if r.fieldnames:
                            r.fieldnames = [x.lstrip('\ufeff') for x in r.fieldnames]
                        for row in r:
                            logo = (row.get('logoPath') or '').strip()
                            if not logo:
                                continue
                            path = logo
                            if path.startswith(strip_prefix):
                                path = path.replace(strip_prefix, '', 1)
                            # normalize path (allow both forward and back slashes)
                            path = os.path.normpath(path.lstrip('/\\'))
                            if not os.path.exists(path):
                                missing.append((logo, full, path))
                except Exception as e:
                    print(f'Failed to read {full}: {e}')
    return missing, files_read


def main():
    parser = argparse.ArgumentParser(description='Check logoPath entries in all lines_picto.csv files under a logo directory.')
    parser.add_argument('--logo-dir', default='logo', help='Directory to search recursively for lines_picto.csv (default: logo)')
    parser.add_argument('--strip-prefix', default='https://hexatransit.fr/assets/', help='URL prefix to strip from logoPath before checking existence')
    args = parser.parse_args()

    if not os.path.isdir(args.logo_dir):
        print(f'Logo directory "{args.logo_dir}" not found or is not a directory')
        sys.exit(1)

    missing, files_read = gather_missing_logo_paths(args.logo_dir, strip_prefix=args.strip_prefix)

    print(f'Checked {len(files_read)} file(s) under "{args.logo_dir}"')
    for p in files_read:
        print(' -', p)

    print('\nMissing logo files ({}):'.format(len(missing)))
    for logo, srcfile, path in missing:
        print(' -', logo)
        print('   file:', srcfile)
        print('   expected local path:', path)

    if missing:
        sys.exit(1)


if __name__ == '__main__':
    main()
