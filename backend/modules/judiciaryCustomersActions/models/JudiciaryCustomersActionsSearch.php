<?php

namespace backend\modules\judiciaryCustomersActions\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;

/**
 * JudiciaryCustomersActionsSearch represents the model behind the search form about `backend\modules\judiciary\models\JudiciaryCustomersActions`.
 */
class JudiciaryCustomersActionsSearch extends JudiciaryCustomersActions {

    /**
     * @inheritdoc
     */
    public $form_action_date;
    public $to_action_date;
    public $from_create_at;
    public $to_create_at;
    public $number_row;

    public $contract_not_in_status;

    public function rules() {
        return [
            [['id', 'judiciary_id', 'customers_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'judiciary_actions_id', 'is_deleted', 'court_name', 'contract_id', 'lawyer_name','number_row'], 'integer', 'on' => 'create'],
            [['id', 'judiciary_id', 'customers_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'judiciary_actions_id', 'is_deleted', 'court_name', 'contract_id', 'lawyer_name','number_row'], 'integer', 'on' => 'update'],
            [['contract_id','judiciary_id','number_row'], 'integer'],
            [['note', 'year', 'form_action_date', 'to_action_date', 'court_name', 'lawyer_name','contract_not_in_status'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
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
    public function search($params) {
        $query = JudiciaryCustomersActions::find();
        $query->innerJoinWith('judiciary');
        $query->innerJoinWith('customers');
        $query->innerJoinWith('judiciaryActions');
        $query->innerJoin('os_contracts','os_judiciary_customers_actions.contract_id = os_contracts.id')->where(['!=','os_contracts.status','finished']);

        if(!empty($params['JudiciaryCustomersActionsSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['JudiciaryCustomersActionsSearch']['number_row'],
                ],
            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
//        if (empty($this->judiciary_id) && empty($params['JudiciaryCustomersActionsSearch']['customers_id']) && empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']) && empty($this->year) && empty($this->created_by) && empty($this->court_name) && empty($this->lawyer_name) && empty($this->contract_id) && empty($params['JudiciaryCustomersActionsSearch']['to_action_date']) && empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])
//        ) {
//            $query->Where(['=', 'action_date', '1000-01-01']);
//            return $dataProvider;
//        }
        $query->andFilterWhere([
            'id' => $this->id,
            'judiciary_id' => $this->judiciary_id,
            'customers_id' => $this->customers_id,
            'judiciary_actions_id' => $this->judiciary_actions_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'last_update_by' => $this->last_update_by,
        ]);
        if (!empty($params['JudiciarySearch']['contract_not_in_status'])) {
            $query->andFilterWhere(['<>','os_contracts.status', $this->contract_not_in_status]);
        }

        $query->andFilterWhere(['like', 'note', $this->note]);
      //  $query->where(['os_judiciary_customers_actions.judiciary_id'=>6])->orderBy(['os_judiciary_customers_actions.id'=>SORT_DESC])->limit(1);

        if (!empty($params['JudiciaryCustomersActionsSearch']['created_by'])) {
            $query->andFilterWhere(['=','os_judiciary_customers_actions.created_by' ,$params['JudiciaryCustomersActionsSearch']['created_by']]);;
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['customers_id'])) {
            $query->andWhere(['customers_id' => $params['JudiciaryCustomersActionsSearch']['customers_id']])->orderBy(['judiciary_actions_id' => SORT_DESC])->limit(1)
                    ->offset(1)
                    ->one();
        }
        $query->andFilterWhere(['os_judiciary_customers_actions.created_by' => $this->created_by]);
        if (!empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'])) {
//                    echo "<pre>";
//        \yii\helpers\VarDumper::dump($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']);
//        echo "</pre>";
//        die();
            $query->andFilterWhere(['in', 'judiciary_actions_id', $params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']])->orderBy(['judiciary_actions_id' => SORT_DESC])->limit(1)
                    ->offset(1)
                    ->one();
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])) {
            $query->andWhere(['>=', 'action_date', $params['JudiciaryCustomersActionsSearch']['form_action_date']])->orderBy(['action_date' => SORT_DESC])->limit(1)
                    ->offset(1)
                    ->one();
        }

        if (!empty($params['JudiciaryCustomersActionsSearch']['to_action_date'])) {

            $query->andwhere(['<=', 'action_date', $params['JudiciaryCustomersActionsSearch']['to_action_date']])->orderBy(['action_date' => SORT_DESC])->limit(1)
                    ->offset(1)
                    ->one();
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['from_create_at'])) {
            $from_Action_date = strtotime($params['JudiciaryCustomersActionsSearch']['from_create_at']);

            $query->andWhere(['>=', 'os_judiciary_customers_actions.created_at', $from_Action_date]);
        }

        if (!empty($params['JudiciaryCustomersActionsSearch']['to_create_at'])) {
            $to_Action_date = strtotime($params['JudiciaryCustomersActionsSearch']['to_create_at']);

            $query->andwhere(['<=', 'os_judiciary_customers_actions.created_at', $to_Action_date]);
        }
        $query->andFilterWhere(['os_judiciary.court_id' => $this->court_name]);
        $query->andFilterWhere(['os_judiciary.year' => $this->year]);
        $query->andFilterWhere(['os_judiciary.lawyer_id' => $this->lawyer_name]);
        $query->andFilterWhere(['os_judiciary.contract_id' => $this->contract_id]);

        return $dataProvider;
    }
    public function searchCounter($params) {
        $query = JudiciaryCustomersActions::find();
        $query->innerJoinWith('judiciary');
        $query->innerJoinWith('customers');
        $query->innerJoinWith('judiciaryActions');
        $query->innerJoin('os_contracts','os_judiciary_customers_actions.contract_id = os_contracts.id')->where(['!=','os_contracts.status','finished']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
//        if (empty($this->judiciary_id) && empty($params['JudiciaryCustomersActionsSearch']['customers_id']) && empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']) && empty($this->year) && empty($this->created_by) && empty($this->court_name) && empty($this->lawyer_name) && empty($this->contract_id) && empty($params['JudiciaryCustomersActionsSearch']['to_action_date']) && empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])
//        ) {
//            $query->Where(['=', 'action_date', '1000-01-01']);
//            return $dataProvider;
//        }
        $query->andFilterWhere([
            'id' => $this->id,
            'judiciary_id' => $this->judiciary_id,
            'customers_id' => $this->customers_id,
            'judiciary_actions_id' => $this->judiciary_actions_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_update_by' => $this->last_update_by,
        ]);

        $query->andFilterWhere(['like', 'note', $this->note]);
        $query->andFilterWhere(['=',      'os_judiciary_customers_actions.created_by' ,$this->created_by]);
        if (!empty($params['JudiciaryCustomersActionsSearch']['customers_id'])) {
            $query->andWhere(['customers_id' => $params['JudiciaryCustomersActionsSearch']['customers_id']])->orderBy(['judiciary_actions_id' => SORT_DESC])->limit(1)
                ->offset(1)
                ->one();
        }
        $query->andFilterWhere(['os_judiciary_customers_actions.created_by' => $this->created_by]);
        if (!empty($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id'])) {
//                    echo "<pre>";
//        \yii\helpers\VarDumper::dump($params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']);
//        echo "</pre>";
//        die();
            $query->andFilterWhere(['in', 'judiciary_actions_id', $params['JudiciaryCustomersActionsSearch']['judiciary_actions_id']])->orderBy(['judiciary_actions_id' => SORT_DESC])->limit(1)
                ->offset(1)
                ->one();
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['form_action_date'])) {
            $query->andWhere(['>=', 'action_date', $params['JudiciaryCustomersActionsSearch']['form_action_date']])->orderBy(['action_date' => SORT_DESC])->limit(1)
                ->offset(1)
                ->one();
        }

        if (!empty($params['JudiciaryCustomersActionsSearch']['to_action_date'])) {

            $query->andwhere(['<=', 'action_date', $params['JudiciaryCustomersActionsSearch']['to_action_date']])->orderBy(['action_date' => SORT_DESC])->limit(1)
                ->offset(1)
                ->one();
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['from_create_at'])) {
            $from_Action_date = strtotime($params['JudiciaryCustomersActionsSearch']['from_create_at']);

            $query->andWhere(['>=', 'os_judiciary_customers_actions.created_at', $from_Action_date]);
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['created_by'])) {
            $query->andFilterWhere(['=','os_judiciary_customers_actions.created_by' ,$params['JudiciaryCustomersActionsSearch']['created_by']]);;
        }
        if (!empty($params['JudiciaryCustomersActionsSearch']['to_create_at'])) {
            $to_Action_date = strtotime($params['JudiciaryCustomersActionsSearch']['to_create_at']);

            $query->andwhere(['<=', 'os_judiciary_customers_actions.created_at', $to_Action_date]);
        }
        $query->andFilterWhere(['os_judiciary.court_id' => $this->court_name]);
        $query->andFilterWhere(['os_judiciary.year' => $this->year]);
        $query->andFilterWhere(['os_judiciary.lawyer_id' => $this->lawyer_name]);
        $query->andFilterWhere(['os_judiciary.contract_id' => $this->contract_id]);
        return $query->count();
    }

}
