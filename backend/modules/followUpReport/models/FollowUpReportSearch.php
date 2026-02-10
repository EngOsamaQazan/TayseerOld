<?php

namespace backend\modules\followUpReport\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\followUpReport\models\FollowUpReport;

/**
 * FollowUpReportSearch represents the model behind the search form about `common\models\FollowUpReport`.
 */
class FollowUpReportSearch extends FollowUpReport
{

    public $customer_name;
    public $seller_name;
    public $number_row;
    public $users_follow_up;
    public $followed_by;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted','number_row','followed_by'], 'integer'],
            [['type', 'Date_of_sale', 'first_installment_date', 'notes', 'status', 'updated_at', 'selected_image', 'company_id', 'customer_name', 'seller_name', 'date_time', 'promise_to_pay_at', 'reminder'], 'safe'],
            [['total_value', 'first_installment_value', 'monthly_installment_value','users_follow_up'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = FollowUpReport::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith(['contract co']);
        if(!empty($params['FollowUpReportSearch']['number_row'])){
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['FollowUpReportSearch']['number_row'],
                ],
                'sort' => ['defaultOrder' => ['date_time' => SORT_ASC]],

            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort' => ['defaultOrder' => ['date_time' => SORT_ASC]],

            ]);
        }

        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];


        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'os_follow_up_report.id' => $this->id,
            'seller_id' => $this->seller_id,
            'os_follow_up_report.Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'os_follow_up_report.first_installment_value' => $this->first_installment_value,
            'os_follow_up_report.first_installment_date' => $this->first_installment_date,
            'os_follow_up_report.monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
            'date_time' => $this->date_time,
            'os_follow_up_report.updated_at' => $this->updated_at,

            'os_follow_up_report.is_deleted' => $this->is_deleted,
            //'reminder' => $this->reminder,

        ]);
        $query->andFilterWhere(['<=', 'promise_to_pay_at', $this->promise_to_pay_at]);
        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }else{
            $query->andFilterWhere(['<>', 'co.status', 'finished']);
        }
        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'selected_image', $this->selected_image])
            ->andFilterWhere(['like', 'company_id', $this->company_id]);
        $query->andFilterWhere(['<=', 'reminder', $this->reminder]);

        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if((!Yii::$app->user->can('مدير')) ){
            if (!Yii::$app->user->can('مدير التحصيل')){
                $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
            }

        }
            $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        $query->andFilterWhere(['<>', 'co.status', 'finished']);

        $query->andFilterWhere(['<>', 'co.status', 'canceled']);


        return $dataProvider;
    }
    public function searchCustamerCounter($params)
    {
        $query = FollowUpReport::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith(['contract co']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date_time' => SORT_ASC]],
        ]);

        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];


        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'os_follow_up_report.id' => $this->id,
            'seller_id' => $this->seller_id,
            'os_follow_up_report.Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'os_follow_up_report.first_installment_value' => $this->first_installment_value,
            'os_follow_up_report.first_installment_date' => $this->first_installment_date,
            'os_follow_up_report.monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
            'date_time' => $this->date_time,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
            //'reminder' => $this->reminder,

        ]);
        $query->andFilterWhere(['<=', 'promise_to_pay_at', $this->promise_to_pay_at]);
        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }else{
            $query->andFilterWhere(['<>', 'co.status', 'finished']);
        }
        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'selected_image', $this->selected_image])
            ->andFilterWhere(['like', 'company_id', $this->company_id]);
        $query->andFilterWhere(['<=', 'reminder', $this->reminder]);

        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if((!Yii::$app->user->can('مدير')) ){
            if (!Yii::$app->user->can('مدير التحصيل')){
                $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
            }

        }
            $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        $query->andFilterWhere(['<>', 'co.status', 'finished']);

        $query->andFilterWhere(['<>', 'co.status', 'canceled']);

        return $query->count('os_follow_up_report.id');

    }
    public function searchCounter($params)
    {
        $query = FollowUpReport::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith(['contract co']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date_time' => SORT_ASC]],
        ]);

        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];


        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'os_follow_up_report.id' => $this->id,
            'seller_id' => $this->seller_id,
            'os_follow_up_report.Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'os_follow_up_report.first_installment_value' => $this->first_installment_value,
            'os_follow_up_report.first_installment_date' => $this->first_installment_date,
            'os_follow_up_report.monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
            'date_time' => $this->date_time,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
            //'reminder' => $this->reminder,

        ]);
        $query->andFilterWhere(['<=', 'promise_to_pay_at', $this->promise_to_pay_at]);
        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }
            $query->andFilterWhere(['<>', 'co.status', 'finished']);

        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'selected_image', $this->selected_image])
            ->andFilterWhere(['like', 'company_id', $this->company_id]);
        $query->andFilterWhere(['<=', 'reminder', $this->reminder]);

        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if((!Yii::$app->user->can('مدير')) ){
            if (!Yii::$app->user->can('مدير التحصيل')){
                $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
            }

        }
            $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        $query->andFilterWhere(['<>', 'co.status', 'finished']);

        $query->andFilterWhere(['<>', 'co.status', 'canceled']);

        return $query->distinct()->count('id');

    }
}
