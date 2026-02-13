#!/bin/bash
# مسح كاش Yii2 من السيرفر الانتاجي
# الاستخدام: ./clear-cache.sh [مسار_التطبيق]
# مثال: ./clear-cache.sh /var/www/html

APP_PATH="${1:-/var/www/html}"

echo "=== مسح الكاش من: $APP_PATH ==="

cd "$APP_PATH" 2>/dev/null || { echo "خطأ: المسار غير موجود"; exit 1; }

# مسح كاش FileCache
[ -d "backend/runtime/cache" ] && rm -rf backend/runtime/cache/* && echo "✓ backend/runtime/cache"
[ -d "frontend/runtime/cache" ] && rm -rf frontend/runtime/cache/* && echo "✓ frontend/runtime/cache"
[ -d "console/runtime/cache" ] && rm -rf console/runtime/cache/* && echo "✓ console/runtime/cache"

# مسح محتويات runtime (logs, assets, etc) مع الإبقاء على المجلد
for dir in backend/runtime frontend/runtime console/runtime common/runtime; do
    if [ -d "$dir" ]; then
        find "$dir" -mindepth 1 ! -name '.gitignore' -exec rm -rf {} + 2>/dev/null
        echo "✓ تم مسح $dir"
    fi
done

# أمر Yii لمسح الكاش
[ -f "yii" ] && php yii cache/flush-all 2>/dev/null && echo "✓ cache/flush-all"

echo "=== انتهى ==="
