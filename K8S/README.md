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
- `EMAIL_TRANSPORT_DEFAULT_URL`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `SQS_ORDER_QUEUE_URL`
- `SQS_BADGE_IMPORT_QUEUE_URL`
- `ALGOLIA_APP_ID`
- `ALGOLIA_ADMIN_API_KEY`

Non-secret environment variables live in `base/config/app-config-map.yaml` and are patched per environment.

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

Run migrations intentionally after deploying a new image:

```bash
kubectl apply -k K8S/operations
kubectl apply -k K8S/overlays/test/operations
kubectl apply -k K8S/overlays/prod/operations
```

## TLS

nginx serves HTTP and HTTPS directly behind a `LoadBalancer` service. cert-manager writes the TLS certificate to the `district-badges-tls` Secret used by nginx.

On a new cluster, nginx will not start its HTTPS listener until that Secret exists. Bootstrap it with a temporary TLS Secret or apply the Certificate first and wait for cert-manager to issue it.
