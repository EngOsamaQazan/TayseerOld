<?php
/**
 * نوافذ منبثقة لشاشة المتابعة - بناء من الصفر
 * تشمل: إرسال رسالة، تغيير حالة العقد، صور العملاء، بيانات العميل، التدقيق، التسوية
 */
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\contracts\models\Contracts;

$contractModel = $contractCalculations->contract_model;
?>

<!-- ═══ نافذة إرسال رسالة SMS ═══ -->
<div class="modal fade" id="smsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> إرسال رسالة نصية</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="phone_number" value="0">
                <div class="form-group">
                    <label>نص الرسالة</label>
                    <textarea id="sms_text" class="form-control" rows="4" placeholder="اكتب نص الرسالة هنا..."></textarea>
                </div>
                <div class="text-muted">عدد الأحرف: <span id="char_count">0</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary" id="send_sms" data-dismiss="modal"><i class="fa fa-paper-plane"></i> إرسال</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة تغيير حالة العقد ═══ -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-exchange"></i> تغيير حالة العقد</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>الحالة الجديدة</label>
                    <select class="form-control status-content">
                        <option value="pending">معلّق</option>
                        <option value="active">نشط</option>
                        <option value="reconciliation">مصالحة</option>
                        <option value="judiciary">قضاء</option>
                        <option value="canceled">ملغي</option>
                        <option value="refused">مرفوض</option>
                        <option value="legal_department">دائرة قانونية</option>
                        <option value="finished">منتهي</option>
                        <option value="settlement">تسوية</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary statse-change" contract-id="<?= $contractModel->id ?>">
                    <i class="fa fa-save"></i> حفظ التغيير
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة بيانات العميل ═══ -->
<div class="modal fade" id="customerInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="customerInfoTitle"><i class="fa fa-user"></i> بيانات العميل</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6"><label>الاسم</label><input type="text" class="form-control cu-name" readonly></div>
                    <div class="col-md-6"><label>الرقم الوطني</label><input type="text" class="form-control cu-id-number" readonly></div>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="col-md-4"><label>تاريخ الميلاد</label><input type="text" class="form-control cu-birth-date" readonly></div>
                    <div class="col-md-4"><label>المدينة</label><input type="text" class="form-control cu-city" readonly></div>
                    <div class="col-md-4"><label>الجنس</label><input type="text" class="form-control cu-sex" readonly></div>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="col-md-4"><label>الوظيفة</label><input type="text" class="form-control cu-job-title" readonly></div>
                    <div class="col-md-4"><label>الرقم الوظيفي</label><input type="text" class="form-control cu-job-number" readonly></div>
                    <div class="col-md-4"><label>البريد</label><input type="text" class="form-control cu-email" readonly></div>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="col-md-4"><label>البنك</label><input type="text" class="form-control cu-bank-name" readonly></div>
                    <div class="col-md-4"><label>رقم الحساب</label><input type="text" class="form-control cu-account-number" readonly></div>
                    <div class="col-md-4"><label>الفرع</label><input type="text" class="form-control cu-bank-branch" readonly></div>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="col-md-4"><label>ضمان اجتماعي</label><input type="text" class="form-control cu-is-social-security" readonly></div>
                    <div class="col-md-4"><label>رقم الضمان</label><input type="text" class="form-control cu-social-security-number" readonly></div>
                    <div class="col-md-4"><label>يملك عقارات</label><input type="text" class="form-control cu-do-have-any-property" readonly></div>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="col-md-12"><label>ملاحظات</label><textarea class="form-control cu-notes" readonly rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إغلاق</button>
                <a class="btn btn-primary" id="cus-link"><i class="fa fa-pencil"></i> تعديل العميل</a>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة صور العملاء ═══ -->
<div class="modal fade" id="customerImagesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-image"></i> صور ومستندات العملاء</h4>
            </div>
            <div class="modal-body">
                <?php
                /* جلب مستندات العملاء المرتبطين بالعقد */
                $docImages = \backend\modules\customers\models\CustomersDocument::find()
                    ->innerJoin('os_contracts_customers', 'os_contracts_customers.customer_id = os_customers_document.customer_id')
                    ->andWhere(['os_contracts_customers.contract_id' => $contractModel->id])
                    ->all();

                $docTypes = [0 => 'هوية', 1 => 'جواز سفر', 2 => 'رخصة', 3 => 'شهادة ميلاد', 4 => 'شهادة تعيين'];

                if (empty($docImages)): ?>
                    <div class="alert alert-warning"><i class="fa fa-info-circle"></i> لم يتم العثور على مستندات</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($docImages as $img): ?>
                            <?php if (!empty($img->images) && $img->images != 0): ?>
                                <div class="col-md-3 text-center" style="margin-bottom:15px">
                                    <?= Html::img(Url::to(['/images/imagemanager/' . $img->images]), [
                                        'style' => 'width:120px;height:120px;object-fit:contain;border-radius:8px;border:1px solid #ddd;padding:5px',
                                    ]) ?>
                                    <p class="text-muted" style="margin-top:5px;font-size:12px">
                                        <?= $docTypes[$img->document_type] ?? 'مستند' ?>
                                    </p>
                                </div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <?php
                /* جلب صور إضافية من مدير الصور */
                $extraImages = \backend\modules\imagemanager\models\Imagemanager::find()
                    ->innerJoin('os_contracts_customers', 'os_contracts_customers.customer_id = os_ImageManager.contractId')
                    ->where(['groupName' => 'coustmers'])
                    ->andWhere(['os_contracts_customers.contract_id' => $contractModel->id])
                    ->all();

                if (!empty($extraImages)): ?>
                    <hr>
                    <h5>صور إضافية</h5>
                    <div class="row">
                        <?php foreach ($extraImages as $ei): ?>
                            <?php $path = Yii::$app->imagemanager->getImagePath($ei->id); ?>
                            <div class="col-md-3 text-center" style="margin-bottom:10px">
                                <img src="<?= $path ?>" style="width:100px;height:100px;object-fit:contain;border-radius:8px" alt="صورة">
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة التدقيق ═══ -->
<div class="modal fade" id="auditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-check-square-o"></i> تدقيق عقد #<?= $contract_id ?></h4>
            </div>
            <div class="modal-body" id="auditDisplay" ondblclick="copyText(this)" style="direction:rtl;text-align:right">
                <?php
                /* معلومات العملاء */
                $contractObj = Contracts::findOne($contract_id);
                if ($contractObj):
                    $allCustomers = $contractObj->customersAndGuarantor ?? [];
                ?>
                    <h4><i class="fa fa-users"></i> معلومات العملاء</h4>
                    <?php foreach ($allCustomers as $cust): ?>
                        <div class="well well-sm">
                            <strong>العميل:</strong> <?= Html::encode($cust->name) ?><br>
                            <strong>الرقم الوطني:</strong> <?= Html::encode($cust->id_number) ?><br>
                            <?php if (!empty($cust->city)):
                                $cityObj = \backend\modules\city\models\City::findOne($cust->city);
                            ?>
                                <strong>المدينة:</strong> <?= $cityObj ? Html::encode($cityObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>
                            <?php if (!empty($cust->job_title)):
                                $jobObj = \backend\modules\jobs\models\Jobs::findOne($cust->job_title);
                            ?>
                                <strong>الوظيفة:</strong> <?= $jobObj ? Html::encode($jobObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>

                            <?php
                            $addrs = \backend\modules\address\models\Address::find()->where(['customers_id' => $cust->id])->all();
                            if (!empty($addrs)): ?>
                                <strong>العناوين:</strong>
                                <ul style="padding-right:20px;margin:5px 0">
                                    <?php foreach ($addrs as $a): ?>
                                        <li><?= ($a->address_type == 1 ? 'عنوان العمل' : 'عنوان السكن') ?>: <?= Html::encode($a->address ?: 'لا يوجد') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>

                    <!-- المعرّفون -->
                    <h4><i class="fa fa-address-book"></i> المعرّفون</h4>
                    <?php foreach ($contractObj->contractsCustomers as $cc): ?>
                        <?php if ($cc->customer && $cc->customer->phoneNumbers): ?>
                            <?php foreach ($cc->customer->phoneNumbers as $pn): ?>
                                <?php $rel = \backend\modules\cousins\models\Cousins::findOne($pn->phone_number_owner); ?>
                                <span class="label label-info" style="margin-left:5px">
                                    <?= Html::encode($pn->owner_name) ?> (<?= $rel ? Html::encode($rel->name) : '—' ?>)
                                </span>
                            <?php endforeach ?>
                        <?php endif ?>
                    <?php endforeach ?>

                    <!-- معلومات قضائية -->
                    <?php
                    $judicaries = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all();
                    if (!empty($judicaries)): ?>
                        <h4 style="margin-top:15px"><i class="fa fa-gavel"></i> المعلومات القضائية</h4>
                        <?php foreach ($judicaries as $jud): ?>
                            <div class="well well-sm">
                                <strong>القضية:</strong> <?= $jud->judiciary_number ?>/<?= $jud->year ?><br>
                                <strong>تاريخ الورود:</strong> <?= $jud->income_date ?: 'لا يوجد' ?><br>
                                <?php $law = \backend\modules\lawyers\models\Lawyers::findOne($jud->lawyer_id); ?>
                                <?php if ($law): ?><strong>المحامي:</strong> <?= Html::encode($law->name) ?><br><?php endif ?>
                                <?php $court = \backend\modules\court\models\Court::findOne($jud->court_id); ?>
                                <?php if ($court): ?><strong>المحكمة:</strong> <?= Html::encode($court->name) ?><br><?php endif ?>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة إضافة تسوية ═══ -->
<div class="modal fade" id="settlementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-balance-scale"></i> إضافة تسوية</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info loan-alert" style="display:none"></div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>القسط الشهري</label>
                            <input type="number" class="form-control" id="monthly_installment" placeholder="القسط الشهري">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>تاريخ أول دفعة</label>
                            <input type="date" class="form-control" id="new_installment_date">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>تاريخ التسوية</label>
                            <input type="date" class="form-control" id="first_installment_date">
                        </div>
                    </div>
                </div>
                <input type="hidden" value="<?= $contractModel->id ?>" id="contract_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary" id="save"><i class="fa fa-save"></i> حفظ التسوية</button>
            </div>
        </div>
    </div>
</div>
