#!/usr/bin/env bash
#
# Run all tests (e2e + unit) across multiple PHP versions using wp-env.
#
# Usage:
#   ./tests/run-all-php-versions.sh                 # All supported versions
#   ./tests/run-all-php-versions.sh 7.4 8.2         # Specific versions only
#   ./tests/run-all-php-versions.sh --e2e-only      # Only Playwright e2e tests
#   ./tests/run-all-php-versions.sh --unit-only     # Only PHPUnit tests
#
# Prerequisites:
#   - Docker running
#   - npm install (for Playwright)
#   - composer install (for PHPUnit, inside container)
#
# The script:
#   1. Stops wp-env
#   2. Patches .wp-env.json with the target PHP version
#   3. Starts wp-env
#   4. Runs PHPUnit unit tests inside the container
#   5. Runs PHPUnit security tests inside the container
#   6. Runs Playwright e2e tests from the host
#   7. Collects results and prints a summary matrix
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WP_ENV_JSON="$PROJECT_DIR/.wp-env.json"
WP_ENV_JSON_BAK="$PROJECT_DIR/.wp-env.json.bak"

# Default PHP versions to test (covers the full supported range)
DEFAULT_VERSIONS=(
    "5.6"
    "7.0"
    "7.4"
    "8.0"
    "8.1"
    "8.2"
    "8.3"
)

# Parse arguments
RUN_E2E=true
RUN_UNIT=true
VERSIONS=()

for arg in "$@"; do
    case "$arg" in
        --e2e-only)
            RUN_UNIT=false
            ;;
        --unit-only)
            RUN_E2E=false
            ;;
        --help|-h)
            echo "Usage: $0 [--e2e-only|--unit-only] [php_version ...]"
            echo ""
            echo "Examples:"
            echo "  $0                    # Test all versions"
            echo "  $0 7.4 8.2           # Test specific versions"
            echo "  $0 --unit-only 5.6   # Only PHPUnit on PHP 5.6"
            echo "  $0 --e2e-only 8.2    # Only Playwright on PHP 8.2"
            exit 0
            ;;
        *)
            VERSIONS+=("$arg")
            ;;
    esac
done

if [ ${#VERSIONS[@]} -eq 0 ]; then
    VERSIONS=("${DEFAULT_VERSIONS[@]}")
fi

# Results tracking
declare -A UNIT_RESULTS
declare -A SECURITY_RESULTS
declare -A E2E_RESULTS

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[test-runner]${NC} $*"; }
success() { echo -e "${GREEN}[PASS]${NC} $*"; }
fail() { echo -e "${RED}[FAIL]${NC} $*"; }
warn() { echo -e "${YELLOW}[SKIP]${NC} $*"; }

# Back up original wp-env config
cp "$WP_ENV_JSON" "$WP_ENV_JSON_BAK"

cleanup() {
    log "Restoring original .wp-env.json"
    cp "$WP_ENV_JSON_BAK" "$WP_ENV_JSON"
    rm -f "$WP_ENV_JSON_BAK"
}
trap cleanup EXIT

# Helper: patch PHP version in .wp-env.json
set_php_version() {
    local version="$1"
    # Use python3 or node to patch JSON (available on most systems)
    if command -v node &>/dev/null; then
        node -e "
            const fs = require('fs');
            const cfg = JSON.parse(fs.readFileSync('$WP_ENV_JSON', 'utf8'));
            cfg.phpVersion = '$version';
            fs.writeFileSync('$WP_ENV_JSON', JSON.stringify(cfg, null, 2) + '\n');
        "
    elif command -v python3 &>/dev/null; then
        python3 -c "
import json
with open('$WP_ENV_JSON') as f:
    cfg = json.load(f)
cfg['phpVersion'] = '$version'
with open('$WP_ENV_JSON', 'w') as f:
    json.dump(cfg, f, indent=2)
    f.write('\n')
"
    else
        # Fallback: sed
        sed -i.tmp "s/\"phpVersion\": \"[^\"]*\"/\"phpVersion\": \"$version\"/" "$WP_ENV_JSON"
        rm -f "$WP_ENV_JSON.tmp"
    fi
}

# Helper: run PHPUnit tests inside wp-env container
run_unit_tests() {
    local version="$1"
    log "Running PHPUnit unit tests on PHP $version..."

    # Install composer deps inside container
    npx wp-env run cli -- bash -c \
        "cd wp-content/plugins/disable-comments && composer install --no-interaction 2>/dev/null" \
        2>/dev/null || true

    # Run unit tests
    if npx wp-env run cli -- bash -c \
        "cd wp-content/plugins/disable-comments && vendor/bin/phpunit tests/test-plugin.php 2>&1"; then
        UNIT_RESULTS[$version]="PASS"
        success "Unit tests passed on PHP $version"
    else
        UNIT_RESULTS[$version]="FAIL"
        fail "Unit tests failed on PHP $version"
    fi
}

# Helper: run security PHPUnit tests
run_security_tests() {
    local version="$1"
    log "Running PHPUnit security tests on PHP $version..."

    if npx wp-env run tests-cli --env-cwd=wp-content/plugins/disable-comments -- bash -c \
        "WP_TESTS_DIR=/wordpress-phpunit vendor/bin/phpunit --configuration phpunit-security.xml 2>&1"; then
        SECURITY_RESULTS[$version]="PASS"
        success "Security tests passed on PHP $version"
    else
        SECURITY_RESULTS[$version]="FAIL"
        fail "Security tests failed on PHP $version"
    fi
}

# Helper: run Playwright e2e tests
run_e2e_tests() {
    local version="$1"
    log "Running Playwright e2e tests against PHP $version..."

    # Ensure plugin is network-activated and users exist
    npx wp-env run cli -- wp plugin activate disable-comments --network 2>/dev/null || true
    npx wp-env run cli -- wp user update admin --user_pass=password 2>/dev/null || true
    npx wp-env run cli -- wp user create editor editor@test.local --role=editor --user_pass=password 2>/dev/null || true
    npx wp-env run cli -- wp user create subscriber subscriber@test.local --role=subscriber --user_pass=password 2>/dev/null || true
    npx wp-env run cli -- wp user create author author@test.local --role=author --user_pass=password 2>/dev/null || true

    if npx playwright test tests/e2e/features.spec.ts --reporter=list 2>&1; then
        E2E_RESULTS[$version]="PASS"
        success "E2E tests passed on PHP $version"
    else
        E2E_RESULTS[$version]="FAIL"
        fail "E2E tests failed on PHP $version"
    fi
}

# ============================================================================
# Main loop
# ============================================================================
log "Testing PHP versions: ${VERSIONS[*]}"
log "Unit tests: $RUN_UNIT | E2E tests: $RUN_E2E"
echo ""

TOTAL_VERSIONS=${#VERSIONS[@]}
CURRENT=0

for version in "${VERSIONS[@]}"; do
    CURRENT=$((CURRENT + 1))
    echo ""
    echo "================================================================"
    log "[$CURRENT/$TOTAL_VERSIONS] PHP $version"
    echo "================================================================"

    # Set PHP version
    set_php_version "$version"

    # Stop and restart wp-env
    log "Restarting wp-env with PHP $version..."
    npx wp-env stop 2>/dev/null || true
    if ! npx wp-env start 2>&1; then
        warn "wp-env failed to start with PHP $version — skipping"
        UNIT_RESULTS[$version]="SKIP"
        SECURITY_RESULTS[$version]="SKIP"
        E2E_RESULTS[$version]="SKIP"
        continue
    fi

    # Verify PHP version
    actual_version=$(npx wp-env run cli -- php -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")
    log "Container PHP version: $actual_version"

    # Activate WooCommerce if available
    npx wp-env run cli -- wp plugin activate woocommerce --network 2>/dev/null || true

    # Run tests
    if $RUN_UNIT; then
        run_unit_tests "$version"
        run_security_tests "$version"
    else
        UNIT_RESULTS[$version]="SKIP"
        SECURITY_RESULTS[$version]="SKIP"
    fi

    if $RUN_E2E; then
        run_e2e_tests "$version"
    else
        E2E_RESULTS[$version]="SKIP"
    fi
done

# ============================================================================
# Summary
# ============================================================================
echo ""
echo "================================================================"
echo "                    TEST RESULTS SUMMARY"
echo "================================================================"
printf "%-12s | %-10s | %-12s | %-10s\n" "PHP Version" "Unit" "Security" "E2E"
printf "%-12s-+-%-10s-+-%-12s-+-%-10s\n" "------------" "----------" "------------" "----------"

FAILURES=0
for version in "${VERSIONS[@]}"; do
    unit="${UNIT_RESULTS[$version]:-SKIP}"
    security="${SECURITY_RESULTS[$version]:-SKIP}"
    e2e="${E2E_RESULTS[$version]:-SKIP}"

    # Color the results
    unit_display="$unit"
    security_display="$security"
    e2e_display="$e2e"

    [ "$unit" = "FAIL" ] && FAILURES=$((FAILURES + 1))
    [ "$security" = "FAIL" ] && FAILURES=$((FAILURES + 1))
    [ "$e2e" = "FAIL" ] && FAILURES=$((FAILURES + 1))

    printf "%-12s | %-10s | %-12s | %-10s\n" "$version" "$unit_display" "$security_display" "$e2e_display"
done

echo "================================================================"
echo ""

if [ $FAILURES -gt 0 ]; then
    fail "Total failures: $FAILURES"
    exit 1
else
    success "All tests passed across all PHP versions!"
    exit 0
fi
