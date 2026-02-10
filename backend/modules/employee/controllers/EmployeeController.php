<?php

namespace backend\modules\employee\controllers;

use backend\modules\employee\models\EmployeeFiles;
use common\models\User;
use http\Url;
use Yii;
use backend\models\Employee;
use backend\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\userLeavePolicy\models\UserLeavePolicy;
use backend\modules\leaveRequest\models\LeaveRequest;
use backend\modules\leavePolicy\models\LeavePolicy;
use yii\filters\AccessControl;
use yii\web\UploadedFile;


/**
 * EmployeeController implements the CRUD actions for Employee model.
 */
class EmployeeController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'remove-file', 'view', 'is_read','employee-leave-policy'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Employee models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Employee model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Employee #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Employee model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Employee();

        if ($model->load($request->post())) {
            $model->profile_avatar_file = UploadedFile::getInstances($model, 'profile_avatar_file');
            $model->profile_attachment_files = UploadedFile::getInstances($model, 'profile_attachment_files');
            $model->password_hash = Yii::$app->security->generatePasswordHash($model->password_hash);
            if (!empty($model->profile_avatar_file)) {
                $model->updateProfileAvatar();
            }
            if (!empty($model->profile_attachment_files)) {

                $model->addProfileAttachment();
            }
            $model->save();
            $this->redirect('index');
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id, $imageID = 0)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $model->setScenario('update');


        $employeeAttachments = EmployeeFiles::find()->where(['user_id' => $id, 'type' => EmployeeFiles::TYPE_ATTACHMENT])->all();
        $oldPasswordHash = $model->password_hash;
        if ($model->load($request->post())) {
            if ($model->password_hash != $oldPasswordHash && !empty($model->password_hash)) {
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password_hash);
            }
            $model->password_hash = $oldPasswordHash;
            if ($model->save()) {
                $model->profile_avatar_file = UploadedFile::getInstances($model, 'profile_avatar_file');
                $model->profile_attachment_files = UploadedFile::getInstances($model, 'profile_attachment_files');
                if (!empty($model->profile_avatar_file)) {
                    $model->updateProfileAvatar();
                }
                if (!empty($model->profile_attachment_files)) {

                    $model->addProfileAttachment();
                }
                $this->redirect('index');
            }
        }

        return $this->render('update', [
            'employeeAttachments' => $employeeAttachments,
            'model' => $model,
            'id' => $id
        ]);
    }

    /**
     * Updates an existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEmployeeLeavePolicy($id)
    {
        $request = Yii::$app->request->post('Employee');
        UserLeavePolicy::deleteAll(['user_id' => $id]);
        foreach ($request['leavePolicy'] as $key => $value) {
            $model = new UserLeavePolicy();
            $model->user_id = $id;
            $model->leave_policy_id = $value;
            $model->save();
        }
        return $this->redirect(['update', 'id' => $id]);
    }

    /**
     * Delete an existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete multiple existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /**
     * Finds the Employee model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Employee::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionLeavePolicyRemaining($policy_id)
    {
        $leaveRequestSum = LeaveRequest::find()
            ->where(['leave_policy' => $policy_id, 'created_by' => Yii::$app->user->id])
            ->andWhere(['or',
                ['status' => 'under review'],
                ['status' => 'approved']
            ])
            ->sum('DATEDIFF(`end_at`,`start_at`)');
        $leaveRequestDays = LeavePolicy::find()->select('total_days')->where(['id' => $policy_id])->one();
        return ($leaveRequestDays->total_days -
            $leaveRequestSum);
    }

    public function actionLeavePolicyRemainingWithoutTheUnderReview($policy_id)
    {
        $leaveRequestSum = LeaveRequest::find()
            ->where(['leave_policy' => $policy_id, 'created_by' => Yii::$app->user->id])
            ->andWhere(['=',
                'status', 'approved'
            ])
            ->sum('DATEDIFF(`end_at`,`start_at`)');
        $leaveRequestDays = LeavePolicy::find()->select('total_days')->where(['id' => $policy_id])->one();
        return ($leaveRequestDays->total_days - $leaveRequestSum);
    }

    public function actionRemoveFile()
    {
        $id = Yii::$app->request->post('id');
        EmployeeFiles::deleteAll(['id' => $id]);
    }
}
