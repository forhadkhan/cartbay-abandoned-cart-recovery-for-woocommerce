#!/usr/bin/env bash
set -e

ZIP="cartbay-abandoned-cart-recovery-for-woocommerce.zip"
TMP="/tmp/cartbay-release-verify"

echo "Verifying release ZIP: $ZIP"

if [ ! -f "$ZIP" ]; then
    echo "❌ ZIP not found: $ZIP"
    exit 1
fi

rm -rf "$TMP" && mkdir -p "$TMP"
unzip -q "$ZIP" -d "$TMP"

PLUGIN="$TMP/cartbay-abandoned-cart-recovery-for-woocommerce"

# Required files — representative sample from each ship directory.
REQUIRED=(
    "cartbay-abandoned-cart-recovery-for-woocommerce.php"
    "uninstall.php"
    "readme.txt"
    "LICENSE.txt"
    "app/Core/Plugin.php"
    "app/Admin/Wizard/WizardController.php"
    "app/Recovery/CaptureService.php"
    "app/Data/SessionRepository.php"
    "app/Analytics/AnalyticsService.php"
    "app/Utils/Logger.php"
    "app/Email/AbstractCartBayRecoveryEmail.php"
    "assets/js/cartbay-capture.js"
    "assets/js/cartbay-capture.asset.php"
    "assets/js/cartbay-block.js"
    "assets/js/cartbay-block.asset.php"
    "templates/emails/recovery-email-1.php"
    "vendor/autoload.php"
    "languages/cartbay-abandoned-cart-recovery-for-woocommerce.pot"
)

# JS build source shipped for GPL source-availability compliance (see release.js).
REQUIRED+=(
    "src"
    "package.json"
    "webpack.config.js"
)

for f in "${REQUIRED[@]}"; do
    if [ ! -e "$PLUGIN/$f" ]; then
        echo "❌ MISSING required file: $f"
        exit 1
    fi
    echo "✅ Found: $f"
done

# Forbidden files/dirs.
FORBIDDEN=(
    "node_modules"
    "AGENTS.md"
    "GEMINI.md"
    "tasks"
    "phpcs.xml"
    "phpstan.neon"
    "phpunit.xml"
    "scripts"
    ".git"
    ".env"
    ".history"
    ".agents"
    ".codex"
    ".kilo"
    ".gemini"
    ".playwright"
    ".github"
    ".graphifyignore"
)

for f in "${FORBIDDEN[@]}"; do
    if [ -e "$PLUGIN/$f" ]; then
        echo "❌ FORBIDDEN file/dir present: $f"
        exit 1
    fi
    echo "✅ Absent (correct): $f"
done

# Version parity across every place the version is written. package.json drives
# the JS source-link banner and CARTBAY_VERSION gates the upgrade migrations;
# both have been missed before and nothing else in the pipeline catches them.
HEADER_VERSION=$(grep -m1 -oP '^\s*\*\s*Version:\s*\K[0-9A-Za-z.\-]+' "$PLUGIN/cartbay-abandoned-cart-recovery-for-woocommerce.php" || true)
HEADER_STABLE=$(grep -m1 -oP '^\s*\*\s*Stable tag:\s*\K[0-9A-Za-z.\-]+' "$PLUGIN/cartbay-abandoned-cart-recovery-for-woocommerce.php" || true)
README_STABLE=$(grep -m1 -oP '^Stable tag:\s*\K[0-9A-Za-z.\-]+' "$PLUGIN/readme.txt" || true)
PKG_VERSION=$(grep -m1 -oP '"version"\s*:\s*"\K[0-9A-Za-z.\-]+' "$PLUGIN/package.json" || true)
CONST_VERSION=$(grep -m1 -oP "CARTBAY_VERSION',\s*'\K[0-9A-Za-z.\-]+" "$PLUGIN/app/Core/Constants.php" || true)

if [ -z "$HEADER_VERSION" ]; then
    echo "❌ Could not read Version from the plugin header"
    exit 1
fi

for pair in "header Stable tag=$HEADER_STABLE" "readme.txt Stable tag=$README_STABLE" "package.json version=$PKG_VERSION" "CARTBAY_VERSION constant=$CONST_VERSION"; do
    label="${pair%%=*}"
    value="${pair##*=}"
    if [ "$value" != "$HEADER_VERSION" ]; then
        echo "❌ VERSION MISMATCH: $label is '$value', plugin header Version is '$HEADER_VERSION'"
        exit 1
    fi
    echo "✅ $label matches $HEADER_VERSION"
done

rm -rf "$TMP"
echo ""
echo "✅ Release verification passed for $HEADER_VERSION."
