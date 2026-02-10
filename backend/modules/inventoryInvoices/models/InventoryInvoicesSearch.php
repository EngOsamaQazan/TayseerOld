<?php

namespace backend\modules\inventoryInvoices\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

/**
 * InventoryInvoicesSearch represents the model behind the search form about `backend\modules\inventoryInvoices\models\InventoryInvoices`.
 */
class InventoryInvoicesSearch extends InventoryInvoices
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'inventory_items_id', 'company_id', 'type', 'suppliers_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted'], 'integer'],
            [['date'], 'safe'],
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
        $query = InventoryInvoices::find();

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

        ])->where(['is_deleted'=>0]);

        return $dataProvider;
    }
}
