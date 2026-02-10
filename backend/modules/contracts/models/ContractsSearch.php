<?php

namespace backend\modules\contracts\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\contracts\models\Contracts;

/**
 * contractsSearch represents the model behind the search form about `app\models\contracts`.
 */
class ContractsSearch extends Contracts
{
    public $customer_name;
    public $seller_name;
    public $to_date;
    public $from_date;
    public $job_title;
    public $phone_number;
    public $number_row;
    public $job_Type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted', 'number_row', 'job_Type'], 'integer'],
            [['Date_of_sale', 'first_installment_date', 'monthly_installment_value', 'notes', 'updated_at', 'customer_name', 'seller_name', 'from_date', 'job_Type', 'to_date', 'job_title'], 'safe'],
            [['total_value', 'first_installment_value'], 'number'],
            [['from_date', 'to_date', 'job_title'], 'string']
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
        $query = contracts::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        if (!empty($params['ContractsSearch']['job_Type']) || !empty($params['ContractsSearch']['job_title'])) {

            $query->innerJoin("`os_jobs`  ", ' os_jobs.id = c.job_title');
            $query->innerJoin("`os_jobs_type` ", ' os_jobs_type.id = os_jobs.job_type');
        }
        if (!empty($params['ContractsSearch']['job_Type'])) {
            $query->where(['=', 'job_type', $params['ContractsSearch']['job_Type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {
            $query->where(['=', 'c.job_title', $params['ContractsSearch']['job_title']]);

        }
        if (!empty($params['c']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);


        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['cc.id' => $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['status'])) {
            $query->andFilterWhere(['os_contracts.status' => $params['ContractsSearch']['status']]);
        } else {
            $query->andFilterWhere(['<>', 'os_contracts.status', 'finished']);
            $query->andFilterWhere(['<>', 'os_contracts.status', 'canceled']);

        }
        if ((!empty($this->from_date))) {
            $query->andFilterWhere(['>=', 'Date_of_sale', $this->from_date]);
        }
        if ((!empty($this->to_date))) {
            $query->andFilterWhere(['<=', 'Date_of_sale', $this->to_date]);
        }

        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['like', 'c.primary_phone_number', $params['ContractsSearch']['phone_number']]);
        }
        $query->orderBy(['id' => SORT_DESC]);
        return $dataProvider;
    }

    public function searchcounter($params)
    {
        $query = contracts::find()->joinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        if (!empty($params['ContractsSearch']['job_Type']) || !empty($params['ContractsSearch']['job_title'])) {

            $query->innerJoin("`os_jobs`  ", ' os_jobs.id = c.job_title');
            $query->innerJoin("`os_jobs_type` ", ' os_jobs_type.id = os_jobs.job_type');
        }
        if (!empty($params['ContractsSearch']['job_Type'])) {
            $query->where(['=', 'job_type', $params['ContractsSearch']['job_Type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {
            $query->where(['=', 'c.job_title', $params['ContractsSearch']['job_title']]);

        }
        if (!empty($params['ContractsSearch']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);


        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['cc.id' => $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['status'])) {
            $query->andFilterWhere(['os_contracts.status' => $params['ContractsSearch']['status']]);
        } else {
            $query->andFilterWhere(['<>', 'os_contracts.status', 'finished']);
            $query->andFilterWhere(['<>', 'os_contracts.status', 'canceled']);

        }
        if ((!empty($this->from_date))) {
            $query->andFilterWhere(['>=', 'Date_of_sale', $this->from_date]);
        }
        if ((!empty($this->to_date))) {
            $query->andFilterWhere(['<=', 'Date_of_sale', $this->to_date]);
        }

        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['like', 'c.primary_phone_number', $params['ContractsSearch']['phone_number']]);
        }
        $query->orderBy(['id' => SORT_DESC]);
        return $query->distinct()->count();
    }

    public function searchLegalDepartment($params)
    {
        $query = contracts::find()->innerJoinWith(['customersWithoutCondition as c'])->innerJoinWith(['seller as s'])->innerJoinWith('customersWithoutCondition as cc');
        if (!empty($params['ContractsSearch']['number_row'])) {

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['ContractsSearch']['number_row'],
                ],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['c.primary_phone_number' => $params['ContractsSearch']['phone_number']]);
        }
        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['cc.id' => $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['from_date'])) {
            $query->andFilterWhere(['>=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['from_date']]);
        }
        if (!empty($params['ContractsSearch']['to_date'])) {
            $query->andFilterWhere(['<=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['to_date']]);
        }
        if (!empty($params['ContractsSearch']['type'])) {
            $query->andFilterWhere(['=', 'os_contracts.type', $params['ContractsSearch']['type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {

            $query->andFilterWhere(['cc.job_title' => $params['ContractsSearch']['job_title']]);
        }
        $query->andWhere(['os_contracts.status' => Contracts::STATUS_LEGAL_DEPARTMENT]);

        $query->orderBy(['id' => SORT_DESC]);
        return $dataProvider;
    }

    public function searchLegalDepartmentCount($params)
    {
        $query = contracts::find()->innerJoinWith(['customersWithoutCondition as c'])->joinWith(['seller as s'])->joinWith('customersWithoutCondition as cc');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->sort->attributes['seller_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['customer_name'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'Date_of_sale' => $this->Date_of_sale,
            'total_value' => $this->total_value,
            'first_installment_value' => $this->first_installment_value,
            'first_installment_date' => $this->first_installment_date,
            'monthly_installment_value' => $this->monthly_installment_value,
            'updated_at' => $this->updated_at,
            'is_deleted' => $this->is_deleted,
        ]);
        if (!empty($params['ContractsSearch']['phone_number'])) {
            $query->andFilterWhere(['c.primary_phone_number' => $params['ContractsSearch']['phone_number']]);
        }

        $query->andFilterWhere(['os_contracts.id' => $this->id]);
        $query->andFilterWhere(['cc.id' => $this->customer_name]);
        $query->andFilterWhere(['os_contracts.seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'notes', $this->notes]);
        if (!empty($params['ContractsSearch']['followed_by'])) {
            $query->andFilterWhere(['followed_by' => $params['ContractsSearch']['followed_by']]);
        }
        if (!empty($params['ContractsSearch']['from_date'])) {
            $query->andFilterWhere(['>=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['from_date']]);
        }
        if (!empty($params['ContractsSearch']['to_date'])) {
            $query->andFilterWhere(['<=', 'os_contracts.Date_of_sale', $params['ContractsSearch']['to_date']]);
        }
        if (!empty($params['ContractsSearch']['type'])) {
            $query->andFilterWhere(['=', 'os_contracts.type', $params['ContractsSearch']['type']]);
        }
        if (!empty($params['ContractsSearch']['job_title'])) {

            $query->andFilterWhere(['cc.job_title' => $params['ContractsSearch']['job_title']]);

        }
        $query->andWhere(['os_contracts.status' => Contracts::STATUS_LEGAL_DEPARTMENT]);

        $query->orderBy(['id' => SORT_DESC]);

        return $query->count();
    }
}
