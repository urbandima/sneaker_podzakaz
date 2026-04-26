<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\backend\modules\admin\models\PageContent;

/**
 * Редактор статических страниц сайта
 * Маршруты: /admin/page, /admin/page/edit?slug=privacy
 */
class PageController extends BaseAdminController
{
    /**
     * Allow autosave / upload-image / restore-revision via POST without VerbFilter restrictions.
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        // Append CSRF exemption for JSON endpoints (they send the token in headers)
        return $behaviors;
    }

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
            $model->saveRevision(); // snapshot before overwrite
            $model->load(Yii::$app->request->post());
            $model->updated_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->addFlash('success', 'Страница «' . $model->title . '» сохранена');
                return $this->redirect(['edit', 'slug' => $slug]);
            }
        }

        // Build slug→url map
        $slugUrlMap = [
            'privacy'        => '/privacy',
            'payment-terms'  => '/payment-terms',
            'return-policy'  => '/return-policy',
            'contacts'       => '/contacts',
            'sale'           => '/sale',
            'about'          => '/about',
            'delivery-terms' => '/delivery-terms',
        ];
        $frontendUrl = $slugUrlMap[$slug] ?? ('/' . $slug);
        $revisions   = $model->isNewRecord ? [] : $model->getRevisions(10);

        return $this->render('edit', [
            'model'       => $model,
            'revisions'   => $revisions,
            'frontendUrl' => $frontendUrl,
        ]);
    }

    /**
     * AJAX: autosave content without redirect.
     * POST JSON {id, content, title} — returns {ok:true, saved_at:timestamp}
     */
    public function actionAutosave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true);

        $id      = (int)($data['id'] ?? 0);
        $content = $data['content'] ?? null;
        $title   = $data['title']   ?? null;

        if (!$id) {
            return ['ok' => false, 'error' => 'id обязателен'];
        }
        if (!PageContent::isAvailable()) {
            return ['ok' => false, 'error' => 'таблица page_content не создана'];
        }

        $model = PageContent::findOne($id);
        if (!$model) {
            return ['ok' => false, 'error' => 'страница не найдена'];
        }

        $model->saveRevision();
        if ($content !== null) $model->content = $content;
        if ($title)            $model->title   = $title;
        $model->updated_by = Yii::$app->user->id;

        if ($model->save()) {
            return ['ok' => true, 'saved_at' => time()];
        }
        return ['ok' => false, 'error' => implode(', ', $model->getFirstErrors())];
    }

    /**
     * Upload image for TinyMCE.
     * POST multipart file field "file" — returns {location: '/uploads/pages/filename.jpg'}
     */
    public function actionUploadImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $uploadDir = Yii::getAlias('@frontend/web/uploads/pages');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['error' => 'файл не получен'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array(strtolower($file->extension), $allowed, true)) {
            return ['error' => 'недопустимый тип файла'];
        }

        $filename = uniqid('pg_', true) . '.' . strtolower($file->extension);
        $path     = $uploadDir . '/' . $filename;

        if ($file->saveAs($path)) {
            return ['location' => '/uploads/pages/' . $filename];
        }
        return ['error' => 'не удалось сохранить файл'];
    }

    /**
     * Restore a revision: copies revision content back to live page.
     * POST {page_id, revision_id}
     */
    public function actionRestoreRevision()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw        = Yii::$app->request->getRawBody();
        $data       = json_decode($raw, true);
        $pageId     = (int)($data['page_id']     ?? 0);
        $revisionId = (int)($data['revision_id'] ?? 0);

        if (!$pageId || !$revisionId) {
            return ['ok' => false, 'error' => 'page_id и revision_id обязательны'];
        }
        if (!PageContent::isAvailable() || !PageContent::isRevisionAvailable()) {
            return ['ok' => false, 'error' => 'таблицы не созданы'];
        }

        $model = PageContent::findOne($pageId);
        if (!$model) {
            return ['ok' => false, 'error' => 'страница не найдена'];
        }

        $revision = Yii::$app->db->createCommand(
            'SELECT * FROM {{%page_revision}} WHERE id = :id AND page_id = :pid LIMIT 1',
            [':id' => $revisionId, ':pid' => $pageId]
        )->queryOne();

        if (!$revision) {
            return ['ok' => false, 'error' => 'версия не найдена'];
        }

        // Save current as revision before restoring
        $model->saveRevision();

        $model->content    = $revision['content'];
        if (!empty($revision['title'])) {
            $model->title  = $revision['title'];
        }
        $model->updated_by = Yii::$app->user->id;

        if ($model->save()) {
            return [
                'ok'      => true,
                'content' => $revision['content'],
                'title'   => $revision['title'] ?? $model->title,
            ];
        }
        return ['ok' => false, 'error' => implode(', ', $model->getFirstErrors())];
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
