# Linux Auditd Implementation

Auditd is used for server-level security logging. It is different from the Laravel `audit_logs` table.

- Laravel `audit_logs`: application activity such as login, failed access, CRUD, exports, and IoT inserts.
- Linux auditd: operating-system activity such as file changes, SSH/sudo changes, permission changes, and manual commands on the server.

## When To Use

Use auditd on the Linux server where the Laravel backend is deployed. It should not be run from the frontend, ESP32, or local Windows development machine.

## Files Added

- `deploy/auditd/carwash.rules`
- `deploy/auditd/install-auditd.sh`

## Install On Server

Assume the project is deployed to:

```bash
/var/www/carwash
```

Run:

```bash
cd /var/www/carwash
sudo bash deploy/auditd/install-auditd.sh /var/www/carwash
```

If the deployment path is different, pass that path as the first argument:

```bash
sudo bash deploy/auditd/install-auditd.sh /srv/carwash-iot
```

## Verify

Check auditd status:

```bash
sudo systemctl status auditd
```

Check loaded rules:

```bash
sudo auditctl -l | grep carwash
```

Trigger a test event:

```bash
sudo touch /var/www/carwash/backend/storage/logs/auditd-test.log
```

Search audit logs:

```bash
sudo ausearch -k carwash_laravel_logs
```

Generate a summary:

```bash
sudo aureport -f
sudo aureport -x
```

## Important Rule Keys

| Key | Purpose |
|---|---|
| `carwash_env` | Detect changes to Laravel `.env` |
| `carwash_laravel_logs` | Detect changes to Laravel log files |
| `carwash_bootstrap_cache` | Detect changes to cached config/routes/services |
| `carwash_public` | Detect changes to public web assets |
| `carwash_nginx_config` | Detect Nginx virtual host config changes |
| `carwash_permissions` | Detect chmod/chown changes under app directory |
| `carwash_exec` | Detect executed commands from inside app directory |
| `auditd_config` | Detect auditd configuration changes |
| `auditd_rules` | Detect auditd rule changes |
| `identity` | Detect user/group/password file changes |

## Correlation With Laravel Audit Log

Use both sources together:

1. Laravel audit log shows what the application says happened.
2. auditd shows what happened on the Linux host.

Example incident workflow:

```bash
sudo ausearch -k carwash_env -ts today
sudo ausearch -k carwash_exec -ts today
```

Then compare timestamps with the dashboard Audit Log page.

## Notes

- auditd logs are usually stored in `/var/log/audit/audit.log`.
- Reading auditd logs requires root or sudo access.
- Do not expose raw auditd logs directly to the web dashboard unless access is admin-only and logs are sanitized.
- For MVP, keep auditd as server hardening and operational evidence. The Laravel `audit_logs` table remains the primary dashboard-facing audit source.
