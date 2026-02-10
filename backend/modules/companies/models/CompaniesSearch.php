<?php

namespace backend\modules\companies\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\companies\models\Companies;

/**
 * CompaniesSearch represents the model behind the search form about `backend\modules\companies\models\Companies`.
 */
class CompaniesSearch extends Companies
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public function rules()
    {
        return [
            [['id', 'created_by', 'created_at', 'updated_at', 'is_deleted'], 'integer'],
            [['name', 'phone_number', 'logo', 'last_updated_by','is_deleted','is_primary_company'], 'safe'],
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
        $query = Companies::find();

        if(!empty($params['CompaniesSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['CompaniesSearch']['number_row'],
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

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])
            ->andFilterWhere(['=', 'logo', $this->logo])
            ->andWhere(['is_deleted' => 0])->andWhere(['is_deleted' => false]);

        return $dataProvider;
    }
    public function searchCounter($params)
    {
        $query = Companies::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])
            ->andFilterWhere(['=', 'logo', $this->logo])
            ->andWhere(['is_deleted' => 0])->andWhere(['is_deleted' => false]);

        return $query->count();
    }
}
