<?php

namespace backend\modules\customers\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use  backend\modules\customers\models\Customers;
use yii\data\SqlDataProvider;
/**
 * CustomersSearch represents the model behind the search form about `app\models\Customers`.
 */
class CustomersSearch extends Customers
{

    /**
     * @inheritdoc
     */
    public $contract_type;
    public $number_row;
    public $job_type;

    public function rules()
    {
        return [
            [['id', 'job_title','number_row','job_type'], 'integer'],
            [['name', 'status', 'city', 'job_title', 'id_number', 'primary_phone_number', 'contract_type'], 'safe'],
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

        $this->load($params);
        $query = Customers::find();
        if (!empty($params['CustomersSearch']['contract_type'])) {
            $query = Customers::find()->innerJoin("(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id  and os_contracts.status = '".$params['CustomersSearch']['contract_type']."')ON customer_id = os_customers.id");
        }
        if (!empty($params['CustomersSearch']['job_Type'])) {

            $query->innerJoin("`os_jobs` ",' os_jobs.id = job_title') ;
            $query->innerJoin("`os_jobs_type` ",' os_jobs_type.id = os_jobs.job_type' );
            $query->where(['=','job_type',$params['CustomersSearch']['job_Type']]);

        }
        if(!empty($params['CustomersSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['CustomersSearch']['number_row'],
                ],
            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }





        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }



        $query->andFilterWhere(['=', 'os_customers.status', $this->status])
            ->andFilterWhere(['=', 'city', $this->city])
            ->andFilterWhere(['=', 'os_customers.id', $this->id])
            ->andFilterWhere(['=', 'job_title', $this->job_title])
            ->andFilterWhere(['like', 'id_number', $this->id_number])
            ->andFilterWhere(['like', 'primary_phone_number', $this->primary_phone_number])->andWhere(['os_customers.is_deleted' => false]);;
        $query->andFilterWhere(['=', 'name', $this->name]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = Customers::find();
        if (!empty($params['CustomersSearch']['contract_type'])) {
            $query = Customers::find()->innerJoin("(`os_contracts_customers` INNER JOIN os_contracts ON contract_id = os_contracts.id  and os_contracts.status = '".$params['CustomersSearch']['contract_type']."')ON customer_id = os_customers.id");

        }
        if (!empty($params['CustomersSearch']['job_Type'])) {

            $query->innerJoin("`os_jobs` ",' os_jobs.id = job_title') ;
            $query->innerJoin("`os_jobs_type` ",' os_jobs_type.id = os_jobs.job_type' );
            $query->where(['=','job_type',$params['CustomersSearch']['job_Type']]);

        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
    $query->andFilterWhere(['=', 'os_customers.status', $this->status]);

        $query->andFilterWhere(['=', 'city', $this->city])
            ->andFilterWhere(['=', 'job_title', $this->job_title])
            ->andFilterWhere(['=', 'os_customers.id', $this->id])
            ->andFilterWhere(['like', 'id_number', $this->id_number])
            ->andFilterWhere(['like', 'primary_phone_number', $this->primary_phone_number])->andWhere(['os_customers.is_deleted' => false]);
        $query->andFilterWhere(['=', 'name', $this->name]);

        return $query->count();
    }
}
