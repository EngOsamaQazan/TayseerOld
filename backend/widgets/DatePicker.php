<?php
namespace backend\widgets;

use yii\base\Widget;
use kartik\date\DatePicker as Base;

class DatePicker extends Base
{
    public $type = self::TYPE_COMPONENT_APPEND;
    public $pickerIcon = "<i class=\"lni lni-calendar\"></i>";
    public $removeIcon ='<i class="lni lni-trash"></i>';
}
