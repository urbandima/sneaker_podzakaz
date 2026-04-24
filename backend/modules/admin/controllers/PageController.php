<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\backend\modules\admin\models\PageContent;

/**
 * Редактор статических страниц сайта
 * Маршруты: /admin/page, /admin/page/edit?slug=privacy
 */
class PageController extends BaseAdminController
{
    /**
     * Список всех статических страниц
     */
    public function actionIndex()
    {
        $available = PageContent::isAvailable();
        $pages     = [];

        if ($available) {
            $dbPages = PageContent::find()->indexBy('slug')->all();
        } else {
            $dbPages = [];
        }

        foreach (PageContent::knownPages() as $slug => $title) {
            $pages[] = [
                'slug'       => $slug,
                'title'      => isset($dbPages[$slug]) ? $dbPages[$slug]->title : $title,
                'has_content'=> isset($dbPages[$slug]) && !empty($dbPages[$slug]->content),
                'updated_at' => isset($dbPages[$slug]) ? $dbPages[$slug]->updated_at : null,
            ];
        }

        return $this->render('index', [
            'pages'     => $pages,
            'available' => $available,
        ]);
    }

    /**
     * Редактировать контент страницы
     */
    public function actionEdit(string $slug)
    {
        if (!array_key_exists($slug, PageContent::knownPages())) {
            throw new NotFoundHttpException('Страница не найдена');
        }

        if (!PageContent::isAvailable()) {
            Yii::$app->session->addFlash('warning', 'Таблица page_content не создана. Выполните миграцию: yii migrate');
            return $this->redirect(['index']);
        }

        $model = PageContent::findOne(['slug' => $slug]);
        if (!$model) {
            $model        = new PageContent();
            $model->slug  = $slug;
            $model->title = PageContent::knownPages()[$slug];
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->updated_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->addFlash('success', 'Страница «' . $model->title . '» сохранена');
                return $this->redirect(['edit', 'slug' => $slug]);
            }
        }

        return $this->render('edit', ['model' => $model]);
    }

    /**
     * AJAX: save content inline (from frontend edit button)
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->user->can('admin') && !Yii::$app->user->identity->isAdmin()) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        $slug    = Yii::$app->request->post('slug');
        $content = Yii::$app->request->post('content');
        $title   = Yii::$app->request->post('title');

        if (!$slug || !array_key_exists($slug, PageContent::knownPages())) {
            return ['success' => false, 'message' => 'Неизвестная страница'];
        }
        if (!PageContent::isAvailable()) {
            return ['success' => false, 'message' => 'DB table missing — run migration'];
        }

        $model = PageContent::findOne(['slug' => $slug]);
        if (!$model) {
            $model        = new PageContent();
            $model->slug  = $slug;
            $model->title = PageContent::knownPages()[$slug];
        }
        if ($content !== null) $model->content = $content;
        if ($title)            $model->title   = $title;
        $model->updated_by = Yii::$app->user->id;

        if ($model->save()) {
            return ['success' => true, 'message' => 'Сохранено'];
        }
        return ['success' => false, 'message' => implode(', ', $model->getFirstErrors())];
    }
}
