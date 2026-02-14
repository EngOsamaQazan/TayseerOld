<?php

namespace backend\modules\jobs\controllers;

use Yii;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsSearch;
use backend\modules\jobs\models\JobsPhone;
use backend\modules\jobs\models\JobsWorkingHours;
use backend\modules\jobs\models\JobsRating;
use backend\modules\jobs\models\JobsType;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * JobsController implements the CRUD actions for Jobs model.
 */
class JobsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                    'delete-phone' => ['post'],
                    'delete-rating' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Jobs models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new JobsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Jobs model with all related data.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $phones = $model->getPhones()->all();
        $workingHours = $model->getWorkingHours()->all();
        $ratings = $model->getRatings()->all();

        return $this->render('view', [
            'model' => $model,
            'phones' => $phones,
            'workingHours' => $workingHours,
            'ratings' => $ratings,
        ]);
    }

    /**
     * Creates a new Jobs model.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Jobs();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Save working hours
            $this->saveWorkingHours($model);

            // Update cache
            $this->updateJobsCache();

            Yii::$app->session->setFlash('success', 'تم إنشاء جهة العمل بنجاح');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Jobs model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Save working hours
            $this->saveWorkingHours($model);

            // Update cache
            $this->updateJobsCache();

            Yii::$app->session->setFlash('success', 'تم تحديث بيانات جهة العمل بنجاح');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Soft deletes an existing Jobs model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->softDelete();

        $this->updateJobsCache();

        Yii::$app->session->setFlash('success', 'تم حذف جهة العمل');
        return $this->redirect(['index']);
    }

    // ========================
    // Phone Actions (AJAX)
    // ========================

    /**
     * Add a phone number to a job (AJAX)
     * @param integer $jobId
     * @return array
     */
    public function actionAddPhone($jobId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new JobsPhone();
        $model->job_id = $jobId;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ['success' => true, 'message' => 'تم إضافة رقم الهاتف بنجاح'];
        }

        return ['success' => false, 'errors' => $model->errors];
    }

    /**
     * Delete a phone number (AJAX soft delete)
     * @param integer $id
     * @return array
     */
    public function actionDeletePhone($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = JobsPhone::findOne($id);
        if ($model) {
            $model->softDelete();
            return ['success' => true, 'message' => 'تم حذف رقم الهاتف'];
        }

        return ['success' => false, 'message' => 'لم يتم العثور على الرقم'];
    }

    // ========================
    // Rating Actions (AJAX)
    // ========================

    /**
     * Add a rating to a job (AJAX)
     * @param integer $jobId
     * @return array
     */
    public function actionAddRating($jobId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new JobsRating();
        $model->job_id = $jobId;
        $model->rated_by = Yii::$app->user->id;
        $model->rated_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
        }

        return ['success' => false, 'errors' => $model->errors];
    }

    /**
     * Delete a rating (AJAX soft delete)
     * @param integer $id
     * @return array
     */
    public function actionDeleteRating($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = JobsRating::findOne($id);
        if ($model) {
            $model->softDelete();
            return ['success' => true, 'message' => 'تم حذف التقييم'];
        }

        return ['success' => false, 'message' => 'لم يتم العثور على التقييم'];
    }

    // ========================
    // Working Hours Actions
    // ========================

    /**
     * Save working hours from POST data
     * @param Jobs $model
     */
    protected function saveWorkingHours($model)
    {
        $hoursData = Yii::$app->request->post('WorkingHours', []);
        if (empty($hoursData)) {
            return;
        }

        // Delete existing hours for this job
        JobsWorkingHours::deleteAll(['job_id' => $model->id]);

        foreach ($hoursData as $dayData) {
            if (!isset($dayData['day_of_week'])) {
                continue;
            }
            $hour = new JobsWorkingHours();
            $hour->job_id = $model->id;
            $hour->day_of_week = $dayData['day_of_week'];
            $hour->opening_time = $dayData['opening_time'] ?? null;
            $hour->closing_time = $dayData['closing_time'] ?? null;
            $hour->is_closed = !empty($dayData['is_closed']) ? 1 : 0;
            $hour->notes = $dayData['notes'] ?? null;
            $hour->save();
        }
    }

    // ========================
    // Helper Methods
    // ========================

    /**
     * Update jobs cache
     */
    protected function updateJobsCache()
    {
        if (isset(Yii::$app->params['key_jobs']) && isset(Yii::$app->params['jobs_query'])) {
            Yii::$app->cache->set(
                Yii::$app->params['key_jobs'],
                Yii::$app->db->createCommand(Yii::$app->params['jobs_query'])->queryAll(),
                Yii::$app->params['time_duration']
            );
        }
        if (isset(Yii::$app->params['key_job_title']) && isset(Yii::$app->params['job_title_query'])) {
            Yii::$app->cache->set(
                Yii::$app->params['key_job_title'],
                Yii::$app->db->createCommand(Yii::$app->params['job_title_query'])->queryAll(),
                Yii::$app->params['time_duration']
            );
        }
    }

    /**
     * Finds the Jobs model based on its primary key value.
     * @param integer $id
     * @return Jobs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Jobs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
