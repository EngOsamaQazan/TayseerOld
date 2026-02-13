# مسح الكاش من السيرفر الانتاجي

## الطريقة 1: من الطرفية (إذا ظهر طلب كلمة المرور)

إذا فتحت طرفية وشغّلت أمر SSH وظهر طلب كلمة المرور، أدخل:

```
Hussain@1986
```

ثم Enter. بعد الدخول للسيرفر نفّذ:

```bash
cd /var/www/html
# أو المسار الفعلي للمشروع إن كان مختلفاً، مثلاً:
# cd /var/www/jadal-main

rm -rf backend/runtime/cache/* frontend/runtime/cache/* console/runtime/cache/* common/runtime/cache/* 2>/dev/null
mkdir -p backend/runtime/cache
chmod -R 0777 backend/runtime/cache frontend/runtime console/runtime common/runtime
echo "تم مسح الكاش"
```

## الطريقة 2: استخدام السكربت clear-cache.sh

1. انسخ الملف `clear-cache.sh` إلى السيرفر (مثلاً عبر SCP أو SFTP).
2. على السيرفر:
   ```bash
   chmod +x clear-cache.sh
   ./clear-cache.sh /var/www/html
   ```
   (غيّر `/var/www/html` إلى المسار الفعلي للمشروع إن لزم.)

## الطريقة 3: اتصال SSH ثم تنفيذ أمر واحد

من PowerShell أو CMD:

```bash
ssh root@54.38.236.112
```

بعد إدخال كلمة المرور نفّذ:

```bash
cd /var/www/html && rm -rf backend/runtime/cache/* frontend/runtime/cache/* console/runtime/cache/* common/runtime/cache/* 2>/dev/null && mkdir -p backend/runtime/cache && chmod 0777 backend/runtime/cache && echo "تم مسح الكاش"
```

---

**ملاحظة أمنية:** يُفضّل تغيير كلمة مرور root بعد الانتهاء، وعدم مشاركتها في ملفات المشروع.
