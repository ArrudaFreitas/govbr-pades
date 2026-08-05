# Security Policy

## Supported versions

This library is in early development and has no released version yet. Nothing here is
supported for production use, and no security fixes are backported.

## Reporting a vulnerability

Please do not open a public issue for a security problem.

Report it privately through
[GitHub Security Advisories](https://github.com/ArrudaFreitas/govbr-pades/security/advisories/new),
or by email to <contato@ianarruda.dev>.

This is a single-maintainer project, so no response time is promised. You will get an
acknowledgement as soon as the report is read.

## Handling credentials

The library talks to the gov.br signature API using an OAuth `client_id` and
`client_secret` issued to your organisation. The `client_secret` authorises signing on
behalf of citizens, so treat a leak as an incident.

### The secret is wrapped, not hidden

`ClientSecret` keeps the value out of stack traces, `var_dump()` and `print_r()`. Two
things it deliberately does not do:

- **`var_export()` prints the real value.** That function must emit re-evaluable PHP
  code, so masking it is impossible — there is no hook. Never `var_export()` a
  `ClientSecret`, a `Credentials`, or any structure containing them.
- **`reveal()` returns the raw string.** It is named that way so every point of exposure
  is greppable. Whatever you do with the return value is outside the library's control.

### Recommendations

- Keep credentials out of version control. Load them from the environment or from a file
  excluded by `.gitignore`.
- Use separate credentials per environment. Staging credentials must never reach
  production.
- Scrub `client_secret`, `code` and `access_token` from application logs. An access token
  authorises signing for as long as it is valid.
- Do not disable TLS verification when talking to `*.iti.br`. The library provides no
  switch for it, and adding one would be a security regression.

## Scope

Vulnerabilities in this library are in scope. Problems in the gov.br signature API itself
belong to ITI — see the
[official integration manual](https://manual-integracao-assinatura-eletronica.servicos.gov.br/).
