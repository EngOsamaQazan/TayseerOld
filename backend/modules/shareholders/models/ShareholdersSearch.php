<?php

namespace backend\modules\shareholders\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ShareholdersSearch extends Shareholders
{
    public function rules()
    {
        return [
            [['id', 'share_count', 'is_active', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['name', 'phone', 'email', 'national_id', 'join_date', 'documents', 'notes'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Shareholders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'share_count' => $this->share_count,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'national_id', $this->national_id]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = Shareholders::find();

        $this->load($params);

        if (!$this->validate()) {
            return 0;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'share_count' => $this->share_count,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'national_id', $this->national_id]);

        return $query->count();
    }
}
