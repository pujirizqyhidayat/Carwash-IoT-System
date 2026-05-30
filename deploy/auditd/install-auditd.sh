#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/carwash}"
RULES_SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/carwash.rules"
RULES_TARGET="/etc/audit/rules.d/carwash.rules"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run this script with sudo/root."
  exit 1
fi

if ! command -v auditctl >/dev/null 2>&1; then
  if command -v apt-get >/dev/null 2>&1; then
    apt-get update
    apt-get install -y auditd audispd-plugins
  elif command -v dnf >/dev/null 2>&1; then
    dnf install -y audit audit-libs
  elif command -v yum >/dev/null 2>&1; then
    yum install -y audit audit-libs
  else
    echo "Unsupported package manager. Install auditd manually first."
    exit 1
  fi
fi

tmp_file="$(mktemp)"
sed "s#/var/www/carwash#${APP_DIR}#g" "${RULES_SOURCE}" > "${tmp_file}"
install -o root -g root -m 0640 "${tmp_file}" "${RULES_TARGET}"
rm -f "${tmp_file}"

systemctl enable auditd

if command -v augenrules >/dev/null 2>&1; then
  augenrules --load
else
  auditctl -R "${RULES_TARGET}"
fi

systemctl restart auditd || service auditd restart

echo "auditd rules installed at ${RULES_TARGET}"
echo "Check active rules with: sudo auditctl -l | grep carwash"
