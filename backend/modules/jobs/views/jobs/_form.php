<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\jobs\models\JobsType;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsWorkingHours;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\Jobs */
/* @var $form yii\widgets\ActiveForm */

// Get existing working hours for edit mode
$existingHours = [];
if (!$model->isNewRecord) {
    $hours = JobsWorkingHours::find()->where(['job_id' => $model->id])->all();
    foreach ($hours as $h) {
        $existingHours[$h->day_of_week] = $h;
    }
}

$dayNames = Jobs::getDayNames();
?>

<div class="jobs-form">
    <?php $form = ActiveForm::begin([
        'id' => 'jobs-form',
        'options' => ['class' => 'form-horizontal'],
    ]); ?>

    <!-- =============== البيانات الأساسية =============== -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-building"></i> البيانات الأساسية</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'أدخل اسم جهة العمل']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'job_type')->widget(Select2::class, [
                        'data' => ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر نوع جهة العمل'],
                        'pluginOptions' => ['allowClear' => true],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->dropDownList([
                        1 => 'فعال',
                        0 => 'غير فعال',
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'example@domain.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'website')->textInput(['maxlength' => true, 'placeholder' => 'https://www.example.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'notes')->textarea(['rows' => 1, 'placeholder' => 'ملاحظات إضافية']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- =============== العنوان والموقع =============== -->
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-map-marker"></i> العنوان والموقع</h3>
            <div class="box-tools pull-left">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'address_city')->textInput(['placeholder' => 'المدينة']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_area')->textInput(['placeholder' => 'المنطقة أو الحي']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_street')->textInput(['placeholder' => 'الشارع والعنوان التفصيلي']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_building')->textInput(['placeholder' => 'المبنى / الطابق / الرقم']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'latitude')->textInput([
                        'placeholder' => 'مثال: 31.9539',
                        'dir' => 'ltr',
                        'id' => 'job-latitude',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'longitude')->textInput([
                        'placeholder' => 'مثال: 35.9106',
                        'dir' => 'ltr',
                        'id' => 'job-longitude',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-info btn-sm" id="btn-get-location">
                            <i class="fa fa-crosshairs"></i> الحصول على الموقع الحالي
                        </button>
                        <?php if (!$model->isNewRecord && $model->latitude && $model->longitude): ?>
                            <a href="<?= $model->getMapUrl() ?>" target="_blank" class="btn btn-success btn-sm">
                                <i class="fa fa-map"></i> عرض على الخريطة
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Map container -->
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-12">
                    <div id="job-map" style="height: 300px; border: 1px solid #ddd; border-radius: 4px; display: <?= ($model->latitude && $model->longitude) ? 'block' : 'none' ?>;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- =============== أوقات العمل =============== -->
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-clock-o"></i> أوقات العمل</h3>
            <div class="box-tools pull-left">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped" id="working-hours-table">
                <thead>
                    <tr>
                        <th style="width:150px">اليوم</th>
                        <th style="width:160px">وقت البداية</th>
                        <th style="width:160px">وقت النهاية</th>
                        <th style="width:80px" class="text-center">مغلق</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dayNames as $dayNum => $dayName): ?>
                        <?php
                        $existing = isset($existingHours[$dayNum]) ? $existingHours[$dayNum] : null;
                        $isClosed = $existing ? $existing->is_closed : ($dayNum == 5 ? 1 : 0);
                        $openTime = $existing ? $existing->opening_time : ($dayNum == 5 ? '' : '08:00');
                        $closeTime = $existing ? $existing->closing_time : ($dayNum == 5 ? '' : '16:00');
                        $notes = $existing ? $existing->notes : '';
                        ?>
                        <tr class="working-hours-row <?= $isClosed ? 'danger' : '' ?>">
                            <td>
                                <strong><?= $dayName ?></strong>
                                <input type="hidden" name="WorkingHours[<?= $dayNum ?>][day_of_week]" value="<?= $dayNum ?>">
                            </td>
                            <td>
                                <input type="time" class="form-control input-sm wh-time"
                                       name="WorkingHours[<?= $dayNum ?>][opening_time]"
                                       value="<?= $openTime ?>"
                                    <?= $isClosed ? 'disabled' : '' ?>>
                            </td>
                            <td>
                                <input type="time" class="form-control input-sm wh-time"
                                       name="WorkingHours[<?= $dayNum ?>][closing_time]"
                                       value="<?= $closeTime ?>"
                                    <?= $isClosed ? 'disabled' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="wh-closed-toggle"
                                       name="WorkingHours[<?= $dayNum ?>][is_closed]"
                                       value="1"
                                    <?= $isClosed ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <input type="text" class="form-control input-sm"
                                       name="WorkingHours[<?= $dayNum ?>][notes]"
                                       value="<?= Html::encode($notes) ?>"
                                       placeholder="ملاحظات">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =============== أزرار الحفظ =============== -->
    <div class="box box-default">
        <div class="box-body text-center">
            <?= Html::submitButton(
                $model->isNewRecord ? '<i class="fa fa-plus"></i> إنشاء جهة العمل' : '<i class="fa fa-save"></i> حفظ التغييرات',
                ['class' => $model->isNewRecord ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
            <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default btn-lg']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
// Toggle working hours time fields when "closed" is checked
$('.wh-closed-toggle').on('change', function() {
    var row = $(this).closest('tr');
    var timeInputs = row.find('.wh-time');
    if ($(this).is(':checked')) {
        timeInputs.prop('disabled', true).val('');
        row.addClass('danger').removeClass('success');
    } else {
        timeInputs.prop('disabled', false);
        row.removeClass('danger');
    }
});

// Get current GPS location
$('#btn-get-location').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري التحديد...');
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                $('#job-latitude').val(position.coords.latitude.toFixed(8));
                $('#job-longitude').val(position.coords.longitude.toFixed(8));
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> الحصول على الموقع الحالي');
                $('#job-map').show();
                initMap(position.coords.latitude, position.coords.longitude);
            },
            function(error) {
                alert('لم نتمكن من تحديد موقعك: ' + error.message);
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> الحصول على الموقع الحالي');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        alert('المتصفح لا يدعم تحديد الموقع');
        btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> الحصول على الموقع الحالي');
    }
});

// Initialize map if coordinates exist
function initMap(lat, lng) {
    var mapDiv = document.getElementById('job-map');
    mapDiv.style.display = 'block';
    mapDiv.innerHTML = '<div class="text-center" style="padding-top:120px;">' +
        '<i class="fa fa-map-marker fa-3x text-danger"></i><br>' +
        '<strong>الموقع:</strong> ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '<br>' +
        '<a href="https://www.google.com/maps?q=' + lat + ',' + lng + '" target="_blank" class="btn btn-sm btn-info" style="margin-top:10px;">' +
        '<i class="fa fa-external-link"></i> فتح في خرائط جوجل</a></div>';
}

// Init map on page load if coords exist
var initLat = parseFloat($('#job-latitude').val());
var initLng = parseFloat($('#job-longitude').val());
if (initLat && initLng) {
    initMap(initLat, initLng);
}
JS;
$this->registerJs($js);
?>
