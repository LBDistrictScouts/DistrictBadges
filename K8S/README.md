# District Badges Kustomize Deployment

Kubernetes manifests for the District Badges CakePHP backend.

The layout follows the other PHP deployments:

- `base/` contains the common runtime resources.
- `overlays/test/` and `overlays/prod/` set environment-specific hostnames, replica counts and 1Password item paths.
- `operations/` contains one-off operational jobs such as database migrations.

## Secrets

Secrets are managed by the 1Password Operator. The `OnePasswordItem` named `district-badges-secrets` creates the Kubernetes Secret consumed by the app and worker deployments.

The 1Password item should expose these keys:

- `SECURITY_SALT`
- `DATABASE_URL`
- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `EMAIL_TRANSPORT_DEFAULT_URL`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `SQS_ORDER_QUEUE_URL`
- `SQS_BADGE_IMPORT_QUEUE_URL`
- `ALGOLIA_APP_ID`
- `ALGOLIA_ADMIN_API_KEY`

Use the matching `badges_queue_consumer_credentials` Terraform output from
`DistrictInfrastructure` for each environment. Map
`email_transport_default_url` to the secret
`EMAIL_TRANSPORT_DEFAULT_URL`. The non-secret `ses_from_address` and
`ses_from_name` values are configured as `EMAIL_FROM_ADDRESS` and
`EMAIL_FROM_NAME` in the environment overlay ConfigMaps.

The separate `ghcr-pull-secret` item is shared by every environment and points to `op://Infrastructure/ArgoCD - LBD Repo Creds/dockerconfig.json`. It is exposed as a `kubernetes.io/dockerconfigjson` Secret and attached to the runtime ServiceAccount.

Create or update the matching 1Password items referenced by the manifests:

```bash
# interactive prompt will ask for environment: base, test, prod, or all
bash K8S/scripts/generate-1password-items.sh
```

Useful options:

```bash
# target only one environment
bash K8S/scripts/generate-1password-items.sh --env test
bash K8S/scripts/generate-1password-items.sh --env prod

# process all items without prompts
bash K8S/scripts/generate-1password-items.sh --env all --force

# preview actions only
bash K8S/scripts/generate-1password-items.sh --env test --dry-run
```

Non-secret environment variables live in `base/config/app-config-map.yaml` and are patched per environment.

Order notification email is enabled with `ORDER_NOTIFICATIONS_ENABLED`. Set it
to `"false"` in an overlay to suppress delivery without removing the SES
credentials.

The Algolia badge index is environment-specific: `BADGES` for production,
`BADGES-TEST` for test, and `BADGES-DEV` for development. Keep the webstore's
`VITE_ALGOLIA_BADGES_INDEX` value aligned with the backend's
`ALGOLIA_INDEX_BADGES` value.

`DATABASE_URL` should point at the environment-specific PostgreSQL service:

- test: `postgres://<user>:<password>@test-district-badges-postgres:5432/<database>?encoding=utf8&timezone=UTC&cacheMetadata=true`
- prod: `postgres://<user>:<password>@prod-district-badges-postgres:5432/<database>?encoding=utf8&timezone=UTC&cacheMetadata=true`

## Database

PostgreSQL runs as a StatefulSet with a `local-db-ssd-rwo` volume claim. The pod has required node affinity for `storage.homelab/db-ssd=true`, matching the Homelab SSD-backed database node convention.

## Deploy

Deploy the base manifest:

```bash
kubectl apply -k K8S
```

Deploy an environment overlay:

```bash
kubectl apply -k K8S/overlays/test
kubectl apply -k K8S/overlays/prod
```

The environment overlays expose the backend at:

- test: `https://badge-system-test.lbdscouts.org.uk`
- production: `https://badge-system.lbdscouts.org.uk`

Their CORS configuration permits the matching webstores at
`https://badges-test.lbdscouts.org.uk` and `https://badges.lbdscouts.org.uk`.

For the initial installation, apply the overlay and wait for the app rollout:

```bash
kubectl apply -k K8S/overlays/test
kubectl -n district-badges-test rollout status deployment/test-district-badges

kubectl apply -k K8S/overlays/prod
kubectl -n district-badges-prod rollout status deployment/prod-district-badges
```

## Regular updates

The app uses Kubernetes' native rolling Deployment strategy with
`maxUnavailable: 0` and `maxSurge: 1`. Run the update script with the newly
published image tag or digest:

```bash
bash K8S/scripts/rolling-update.sh \
  --env test \
  --image ghcr.io/lbdistrictscouts/districtbadges-backend:dev

bash K8S/scripts/rolling-update.sh \
  --env prod \
  --image ghcr.io/lbdistrictscouts/districtbadges-backend:sha-0123456
```

The script performs the update in this order:

1. Start a migration Job with the requested image, causing Kubernetes to pull it.
2. Run the CakePHP schema migrations and DistrictCoreData sync, stopping immediately if either fails.
3. Resolve the successfully migrated image to an immutable digest.
4. Roll the app Deployment to that digest without taking an existing replica down.
5. Roll both queue workers to the same digest.

If migration or CoreData sync fails, no workloads are changed. `kubectl` and `jq` are required. Run
only one update per environment at a time. Database changes must remain compatible
with the previous application version while the rolling update is in progress.

The operation manifests remain available for exceptional manual migration runs:

```bash
kubectl apply -k K8S/overlays/test/operations
kubectl apply -k K8S/overlays/prod/operations
```

## TLS

nginx serves HTTP and HTTPS directly behind a `LoadBalancer` service. cert-manager writes the TLS certificate to the `district-badges-tls` Secret used by nginx.

On a new cluster, nginx will not start its HTTPS listener until that Secret exists. Bootstrap it with a temporary TLS Secret or apply the Certificate first and wait for cert-manager to issue it.
