<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\SqlDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrEmployeeExtended;
use backend\modules\hr\models\HrEmergencyContact;
use backend\modules\hr\models\HrEmployeeDocument;
use backend\modules\hr\models\HrAttendance;
use backend\modules\hr\models\HrEmployeeSalary;
use common\models\User;

/**
 * HrEmployeeController — Full CRUD for extended employee data
 */
class HrEmployeeController extends Controller
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
     * List employees with search (GridView).
     * Queries os_user joined with os_hr_employee_extended, os_department, os_designation.
     * Supports search by name, employee_code, department, status.
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $searchName = $request->get('search_name', '');
        $searchCode = $request->get('search_code', '');
        $searchDepartment = $request->get('search_department', '');
        $searchStatus = $request->get('search_status', '');

        $query = (new Query())
            ->select([
                'u.id',
                'u.username',
                'u.name',
                'u.email',
                'u.mobile',
                'u.employee_status',
                'u.date_of_hire',
                'u.avatar',
                'ext.employee_code',
                'ext.contract_type',
                'ext.is_field_staff',
                'ext.id as extended_id',
                'd.title as department_name',
                'des.title as designation_name',
            ])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->leftJoin('{{%department}} d', 'd.id = u.department')
            ->leftJoin('{{%designation}} des', 'des.id = u.job_title');

        // Apply search filters
        if (!empty($searchName)) {
            $query->andWhere(['or',
                ['like', 'u.username', $searchName],
                ['like', 'u.name', $searchName],
            ]);
        }
        if (!empty($searchCode)) {
            $query->andWhere(['like', 'ext.employee_code', $searchCode]);
        }
        if (!empty($searchDepartment)) {
            $query->andWhere(['u.department' => $searchDepartment]);
        }
        if (!empty($searchStatus)) {
            $query->andWhere(['u.employee_status' => $searchStatus]);
        }

        $query->andWhere(['not', ['u.confirmed_at' => null]]);
        $query->orderBy(['u.id' => SORT_DESC]);

        $allData = $query->all();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $allData,
            'pagination' => ['pageSize' => 20],
            'key' => 'id',
        ]);

        // Department list for filter dropdown
        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'title'])->from('{{%department}}')->where(['status' => 'active'])->all(),
            'id',
            'title'
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchName' => $searchName,
            'searchCode' => $searchCode,
            'searchDepartment' => $searchDepartment,
            'searchStatus' => $searchStatus,
            'departments' => $departments,
        ]);
    }

    /**
     * View employee profile with tabs (info, documents, emergency contacts,
     * attendance summary, salary, field info).
     *
     * @param int $id User ID
     * @return string
     */
    public function actionView($id)
    {
        $user = User::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('الموظف المطلوب غير موجود.');
        }

        $extended = HrEmployeeExtended::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->one();

        $documents = HrEmployeeDocument::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $emergencyContacts = HrEmergencyContact::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->all();

        // Current month attendance summary
        $currentMonth = date('Y-m');
        $attendanceSummary = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as leave_days,
                SUM(COALESCE(total_hours, 0)) as total_hours,
                SUM(COALESCE(overtime_hours, 0)) as overtime_hours
            FROM {{%hr_attendance}}
            WHERE user_id = :userId
              AND attendance_date LIKE :month
              AND is_deleted = 0
        ", [':userId' => $id, ':month' => $currentMonth . '%'])->queryOne();

        // Salary structure
        $salaryComponents = HrEmployeeSalary::find()
            ->where([
                'user_id' => $id,
                'is_deleted' => 0,
            ])
            ->andWhere(['or',
                ['effective_to' => null],
                ['>=', 'effective_to', date('Y-m-d')],
            ])
            ->orderBy(['effective_from' => SORT_DESC])
            ->all();

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'ملف الموظف — ' . ($user->name ?: $user->username),
                'content' => $this->renderAjax('view', [
                    'user' => $user,
                    'extended' => $extended,
                    'documents' => $documents,
                    'emergencyContacts' => $emergencyContacts,
                    'attendanceSummary' => $attendanceSummary,
                    'salaryComponents' => $salaryComponents,
                ]),
            ];
        }

        return $this->render('view', [
            'user' => $user,
            'extended' => $extended,
            'documents' => $documents,
            'emergencyContacts' => $emergencyContacts,
            'attendanceSummary' => $attendanceSummary,
            'salaryComponents' => $salaryComponents,
        ]);
    }

    /**
     * Create new extended employee record for an existing os_user that
     * doesn't have extended data yet.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrEmployeeExtended();
        $request = Yii::$app->request;

        // Users without extended records
        $usersWithoutExtended = (new Query())
            ->select(['u.id', 'u.username', 'u.name'])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->where(['ext.id' => null])
            ->all();

        $userList = ArrayHelper::map($usersWithoutExtended, 'id', function ($row) {
            return $row['name'] ? $row['name'] . ' (' . $row['username'] . ')' : $row['username'];
        });

        if ($model->load($request->post())) {
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            // Auto-generate employee code if empty
            if (empty($model->employee_code)) {
                $maxCode = (new Query())
                    ->from('{{%hr_employee_extended}}')
                    ->max('id');
                $model->employee_code = 'EMP-' . str_pad(($maxCode ?? 0) + 1, 4, '0', STR_PAD_LEFT);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ بيانات الموظف: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء ملف الموظف الموسع بنجاح.');
                return $this->redirect(['view', 'id' => $model->user_id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء ملف موظف موسع',
                'content' => $this->renderAjax('create', [
                    'model' => $model,
                    'userList' => $userList,
                ]),
            ];
        }

        return $this->render('create', [
            'model' => $model,
            'userList' => $userList,
        ]);
    }

    /**
     * Update employee extended data.
     *
     * @param int $id HrEmployeeExtended ID
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث بيانات الموظف: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث بيانات الموظف بنجاح.');
                return $this->redirect(['view', 'id' => $model->user_id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل بيانات الموظف',
                'content' => $this->renderAjax('update', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Soft delete employee extended record.
     *
     * @param int $id HrEmployeeExtended ID
     * @return Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->is_deleted = 1;
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;
            if (!$model->save(false)) {
                throw new \Exception('فشل حذف سجل الموظف.');
            }
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'تم حذف سجل الموظف بنجاح.');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        return $this->redirect(['index']);
    }

    /**
     * Export employees to CSV.
     *
     * @return void
     */
    public function actionExport()
    {
        $rows = (new Query())
            ->select([
                'u.id',
                'u.username',
                'u.name',
                'u.email',
                'u.mobile',
                'u.employee_status',
                'u.date_of_hire',
                'ext.employee_code',
                'ext.national_id',
                'ext.employment_type',
                'd.name as department_name',
                'des.name as designation_name',
            ])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->leftJoin('{{%department}} d', 'd.id = u.department')
            ->leftJoin('{{%designation}} des', 'des.id = u.job_title')
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        $filename = 'employees_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // BOM for Excel Arabic support
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');

        // Header row
        fputcsv($fp, [
            'ID',
            'اسم المستخدم',
            'الاسم',
            'البريد الإلكتروني',
            'الهاتف',
            'الحالة',
            'تاريخ التوظيف',
            'رقم الموظف',
            'رقم الهوية',
            'نوع التوظيف',
            'القسم',
            'المسمى الوظيفي',
        ]);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['id'],
                $row['username'],
                $row['name'],
                $row['email'],
                $row['mobile'],
                $row['employee_status'],
                $row['date_of_hire'],
                $row['employee_code'],
                $row['national_id'],
                $row['employment_type'],
                $row['department_name'],
                $row['designation_name'],
            ]);
        }

        fclose($fp);
        Yii::$app->end();
    }

    /**
     * Finds the HrEmployeeExtended model based on its primary key.
     *
     * @param int $id
     * @return HrEmployeeExtended
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrEmployeeExtended::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('سجل الموظف المطلوب غير موجود.');
    }
}
