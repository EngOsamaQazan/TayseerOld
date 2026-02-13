#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  سكربت التحقق من المسارات — يُشغّل على السيرفر بعد الاتصال
#  الاستخدام: ssh root@54.38.236.112 'bash -s' < deploy-verify-paths.sh
#  أو: انسخ المحتوى والصقه بعد ssh
# ═══════════════════════════════════════════════════════════════

echo "════════════════════════════════════════"
echo "التحقق من مسارات المشروع على السيرفر"
echo "════════════════════════════════════════"
echo ""

echo "1. محتويات /var/www:"
ls -la /var/www/ 2>/dev/null || echo "   المجلد غير موجود"
echo ""

echo "2. البحث عن مشاريع Yii2 (backend/web/index.php):"
find /var/www /home -path "*/backend/web/index.php" -type f 2>/dev/null | while read f; do
    # من backend/web/index.php إلى جذر المشروع (ثلاث مستويات لأعلى)
    dir=$(cd "$(dirname "$(dirname "$(dirname "$f")")")" && pwd)
    echo "   ✓ $dir"
done
echo ""

echo "3. البحث عن ملف yii في الجذر:"
find /var/www /home -name "yii" -type f 2>/dev/null | while read f; do
    dir=$(dirname "$f")
    if [ -f "$dir/init" ] && [ -d "$dir/backend" ]; then
        echo "   ✓ $dir"
    fi
done
echo ""

echo "4. محتويات /var/www/html (إن وُجد):"
ls -la /var/www/html/ 2>/dev/null | head -15 || echo "   غير موجود"
echo ""

echo "════════════════════════════════════════"
echo "استخدم المسارات أعلاه في deploy.config"
echo "JADAL_PATH و NAMAA_PATH"
echo "════════════════════════════════════════"
