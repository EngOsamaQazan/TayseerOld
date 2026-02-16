<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  نظام إدارة الصلاحيات المتقدم
 *  ──────────────────────────────
 *  يوفر واجهة موحدة لإدارة الأدوار والصلاحيات وإسنادها
 *  للمستخدمين مع مصفوفة بصرية شاملة.
 * ═══════════════════════════════════════════════════════════════
 */

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\helper\Permissions;

class PermissionsManagementController extends Controller
{

    /* ─── تعريف مجموعات الصلاحيات المنطقية ─── */
    public static function getPermissionGroups()
    {
        return [
            'financial' => [
                'label' => 'الإدارة المالية',
                'icon'  => 'fa-money',
                'color' => '#15803d',
                'permissions' => [
                    'الحركات المالية',
                    'الحركات المالية: تعديل',
                    'الحركات المالية: حذف',
                    'الحركات المالية: استيراد',
                    'الحركات المالية: ترحيل',
                    'الحركات المالية لتصدير ونقل البيانات',
                    'الدخل',
                    'الدخل: تعديل',
                    'الدخل: حذف',
                    'الدخل: ارجاع',
                    'المصاريف',
                    'المصاريف: تعديل',
                    'المصاريف: حذف',
                    'المصاريف: ارجاع',
                    'فئات المصايف',
                    'التسويات الماليه',
                ],
            ],
            'contracts' => [
                'label' => 'العقود والمتابعة',
                'icon'  => 'fa-file-text-o',
                'color' => '#1d4ed8',
                'permissions' => [
                    'العقود',
                    'المتابعة',
                    'تقرير المتابعة',
                    'التحويل إلى الدائره القانونية',
                    'الحسميات',
                    'مدير التحصيل',
                ],
            ],
            'customers' => [
                'label' => 'العملاء والمستثمرين',
                'icon'  => 'fa-users',
                'color' => '#7c3aed',
                'permissions' => [
                    'العملاء',
                    'المستثمرين',
                ],
            ],
            'judiciary' => [
                'label' => 'القضاء والقانون',
                'icon'  => 'fa-gavel',
                'color' => '#b91c1c',
                'permissions' => [
                    'القضاء',
                    'الإجراءات القضائية',
                    'إجراءات العملاء القضائية',
                    'المحاكم',
                    'المحامون',
                    'انواع القضايا',
                    'الموطن المختار',
                    'التقارير القضائية',
                ],
            ],
            'hr' => [
                'label' => 'الموارد البشرية',
                'icon'  => 'fa-id-card-o',
                'color' => '#0891b2',
                'permissions' => [
                    'الموظفين',
                    'الوظائف',
                    'العطل',
                    'سياسات الاجازات',
                    'أنواع الإجازات',
                    'أيام العمل',
                    'طلب إجازة',
                    'اشعارات الموظفين',
                ],
            ],
            'inventory' => [
                'label' => 'المخزون',
                'icon'  => 'fa-cubes',
                'color' => '#ca8a04',
                'permissions' => [
                    'عناصر المخزون',
                    'مواقع المخزون',
                    'موردي المخزون',
                    'كمية عناصر المخزون',
                    'فواتير المخزون',
                    'استعلام عناصر المخزون',
                ],
            ],
            'reports' => [
                'label' => 'التقارير',
                'icon'  => 'fa-bar-chart',
                'color' => '#ea580c',
                'permissions' => [
                    'التقارير',
                    'تقارير المتابعات',
                    'تقارير مجموع دفعات العملاء',
                ],
            ],
            'system' => [
                'label' => 'إدارة النظام',
                'icon'  => 'fa-cogs',
                'color' => '#475569',
                'permissions' => [
                    'الصلاحيات',
                    'القواعد',
                    'الجذر',
                    'اسناد الصلاحيات  للموظفين',
                    'الاشعارات',
                ],
            ],
            'settings' => [
                'label' => 'الإعدادات والمراجع',
                'icon'  => 'fa-sliders',
                'color' => '#64748b',
                'permissions' => [
                    'الحالات',
                    'حالات الوثائق',
                    'الاقارب',
                    'الجنسيه',
                    'البنوك',
                    'كيف سمعت عنا',
                    'المدن',
                    'طرق الدفع',
                    'الانفعالات',
                    'طريقة الاتصال',
                    'رد العميل',
                    'انواع الوثائق',
                    'الرسائل',
                ],
            ],
            'diwan' => [
                'label' => 'قسم الديوان',
                'icon'  => 'fa-archive',
                'color' => '#7B1B3A',
                'permissions' => [
                    'الديوان',
                    'تقارير الديوان',
                ],
            ],
            'other' => [
                'label' => 'أخرى',
                'icon'  => 'fa-ellipsis-h',
                'color' => '#9ca3af',
                'permissions' => [
                    'حامل الوثيقة',
                    'الاداره',
                    'تحديد',
                    'أرشيف',
                    'التصدير',
                    'صفحة المدير',
                    'مدير',
                    'مدير صورة',
                    'تحميل الملف',
                    'تحديث مدير الصور',
                    'الملف الشخصي',
                ],
            ],
        ];
    }


    /* ═══════════════════════════════════════════════════════════
     *  Behaviors — حماية الوصول
     * ═══════════════════════════════════════════════════════════ */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => [
                            'index',
                            'get-user-permissions',
                            'save-user-permissions',
                            'save-role',
                            'delete-role',
                            'apply-role-to-user',
                            'clone-permissions',
                            'revoke-all',
                            'toggle-user-status',
                            'seed-roles',
                            'get-role-permissions',
                            'ensure-permissions',
                        ],
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission([
                                Permissions::PERMISSION,
                                Permissions::ROLE,
                                Permissions::ASSIGNMENT,
                            ]);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save-user-permissions' => ['post'],
                    'save-role'             => ['post'],
                    'delete-role'           => ['post'],
                    'apply-role-to-user'    => ['post'],
                    'clone-permissions'     => ['post'],
                    'revoke-all'            => ['post'],
                    'toggle-user-status'    => ['post'],
                    'seed-roles'            => ['post'],
                ],
            ],
        ];
    }


    /* ═══════════════════════════════════════════════════════════
     *  الصفحة الرئيسية — تبويبات: إسناد | أدوار | مصفوفة
     * ═══════════════════════════════════════════════════════════ */
    public function actionIndex()
    {
        $auth = Yii::$app->authManager;
        $db   = Yii::$app->db;

        /* ── جلب المستخدمين مع عدد صلاحياتهم ── */
        $users = $db->createCommand("
            SELECT u.id, u.username, u.email, u.avatar, u.employee_type, u.blocked_at,
                   COUNT(a.item_name) AS perm_count
            FROM {{%user}} u
            LEFT JOIN {{%auth_assignment}} a ON a.user_id = u.id
            GROUP BY u.id, u.username, u.email, u.avatar, u.employee_type, u.blocked_at
            ORDER BY perm_count DESC, u.username ASC
        ")->queryAll();

        /* ── جلب الصلاحيات المسمّاة (غير مسارات الراوت) ── */
        $namedPermissions = $db->createCommand("
            SELECT name, description FROM {{%auth_item}}
            WHERE type = 2 AND name NOT LIKE '/%' AND name NOT LIKE '%/*'
            ORDER BY name
        ")->queryAll();

        /* ── جلب الأدوار ── */
        $roles = $auth->getRoles();

        /* ── عدد المستخدمين لكل دور ── */
        $roleUserCounts = [];
        foreach ($roles as $role) {
            $roleUserCounts[$role->name] = count($auth->getUserIdsByRole($role->name));
        }

        /* ── عدد الصلاحيات الابن لكل دور ── */
        $rolePermCounts = [];
        foreach ($roles as $role) {
            $rolePermCounts[$role->name] = count($auth->getChildren($role->name));
        }

        /* ── مجموعات الصلاحيات ── */
        $groups = self::getPermissionGroups();

        /* ── إحصائيات عامة ── */
        $totalUsers       = count($users);
        $usersWithPerms   = count(array_filter($users, fn($u) => $u['perm_count'] > 0));
        $totalNamedPerms  = count($namedPermissions);
        $totalRoles       = count($roles);

        return $this->render('index', [
            'users'           => $users,
            'namedPermissions'=> $namedPermissions,
            'roles'           => $roles,
            'roleUserCounts'  => $roleUserCounts,
            'rolePermCounts'  => $rolePermCounts,
            'groups'          => $groups,
            'totalUsers'      => $totalUsers,
            'usersWithPerms'  => $usersWithPerms,
            'totalNamedPerms' => $totalNamedPerms,
            'totalRoles'      => $totalRoles,
        ]);
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — جلب صلاحيات مستخدم معيّن
     * ═══════════════════════════════════════════════════════════ */
    public function actionGetUserPermissions($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $db = Yii::$app->db;

        /* جلب الصلاحيات المُسندة مباشرة (مسمّاة فقط) */
        $assigned = $db->createCommand("
            SELECT item_name FROM {{%auth_assignment}}
            WHERE user_id = :uid AND item_name NOT LIKE '/%'
        ", [':uid' => $id])->queryColumn();

        /* جلب بيانات المستخدم */
        $user = $db->createCommand("
            SELECT id, username, email, avatar FROM {{%user}} WHERE id = :uid
        ", [':uid' => $id])->queryOne();

        return [
            'success'     => true,
            'user'        => $user,
            'permissions' => $assigned,
        ];
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — حفظ صلاحيات مستخدم
     * ═══════════════════════════════════════════════════════════ */
    public function actionSaveUserPermissions($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;
        $request = Yii::$app->request;

        /* دعم الـ id من GET أو POST */
        if ($id === null) {
            $id = $request->post('id');
        }
        if (empty($id)) {
            return ['success' => false, 'message' => 'معرّف المستخدم مطلوب'];
        }

        $newPermissions = $request->post('permissions', []);
        if (!is_array($newPermissions)) {
            $newPermissions = [];
        }

        /* ── جلب الصلاحيات الحالية (المسمّاة فقط) ── */
        $db = Yii::$app->db;
        $currentPerms = $db->createCommand("
            SELECT item_name FROM {{%auth_assignment}}
            WHERE user_id = :uid AND item_name NOT LIKE '/%'
        ", [':uid' => $id])->queryColumn();

        $toAdd    = array_diff($newPermissions, $currentPerms);
        $toRemove = array_diff($currentPerms, $newPermissions);

        $transaction = $db->beginTransaction();
        try {
            /* إزالة الصلاحيات المحذوفة */
            foreach ($toRemove as $permName) {
                $perm = $auth->getPermission($permName);
                if ($perm) {
                    $auth->revoke($perm, $id);
                }
            }

            /* إضافة الصلاحيات الجديدة */
            foreach ($toAdd as $permName) {
                $perm = $auth->getPermission($permName);
                if ($perm) {
                    /* تجنب التكرار */
                    $exists = $db->createCommand("
                        SELECT COUNT(*) FROM {{%auth_assignment}}
                        WHERE item_name = :item AND user_id = :uid
                    ", [':item' => $permName, ':uid' => $id])->queryScalar();
                    if (!$exists) {
                        $auth->assign($perm, $id);
                    }
                }
            }

            $transaction->commit();

            /* مسح كاش RBAC */
            $auth->invalidateCache();

            /* عدد الصلاحيات الجديد */
            $newCount = $db->createCommand("
                SELECT COUNT(*) FROM {{%auth_assignment}}
                WHERE user_id = :uid AND item_name NOT LIKE '/%'
            ", [':uid' => $id])->queryScalar();

            return [
                'success'  => true,
                'message'  => 'تم حفظ الصلاحيات بنجاح',
                'added'    => count($toAdd),
                'removed'  => count($toRemove),
                'newCount' => (int) $newCount,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — إنشاء / تعديل دور
     * ═══════════════════════════════════════════════════════════ */
    public function actionSaveRole()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth    = Yii::$app->authManager;
        $request = Yii::$app->request;

        $name        = trim($request->post('name', ''));
        $description = trim($request->post('description', ''));
        $permissions  = $request->post('permissions', []);
        $originalName = $request->post('originalName', '');

        if (empty($name)) {
            return ['success' => false, 'message' => 'اسم الدور مطلوب'];
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            /* ── تعديل دور موجود ── */
            if (!empty($originalName) && $auth->getRole($originalName)) {
                $role = $auth->getRole($originalName);
                $role->name = $name;
                $role->description = $description;
                $auth->update($originalName, $role);

                /* حذف الأبناء الحاليين */
                $auth->removeChildren($role);
            } else {
                /* ── إنشاء دور جديد ── */
                if ($auth->getRole($name)) {
                    return ['success' => false, 'message' => 'يوجد دور بهذا الاسم مسبقاً'];
                }
                $role = $auth->createRole($name);
                $role->description = $description;
                $auth->add($role);
            }

            /* ── ربط الصلاحيات بالدور ── */
            foreach ($permissions as $permName) {
                $perm = $auth->getPermission($permName);
                if ($perm) {
                    $auth->addChild($role, $perm);
                }
            }

            $transaction->commit();
            $auth->invalidateCache();

            return [
                'success' => true,
                'message' => 'تم حفظ الدور بنجاح',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — حذف دور
     * ═══════════════════════════════════════════════════════════ */
    public function actionDeleteRole()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;
        $name = Yii::$app->request->post('name', '');

        $role = $auth->getRole($name);
        if (!$role) {
            return ['success' => false, 'message' => 'الدور غير موجود'];
        }

        try {
            $auth->remove($role);
            $auth->invalidateCache();
            return ['success' => true, 'message' => 'تم حذف الدور بنجاح'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — تطبيق دور على مستخدم (إضافة كل صلاحيات الدور)
     * ═══════════════════════════════════════════════════════════ */
    public function actionApplyRoleToUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth    = Yii::$app->authManager;
        $db      = Yii::$app->db;
        $request = Yii::$app->request;

        $roleName = $request->post('role', '');
        $userId   = $request->post('userId', '');

        $role = $auth->getRole($roleName);
        if (!$role) {
            return ['success' => false, 'message' => 'الدور غير موجود'];
        }

        /* جلب صلاحيات الدور */
        $rolePerms = $auth->getChildren($roleName);
        $currentPerms = $db->createCommand("
            SELECT item_name FROM {{%auth_assignment}}
            WHERE user_id = :uid AND item_name NOT LIKE '/%'
        ", [':uid' => $userId])->queryColumn();

        $added = 0;
        $transaction = $db->beginTransaction();
        try {
            foreach ($rolePerms as $child) {
                if (!in_array($child->name, $currentPerms)) {
                    $perm = $auth->getPermission($child->name);
                    if ($perm) {
                        $auth->assign($perm, $userId);
                        $added++;
                    }
                }
            }
            $transaction->commit();
            $auth->invalidateCache();

            return [
                'success' => true,
                'message' => "تم تطبيق الدور «{$roleName}» بنجاح — أُضيفت {$added} صلاحية جديدة",
                'added'   => $added,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — نسخ صلاحيات مستخدم إلى آخر
     * ═══════════════════════════════════════════════════════════ */
    public function actionClonePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth    = Yii::$app->authManager;
        $db      = Yii::$app->db;
        $request = Yii::$app->request;

        $fromUserId = $request->post('fromUserId', '');
        $toUserId   = $request->post('toUserId', '');

        if (empty($fromUserId) || empty($toUserId)) {
            return ['success' => false, 'message' => 'بيانات غير مكتملة'];
        }

        /* جلب صلاحيات المستخدم المصدر (المسمّاة) */
        $sourcePerms = $db->createCommand("
            SELECT item_name FROM {{%auth_assignment}}
            WHERE user_id = :uid AND item_name NOT LIKE '/%'
        ", [':uid' => $fromUserId])->queryColumn();

        /* جلب صلاحيات المستخدم الهدف الحالية */
        $targetPerms = $db->createCommand("
            SELECT item_name FROM {{%auth_assignment}}
            WHERE user_id = :uid AND item_name NOT LIKE '/%'
        ", [':uid' => $toUserId])->queryColumn();

        $toAdd = array_diff($sourcePerms, $targetPerms);
        $added = 0;
        $transaction = $db->beginTransaction();
        try {
            foreach ($toAdd as $permName) {
                $perm = $auth->getPermission($permName);
                if ($perm) {
                    $auth->assign($perm, $toUserId);
                    $added++;
                }
            }
            $transaction->commit();
            $auth->invalidateCache();

            return [
                'success' => true,
                'message' => "تم نسخ الصلاحيات بنجاح — أُضيفت {$added} صلاحية جديدة",
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — سحب جميع صلاحيات مستخدم
     * ═══════════════════════════════════════════════════════════ */
    public function actionRevokeAll()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;
        $id   = Yii::$app->request->post('userId', '');

        if (empty($id)) {
            return ['success' => false, 'message' => 'معرّف المستخدم مطلوب'];
        }

        try {
            $auth->revokeAll($id);
            $auth->invalidateCache();
            return ['success' => true, 'message' => 'تم سحب جميع الصلاحيات'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — تعطيل / تفعيل مستخدم
     * ═══════════════════════════════════════════════════════════ */
    public function actionToggleUserStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $db     = Yii::$app->db;
        $userId = Yii::$app->request->post('userId', '');

        if (empty($userId)) {
            return ['success' => false, 'message' => 'معرّف المستخدم مطلوب'];
        }

        /* منع تعطيل النفس */
        if ($userId == Yii::$app->user->id) {
            return ['success' => false, 'message' => 'لا يمكنك تعطيل حسابك الشخصي'];
        }

        try {
            $user = $db->createCommand("SELECT id, employee_type, blocked_at FROM {{%user}} WHERE id = :id", [':id' => $userId])->queryOne();
            if (!$user) {
                return ['success' => false, 'message' => 'المستخدم غير موجود'];
            }

            $isActive = ($user['employee_type'] === 'Active' && empty($user['blocked_at']));

            if ($isActive) {
                /* ── تعطيل المستخدم ── */
                $db->createCommand()->update('{{%user}}', [
                    'employee_type' => 'Suspended',
                    'blocked_at'    => time(),
                ], ['id' => $userId])->execute();

                return [
                    'success'   => true,
                    'message'   => 'تم تعطيل المستخدم بنجاح',
                    'newStatus' => 'Suspended',
                ];
            } else {
                /* ── تفعيل المستخدم ── */
                $db->createCommand()->update('{{%user}}', [
                    'employee_type' => 'Active',
                    'blocked_at'    => null,
                ], ['id' => $userId])->execute();

                return [
                    'success'   => true,
                    'message'   => 'تم تفعيل المستخدم بنجاح',
                    'newStatus' => 'Active',
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — إنشاء الأدوار الافتراضية مع صلاحياتها
     * ═══════════════════════════════════════════════════════════ */
    public function actionSeedRoles()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $rolesData = self::getDefaultRoles();

        $created = 0;
        $skipped = 0;
        $errors  = [];

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            foreach ($rolesData as $roleName => $roleInfo) {
                /* تخطي إذا الدور موجود */
                if ($auth->getRole($roleName)) {
                    $skipped++;
                    continue;
                }

                $role = $auth->createRole($roleName);
                $role->description = $roleInfo['description'] ?? '';
                $auth->add($role);

                /* ربط الصلاحيات */
                foreach ($roleInfo['permissions'] as $permName) {
                    $perm = $auth->getPermission($permName);
                    if ($perm) {
                        try {
                            $auth->addChild($role, $perm);
                        } catch (\Exception $e) {
                            /* تجاهل الأخطاء التكرارية */
                        }
                    }
                }

                $created++;
            }

            $transaction->commit();
            $auth->invalidateCache();

            return [
                'success' => true,
                'message' => "تم إنشاء {$created} دور جديد" . ($skipped ? " — {$skipped} موجود مسبقاً" : ''),
                'created' => $created,
                'skipped' => $skipped,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — جلب صلاحيات دور معيّن
     * ═══════════════════════════════════════════════════════════ */
    public function actionGetRolePermissions($name)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $role = $auth->getRole($name);
        if (!$role) {
            return ['success' => false, 'message' => 'الدور غير موجود'];
        }

        $permissions = [];
        $children = $auth->getChildren($name);
        foreach ($children as $child) {
            $permissions[] = $child->name;
        }

        return [
            'success' => true,
            'role' => [
                'name'        => $role->name,
                'description' => $role->description,
            ],
            'permissions' => $permissions,
        ];
    }


    /* ═══════════════════════════════════════════════════════════
     *  AJAX — ضمان وجود جميع الصلاحيات المسمّاة في قاعدة البيانات
     * ═══════════════════════════════════════════════════════════ */
    public function actionEnsurePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        /* جمع كل الصلاحيات من المجموعات */
        $groups = self::getPermissionGroups();
        $allPerms = [];
        foreach ($groups as $group) {
            foreach ($group['permissions'] as $perm) {
                $allPerms[] = $perm;
            }
        }
        $allPerms = array_unique($allPerms);

        $created = 0;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            /* تعطيل STRICT_MODE مؤقتاً لتجنب مشاكل ترميز العربية */
            $db->createCommand("SET SESSION sql_mode=''")->execute();

            foreach ($allPerms as $permName) {
                if (!$auth->getPermission($permName)) {
                    $perm = $auth->createPermission($permName);
                    $perm->description = $permName;
                    $auth->add($perm);
                    $created++;
                }
            }

            $transaction->commit();
            $auth->invalidateCache();

            $totalNamed = (int)$db->createCommand("
                SELECT COUNT(*) FROM {{%auth_item}}
                WHERE type = 2 AND name NOT LIKE '/%' AND name NOT LIKE '%/*'
            ")->queryScalar();

            return [
                'success' => true,
                'message' => $created > 0
                    ? "تم إنشاء {$created} صلاحية جديدة — الإجمالي: {$totalNamed}"
                    : "جميع الصلاحيات موجودة — الإجمالي: {$totalNamed}",
                'created' => $created,
                'total'   => $totalNamed,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }


    /* ─── تعريف الأدوار الافتراضية وصلاحياتها ─── */
    public static function getDefaultRoles()
    {
        return [
            /* ══════════════════════════════════════════════
             *  مدير النظام — وصول كامل لجميع أقسام النظام
             * ══════════════════════════════════════════════ */
            'مدير النظام' => [
                'description' => 'مدير النظام — وصول كامل لجميع الأقسام والعمليات',
                'permissions' => [
                    /* المالية */
                    'الحركات المالية', 'الحركات المالية: تعديل', 'الحركات المالية: حذف',
                    'الحركات المالية: استيراد', 'الحركات المالية: ترحيل',
                    'الحركات المالية لتصدير ونقل البيانات',
                    'الدخل', 'الدخل: تعديل', 'الدخل: حذف', 'الدخل: ارجاع',
                    'المصاريف', 'المصاريف: تعديل', 'المصاريف: حذف', 'المصاريف: ارجاع',
                    'فئات المصايف', 'التسويات الماليه',
                    /* العقود */
                    'العقود', 'المتابعة', 'تقرير المتابعة',
                    'التحويل إلى الدائره القانونية', 'الحسميات', 'مدير التحصيل',
                    /* العملاء */
                    'العملاء', 'المستثمرين',
                    /* القضاء */
                    'القضاء', 'الإجراءات القضائية', 'إجراءات العملاء القضائية',
                    'المحاكم', 'المحامون', 'انواع القضايا', 'الموطن المختار', 'التقارير القضائية',
                    /* الموارد البشرية */
                    'الموظفين', 'الوظائف', 'العطل', 'سياسات الاجازات',
                    'أنواع الإجازات', 'أيام العمل', 'طلب إجازة', 'اشعارات الموظفين',
                    /* المخزون */
                    'عناصر المخزون', 'مواقع المخزون', 'موردي المخزون',
                    'كمية عناصر المخزون', 'فواتير المخزون',
                    /* التقارير */
                    'التقارير', 'تقارير المتابعات', 'تقارير مجموع دفعات العملاء',
                    /* النظام */
                    'الصلاحيات', 'القواعد', 'الجذر', 'اسناد الصلاحيات  للموظفين', 'الاشعارات',
                    /* الإعدادات */
                    'الحالات', 'حالات الوثائق', 'الاقارب', 'الجنسيه', 'البنوك',
                    'كيف سمعت عنا', 'المدن', 'طرق الدفع', 'الانفعالات',
                    'طريقة الاتصال', 'رد العميل', 'انواع الوثائق', 'الرسائل',
                    /* الديوان */
                    'الديوان', 'تقارير الديوان',
                    /* أخرى */
                    'حامل الوثيقة', 'الاداره', 'تحديد', 'أرشيف', 'التصدير',
                    'صفحة المدير', 'مدير', 'مدير صورة', 'تحميل الملف',
                    'تحديث مدير الصور', 'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  مستثمر — عرض التقارير والعقود والمستثمرين
             * ══════════════════════════════════════════════ */
            'مستثمر' => [
                'description' => 'مستثمر — عرض التقارير المالية والعقود ومتابعة الاستثمارات',
                'permissions' => [
                    'المستثمرين',
                    'العقود',
                    'التقارير',
                    'تقارير المتابعات',
                    'تقارير مجموع دفعات العملاء',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  محاسب — إدارة مالية كاملة
             * ══════════════════════════════════════════════ */
            'محاسب' => [
                'description' => 'محاسب — إدارة الحركات المالية والدخل والمصاريف والتسويات',
                'permissions' => [
                    /* المالية — وصول كامل */
                    'الحركات المالية', 'الحركات المالية: تعديل', 'الحركات المالية: حذف',
                    'الحركات المالية: استيراد', 'الحركات المالية: ترحيل',
                    'الحركات المالية لتصدير ونقل البيانات',
                    'الدخل', 'الدخل: تعديل', 'الدخل: حذف', 'الدخل: ارجاع',
                    'المصاريف', 'المصاريف: تعديل', 'المصاريف: حذف', 'المصاريف: ارجاع',
                    'فئات المصايف', 'التسويات الماليه',
                    /* العقود — عرض فقط */
                    'العقود',
                    /* العملاء — عرض */
                    'العملاء', 'المستثمرين',
                    /* التقارير */
                    'التقارير', 'تقارير مجموع دفعات العملاء',
                    /* الحسميات */
                    'الحسميات',
                    /* الاشعارات */
                    'الاشعارات',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  موظفة متابعه — متابعة العقود والعملاء
             * ══════════════════════════════════════════════ */
            'موظفة متابعه' => [
                'description' => 'موظفة متابعة — متابعة العقود والأقساط والتواصل مع العملاء',
                'permissions' => [
                    'العقود', 'المتابعة', 'تقرير المتابعة',
                    'العملاء',
                    'الحسميات', 'مدير التحصيل',
                    'التقارير', 'تقارير المتابعات', 'تقارير مجموع دفعات العملاء',
                    'الاشعارات', 'اشعارات الموظفين',
                    'رد العميل', 'طريقة الاتصال', 'الانفعالات',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  محامي — الشؤون القانونية والقضائية
             * ══════════════════════════════════════════════ */
            'محامي' => [
                'description' => 'محامي — إدارة القضايا والإجراءات القانونية والتحويلات للدائرة القانونية',
                'permissions' => [
                    /* القضاء — وصول كامل */
                    'القضاء', 'الإجراءات القضائية', 'إجراءات العملاء القضائية',
                    'المحاكم', 'المحامون', 'انواع القضايا',
                    'الموطن المختار', 'التقارير القضائية',
                    /* العقود والتحويل */
                    'العقود', 'التحويل إلى الدائره القانونية',
                    /* العملاء — عرض */
                    'العملاء',
                    /* التقارير */
                    'التقارير', 'تقارير المتابعات',
                    'الاشعارات',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  موظف مبيعات — البيع والعملاء والمخزون
             * ══════════════════════════════════════════════ */
            'موظف مبيعات' => [
                'description' => 'موظف مبيعات — إدارة العملاء والعقود واعتماد عناصر المخزون',
                'permissions' => [
                    'العملاء', 'العقود',
                    'المتابعة', 'تقرير المتابعة',
                    /* المخزون — عرض واعتماد */
                    'عناصر المخزون', 'كمية عناصر المخزون', 'فواتير المخزون',
                    'الاشعارات', 'اشعارات الموظفين',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  مدير مبيعات — إشراف كامل على المبيعات
             * ══════════════════════════════════════════════ */
            'مدير مبيعات' => [
                'description' => 'مدير مبيعات — إشراف كامل على المبيعات والمخزون والتقارير مع صلاحية التأكيد النهائي',
                'permissions' => [
                    'العملاء', 'المستثمرين',
                    'العقود', 'المتابعة', 'تقرير المتابعة',
                    'الحسميات', 'مدير التحصيل',
                    /* المخزون — وصول كامل */
                    'عناصر المخزون', 'مواقع المخزون', 'موردي المخزون',
                    'كمية عناصر المخزون', 'فواتير المخزون',
                    /* التقارير */
                    'التقارير', 'تقارير المتابعات', 'تقارير مجموع دفعات العملاء',
                    'الاشعارات', 'اشعارات الموظفين',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  موزع أجهزة (مورد) — إدخال سيريالات المخزون
             * ══════════════════════════════════════════════ */
            'موزع أجهزة' => [
                'description' => 'موزع أجهزة / مورد — إدخال سيريالات الأجهزة في المخزون لاعتمادها من موظفة المبيعات ثم التأكيد من مدير المبيعات',
                'permissions' => [
                    'عناصر المخزون', 'كمية عناصر المخزون',
                    'مواقع المخزون', 'موردي المخزون', 'فواتير المخزون',
                    'الاشعارات',
                    'الملف الشخصي',
                ],
            ],

            /* ══════════════════════════════════════════════
             *  مندوب محكمة — عرض القضايا وإضافة إجراءات
             * ══════════════════════════════════════════════ */
            'مندوب محكمة' => [
                'description' => 'مندوب محكمة — عرض القضايا وإضافة الإجراءات القضائية وعرض بيانات العملاء',
                'permissions' => [
                    'القضاء', 'اجراءات العملاء القضائيه',
                    'العملاء',
                    'العقود', 'المتابعة',
                    'الاشعارات',
                    'الملف الشخصي',
                ],
            ],
        ];
    }
}
