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
use backend\modules\hr\models\HrPayrollRun;
use backend\modules\hr\models\HrPayslip;
use backend\modules\hr\models\HrPayslipLine;
use backend\modules\hr\models\HrSalaryComponent;
use backend\modules\hr\models\HrEmployeeSalary;
use backend\modules\hr\models\HrAttendance;
use backend\modules\hr\models\HrLoan;
use common\models\User;

/**
 * HrPayrollController — Payroll management
 */
class HrPayrollController extends Controller
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
     * List payroll runs.
     *
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrPayrollRun::find()
                ->orderBy(['period_year' => SORT_DESC, 'period_month' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new payroll run (select month/year).
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrPayrollRun();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            // Generate run code
            $model->run_code = 'PAY-' . $model->period_year . '-' . str_pad($model->period_month, 2, '0', STR_PAD_LEFT);
            $model->status = 'draft';

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Check for duplicate payroll run
                $exists = HrPayrollRun::find()
                    ->where([
                        'period_month' => $model->period_month,
                        'period_year' => $model->period_year,
                        'is_deleted' => 0,
                    ])
                    ->andFilterWhere(['branch_id' => $model->branch_id])
                    ->exists();

                if ($exists) {
                    throw new \Exception('توجد مسيرة رواتب مسبقة لهذه الفترة.');
                }

                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء مسيرة الرواتب: ' . implode(', ', $model->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء مسيرة الرواتب بنجاح — ' . $model->run_code);
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مسيرة رواتب جديدة',
                'content' => $this->renderAjax('create', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * View payroll run with all payslips.
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $payslips = HrPayslip::find()
            ->where(['payroll_run_id' => $id, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'مسيرة الرواتب — ' . $model->run_code,
                'content' => $this->renderAjax('view', [
                    'model' => $model,
                    'payslips' => $payslips,
                ]),
            ];
        }

        return $this->render('view', [
            'model' => $model,
            'payslips' => $payslips,
        ]);
    }

    /**
     * Calculate payroll — create payslips for all active employees based on
     * salary structure, attendance, and loans.
     *
     * @param int $id Payroll run ID
     * @return Response
     */
    public function actionCalculate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $run = $this->findModel($id);

        if (!in_array($run->status, ['draft', 'calculated'])) {
            return [
                'success' => false,
                'message' => 'لا يمكن إعادة الحساب — حالة المسيرة: ' . $run->status,
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete existing payslips for this run (recalculation)
            HrPayslipLine::deleteAll(['payslip_id' => (new Query())
                ->select('id')
                ->from('{{%hr_payslip}}')
                ->where(['payroll_run_id' => $id])
            ]);
            HrPayslip::deleteAll(['payroll_run_id' => $id]);

            // Get active employees
            $employees = (new Query())
                ->select(['u.id as user_id'])
                ->from('{{%user}} u')
                ->where(['IS', 'u.blocked_at', null])
                ->andWhere(['u.employee_status' => 'active'])
                ->all();

            $periodStart = $run->period_year . '-' . str_pad($run->period_month, 2, '0', STR_PAD_LEFT) . '-01';
            $periodEnd = date('Y-m-t', strtotime($periodStart));
            $workingDaysInMonth = $this->getWorkingDays($periodStart, $periodEnd);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $employeeCount = 0;

            foreach ($employees as $emp) {
                $userId = $emp['user_id'];

                // Fetch salary components
                $salaryItems = (new Query())
                    ->select(['es.amount', 'sc.component_type', 'sc.code', 'sc.name', 'sc.id as component_id', 'sc.sort_order'])
                    ->from('{{%hr_employee_salary}} es')
                    ->innerJoin('{{%hr_salary_component}} sc', 'sc.id = es.component_id AND sc.is_deleted = 0')
                    ->where([
                        'es.user_id' => $userId,
                        'es.is_deleted' => 0,
                    ])
                    ->andWhere(['<=', 'es.effective_from', $periodEnd])
                    ->andWhere(['or',
                        ['es.effective_to' => null],
                        ['>=', 'es.effective_to', $periodStart],
                    ])
                    ->orderBy(['sc.sort_order' => SORT_ASC])
                    ->all();

                if (empty($salaryItems)) {
                    continue; // Skip employees without salary structure
                }

                // Calculate attendance
                $attendance = Yii::$app->db->createCommand("
                    SELECT
                        COUNT(*) as total_records,
                        SUM(CASE WHEN status IN ('present','late','field_duty') THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as leave_days,
                        SUM(COALESCE(overtime_hours, 0)) as overtime_hours,
                        SUM(COALESCE(late_minutes, 0)) as total_late_minutes
                    FROM {{%hr_attendance}}
                    WHERE user_id = :userId
                      AND attendance_date BETWEEN :start AND :end
                      AND is_deleted = 0
                ", [
                    ':userId' => $userId,
                    ':start' => $periodStart,
                    ':end' => $periodEnd,
                ])->queryOne();

                $presentDays = (float) ($attendance['present_days'] ?? $workingDaysInMonth);
                $absentDays = (float) ($attendance['absent_days'] ?? 0);
                $leaveDays = (float) ($attendance['leave_days'] ?? 0);
                $overtimeHours = (float) ($attendance['overtime_hours'] ?? 0);

                // Calculate earnings and deductions
                $basicSalary = 0;
                $earnings = 0;
                $deductions = 0;
                $payslipLines = [];

                foreach ($salaryItems as $item) {
                    $amount = (float) $item['amount'];

                    if ($item['code'] === 'BASE') {
                        $basicSalary = $amount;
                    }

                    if ($item['component_type'] === 'earning') {
                        $earnings += $amount;
                    } else {
                        $deductions += $amount;
                    }

                    $payslipLines[] = [
                        'component_id' => $item['component_id'],
                        'component_type' => $item['component_type'],
                        'description' => $item['name'],
                        'amount' => $amount,
                        'sort_order' => $item['sort_order'],
                    ];
                }

                // Absence deduction (pro-rata)
                if ($absentDays > 0 && $workingDaysInMonth > 0 && $basicSalary > 0) {
                    $dailyRate = $basicSalary / $workingDaysInMonth;
                    $absenceDeduction = round($dailyRate * $absentDays, 2);
                    $deductions += $absenceDeduction;
                    $payslipLines[] = [
                        'component_id' => 0,
                        'component_type' => 'deduction',
                        'description' => 'خصم غياب (' . $absentDays . ' يوم)',
                        'amount' => $absenceDeduction,
                        'sort_order' => 900,
                    ];
                }

                // Active loan deductions
                $activeLoans = (new Query())
                    ->select(['id', 'monthly_deduction', 'loan_type'])
                    ->from('{{%hr_loan}}')
                    ->where([
                        'user_id' => $userId,
                        'status' => 'active',
                        'is_deleted' => 0,
                    ])
                    ->all();

                foreach ($activeLoans as $loan) {
                    $loanDeduction = (float) $loan['monthly_deduction'];
                    $deductions += $loanDeduction;
                    $loanLabel = $loan['loan_type'] === 'advance' ? 'سلفة' : 'قرض';
                    $payslipLines[] = [
                        'component_id' => 0,
                        'component_type' => 'deduction',
                        'description' => 'قسط ' . $loanLabel . ' #' . $loan['id'],
                        'amount' => $loanDeduction,
                        'sort_order' => 950,
                    ];
                }

                $netSalary = round($earnings - $deductions, 2);

                // Create payslip
                $payslip = new HrPayslip();
                $payslip->payroll_run_id = $id;
                $payslip->user_id = $userId;
                $payslip->basic_salary = $basicSalary;
                $payslip->total_earnings = $earnings;
                $payslip->total_deductions = $deductions;
                $payslip->net_salary = $netSalary;
                $payslip->working_days = $workingDaysInMonth;
                $payslip->present_days = $presentDays;
                $payslip->absent_days = $absentDays;
                $payslip->leave_days = $leaveDays;
                $payslip->overtime_hours = $overtimeHours;
                $payslip->status = 'draft';

                if (!$payslip->save()) {
                    throw new \Exception("فشل إنشاء كشف راتب للموظف #{$userId}: " . implode(', ', $payslip->getFirstErrors()));
                }

                // Create payslip lines
                foreach ($payslipLines as $lineData) {
                    $line = new HrPayslipLine();
                    $line->payslip_id = $payslip->id;
                    $line->component_id = $lineData['component_id'];
                    $line->component_type = $lineData['component_type'];
                    $line->description = $lineData['description'];
                    $line->amount = $lineData['amount'];
                    $line->sort_order = $lineData['sort_order'];
                    if (!$line->save()) {
                        throw new \Exception("فشل حفظ بند الراتب: " . implode(', ', $line->getFirstErrors()));
                    }
                }

                $totalGross += $earnings;
                $totalDeductions += $deductions;
                $totalNet += $netSalary;
                $employeeCount++;
            }

            // Update run totals
            $run->total_employees = $employeeCount;
            $run->total_gross = $totalGross;
            $run->total_deductions = $totalDeductions;
            $run->total_net = $totalNet;
            $run->status = 'calculated';

            if (!$run->save()) {
                throw new \Exception('فشل تحديث مسيرة الرواتب: ' . implode(', ', $run->getFirstErrors()));
            }

            $transaction->commit();
            return [
                'success' => true,
                'message' => "تم حساب الرواتب بنجاح — {$employeeCount} موظف.",
                'total_employees' => $employeeCount,
                'total_gross' => $totalGross,
                'total_net' => $totalNet,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Approve payroll run.
     *
     * @param int $id
     * @return array|Response
     */
    public function actionApprove($id)
    {
        $run = $this->findModel($id);

        if ($run->status !== 'calculated') {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'لا يمكن اعتماد المسيرة — الحالة الحالية: ' . $run->status];
            }
            Yii::$app->session->setFlash('error', 'لا يمكن اعتماد المسيرة — الحالة الحالية: ' . $run->status);
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $run->status = 'approved';
            $run->approved_by = Yii::$app->user->id;
            $run->approved_at = time();

            // Confirm all payslips
            HrPayslip::updateAll(
                ['status' => 'confirmed'],
                ['payroll_run_id' => $id, 'is_deleted' => 0]
            );

            // Update loan repayments
            $payslipLines = (new Query())
                ->select(['pl.description', 'pl.amount'])
                ->from('{{%hr_payslip_line}} pl')
                ->innerJoin('{{%hr_payslip}} ps', 'ps.id = pl.payslip_id')
                ->where([
                    'ps.payroll_run_id' => $id,
                    'pl.component_type' => 'deduction',
                ])
                ->andWhere(['like', 'pl.description', 'قسط'])
                ->all();

            // Extract loan IDs from description and update repaid amounts
            foreach ($payslipLines as $line) {
                if (preg_match('/#(\d+)/', $line['description'], $matches)) {
                    $loanId = (int) $matches[1];
                    Yii::$app->db->createCommand()->update(
                        '{{%hr_loan}}',
                        [
                            'repaid' => new \yii\db\Expression('repaid + :amount', [':amount' => $line['amount']]),
                            'remaining_installments' => new \yii\db\Expression('GREATEST(remaining_installments - 1, 0)'),
                            'updated_at' => time(),
                            'updated_by' => Yii::$app->user->id,
                        ],
                        ['id' => $loanId]
                    )->execute();

                    // Check if loan is fully repaid
                    $loan = (new Query())
                        ->select(['amount', 'repaid'])
                        ->from('{{%hr_loan}}')
                        ->where(['id' => $loanId])
                        ->one();

                    if ($loan && (float) $loan['repaid'] >= (float) $loan['amount']) {
                        Yii::$app->db->createCommand()->update(
                            '{{%hr_loan}}',
                            ['status' => 'completed', 'updated_at' => time()],
                            ['id' => $loanId]
                        )->execute();
                    }
                }
            }

            if (!$run->save()) {
                throw new \Exception('فشل اعتماد المسيرة: ' . implode(', ', $run->getFirstErrors()));
            }

            $transaction->commit();

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'تم اعتماد مسيرة الرواتب بنجاح.'];
            }

            Yii::$app->session->setFlash('success', 'تم اعتماد مسيرة الرواتب بنجاح.');
            return $this->redirect(['view', 'id' => $id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => $e->getMessage()];
            }
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * View individual payslip.
     *
     * @param int $id Payslip ID
     * @return string
     */
    public function actionPayslip($id)
    {
        $payslip = HrPayslip::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($payslip === null) {
            throw new NotFoundHttpException('كشف الراتب المطلوب غير موجود.');
        }

        $lines = HrPayslipLine::find()
            ->where(['payslip_id' => $id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $user = User::findOne($payslip->user_id);
        $run = HrPayrollRun::findOne($payslip->payroll_run_id);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'كشف راتب — ' . ($user->name ?: $user->username),
                'content' => $this->renderAjax('payslip', [
                    'payslip' => $payslip,
                    'lines' => $lines,
                    'user' => $user,
                    'run' => $run,
                ]),
            ];
        }

        return $this->render('payslip', [
            'payslip' => $payslip,
            'lines' => $lines,
            'user' => $user,
            'run' => $run,
        ]);
    }

    /**
     * Generate payslip PDF (simple HTML-to-PDF).
     *
     * @param int $id Payslip ID
     * @return string
     */
    public function actionPayslipPdf($id)
    {
        $payslip = HrPayslip::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($payslip === null) {
            throw new NotFoundHttpException('كشف الراتب المطلوب غير موجود.');
        }

        $lines = HrPayslipLine::find()
            ->where(['payslip_id' => $id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $user = User::findOne($payslip->user_id);
        $run = HrPayrollRun::findOne($payslip->payroll_run_id);

        // Render HTML content for PDF
        $htmlContent = $this->renderPartial('_payslip_pdf', [
            'payslip' => $payslip,
            'lines' => $lines,
            'user' => $user,
            'run' => $run,
        ]);

        // Simple HTML-to-PDF using browser print or mpdf if available
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->add('Content-Disposition', 'inline; filename="payslip_' . $payslip->id . '.html"');

        return $htmlContent;
    }

    /**
     * Manage salary components (list).
     *
     * @return string
     */
    public function actionComponents()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrSalaryComponent::find()
                ->where(['is_deleted' => 0])
                ->orderBy(['sort_order' => SORT_ASC, 'component_type' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('components', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create salary component.
     *
     * @return string|Response
     */
    public function actionComponentCreate()
    {
        $model = new HrSalaryComponent();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء مكون الراتب: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء مكون الراتب بنجاح.');
                return $this->redirect(['components']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء مكون راتب',
                'content' => $this->renderAjax('component-form', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('component-form', [
            'model' => $model,
        ]);
    }

    /**
     * Update salary component.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionComponentUpdate($id)
    {
        $model = HrSalaryComponent::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('مكون الراتب المطلوب غير موجود.');
        }

        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث مكون الراتب: ' . implode(', ', $model->getFirstErrors()));
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث مكون الراتب بنجاح.');
                return $this->redirect(['components']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل مكون الراتب',
                'content' => $this->renderAjax('component-form', [
                    'model' => $model,
                ]),
            ];
        }

        return $this->render('component-form', [
            'model' => $model,
        ]);
    }

    /**
     * Calculate working days in a date range (excludes Fridays and Saturdays).
     *
     * @param string $start
     * @param string $end
     * @return int
     */
    protected function getWorkingDays($start, $end)
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $endDate->modify('+1 day');
        $workingDays = 0;

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $day) {
            $dayOfWeek = (int) $day->format('N'); // 1=Mon, 7=Sun
            // Skip Friday (5) and Saturday (6) — Jordan weekend
            if ($dayOfWeek !== 5 && $dayOfWeek !== 6) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Finds the HrPayrollRun model.
     *
     * @param int $id
     * @return HrPayrollRun
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrPayrollRun::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('مسيرة الرواتب المطلوبة غير موجودة.');
    }
}
