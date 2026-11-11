#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if ! command -v trivy >/dev/null 2>&1; then
    printf 'Error: Trivy is required to generate image SBOMs. Install it from https://trivy.dev/\n' >&2
    exit 127
fi

SBOM_DIR="$ROOT_DIR/sbom"
mkdir -p "$SBOM_DIR"

trivy fs \
    --scanners vuln \
    --include-dev-deps \
    --skip-dirs .git,node_modules,vendor,var \
    --format cyclonedx \
    --output "$SBOM_DIR/project.cdx.json" \
    .
printf 'Project CycloneDX SBOM written to %s\n' "$SBOM_DIR/project.cdx.json"

for image in athorrent-php:local athorrent-nginx:local; do
    sbom_file="$SBOM_DIR/${image//:/-}.cdx.json"
    trivy image --format cyclonedx --output "$sbom_file" "$image"
    printf 'CycloneDX SBOM written to %s\n' "$sbom_file"
done
