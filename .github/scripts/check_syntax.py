#!/usr/bin/env python3
import sys
import json
import csv
from pathlib import Path


def check_json(path: Path) -> bool:
    try:
        with path.open(encoding="utf-8") as f:
            json.load(f)
    except Exception as e:
        print(f"ERROR: Invalid JSON in {path}: {e}", file=sys.stderr)
        return False
    print(f"OK: {path} is valid JSON")
    return True


def check_csv(path: Path, delimiter=';') -> bool:
    try:
        with path.open(encoding='utf-8', newline='') as f:
            reader = csv.reader(f, delimiter=delimiter)
            rows = list(reader)
            if not rows:
                print(f"ERROR: {path} is empty", file=sys.stderr)
                return False
    except Exception as e:
        print(f"ERROR: Failed to parse CSV {path}: {e}", file=sys.stderr)
        return False
    print(f"OK: {path} parsed as CSV with delimiter '{delimiter}' ({len(rows)} rows)")
    return True


def main() -> int:
    root = Path('logo')

    ok = True

    # find all trafic.json files under logo/ recursively
    json_files = list(root.rglob('trafic.json'))
    if not json_files:
        print(f"ERROR: No trafic.json files found under {root}", file=sys.stderr)
        ok = False
    else:
        for p in json_files:
            ok = check_json(p) and ok

    # find all lines_picto.csv files under logo/ recursively
    csv_files = list(root.rglob('lines_picto.csv'))
    if not csv_files:
        print(f"ERROR: No lines_picto.csv files found under {root}", file=sys.stderr)
        ok = False
    else:
        for p in csv_files:
            ok = check_csv(p) and ok

    return 0 if ok else 2


if __name__ == '__main__':
    sys.exit(main())
