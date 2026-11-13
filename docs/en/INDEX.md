# SilverStripe Error Tracking on Sentry

## Getting Started

1. Create a new Sentry project

2. Set the DSN to the `SS_SENTRY_DSN` variable in the `.env` file

   ```env
   SS_SENTRY_DSN="https://your-key@your-host.ingest.sentry.io/your-project-id"
   ```

## Customisation

- `SS_ENVIRONMENT_TYPE` sets the environment type (also used by SilverStripe)
- `SS_SENTRY_ENVIRONMENT` overrides `SS_ENVIRONMENT_TYPE`, can be used to provide more granular environment type e.g. `production` or `staging`
- `SS_SENTRY_RELEASE` sets the release tag
- `SS_SENTRY_EXCLUDE_SENSITIVE_DATA` prevents logging the request body, the full SQL string and the command-line string if it is set to `true`

## Examples

```env
SS_SENTRY_DSN="https://your-key@your-host.ingest.sentry.io/your-project-id"
SS_SENTRY_ENVIRONMENT="production"
SS_SENTRY_RELEASE="1.0.0"
SS_ENVIRONMENT_TYPE="live"
SS_SENTRY_EXCLUDE_SENSITIVE_DATA="false"
```
