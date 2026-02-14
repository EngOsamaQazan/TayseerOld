#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  نشر من الصفر — حذف كامل لجدل ونماء ثم رفع المشروع بالكامل
#  يَحذف كل الملفات داخل مسار كل موقع ثم يرفع المشروع من جديد
#  يعمل على Windows (Git Bash + sshpass.exe في مجلد السكربت)
# ═══════════════════════════════════════════════════════════════

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/deploy.config"
TAR_FILE="$SCRIPT_DIR/.deploy-fresh-temp.tar.gz"

# sshpass من مجلد السكربت (للويندوز)
[ -x "$SCRIPT_DIR/sshpass.exe" ] && export PATH="$SCRIPT_DIR:$PATH"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'
log_info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

cleanup() { rm -f "$TAR_FILE"; }
trap cleanup EXIT

# ── تحميل الإعدادات ──
if [ ! -f "$CONFIG_FILE" ]; then
    log_error "ملف deploy.config غير موجود. انسخ deploy.config.example إلى deploy.config واملأ القيم."
    exit 1
fi
source "$CONFIG_FILE"

: "${SERVER_HOST:?ضع SERVER_HOST في deploy.config}"
: "${SERVER_USER:?ضع SERVER_USER في deploy.config}"
: "${JADAL_PATH:?ضع JADAL_PATH في deploy.config}"
: "${NAMAA_PATH:?ضع NAMAA_PATH في deploy.config}"

ssh_opts="-o StrictHostKeyChecking=no -o ConnectTimeout=10"
[ -n "$SSH_KEY" ] && ssh_opts="$ssh_opts -i $SSH_KEY"
ssh_cmd="ssh $ssh_opts ${SERVER_USER}@${SERVER_HOST}"

run_ssh() {
    if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
        sshpass -p "$SSH_PASS" $ssh_cmd "$@"
    else
        $ssh_cmd "$@"
    fi
}
run_scp() {
    if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
        sshpass -p "$SSH_PASS" scp $ssh_opts "$@"
    else
        scp $ssh_opts "$@"
    fi
}

# ── نشر إلى مسار: حذف كامل ثم رفع من الصفر ──
deploy_fresh_to() {
    local path="$1"
    local env_name="$2"
    local label="$3"
    local host="${4:-$SERVER_HOST}"
    local user="${5:-$SERVER_USER}"

    log_info "════════════════════════════════════════"
    log_info "نشر من الصفر: $label → $path"
    log_info "════════════════════════════════════════"

    # 1) حذف كامل لكل محتويات المجلد على السيرفر
    log_info "→ حذف كامل لجميع الملفات على السيرفر..."
    run_ssh bash -s "$path" << 'WIPE_SCRIPT'
set -e
P="$1"
[ -z "$P" ] && exit 1
if [ ! -d "$P" ]; then
    echo "إنشاء المجلد: $P"
    mkdir -p "$P"
else
    echo "حذف كل المحتويات داخل $P"
    find "$P" -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || true
fi
WIPE_SCRIPT

    # 2) إنشاء أرشيف المشروع محلياً
    log_info "→ إنشاء أرشيف المشروع..."
    (cd "$PROJECT_DIR" && tar --exclude='.git' --exclude='vendor' \
        --exclude='backend/runtime' --exclude='frontend/runtime' --exclude='console/runtime' --exclude='api/runtime' \
        --exclude='.idea' --exclude='.vscode' --exclude='deploy.config' --exclude='sshpass.exe' \
        --exclude='backend/web/images' --exclude='backend/web/uploads' \
        --exclude='node_modules' --exclude='*.sql' --exclude='*.log' \
        --exclude='common/config/main-local.php' --exclude='common/config/params-local.php' \
        --exclude='backend/config/main-local.php' --exclude='backend/config/params-local.php' \
        --exclude='frontend/config/main-local.php' --exclude='frontend/config/params-local.php' \
        --exclude='console/config/main-local.php' --exclude='console/config/params-local.php' \
        -czf "$TAR_FILE" .)

    # 3) رفع الأرشيف
    log_info "→ رفع الأرشيف إلى السيرفر..."
    run_scp "$TAR_FILE" "${user}@${host}:${path}/.deploy-fresh.tar.gz"

    # 4) فك الضغط وإعداد المشروع على السيرفر
    log_info "→ فك الضغط وإعداد المشروع..."
    run_ssh bash -s "$path" "$env_name" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"
cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود"; exit 1; }

echo "→ فك أرشيف المشروع..."
tar -xzf .deploy-fresh.tar.gz
rm -f .deploy-fresh.tar.gz

echo "→ إنشاء مجلدات runtime (للجلسات والكاش)..."
mkdir -p backend/runtime frontend/runtime console/runtime api/runtime

echo "→ php init --env=$ENV_NAME --overwrite=y"
php init --env="$ENV_NAME" --overwrite=y 2>/dev/null || true

echo "→ composer install..."
php7.4 /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs 2>/dev/null || \
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs 2>/dev/null || true

echo "→ php yii migrate --interactive=0"
php7.4 yii migrate --interactive=0 2>/dev/null || php yii migrate --interactive=0 2>/dev/null || true

echo "→ مسح الكاش..."
rm -rf backend/runtime/cache/* backend/web/assets/* 2>/dev/null || true
php7.4 yii cache/flush-all 2>/dev/null || php yii cache/flush-all 2>/dev/null || true

echo "→ إصلاح الصلاحيات..."
chown -R www-data:www-data "$DEPLOY_PATH"
chmod -R 0777 backend/runtime frontend/runtime console/runtime api/runtime 2>/dev/null || true

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT

    log_info "✓ انتهى: $label"
    echo ""
}

# ── التحقق من الاتصال ──
log_info "التحقق من الاتصال بالسيرفر..."
if ! run_ssh "exit 0" 2>/dev/null; then
    log_error "فشل الاتصال. تحقق من deploy.config (SERVER_HOST, SERVER_USER, SSH_PASS أو SSH_KEY)"
    exit 1
fi
log_info "الاتصال ناجح ✓"
echo ""

# إنشاء المسارات إن لم توجد
for p in "$JADAL_PATH" "$NAMAA_PATH"; do
    run_ssh "mkdir -p '$p'" 2>/dev/null || true
done
echo ""

# ── تنفيذ النشر من الصفر لجدل ثم نماء ──
log_info "بدء النشر من الصفر (حذف كامل + رفع المشروع)..."
log_info "المصدر: $PROJECT_DIR"
echo ""

deploy_fresh_to "$JADAL_PATH" "JadalProduction" "جدل (jadal.aqssat.co)" "${JADAL_HOST:-$SERVER_HOST}" "${JADAL_USER:-$SERVER_USER}" || { log_error "فشل نشر جدل"; exit 1; }
deploy_fresh_to "$NAMAA_PATH" "NamaaProduction" "نماء (namaa.aqssat.co)" "${NAMAA_HOST:-$SERVER_HOST}" "${NAMAA_USER:-$SERVER_USER}" || { log_error "فشل نشر نماء"; exit 1; }

log_info "════════════════════════════════════════"
log_info "تم النشر من الصفر بنجاح على جدل ونماء ✓"
log_info "════════════════════════════════════════"
