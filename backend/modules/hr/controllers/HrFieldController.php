<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrFieldTask;
use backend\modules\hr\models\HrFieldSession;
use backend\modules\hr\models\HrFieldEvent;
use backend\modules\hr\models\HrFieldConfig;
use backend\modules\hr\models\HrLocationPoint;
use common\models\User;

/**
 * HrFieldController — Field operations management
 */
class HrFieldController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Field tasks board (Kanban-style view).
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $filterAssignee = $request->get('assignee', '');
        $filterPriority = $request->get('priority', '');
        $filterType = $request->get('task_type', '');

        // Group tasks by status for Kanban board
        $statuses = ['assigned', 'accepted', 'en_route', 'arrived', 'in_progress', 'completed', 'failed', 'cancelled'];
        $kanbanData = [];

        foreach ($statuses as $status) {
            $query = HrFieldTask::find()
                ->where(['status' => $status, 'is_deleted' => 0]);

            if (!empty($filterAssignee)) {
                $query->andWhere(['assigned_to' => $filterAssignee]);
            }
            if (!empty($filterPriority)) {
                $query->andWhere(['priority' => $filterPriority]);
            }
            if (!empty($filterType)) {
                $query->andWhere(['task_type' => $filterType]);
            }

            $kanbanData[$status] = $query->orderBy(['due_date' => SORT_ASC, 'priority' => SORT_DESC])->all();
        }

        // Field staff list for filter
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        // Today's task statistics
        $todayStats = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN ('assigned','accepted','en_route','arrived','in_progress') THEN 1 ELSE 0 END) as active
            FROM {{%hr_field_task}}
            WHERE DATE(FROM_UNIXTIME(created_at)) = CURDATE()
              AND is_deleted = 0
        ")->queryOne();

        return $this->render('index', [
            'kanbanData' => $kanbanData,
            'statuses' => $statuses,
            'fieldStaff' => $fieldStaff,
            'filterAssignee' => $filterAssignee,
            'filterPriority' => $filterPriority,
            'filterType' => $filterType,
            'todayStats' => $todayStats,
        ]);
    }

    /**
     * Live map showing field staff locations.
     *
     * @return string
     */
    public function actionMap()
    {
        // Get active field sessions with latest location
        $activeSessions = (new Query())
            ->select([
                'fs.id as session_id',
                'fs.user_id',
                'fs.started_at',
                'u.username',
                'u.name',
                'u.avatar',
            ])
            ->from('{{%hr_field_session}} fs')
            ->innerJoin('{{%user}} u', 'u.id = fs.user_id')
            ->where(['fs.status' => 'active'])
            ->all();

        $staffLocations = [];
        foreach ($activeSessions as $session) {
            // Get latest location point for this session
            $lastPoint = (new Query())
                ->select(['latitude', 'longitude', 'accuracy', 'speed', 'captured_at', 'battery_level'])
                ->from('{{%hr_location_point}}')
                ->where(['session_id' => $session['session_id']])
                ->orderBy(['captured_at' => SORT_DESC])
                ->limit(1)
                ->one();

            // Get current task (if any)
            $currentTask = (new Query())
                ->select(['id', 'title', 'status', 'task_type', 'target_address'])
                ->from('{{%hr_field_task}}')
                ->where([
                    'assigned_to' => $session['user_id'],
                    'is_deleted' => 0,
                ])
                ->andWhere(['in', 'status', ['accepted', 'en_route', 'arrived', 'in_progress']])
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $staffLocations[] = [
                'session' => $session,
                'lastPoint' => $lastPoint,
                'currentTask' => $currentTask,
            ];
        }

        // Active sessions count
        $activeSessionCount = count($activeSessions);

        // Tasks in progress today
        $tasksInProgress = (int) Yii::$app->db->createCommand("
            SELECT COUNT(*)
            FROM {{%hr_field_task}}
            WHERE status IN ('accepted','en_route','arrived','in_progress')
              AND is_deleted = 0
        ")->queryScalar();

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'staffLocations' => $staffLocations,
                'activeSessionCount' => $activeSessionCount,
                'tasksInProgress' => $tasksInProgress,
            ];
        }

        return $this->render('map', [
            'staffLocations' => $staffLocations,
            'activeSessionCount' => $activeSessionCount,
            'tasksInProgress' => $tasksInProgress,
        ]);
    }

    /**
     * Create field task.
     *
     * @return string|Response
     */
    public function actionTaskCreate()
    {
        $model = new HrFieldTask();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->status = 'assigned';
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء المهمة الميدانية: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء المهمة الميدانية بنجاح.');

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم إنشاء المهمة بنجاح.', 'id' => $model->id];
                }

                return $this->redirect(['task-view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Field staff list
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مهمة ميدانية',
                'content' => $this->renderAjax('task-form', [
                    'model' => $model,
                    'fieldStaff' => $fieldStaff,
                ]),
            ];
        }

        return $this->render('task-form', [
            'model' => $model,
            'fieldStaff' => $fieldStaff,
        ]);
    }

    /**
     * Update field task.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionTaskUpdate($id)
    {
        $model = $this->findTaskModel($id);
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            // Track status transition timestamps
            $newStatus = $model->status;
            if ($newStatus === 'accepted' && !$model->accepted_at) {
                $model->accepted_at = date('Y-m-d H:i:s');
            } elseif ($newStatus === 'en_route' && !$model->en_route_at) {
                $model->en_route_at = date('Y-m-d H:i:s');
            } elseif ($newStatus === 'arrived' && !$model->arrived_at) {
                $model->arrived_at = date('Y-m-d H:i:s');
            } elseif (in_array($newStatus, ['completed', 'failed']) && !$model->completed_at) {
                $model->completed_at = date('Y-m-d H:i:s');
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث المهمة: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();

                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'تم تحديث المهمة بنجاح.'];
                }

                Yii::$app->session->setFlash('success', 'تم تحديث المهمة بنجاح.');
                return $this->redirect(['task-view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                if ($request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => $e->getMessage()];
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Field staff list
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل المهمة الميدانية',
                'content' => $this->renderAjax('task-form', [
                    'model' => $model,
                    'fieldStaff' => $fieldStaff,
                ]),
            ];
        }

        return $this->render('task-form', [
            'model' => $model,
            'fieldStaff' => $fieldStaff,
        ]);
    }

    /**
     * View task detail.
     *
     * @param int $id
     * @return string
     */
    public function actionTaskView($id)
    {
        $model = $this->findTaskModel($id);

        // Get related field events for this task
        $events = HrFieldEvent::find()
            ->where(['task_id' => $id, 'is_deleted' => 0])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        // Assigned staff info
        $staff = User::findOne($model->assigned_to);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تفاصيل المهمة — ' . $model->title,
                'content' => $this->renderAjax('task-view', [
                    'model' => $model,
                    'events' => $events,
                    'staff' => $staff,
                ]),
            ];
        }

        return $this->render('task-view', [
            'model' => $model,
            'events' => $events,
            'staff' => $staff,
        ]);
    }

    /**
     * List field sessions.
     *
     * @return string
     */
    public function actionSessions()
    {
        $request = Yii::$app->request;
        $filterUser = $request->get('user_id', '');
        $filterDate = $request->get('date', '');
        $filterStatus = $request->get('status', '');

        $query = HrFieldSession::find()
            ->where(['!=', 'status', '']);

        if (!empty($filterUser)) {
            $query->andWhere(['user_id' => $filterUser]);
        }
        if (!empty($filterDate)) {
            $query->andWhere(['like', 'started_at', $filterDate]);
        }
        if (!empty($filterStatus)) {
            $query->andWhere(['status' => $filterStatus]);
        }

        $query->orderBy(['started_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        // Field staff list for filter
        $fieldStaff = ArrayHelper::map(
            (new Query())
                ->select(['u.id', 'u.username', 'u.name'])
                ->from('{{%user}} u')
                ->innerJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_field_staff = 1 AND ext.is_deleted = 0')
                ->where(['IS', 'u.blocked_at', null])
                ->all(),
            'id',
            function ($row) {
                return $row['name'] ?: $row['username'];
            }
        );

        return $this->render('sessions', [
            'dataProvider' => $dataProvider,
            'fieldStaff' => $fieldStaff,
            'filterUser' => $filterUser,
            'filterDate' => $filterDate,
            'filterStatus' => $filterStatus,
        ]);
    }

    /**
     * View session with route playback.
     *
     * @param int $id Session ID
     * @return string
     */
    public function actionSessionView($id)
    {
        $session = HrFieldSession::find()
            ->where(['id' => $id])
            ->one();

        if ($session === null) {
            throw new NotFoundHttpException('جلسة العمل الميدانية غير موجودة.');
        }

        // Get all location points for this session
        $locationPoints = (new Query())
            ->select(['latitude', 'longitude', 'accuracy', 'speed', 'bearing', 'captured_at', 'battery_level', 'is_mock'])
            ->from('{{%hr_location_point}}')
            ->where(['session_id' => $id])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        // Get events during this session
        $events = HrFieldEvent::find()
            ->where(['session_id' => $id, 'is_deleted' => 0])
            ->orderBy(['captured_at' => SORT_ASC])
            ->all();

        $staff = User::findOne($session->user_id);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'session' => $session->attributes,
                'locationPoints' => $locationPoints,
                'events' => array_map(function ($e) {
                    return $e->attributes;
                }, $events),
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->name ?: $staff->username,
                ],
            ];
        }

        return $this->render('session-view', [
            'session' => $session,
            'locationPoints' => $locationPoints,
            'events' => $events,
            'staff' => $staff,
        ]);
    }

    /**
     * Field tracking configuration.
     *
     * @return string|Response
     */
    public function actionConfig()
    {
        $model = HrFieldConfig::find()->one();

        if ($model === null) {
            $model = new HrFieldConfig();
            $model->tracking_mode = 'on_duty';
            $model->location_interval_seconds = 120;
            $model->min_accuracy_meters = 50;
            $model->retention_days = 90;
            $model->require_consent = 1;
            $model->allow_offline = 1;
            $model->geofence_enabled = 1;
            $model->photo_required_on_arrival = 0;
            $model->spoofing_detection = 1;
        }

        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            if ($model->isNewRecord) {
                $model->created_at = time();
                $model->created_by = Yii::$app->user->id;
            }
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ إعدادات التتبع: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم حفظ إعدادات التتبع الميداني بنجاح.');
                return $this->redirect(['config']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('config', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the HrFieldTask model.
     *
     * @param int $id
     * @return HrFieldTask
     * @throws NotFoundHttpException
     */
    protected function findTaskModel($id)
    {
        $model = HrFieldTask::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('المهمة الميدانية المطلوبة غير موجودة.');
    }
}
