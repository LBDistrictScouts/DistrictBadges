#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    echo "Usage: $0 --env test|prod --image IMAGE[:TAG|@DIGEST]" >&2
}

environment=""
image=""
while (($#)); do
    case "$1" in
        --env)
            environment="${2:-}"
            shift 2
            ;;
        --image)
            image="${2:-}"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            usage
            exit 2
            ;;
    esac
done

if [[ "$environment" != "test" && "$environment" != "prod" ]] || [[ -z "$image" ]]; then
    usage
    exit 2
fi

for command_name in kubectl jq; do
    command -v "$command_name" >/dev/null || {
        echo "Required command not found: $command_name" >&2
        exit 1
    }
done

namespace="district-badges-$environment"
prefix="$environment-"
app_deployment="${prefix}district-badges"
badge_worker="${prefix}badge-import-worker"
order_worker="${prefix}order-worker"
job="${prefix}district-badges-migrate-$(date -u +%Y%m%d%H%M%S)"

echo "Running migrations from $image before updating any workloads."
kubectl create job "$job" -n "$namespace" --image="$image" \
    --dry-run=client -o json -- \
    php bin/cake.php migrations migrate \
    | jq \
        --arg service_account "${prefix}district-badges-runtime" \
        --arg config_map "${prefix}district-badges-config" \
        --arg secret "${prefix}district-badges-secrets" \
        '.spec.backoffLimit = 1
        | .spec.ttlSecondsAfterFinished = 300
        | .spec.template.spec.serviceAccountName = $service_account
        | .spec.template.spec.containers[0].envFrom = [
            {"configMapRef": {"name": $config_map}},
            {"secretRef": {"name": $secret}}
          ]' \
    | kubectl apply -f -

if ! kubectl -n "$namespace" wait --for=condition=complete "job/$job" --timeout=5m; then
    kubectl -n "$namespace" logs "job/$job" --all-containers=true || true
    echo "Migration failed; no workloads were updated." >&2
    exit 1
fi
kubectl -n "$namespace" logs "job/$job" --all-containers=true

migration_pod="$(kubectl -n "$namespace" get pods -l "job-name=$job" \
    -o jsonpath='{.items[0].metadata.name}')"
resolved_image="$(kubectl -n "$namespace" get pod "$migration_pod" \
    -o jsonpath='{.status.containerStatuses[0].imageID}')"
resolved_image="${resolved_image#docker-pullable://}"
if [[ "$resolved_image" != *@sha256:* ]]; then
    echo "Could not resolve an immutable image digest from $migration_pod: $resolved_image" >&2
    exit 1
fi

echo "Rolling workloads to $resolved_image."
kubectl -n "$namespace" set image "deployment/$app_deployment" \
    "app=$resolved_image" "copy-app-code=$resolved_image"
kubectl -n "$namespace" rollout status "deployment/$app_deployment" --timeout=5m

kubectl -n "$namespace" set image "deployment/$badge_worker" "worker=$resolved_image"
kubectl -n "$namespace" set image "deployment/$order_worker" "worker=$resolved_image"
kubectl -n "$namespace" rollout status "deployment/$badge_worker" --timeout=5m
kubectl -n "$namespace" rollout status "deployment/$order_worker" --timeout=5m

echo "Update complete: $resolved_image"
