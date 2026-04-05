<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\SidebarMenuItem $model */
/** @var array $types */
/** @var array $parents */

$this->title = 'Редактировать пункт меню';
$this->params['breadcrumbs'][] = ['label' => 'Боковое меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;

// Рендерим общую форму
include '_form.php';
