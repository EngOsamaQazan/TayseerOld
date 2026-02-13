#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  سكربت النشر الموحد — جدل + نماء
#  يرفع الكود على كلا الموقعين: jadal.aqssat.co و namaa.aqssat.co
# ═══════════════════════════════════════════════════════════════

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/deploy.config"

# ألوان للطباعة
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# تحميل الإعدادات
if [ ! -f "$CONFIG_FILE" ]; then
    log_error "ملف الإعدادات غير موجود. انسخ deploy.config.example إلى deploy.config واملأ القيم:"
    echo "  cp deploy.config.example deploy.config"
    exit 1
fi

source "$CONFIG_FILE"

# التحقق من القيم الأساسية
: "${SERVER_HOST:?يجب تعيين SERVER_HOST في deploy.config}"
: "${SERVER_USER:?يجب تعيين SERVER_USER في deploy.config}"
: "${JADAL_PATH:?يجب تعيين JADAL_PATH في deploy.config}"
: "${NAMAA_PATH:?يجب تعيين NAMAA_PATH في deploy.config}"
: "${GIT_BRANCH:=main}"

# بناء أمر SSH (host و user اختياريان - يُستخدمان إن وُجدا)
build_ssh_cmd() {
    local host="${1:-$SERVER_HOST}"
    local user="${2:-$SERVER_USER}"
    local extra=""
    if [ -n "$SSH_KEY" ]; then
        extra="-i $SSH_KEY"
    fi
    echo "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 $extra ${user}@${host}"
}

# تنفيذ النشر على مسار معيّن
# الاستخدام: deploy_to_path المسار البيئة التسمية [host] [user]
deploy_to_path() {
    local path="$1"
    local env_name="$2"
    local label="$3"
    local host="${4:-$SERVER_HOST}"
    local user="${5:-$SERVER_USER}"
    local ssh_cmd
    ssh_cmd=$(build_ssh_cmd "$host" "$user")

    log_info "─────────────────────────────────────────"
    log_info "نشر $label → $path"
    log_info "─────────────────────────────────────────"

    if [ -n "$SSH_PASS" ]; then
        # استخدام sshpass إذا وُجدت كلمة المرور
        if command -v sshpass &>/dev/null; then
            sshpass -p "$SSH_PASS" $ssh_cmd bash -s "$path" "$env_name" "$GIT_BRANCH" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"
GIT_BRANCH="$3"

cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود: $DEPLOY_PATH"; exit 1; }

echo "→ git fetch && git checkout $GIT_BRANCH && git pull origin $GIT_BRANCH"
git fetch 2>/dev/null || true
git checkout "$GIT_BRANCH" 2>/dev/null || true
git pull origin "$GIT_BRANCH" || { echo "تحذير: فشل git pull"; }

echo "→ composer install"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>/dev/null || composer install --no-interaction --prefer-dist --optimize-autoloader

echo "→ php init --env=$ENV_NAME --overwrite=n"
php init --env="$ENV_NAME" --overwrite=n 2>/dev/null || true

echo "→ php yii migrate --interactive=0"
php yii migrate --interactive=0 2>/dev/null || true

echo "→ مسح الكاش"
rm -rf backend/runtime/cache/* 2>/dev/null
rm -rf backend/web/assets/* 2>/dev/null
find backend/runtime -mindepth 1 ! -name '.gitignore' -delete 2>/dev/null
php yii cache/flush-all 2>/dev/null || true

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT
        else
            log_error "يجب تثبيت sshpass لاستخدام كلمة المرور: apt install sshpass"
            exit 1
        fi
    else
        $ssh_cmd bash -s "$path" "$env_name" "$GIT_BRANCH" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"
GIT_BRANCH="$3"

cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود: $DEPLOY_PATH"; exit 1; }

echo "→ git fetch && git checkout $GIT_BRANCH && git pull origin $GIT_BRANCH"
git fetch 2>/dev/null || true
git checkout "$GIT_BRANCH" 2>/dev/null || true
git pull origin "$GIT_BRANCH" || { echo "تحذير: فشل git pull"; }

echo "→ composer install"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>/dev/null || composer install --no-interaction --prefer-dist --optimize-autoloader

echo "→ php init --env=$ENV_NAME --overwrite=n"
php init --env="$ENV_NAME" --overwrite=n 2>/dev/null || true

echo "→ php yii migrate --interactive=0"
php yii migrate --interactive=0 2>/dev/null || true

echo "→ مسح الكاش"
rm -rf backend/runtime/cache/* 2>/dev/null
rm -rf backend/web/assets/* 2>/dev/null
find backend/runtime -mindepth 1 ! -name '.gitignore' -delete 2>/dev/null
php yii cache/flush-all 2>/dev/null || true

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT
    fi
}

# ── التحقق من الاتصال قبل البدء ──
log_info "التحقق من الاتصال بالسيرفر..."
ssh_cmd_test=$(build_ssh_cmd)
if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
    if ! sshpass -p "$SSH_PASS" $ssh_cmd_test "exit 0" 2>/dev/null; then
        log_error "فشل الاتصال بالسيرفر. تحقق من: SERVER_HOST, SERVER_USER, SSH_PASS"
        exit 1
    fi
elif [ -z "$SSH_PASS" ] || [ -n "$SSH_KEY" ]; then
    if ! $ssh_cmd_test "exit 0" 2>/dev/null; then
        log_error "فشل الاتصال بالسيرفر. تحقق من المفتاح أو كلمة المرور"
        exit 1
    fi
fi
log_info "الاتصال ناجح ✓"
echo ""

# ── التنفيذ ──
log_info "بدء النشر الموحد على جدل ونماء..."
log_info "الفرع: $GIT_BRANCH"
log_info "جدل: $JADAL_PATH | نماء: $NAMAA_PATH"
echo ""

# نشر جدل (يدعم سيرفر منفصل إن وُجد JADAL_HOST)
deploy_to_path "$JADAL_PATH" "JadalProduction" "جدل (jadal.aqssat.co)" "${JADAL_HOST:-$SERVER_HOST}" "${JADAL_USER:-$SERVER_USER}" || { log_error "فشل نشر جدل"; exit 1; }
echo ""

# نشر نماء (يدعم سيرفر منفصل إن وُجد NAMAA_HOST)
deploy_to_path "$NAMAA_PATH" "NamaaProduction" "نماء (namaa.aqssat.co)" "${NAMAA_HOST:-$SERVER_HOST}" "${NAMAA_USER:-$SERVER_USER}" || { log_error "فشل نشر نماء"; exit 1; }

echo ""
log_info "════════════════════════════════════════"
log_info "تم النشر بنجاح على كلا الموقعين ✓"
log_info "  • jadal.aqssat.co"
log_info "  • namaa.aqssat.co"
log_info "════════════════════════════════════════"
