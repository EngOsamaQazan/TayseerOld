<?php


namespace common\components;


class City
{
 function findMyCity($id){
     $city = \backend\modules\city\models\City::find()->where(['id'=>$id])->all();
     foreach ($city as $myCity){

         return $myCity->name;
     }
 }
}