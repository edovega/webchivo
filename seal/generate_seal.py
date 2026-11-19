#!/usr/bin/env python3
"""Generate seal.c, a tiny program that prints one lab flag on request.

Runs inside a Docker build stage. It reads the build secret mounted at
/run/secrets/ctf_flags (the maintainer's .env), takes the lines FLAG_01 to
FLAG_08, XORs each value with a per-build random key and writes C source that
contains only ciphertext and two key shares. No flag is ever printed.

Usage: generate_seal.py <output.c>
"""

import pathlib
import re
import secrets
import sys

SECRET_PATH = pathlib.Path("/run/secrets/ctf_flags")
FLAG_COUNT = 8
KEY_LEN = 64
LINE_RE = re.compile(r"^FLAG_0([1-8])=(.*)$")
VALUE_RE = re.compile(r"^[A-Za-z0-9_{}-]{8,64}$")


def fail(message: str) -> None:
    print(f"generate_seal: ERROR: {message}", file=sys.stderr)
    sys.exit(1)


def test_flags() -> list[str]:
    return ["FLAG_%08x" % n for n in range(1, FLAG_COUNT + 1)]


def read_flags() -> list[str]:
    if not SECRET_PATH.is_file():
        print("generate_seal: WARNING: no build secret, using test flags", file=sys.stderr)
        return test_flags()

    found: dict[int, str] = {}
    for raw in SECRET_PATH.read_bytes().decode("utf-8", errors="strict").split("\n"):
        line = raw[:-1] if raw.endswith("\r") else raw
        match = LINE_RE.match(line)
        if not match:
            continue
        number = int(match.group(1))
        if number in found:
            fail(f"FLAG_0{number} is defined more than once")
        found[number] = match.group(2)

    if not found:
        print("generate_seal: WARNING: no FLAG_0N lines, using test flags", file=sys.stderr)
        return test_flags()

    missing = [n for n in range(1, FLAG_COUNT + 1) if n not in found]
    if missing:
        fail("missing " + ", ".join(f"FLAG_0{n}" for n in missing))

    for number, value in sorted(found.items()):
        if not VALUE_RE.match(value):
            fail(f"FLAG_0{number} is invalid: use 8 to 64 characters from A-Z a-z 0-9 _ - {{ }}")

    return [found[n] for n in range(1, FLAG_COUNT + 1)]


def c_array(data: bytes) -> str:
    return ", ".join(f"0x{b:02x}" for b in data)


def main() -> None:
    if len(sys.argv) != 2:
        fail("usage: generate_seal.py <output.c>")
    out_path = pathlib.Path(sys.argv[1])

    flags = read_flags()
    key = secrets.token_bytes(KEY_LEN)
    share_a = secrets.token_bytes(KEY_LEN)
    share_b = bytes(k ^ a for k, a in zip(key, share_a))

    rows = []
    lengths = []
    for value in flags:
        plain = value.encode("ascii")
        padded = plain + secrets.token_bytes(KEY_LEN - len(plain))
        cipher = bytes(p ^ k for p, k in zip(padded, key))
        rows.append("    {" + c_array(cipher) + "}")
        lengths.append(str(len(plain)))

    source = f"""/* Generated at build time. Contains no plaintext. */
#include <string.h>
#include <unistd.h>

static const volatile unsigned char share_a[{KEY_LEN}] = {{{c_array(share_a)}}};

static const unsigned char sealed[{FLAG_COUNT}][{KEY_LEN}] = {{
{(","+chr(10)).join(rows)}
}};

static const unsigned char sizes[{FLAG_COUNT}] = {{{", ".join(lengths)}}};

static const volatile unsigned char share_b[{KEY_LEN}] = {{{c_array(share_b)}}};

int main(int argc, char **argv)
{{
    unsigned char out[{KEY_LEN} + 1];
    size_t i, len;
    int n;

    if (argc != 2 || argv[1][0] < '1' || argv[1][0] > '8' || argv[1][1] != '\\0')
        return 1;
    n = argv[1][0] - '1';
    len = sizes[n];
    for (i = 0; i < len; i++)
        out[i] = (unsigned char)(sealed[n][i] ^ share_a[i] ^ share_b[i]);
    out[len] = '\\n';
    if (write(1, out, len + 1) != (ssize_t)(len + 1))
        return 1;
    memset(out, 0, sizeof out);
    return 0;
}}
"""
    out_path.write_text(source, encoding="ascii")


if __name__ == "__main__":
    main()
