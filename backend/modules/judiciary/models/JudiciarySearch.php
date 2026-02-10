<?php

namespace backend\modules\judiciary\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\judiciary\models\Judiciary;
use yii\data\SqlDataProvider;
use yii\db\Query;

/**
 * JudiciarySearch represents the model behind the search form about `backend\modules\judiciary\models\Judiciary`.
 */
class JudiciarySearch extends Judiciary
{

    /**
     * @inheritdoc
     */
    public $contract_id;
    public $number_row;
    public $jobs_type;
    public $job_title;
    public $status;
    public $judiciary_actions;
    public $company_id;

    public $contract_not_in_status;

    public function rules()
    {
        return [
            [['id', 'court_id', 'type_id', 'lawyer_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted', 'number_row', 'case_cost'], 'integer'],
            [['lawyer_cost', 'judiciary_number', 'contract_id'], 'number'],
            [['income_date', 'year', 'from_income_date', 'to_income_date'], 'string'],
            [['from_income_date', 'to_income_date', 'contract_not_in_status', 'company_id'], 'safe']
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
        $query = Judiciary::find()->distinct();
        $query->joinWith('contract');
        $query->joinWith('lawyer');
        $query->joinWith('court');
        $query->joinWith("customersAndGuarantor");
        $query->innerJoin("{{%jobs}}", '{{%customers}}.job_title = {{%jobs}}.id');
        $query->innerJoin("{{%jobs_type}}", '{{%jobs}}.job_type = {{%jobs_type}}.id');

        // Subquery to find the last action for each judiciary
        $subQuery = (new Query())
            ->select(['judiciary_id', 'max_action_id' => 'MAX(judiciary_actions_id)'])
            ->from('{{%judiciary_customers_actions}}')
            ->groupBy('judiciary_id');

        // Join the main query with the subquery
        $query->leftJoin(['lastAction' => $subQuery], '{{%judiciary}}.id = lastAction.judiciary_id');

        // Adjust the join with judiciary_actions to only include the last action
        $query->leftJoin('{{%judiciary_actions}}', '{{%judiciary_actions}}.id = lastAction.max_action_id');

        if (!empty($params['JudiciarySearch']['job_title'])) {
            $query->andWhere(['{{%customers}}.job_title' => $params['JudiciarySearch']['job_title']]);
        }

        if (!empty($params['JudiciarySearch']['jobs_type'])) {
            $query->andWhere(['{{%jobs_type}}.id' => $params['JudiciarySearch']['jobs_type']]);
        }

        if (!empty($params['JudiciarySearch']['lawyer_cost'])) {
            $query->andWhere(['{{%judiciary}}.lawyer_cost' => $params['JudiciarySearch']['lawyer_cost']]);
        }

        if (!empty($params['JudiciarySearch']['case_cost'])) {
            $query->andWhere(['{{%judiciary}}.case_cost' => $params['JudiciarySearch']['case_cost']]);
        }

        if (!empty($params['JudiciarySearch']['court_id'])) {
            $query->andWhere(['{{%court}}.id' => $params['JudiciarySearch']['court_id']]);
        }

        if (!empty($params['JudiciarySearch']['contract_id'])) {
            $query->andWhere(['{{%contracts}}.id' => $params['JudiciarySearch']['contract_id']]);
        }

        if (!empty($params['JudiciarySearch']['from_income_date'])) {
            $query->where(['>=', 'income_date', $params['JudiciarySearch']['from_income_date']]);
        }
        if (!empty($params['JudiciarySearch']['to_income_date'])) {
            $query->where(['<=', 'income_date', $params['JudiciarySearch']['to_income_date']]);
        }

        if (!empty($params['JudiciarySearch']['year'])) {
            $query->andWhere(['os_judiciary.year' => $params['JudiciarySearch']['year']]);
        }


        if (!empty($params['JudiciarySearch']['status'])) {
            if ($params['JudiciarySearch']['status'] == 'Available') {
                $query->andWhere(['not in', '{{%contracts}}.status', ['canceled', 'finished']]);
            } elseif ($params['JudiciarySearch']['status'] == 'unAvailable') {
                $query->andWhere(['in', '{{%contracts}}.status', ['canceled', 'finished']]);
            }
        }

        if (!empty($params['JudiciarySearch']['judiciary_actions'])) {
            $query->andWhere(['{{%judiciary_actions}}.id' => $params['JudiciarySearch']['judiciary_actions']]);
        }

        if (!empty($params['JudiciarySearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => $params['JudiciarySearch']['number_row']],
            ]);
        } else {
            $dataProvider = new ActiveDataProvider(['query' => $query]);
        }

        if (!empty($params['JudiciarySearch']['judiciary_number'])) {
            $query->andWhere(['{{%judiciary}}.judiciary_number' => $params['JudiciarySearch']['judiciary_number']]);
        }

        $query->andWhere(['os_judiciary.is_deleted' => false]);




        $this->load($params);

        if (!$this->validate()) {
            // Uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Add more filtering conditions here if needed

        return ['dataProvider' => $dataProvider, 'count' => $query->count()];
    }

    public function reportSearch($params)
    {
        $query = Judiciary::find();
        $query->joinWith('contract');
        $query->joinWith('lawyer');
        $query->joinWith('court');

        if (!empty($params['JudiciarySearch']['number_row'])) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['JudiciarySearch']['number_row'],
                ],
            ]);
        } else {
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
        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'last_update_by' => $this->last_update_by,
        ]);

        if (!empty($params['JudiciarySearch']['from_income_date'])) {
            $query->where(['>=', 'income_date', $params['JudiciarySearch']['from_income_date']]);
        }
        if (!empty($params['JudiciarySearch']['to_income_date'])) {
            $query->where(['<=', 'income_date', $params['JudiciarySearch']['to_income_date']]);
        }
        $query->andFilterWhere(['year' => $this->year]);
        $query->andFilterWhere(['type_id' => $this->type_id]);
        $query->andFilterWhere(['case_cost' => $this->case_cost]);
        $query->andFilterWhere(['contract_id' => $this->contract_id]);
        $query->andFilterWhere(['lawyer_cost' => $this->lawyer_cost]);
        $query->andFilterWhere(['lawyer_id' => $this->lawyer_id]);
        $query->andFilterWhere(['judiciary_number' => $this->judiciary_number]);
        $query->andFilterWhere(['court_id' => $this->court_id]);
        $query->andWhere(['os_judiciary.is_deleted' => false]);
        $query->where(['!=', 'judiciary_number', ' ']);
        return ['dataProvider' => $dataProvider, 'count' => $query->count()];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function report()
    {

        $query = "SELECT
            `os_judiciary`.`contract_id` as contract_id ,
            `os_court`.`name` as court_name,
            CONCAT(`os_judiciary`.`judiciary_number`,'-',`os_judiciary`.`year`) as judiciary_number ,
            `os_judiciary`.`lawyer_cost` as lawyer_cost,
            `os_lawyers`.`name` as lawyer_name,
            `c`.`name` as customer_name,
            `os_judiciary_actions`.`name` as action_name,
            `jcc`.`action_date` as customer_date
            FROM
            `os_judiciary`
            LEFT JOIN `os_court` ON(
            `os_judiciary`.`court_id` = `os_court`.`id`
            ) AND(`os_court`.`is_deleted` = FALSE)
            LEFT JOIN `os_lawyers` ON(
            `os_judiciary`.`lawyer_id` = `os_lawyers`.`id`
            ) AND(`os_lawyers`.`is_deleted` = FALSE)
            LEFT JOIN `os_contracts` ON `os_judiciary`.`contract_id` = `os_contracts`.`id` AND(
            `os_contracts`.`status` NOT IN('finished', 'canceld', 'pending')
            )
            LEFT JOIN `os_judiciary_customers_actions` AS `jcc`
            ON
            `jcc`.`judiciary_id` = `os_judiciary`.`id` AND(`jcc`.`is_deleted` = FALSE)
            LEFT JOIN `os_customers` AS c
            ON
            `jcc`.`customers_id` = `c`.`id`
            LEFT JOIN `os_judiciary_actions` ON `os_judiciary_actions`.`id` = `jcc`.`judiciary_actions_id`
            WHERE
            (`jcc`.`is_deleted` = FALSE) AND(`os_judiciary`.`is_deleted` = FALSE) AND(
            `jcc`.`action_date` =(
            SELECT
            MAX(action_date)
            FROM
            os_judiciary_customers_actions
            WHERE
            os_judiciary_customers_actions.customers_id = c.id AND os_judiciary_customers_actions.judiciary_id = os_judiciary.id AND os_judiciary_customers_actions.is_deleted = FALSE
            )
            )
            ORDER BY
            `jcc`.`action_date`
        DESC";
        $count = Yii::$app->db->createCommand("SELECT
                    count(`os_judiciary`.`contract_id`)
                     FROM `os_judiciary`
                    LEFT JOIN `os_court` ON (`os_judiciary`.`court_id` = `os_court`.`id`) AND(`os_court`.`is_deleted` = FALSE)
                    LEFT JOIN `os_lawyers` ON (`os_judiciary`.`lawyer_id` = `os_lawyers`.`id`) AND(`os_lawyers`.`is_deleted` = FALSE)
                    LEFT JOIN `os_contracts` ON `os_judiciary`.`contract_id` = `os_contracts`.`id` AND(`os_contracts`.`status` NOT IN('finished', 'canceld', 'pending'))
                    LEFT JOIN `os_judiciary_customers_actions` as `jcc` ON `jcc`.`judiciary_id` = `os_judiciary`.`id` AND(`jcc`.`is_deleted` = FALSE)
                    LEFT JOIN `os_customers` as c ON `jcc`.`customers_id` = `c`.`id`
                    LEFT JOIN `os_judiciary_actions` ON `os_judiciary_actions`.`id` = `jcc`.`judiciary_actions_id`
                    WHERE (`jcc`.`is_deleted`=FALSE)
                    AND(`os_judiciary`.`is_deleted` = FALSE)
                    AND(`jcc`.`action_date`=(
                     SELECT MAX(action_date)
                          FROM os_judiciary_customers_actions
                          WHERE os_judiciary_customers_actions.customers_id = c.id
                          AND os_judiciary_customers_actions.judiciary_id = os_judiciary.id
                         AND os_judiciary_customers_actions.is_deleted =FALSE
                    )
                       )  
                ORDER BY `jcc`.`action_date`  DESC")->queryScalar();;
        $dataProvider = new SqlDataProvider([
            'sql' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        return ['dataProvider' => $dataProvider, 'count' => $count];
    }
}
