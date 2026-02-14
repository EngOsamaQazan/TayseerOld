#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  رفع كامل (بدون rsync) — باستخدام tar + scp
#  يعمل على Windows بدون rsync
# ═══════════════════════════════════════════════════════════════

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/deploy.config"
TAR_FILE="$SCRIPT_DIR/.deploy-full-temp.tar.gz"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# تنظيف عند الخروج
cleanup() {
    rm -f "$TAR_FILE"
}
trap cleanup EXIT

if [ ! -f "$CONFIG_FILE" ]; then
    log_error "ملف الإعدادات غير موجود. انسخ deploy.config.example إلى deploy.config"
    exit 1
fi

source "$CONFIG_FILE"

: "${SERVER_HOST:?يجب تعيين SERVER_HOST في deploy.config}"
: "${SERVER_USER:?يجب تعيين SERVER_USER في deploy.config}"
: "${JADAL_PATH:?يجب تعيين JADAL_PATH في deploy.config}"
: "${NAMAA_PATH:?يجب تعيين NAMAA_PATH في deploy.config}"

# بناء خيارات SSH
ssh_opts="-o StrictHostKeyChecking=no -o ConnectTimeout=10"
[ -n "$SSH_KEY" ] && ssh_opts="$ssh_opts -i $SSH_KEY"
ssh_cmd="ssh $ssh_opts ${SERVER_USER}@${SERVER_HOST}"
scp_cmd="scp $ssh_opts"

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

# رفع إلى مسار
full_deploy_to_path() {
    local path="$1"
    local env_name="$2"
    local label="$3"
    local host="${4:-$SERVER_HOST}"
    local user="${5:-$SERVER_USER}"

    log_info "─────────────────────────────────────────"
    log_info "رفع كامل: $label → $path"
    log_info "─────────────────────────────────────────"

    # إنشاء أرشيف
    log_info "→ إنشاء أرشيف المشروع..."
    (cd "$PROJECT_DIR" && tar --exclude='.git' --exclude='vendor' --exclude='backend/runtime' \
        --exclude='frontend/runtime' --exclude='console/runtime' --exclude='api/runtime' \
        --exclude='.idea' --exclude='.vscode' --exclude='deploy.config' \
        --exclude='backend/web/images' --exclude='backend/web/uploads' \
        --exclude='node_modules' --exclude='*.sql' --exclude='*.log' \
        --exclude='common/config/main-local.php' --exclude='common/config/params-local.php' \
        --exclude='backend/config/main-local.php' --exclude='backend/config/params-local.php' \
        --exclude='frontend/config/main-local.php' --exclude='frontend/config/params-local.php' \
        --exclude='console/config/main-local.php' --exclude='console/config/params-local.php' \
        -czf "$TAR_FILE" .)

    # رفع الأرشيف
    log_info "→ رفع الأرشيف إلى السيرفر..."
    run_scp "$TAR_FILE" "${user}@${host}:${path}/.deploy-temp.tar.gz"

    # فك الضغط والتنفيذ على السيرفر
    run_ssh bash -s "$path" "$env_name" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"

cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود"; exit 1; }

echo "→ حفظ images و uploads مؤقتاً..."
mkdir -p /tmp/deploy-preserve
[ -d backend/web/images ] && cp -a backend/web/images /tmp/deploy-preserve/ 2>/dev/null || true
[ -d backend/web/uploads ] && cp -a backend/web/uploads /tmp/deploy-preserve/ 2>/dev/null || true

echo "→ حذف الملفات القديمة..."
for d in api backend common console database docker docs environments frontend init vendor; do
    [ -d "$d" ] && rm -rf "$d"
done
rm -f .htaccess composer.json deploy*.sh deploy*.md init.bat yii yii.bat yii_test yii_test.bat requirements.php LICENSE.md clear-cache* 2>/dev/null || true

echo "→ فك الضغط..."
tar -xzf .deploy-temp.tar.gz
rm -f .deploy-temp.tar.gz

echo "→ استعادة images و uploads..."
[ -d /tmp/deploy-preserve/images ] && mv /tmp/deploy-preserve/images backend/web/ 2>/dev/null || true
[ -d /tmp/deploy-preserve/uploads ] && mv /tmp/deploy-preserve/uploads backend/web/ 2>/dev/null || true
rm -rf /tmp/deploy-preserve

echo "→ php init --env=$ENV_NAME --overwrite=y"
php init --env="$ENV_NAME" --overwrite=y 2>/dev/null || true

echo "→ composer install (using php7.4)"
php7.4 /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs 2>/dev/null || \
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs

echo "→ php yii migrate --interactive=0"
php7.4 yii migrate --interactive=0 2>/dev/null || true

echo "→ مسح الكاش"
rm -rf backend/runtime/cache/* 2>/dev/null
rm -rf backend/web/assets/* 2>/dev/null
php7.4 yii cache/flush-all 2>/dev/null || true

echo "→ إصلاح الصلاحيات"
chown -R www-data:www-data "$DEPLOY_PATH"

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT
}

# التحقق من الاتصال
log_info "التحقق من الاتصال..."
if ! run_ssh "exit 0" 2>/dev/null; then
    log_error "فشل الاتصال بالسيرفر"
    exit 1
fi
log_info "الاتصال ناجح ✓"
echo ""

# إنشاء المجلدات إن لزم
for p in "$JADAL_PATH" "$NAMAA_PATH"; do
    if ! run_ssh "[ -d '$p' ]" 2>/dev/null; then
        run_ssh "mkdir -p '$p'"
        log_info "تم إنشاء: $p"
    fi
done
echo ""

# التنفيذ
full_deploy_to_path "$JADAL_PATH" "JadalProduction" "جدل (jadal.aqssat.co)" "${JADAL_HOST:-$SERVER_HOST}" "${JADAL_USER:-$SERVER_USER}" || exit 1
echo ""

full_deploy_to_path "$NAMAA_PATH" "NamaaProduction" "نماء (namaa.aqssat.co)" "${NAMAA_HOST:-$SERVER_HOST}" "${NAMAA_USER:-$SERVER_USER}" || exit 1

echo ""
log_info "════════════════════════════════════════"
log_info "تم الرفع الكامل بنجاح ✓"
log_info "════════════════════════════════════════"
