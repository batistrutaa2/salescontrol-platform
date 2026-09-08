#!/usr/bin/env bash
set -Eeuo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PREVIOUS_RELEASE="$DEPLOY_DIR/.previous-release"

if [[ ! -s "$PREVIOUS_RELEASE" ]]; then
  echo "Nenhuma release anterior registrada para rollback." >&2
  exit 1
fi

read -r image tag < "$PREVIOUS_RELEASE"
exec "$DEPLOY_DIR/deploy/deploy.sh" "$image" "$tag"
