<?php

namespace backend\components;

use Yii;
use yii\base\Behavior;
use yii\web\Application;
use yii\web\ForbiddenHttpException;
use common\helper\Permissions;

/**
 * سلوك التحقق من صلاحية الوصول حسب المسار
 * ─────────────────────────────────────────────
 * يمنع فتح أي شاشة عبر الرابط المباشر إذا كان المستخدم لا يملك الصلاحية المطلوبة.
 * يُربط بالتطبيق في backend\config\main.php كـ 'as routeAccess'.
 */
class RouteAccessBehavior extends Behavior
{
    /** مسارات يُسمح بالوصول لها دون فحص صلاحية (تسجيل الدخول/الخروج، خطأ، إلخ). لوحة التحكم site/index تحتاج صلاحية لوحة التحكم */
    public static $publicRoutes = [
        'site/logout',
        'site/error',
        'site/login',
        'site/import',
        'dektrium/user/security/login',
        'dektrium/user/registration/register',
        'dektrium/user/recovery/request',
        'dektrium/user/recovery/reset',
        'dektrium/user/profile/show',
        'dektrium/user/settings/profile',
        'dektrium/user/settings/account',
    ];

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'checkRouteAccess',
        ];
    }

    /**
     * استخراج معرّف المتحكم من pathInfo (مثلاً hr/hr-employee/index → hr/hr-employee)
     */
    protected static function getControllerUniqueIdFromPath($pathInfo)
    {
        $path = trim($pathInfo ?? '', '/');
        if ($path === '') {
            return 'site'; // الصفحة الرئيسية = site/index
        }
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            array_pop($parts);
            return implode('/', $parts);
        }
        return $path;
    }

    /**
     * التحقق من صلاحية الوصول للمسار الحالي
     * @param \yii\base\Event $event
     * @throws ForbiddenHttpException
     */
    public function checkRouteAccess($event)
    {
        if (!Yii::$app->request->isGet && !Yii::$app->request->isPost) {
            return;
        }
        if (Yii::$app->user->isGuest) {
            return;
        }

        $pathInfo = trim(Yii::$app->request->pathInfo ?? '', '/');
        $controllerId = self::getControllerUniqueIdFromPath($pathInfo);

        foreach (self::$publicRoutes as $public) {
            $p = trim($public, '/');
            if ($pathInfo === $p || $pathInfo === '' && $p === 'site' || strpos($pathInfo . '/', $p . '/') === 0) {
                return;
            }
        }

        $permissions = Permissions::getRequiredPermissionsForRoute($controllerId);
        if ($permissions === null) {
            // منع افتراضي: أي مسار غير مدرج في الخريطة أو في القائمة العامة = ممنوع
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
        // مصفوفة فارغة = لا يشترط أي صلاحية (مثلاً نظام الحضور والانصراف متاح للكل)
        if ($permissions === []) {
            return;
        }

        if (!Permissions::hasAnyPermission($permissions)) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
