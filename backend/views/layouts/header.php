<?php
/**
 * رأس الصفحة الرئيسي (Header)
 * ==============================
 * يحتوي على: شعار التطبيق، قائمة التنقل، قائمة المستخدم
 * 
 * @var string $directoryAsset مسار ملفات AdminLTE
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;
use backend\modules\notification\models\Notification;

/* === جلب بيانات الشركة الرئيسية === */
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();

if ($primary_company == '') {
    $logo = Yii::$app->params['companies_logo'];
    $companyName = '';
} else {
    $logo = $primary_company->logo;
    $companyName = $primary_company->name;
}

/* === جلب صورة المستخدم === */
$avatar = \backend\modules\employee\models\EmployeeFiles::find()
    ->where(['user_id' => Yii::$app->user->id])
    ->andWhere(['type' => 0])
    ->orderBy(['id' => SORT_DESC])
    ->one();

/* === جلب آخر 10 إشعارات + عدد غير المقروءة === */
$userId = Yii::$app->user->id;
$unreadCount = (int) Notification::find()
    ->where(['recipient_id' => $userId, 'is_unread' => 1])
    ->count();
$latestNotifs = Notification::find()
    ->where(['recipient_id' => $userId])
    ->orderBy(['id' => SORT_DESC])
    ->limit(10)
    ->all();

/* === تسجيل رابط التطبيق الأساسي في جافاسكريبت === */
Yii::$app->view->registerJsVar('base_url', Yii::$app->request->hostInfo . Yii::$app->getUrlManager()->getBaseUrl());
?>

<div class="header">
    <header class="main-header">

        <!-- === شعار التطبيق === -->
        <?= Html::a(
            '<span class="logo-mini"><i class="fa fa-building-o"></i></span>' .
            '<span class="logo-lg">' . (!empty($companyName) ? Html::encode($companyName) : 'جدل') . '</span>',
            Yii::$app->homeUrl,
            ['class' => 'logo']
        ) ?>

        <!-- === شريط التنقل العلوي === -->
        <nav class="navbar navbar-static-top" role="navigation">

            <!-- زر فتح/إغلاق القائمة الجانبية -->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only"><?= Yii::t('app', 'تبديل القائمة') ?></span>
            </a>

            <?php
            /* === صورة افتراضية — أيقونة شخص === */
            $defaultAvatar = Yii::$app->request->baseUrl . '/img/default-avatar.png';
            $avatarSrc = !empty($avatar->path) ? Url::to([$avatar->path]) : $defaultAvatar;
            $onError = "this.onerror=null;this.src='" . $defaultAvatar . "';";
            $markReadUrl = Url::to(['/notification/notification/is-read']);
            ?>

            <!-- === منطقة المستخدم والإشعارات === -->
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav" style="display:flex;align-items:center;gap:0">

                    <!-- === جرس الإشعارات === -->
                    <li class="dropdown notifications-menu" id="notifDropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="الإشعارات"
                           style="position:relative;padding:8px 12px;display:flex;align-items:center">
                            <i class="fa fa-bell-o" style="font-size:19px;color:#fff"></i>
                            <?php if ($unreadCount > 0): ?>
                            <span class="notif-badge" id="notifBadge"
                                  style="position:absolute;top:4px;right:6px;font-size:10px;min-width:17px;height:17px;line-height:17px;padding:0 4px;border-radius:9px;background:#e74c3c;color:#fff;text-align:center;font-weight:700"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                            <?php endif ?>
                        </a>
                        <ul class="dropdown-menu" style="width:350px;max-width:92vw;padding:0;border-radius:10px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.18);left:auto;right:0">
                            <!-- رأس الإشعارات -->
                            <li style="background:var(--fin-primary,#800020);color:#fff;padding:0;border:0">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px">
                                    <span style="font-size:14px;font-weight:700"><i class="fa fa-bell"></i> الإشعارات</span>
                                    <button type="button" id="btnMarkAllRead" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;padding:3px 10px;border-radius:12px;cursor:pointer;font-weight:600;transition:background .2s"
                                            title="تمييز الجميع كمقروء">
                                        <i class="fa fa-check-double"></i> تمييز الجميع كمقروء
                                    </button>
                                </div>
                            </li>
                            <!-- قائمة الإشعارات -->
                            <li>
                                <ul class="menu" id="notifList" style="list-style:none;padding:0;margin:0;max-height:380px;overflow-y:auto">
                                    <?php if (empty($latestNotifs)): ?>
                                    <li style="padding:30px 15px;text-align:center;color:#aaa">
                                        <i class="fa fa-bell-slash-o" style="font-size:28px;display:block;margin-bottom:10px;color:#ddd"></i>
                                        لا توجد إشعارات
                                    </li>
                                    <?php else: ?>
                                    <?php foreach ($latestNotifs as $n): ?>
                                    <?php
                                        $isUnread = ((int)$n->is_unread === 1);
                                        $timeAgo  = Yii::$app->formatter->asRelativeTime($n->created_time);
                                        $href     = !empty($n->href) ? Url::to([$n->href]) : '#';
                                    ?>
                                    <li class="notif-item <?= $isUnread ? 'notif-unread' : '' ?>" style="border-bottom:1px solid #f0f0f0;transition:background .3s">
                                        <a href="<?= Html::encode($href) ?>" style="display:block;padding:11px 14px;color:#333;text-decoration:none;white-space:normal;line-height:1.5">
                                            <div style="display:flex;align-items:flex-start;gap:10px">
                                                <div style="flex-shrink:0;margin-top:5px">
                                                    <span class="notif-dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $isUnread ? '#e74c3c' : '#ddd' ?>"></span>
                                                </div>
                                                <div style="flex:1;min-width:0">
                                                    <div style="font-size:13px;font-weight:<?= $isUnread ? '600' : '400' ?>;color:<?= $isUnread ? '#222' : '#666' ?>;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= Html::encode($n->title_html ?: $n->body_html) ?></div>
                                                    <div style="font-size:11px;color:#aaa"><i class="fa fa-clock-o"></i> <?= $timeAgo ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <?php endforeach ?>
                                    <?php endif ?>
                                </ul>
                            </li>
                            <!-- تذييل — مشاهدة الجميع -->
                            <li style="border-top:1.5px solid #eee;background:#fafafa">
                                <?= Html::a(
                                    '<i class="fa fa-list-ul"></i> مشاهدة جميع الإشعارات',
                                    ['/notification/notification/index'],
                                    ['style' => 'display:block;padding:11px 15px;text-align:center;font-size:13px;font-weight:700;color:var(--fin-primary,#800020);text-decoration:none']
                                ) ?>
                            </li>
                        </ul>
                    </li>

                    <!-- === قائمة حساب المستخدم === -->
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"
                           style="display:flex;align-items:center;gap:8px;padding:6px 12px">
                            <img src="<?= Html::encode($avatarSrc) ?>"
                                 onerror="<?= $onError ?>"
                                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;background:#ddd;border:2px solid rgba(255,255,255,.3)"
                                 alt="<?= Yii::t('app', 'صورة المستخدم') ?>">
                            <span class="hidden-xs" style="font-size:13px;font-weight:600;color:#fff"><?= Html::encode(Yii::$app->user->identity['username']) ?></span>
                        </a>

                        <ul class="dropdown-menu">
                            <li class="user-header">
                                <img src="<?= Html::encode($avatarSrc) ?>"
                                     onerror="<?= $onError ?>"
                                     style="width:60px;height:60px;border-radius:50%;object-fit:cover;background:#ddd"
                                     alt="<?= Yii::t('app', 'صورة المستخدم') ?>">
                                <p><?= Html::encode(Yii::$app->user->identity['username']) ?></p>
                            </li>
                            <li class="user-footer">
                                <div class="pull-left">
                                    <?= Html::a(
                                        '<i class="fa fa-user"></i> ' . Yii::t('app', 'الملف الشخصي'),
                                        Url::to(['/employee/update', 'id' => Yii::$app->user->id]),
                                        ['class' => 'btn btn-default btn-flat']
                                    ) ?>
                                </div>
                                <div class="pull-right">
                                    <?= Html::a(
                                        '<i class="fa fa-sign-out"></i> ' . Yii::t('app', 'تسجيل الخروج'),
                                        ['/site/logout'],
                                        ['data-method' => 'post', 'class' => 'btn btn-default btn-flat']
                                    ) ?>
                                </div>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>

            <?php
            /* === JavaScript — تمييز كمقروء تلقائياً عند فتح القائمة + زر يدوي === */
            $notifJs = <<<JSBLOCK
/* عند فتح قائمة الإشعارات → تمييز تلقائي كمقروء */
var notifMarked=false;
$("#notifDropdown").on("show.bs.dropdown",function(){
    if(notifMarked) return;
    notifMarked=true;
    $.post("$markReadUrl",function(){
        /* إخفاء البادج */
        $("#notifBadge").fadeOut(300);
        /* تحويل كل الإشعارات لحالة مقروءة بصرياً بعد ثانية */
        setTimeout(function(){
            $(".notif-unread").css("background","transparent").removeClass("notif-unread");
            $(".notif-dot").css("background","#ddd");
        },1000);
    });
});

/* زر تمييز الجميع كمقروء */
$("#btnMarkAllRead").on("click",function(e){
    e.stopPropagation();
    var btn=$(this);
    btn.html('<i class="fa fa-spinner fa-spin"></i> جاري...');
    $.post("$markReadUrl",function(){
        $("#notifBadge").fadeOut(300);
        $(".notif-unread").css("background","transparent").removeClass("notif-unread");
        $(".notif-dot").css("background","#ddd");
        btn.html('<i class="fa fa-check"></i> تم التمييز');
        setTimeout(function(){btn.html('<i class="fa fa-check-double"></i> تمييز الجميع كمقروء');},2000);
    });
});

/* hover effect على الإشعارات غير المقروءة */
$(".notif-unread").css("background","#fef9f0");
JSBLOCK;
            $this->registerJs($notifJs, \yii\web\View::POS_READY);
            ?>
        </nav>
    </header>
</div>
