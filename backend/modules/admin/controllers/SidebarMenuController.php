<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use app\backend\modules\admin\models\SidebarMenuItem;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;

/**
 * SidebarMenuController
 * Управление боковым меню в админ панели (только Admin)
 */
class SidebarMenuController extends BaseAdminController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        // Разрешаем только администраторам
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => function () {
                    return !Yii::$app->user->isGuest
                        && Yii::$app->user->identity->isAdmin();
                },
            ],
        ];
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
                // CMP-418: actionToggle flips is_active unconditionally with no isPost/
                // VerbFilter guard — GET-CSRF exploitable. Its UI link in
                // sidebar-menu/index.php already has data-method="post".
                'toggle' => ['POST'],
            ],
        ];
        return $behaviors;
    }

    /**
     * Список всех пунктов меню
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => SidebarMenuItem::find()
                ->with('parent')
                ->orderBy(['sort_order' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр одного пункта
     */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Создание нового пункта
     */
    public function actionCreate()
    {
        $model = new SidebarMenuItem();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                \Yii::$app->session->setFlash('success', 'Пункт меню создан');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'types' => SidebarMenuItem::getTypesList(),
            'parents' => ArrayHelper::merge(
                ['' => 'Без родителя'],
                SidebarMenuItem::getParentsList()
            ),
        ]);
    }

    /**
     * Редактирование пункта
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            \Yii::$app->session->setFlash('success', 'Пункт меню обновлён');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'types' => SidebarMenuItem::getTypesList(),
            'parents' => ArrayHelper::merge(
                ['' => 'Без родителя'],
                SidebarMenuItem::getParentsList()
            ),
        ]);
    }

    /**
     * Удаление пункта
     */
    public function actionDelete(int $id)
    {
        $this->findModel($id)->delete();
        \Yii::$app->session->setFlash('success', 'Пункт меню удалён');

        return $this->redirect(['index']);
    }

    /**
     * Переключение активности
     */
    public function actionToggle(int $id): \yii\web\Response
    {
        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        $model->save(false);

        return $this->redirect(['index']);
    }

    /**
     * Сортировка пунктов (AJAX)
     *
     * CMP-470: сигнатура была `: \yii\web\Response`, а метод всегда
     * `return ['success' => true];` — простой массив. Это не JS/парсинг-баг,
     * а несовпадение PHP-типов на уровне языка: строгий return type у самого
     * PHP (не у Yii) фатально падает `TypeError` на КАЖДОМ вызове, независимо
     * от содержимого `$items` — экшен ни разу не мог успешно завершиться, даже
     * если бы парсинг `items` ниже был в порядке. Тип убран, как и у соседних
     * JSON-экшенов в этом же контроллере/проекте (actionToggle и т.п. без
     * return type); `Yii::$app->response->format = FORMAT_JSON` сам оборачивает
     * возвращаемый массив в JSON-ответ.
     */
    public function actionSort()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        /**
         * CMP-470: реальный вызывающий код — drag&drop обработчик в
         * admin-settings.js (блок "sidebar-menu/index.php") — шлёт
         * `fetch(sortUrl, {headers:{'Content-Type':'application/x-www-form-urlencoded'},
         * body: 'items=' + JSON.stringify(items)})`, т.е. `items` приходит в
         * $_POST как ОДНА JSON-строка (`["3","1","2"]`), а не как PHP-массив
         * (тот получился бы только при `items[]=3&items[]=1&...`). Старый код
         * делал `foreach ($this->request->post('items', []), ...)` — на PHP 8
         * `foreach` по строке не варнинг, а фатальная ErrorException
         * ("foreach() argument must be of type array|object, string given"):
         * живым прогоном подтверждено — реальный drag&drop в браузере ронял
         * весь запрос 500-й, ни один sort_order не менялся.
         */
        $items = $this->request->post('items', []);
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        foreach ($items as $index => $id) {
            $model = SidebarMenuItem::findOne($id);
            if ($model) {
                $model->sort_order = $index * 10;
                $model->save(false);
            }
        }

        return ['success' => true];
    }

    /**
     * Поиск модели
     */
    protected function findModel(int $id): SidebarMenuItem
    {
        if (($model = SidebarMenuItem::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Пункт меню не найден');
    }
}
