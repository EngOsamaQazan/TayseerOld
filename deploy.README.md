# سكربت النشر الموحد — جدل + نماء

يرفع الكود على **كلا الموقعين** معاً:
- **jadal.aqssat.co**
- **namaa.aqssat.co**

---

## نوعان من النشر

| السكربت | الوصف |
|---------|-------|
| `deploy.sh` | سحب التحديثات عبر `git pull` على السيرفر (يتطلب أن يكون المشروع مُستنسخاً مسبقاً) |
| `deploy-full.sh` | **رفع كامل** — يرفع كل الكود المحلي ويستبدل الملفات على السيرفر (بدون الاعتماد على git) |

### متى تستخدم الرفع الكامل (`deploy-full.sh`)

- عند أول نشر للمشروع
- عند رغبتك في **استبدال كلي** لجميع الملفات
- عندما لا يتوفر Git على السيرفر أو تريد المزامنة من جهازك مباشرة

```bash
chmod +x deploy-full.sh
./deploy-full.sh
```

**متطلبات الرفع الكامل:**
- `deploy-full.sh` — يتطلب `rsync` (Linux/Mac)
- `deploy-full-tar.sh` — يعمل بدون rsync (يستخدم tar + scp، مناسب لـ Windows)

---

## الخطوة 1: التحقق من المسارات

**مهم:** تأكد من صحة المسارات قبل أول نشر. شغّل سكربت التحقق من جهازك:

```bash
ssh root@54.38.236.112 'bash -s' < deploy-verify-paths.sh
```

أو انسخ محتوى `deploy-verify-paths.sh` واصقه بعد الاتصال بالسيرفر. النتيجة ستظهر المسارات الصحيحة — عدّل `deploy.config` وفقاً لها.

---

## البيانات المطلوبة منك

| المتغير | الوصف | مثال |
|---------|-------|------|
| `SERVER_HOST` | عنوان السيرفر (IP أو دومين) | `54.38.236.112` |
| `SERVER_USER` | اسم المستخدم لـ SSH | `root` |
| `SSH_PASS` | كلمة مرور SSH (أو اتركه فارغاً واستخدم مفتاح) | `كلمة_المرور` |
| `SSH_KEY` | مسار مفتاح SSH (اختياري، إن وُجد) | `$HOME/.ssh/id_rsa` |
| `JADAL_PATH` | المسار الكامل لمجلد جدل على السيرفر | `/var/www/jadal` |
| `NAMAA_PATH` | المسار الكامل لمجلد نماء على السيرفر | `/var/www/namaa` |
| `GIT_BRANCH` | فرع Git للنشر | `main` |

---

## إذا كان كل موقع على سيرفر مختلف

عدّل `deploy.config` واستخدم المتغيرات التالية بدلاً من `SERVER_HOST` و `SERVER_USER`:

```
JADAL_HOST="..."
JADAL_USER="..."
JADAL_PATH="..."
NAMAA_HOST="..."
NAMAA_USER="..."
NAMAA_PATH="..."
```

ثم عدّل `deploy.sh` ليدعم هذه الحالة (أو أنشئ نسخة مخصصة).

---

## الخطوة 2: التشغيل

**على Linux/Mac:**
```bash
chmod +x deploy.sh
./deploy.sh
```

**على Windows:**
- افتح **Git Bash** (يأتي مع Git for Windows) ثم نفّذ: `./deploy.sh`
- أو استخدم **WSL** (Windows Subsystem for Linux)

---

## ما يفعله السكربت

1. `git pull` — سحب آخر التحديثات
2. `composer install` — تثبيت/تحديث الحزم
3. `php init` — تهيئة البيئة (JadalProduction / NamaaProduction)
4. `php yii migrate` — تشغيل الهجرات
5. مسح الكاش

---

## متطلبات

- **على جهازك:** `ssh` و (اختياري) `sshpass` إذا استخدمت كلمة مرور
- **على السيرفر:** Git، Composer، PHP، المشروع موجود ومُستنسخ مسبقاً

---

## تثبيت sshpass (للاستخدام مع كلمة المرور)

```bash
# Ubuntu/Debian
sudo apt install sshpass

# Mac
brew install sshpass
```
