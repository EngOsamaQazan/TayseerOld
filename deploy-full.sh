#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  رفع كامل — استبدال جميع الملفات على السيرفر
#  يرفع الكود المحلي بالكامل إلى jadal و namaa (بدون الاعتماد على git)
# ═══════════════════════════════════════════════════════════════

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR" && pwd)"
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

: "${SERVER_HOST:?يجب تعيين SERVER_HOST في deploy.config}"
: "${SERVER_USER:?يجب تعيين SERVER_USER في deploy.config}"
: "${JADAL_PATH:?يجب تعيين JADAL_PATH في deploy.config}"
: "${NAMAA_PATH:?يجب تعيين NAMAA_PATH في deploy.config}"

# التحقق من وجود rsync
if ! command -v rsync &>/dev/null; then
    log_error "rsync غير مثبت. ثبّته أولاً:"
    echo "  Ubuntu/Debian: sudo apt install rsync"
    echo "  Mac: brew install rsync"
    echo "  Windows: استخدم Git Bash (يأتي مع rsync)"
    exit 1
fi

# بناء أمر rsync مع الاستثناءات
RSYNC_EXCLUDES=(
    --exclude='.git'
    --exclude='vendor'
    --exclude='backend/runtime'
    --exclude='frontend/runtime'
    --exclude='console/runtime'
    --exclude='api/runtime'
    --exclude='.idea'
    --exclude='.vscode'
    --exclude='*.log'
    --exclude='deploy.config'
    --exclude='backend/web/images'
    --exclude='backend/web/uploads'
    --exclude='node_modules'
    --exclude='.env'
    --exclude='*.sql'
    --exclude='common/config/main-local.php'
    --exclude='common/config/params-local.php'
    --exclude='backend/config/main-local.php'
    --exclude='backend/config/params-local.php'
    --exclude='frontend/config/main-local.php'
    --exclude='frontend/config/params-local.php'
    --exclude='console/config/main-local.php'
    --exclude='console/config/params-local.php'
)

# رفع كامل إلى مسار معيّن
# الاستخدام: full_deploy_to_path المسار البيئة التسمية [host] [user]
full_deploy_to_path() {
    local path="$1"
    local env_name="$2"
    local label="$3"
    local host="${4:-$SERVER_HOST}"
    local user="${5:-$SERVER_USER}"
    local dest="${user}@${host}:${path}"

    log_info "─────────────────────────────────────────"
    log_info "رفع كامل: $label → $path"
    log_info "─────────────────────────────────────────"

    # بناء خيارات SSH لـ rsync
    local ssh_opts="-o StrictHostKeyChecking=no -o ConnectTimeout=10"
    if [ -n "$SSH_KEY" ]; then
        ssh_opts="$ssh_opts -i $SSH_KEY"
    fi

    # رفع الملفات
    if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
        log_info "→ رفع الملفات (rsync)..."
        sshpass -p "$SSH_PASS" rsync -avz --delete \
            -e "ssh $ssh_opts" \
            "${RSYNC_EXCLUDES[@]}" \
            "$PROJECT_DIR/" \
            "$dest/"
    else
        if [ -n "$SSH_PASS" ]; then
            log_error "يجب تثبيت sshpass لاستخدام كلمة المرور: apt install sshpass"
            exit 1
        fi
        log_info "→ رفع الملفات (rsync)..."
        rsync -avz --delete \
            -e "ssh $ssh_opts" \
            "${RSYNC_EXCLUDES[@]}" \
            "$PROJECT_DIR/" \
            "$dest/"
    fi

    # تنفيذ أوامر ما بعد الرفع على السيرفر
    local ssh_cmd="ssh $ssh_opts ${user}@${host}"
    if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
        sshpass -p "$SSH_PASS" $ssh_cmd bash -s "$path" "$env_name" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"

cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود: $DEPLOY_PATH"; exit 1; }

echo "→ composer install"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>/dev/null || composer install --no-interaction --prefer-dist --optimize-autoloader

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
find backend/runtime -mindepth 1 ! -name '.gitignore' -delete 2>/dev/null
php7.4 yii cache/flush-all 2>/dev/null || true

echo "→ إصلاح الصلاحيات"
chown -R www-data:www-data "$DEPLOY_PATH"

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT
    else
        $ssh_cmd bash -s "$path" "$env_name" << 'REMOTE_SCRIPT'
set -e
DEPLOY_PATH="$1"
ENV_NAME="$2"

cd "$DEPLOY_PATH" || { echo "خطأ: المسار غير موجود: $DEPLOY_PATH"; exit 1; }

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
find backend/runtime -mindepth 1 ! -name '.gitignore' -delete 2>/dev/null
php7.4 yii cache/flush-all 2>/dev/null || true

echo "→ إصلاح الصلاحيات"
chown -R www-data:www-data "$DEPLOY_PATH"

echo "✓ انتهى النشر بنجاح"
REMOTE_SCRIPT
    fi
}

# ── التحقق من الاتصال ──
log_info "التحقق من الاتصال بالسيرفر..."
ssh_opts="-o StrictHostKeyChecking=no -o ConnectTimeout=10"
[ -n "$SSH_KEY" ] && ssh_opts="$ssh_opts -i $SSH_KEY"
ssh_cmd="ssh $ssh_opts ${SERVER_USER}@${SERVER_HOST}"

if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
    if ! sshpass -p "$SSH_PASS" $ssh_cmd "exit 0" 2>/dev/null; then
        log_error "فشل الاتصال بالسيرفر. تحقق من: SERVER_HOST, SERVER_USER, SSH_PASS"
        exit 1
    fi
else
    if ! $ssh_cmd "exit 0" 2>/dev/null; then
        log_error "فشل الاتصال بالسيرفر. تحقق من المفتاح أو كلمة المرور"
        exit 1
    fi
fi
log_info "الاتصال ناجح ✓"
echo ""

# ── التحقق من وجود المجلدات على السيرفر ──
log_info "التحقق من مسارات الوجهة..."
run_ssh() {
    if [ -n "$SSH_PASS" ] && command -v sshpass &>/dev/null; then
        sshpass -p "$SSH_PASS" $ssh_cmd "$@"
    else
        $ssh_cmd "$@"
    fi
}
for p in "$JADAL_PATH" "$NAMAA_PATH"; do
    if ! run_ssh "[ -d '$p' ]" 2>/dev/null; then
        run_ssh "mkdir -p '$p'"
        log_info "تم إنشاء المجلد: $p"
    fi
done
echo ""

# ── التنفيذ ──
log_info "بدء الرفع الكامل لجدل ونماء..."
log_info "المصدر: $PROJECT_DIR"
echo ""

full_deploy_to_path "$JADAL_PATH" "JadalProduction" "جدل (jadal.aqssat.co)" "${JADAL_HOST:-$SERVER_HOST}" "${JADAL_USER:-$SERVER_USER}" || { log_error "فشل رفع جدل"; exit 1; }
echo ""

full_deploy_to_path "$NAMAA_PATH" "NamaaProduction" "نماء (namaa.aqssat.co)" "${NAMAA_HOST:-$SERVER_HOST}" "${NAMAA_USER:-$SERVER_USER}" || { log_error "فشل رفع نماء"; exit 1; }

echo ""
log_info "════════════════════════════════════════"
log_info "تم الرفع الكامل بنجاح على كلا الموقعين ✓"
log_info "  • jadal.aqssat.co"
log_info "  • namaa.aqssat.co"
log_info "════════════════════════════════════════"
