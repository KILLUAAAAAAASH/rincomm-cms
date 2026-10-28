# Rincomm CMS â€” Module 9 Feature 2: RouterOS live acceptance checklist

**Status:** Laravel application implementation tested; **live Rincomm router not yet verified**.
**FRS requirement:** Module 9, Real-time connection status.
**Current protocol implementation:** RouterOS v7 HTTPS REST, one primary router.
**Never represent simulated API responses as evidence of live equipment.**

## Safe deployment principle

The CMS only reads the RouterOS `/rest/ppp/active` endpoint. It never provisions
PPPoE secrets, activates or terminates sessions, modifies billing, or changes router
configuration. A CMS subscriber account does not create a PPPoE login on a router.

## 1. Prepare separate MikroTik CHR (no Rincomm production access needed)

Install MikroTik **Cloud Hosted Router (CHR)** RouterOS v7 in VirtualBox or Hyper-V
using the official installer documentation and disk image. Use an isolated host-only
or otherwise restricted VM network. Do not bridge it to the production ISP LAN.

Official references:
- CHR downloads: https://mikrotik.com/download/chr
- CHR installation: https://manual.mikrotik.com/docs/getting-started/installation-and-upgrade/install/chr-installation/
- RouterOS REST: https://manual.mikrotik.com/docs/developer-guides/rest-api/

On the **test CHR only**, have an authorized operator configure HTTPS `www-ssl` with a
valid certificate, permit REST/API/read permissions for a dedicated least-privilege
account, and restrict who can connect. Do not expose its REST service publicly.
For ONLINE acceptance, a genuine PPPoE client must authenticate to the test CHR's
PPPoE server; a username stored only in Laravel does **not** produce a live session.

## 2. Configure Laravel environment for an approved router

Set the following in local `.env` (NEVER commit `.env`, and never paste values into chats):

```
MIKROTIK_ENABLED=true
MIKROTIK_BASE_URL=https://<router-address-that-matches-certificate>
MIKROTIK_USERNAME=<dedicated-read-only-router-user>
MIKROTIK_PASSWORD=<private-credential>
MIKROTIK_CA_BUNDLE=<absolute-path-to-trusted-ca-pem-if-needed>
```

Do not disable TLS certificate validation to make a test pass. The application
process must be able to reach that address on the authorized private network/VPN.
If configuration is cached, coordinate a safe config refresh with the operator;
do not clear caches on a shared production instance without approval.

## 3. Perform read-only live diagnostic

With the CHR running and `.env` set in the Laravel project:

```
php artisan mikrotik:probe --live
```

Enter a real test CHR PPPoE username when prompted; input is hidden. Never paste
RouterOS credentials, real subscriber details, or the output of `.env` into chat.

- **ONLINE:** the configured router authenticated the request and returned an
  active PPPoE session for that username.
- **OFFLINE:** the configured router answered, but the username has no matching
  active PPPoE session.
- **UNAVAILABLE:** monitoring disabled, wrong/missing configuration, TLS/network
  failure, permission error, or malformed response. Never reinterpret this as OFFLINE.

If a production router is later approved for read-only integration, use the same
Laravel client after confirming RouterOS version, certificate, network route,
permission policy, and which router hosts the subscriber's session. A v6 router
requires a different implementation (API-SSL); the existing v7 REST client must
not be claimed to support v6. Multiple PPPoE servers require a supported design
and validated mapping instead of assuming all subscribers live on `primary`.

## 4. End-to-end acceptance criteria

- Customer owns a CMS-approved subscriber record and the correct PPPoE username
  is mapped under Admin -> Subscriber -> Network Account.
- With a genuine active PPPoE session: customer dashboard changes to ONLINE.
- Disconnect only the **isolated test client**: the dashboard changes to OFFLINE
  after at most one refresh cycle plus the 15-second cache expiry.
- Disable/deny test CHR reachability: dashboard changes to UNAVAILABLE, not OFFLINE.
- Confirm another subscriber cannot request or view somebody else's session.
- Confirm the subscription Active/Pending state does not change during router checks.
- Re-run focused/full Laravel tests; record actual test time, test CHR RouterOS
  version and screenshot results, without leaking subscriber/router secrets.

## Deployment blocker

**Real Rincomm router remains not verified** until the authorized Rincomm operator
provides connection details and confirms the live server topology. It cannot
be validated by tests against CHR or `Http::fake()` alone.
