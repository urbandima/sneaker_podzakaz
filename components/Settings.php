<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\CompanySettings;
use app\models\OrderStatus;

class Settings extends Component
{
    private $_company;
    private $_statuses;

    public function getCompany(): array
    {
        if ($this->_company === null) {
            $row = CompanySettings::find()->orderBy(['id' => SORT_ASC])->asArray()->one();
            $this->_company = $row ?: [];
        }
        return $this->_company;
    }

    public function getStatuses(bool $onlyKeys = false, bool $includeInactive = false): array
    {
        if ($this->_statuses === null) {
            $query = OrderStatus::find()->orderBy(['sort' => SORT_ASC]);
            if (!$includeInactive) {
                $query->where(['is_active' => 1]);
            }
            $this->_statuses = $query->asArray()->all();
        }
        if ($onlyKeys) {
            return array_column($this->_statuses, 'key');
        }
        $map = [];
        foreach ($this->_statuses as $s) {
            if (!$includeInactive && !$s['is_active']) {
                continue;
            }
            $map[$s['key']] = $s['label'];
        }
        return $map;
    }

    public function getLogistStatuses(): array
    {
        $rows = OrderStatus::find()->where(['logist_available' => 1])->orderBy(['sort' => SORT_ASC])->asArray()->all();
        $map = [];
        foreach ($rows as $s) {
            $map[$s['key']] = $s['label'];
        }
        return $map;
    }
}
