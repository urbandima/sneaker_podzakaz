<?php

/**
 * SeoController — SEO инструменты
 * 
 * НАЗНАЧЕНИЕ:
 * Управление редиректами, sitemap, robots.txt, массовое редактирование meta-тегов
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\backend\modules\seo\models\Redirect;
use app\backend\modules\seo\models\SeoMetaBulkForm;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Category;

class SeoController extends BaseAdminController
{
    /**
     * Главная страница SEO инструментов
     */
    public function actionIndex()
    {
        $stats = [
            'redirects' => Redirect::find()->count(),
            'activeRedirects' => Redirect::find()->where(['is_active' => true])->count(),
            'productsWithoutMeta' => Product::find()->where(['or', ['meta_title' => null], ['meta_description' => null]])->count(),
            'categoriesWithoutMeta' => Category::find()->where(['or', ['meta_title' => null], ['meta_description' => null]])->count(),
        ];

        return $this->render('index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Управление редиректами
     */
    public function actionRedirects()
    {
        $query = Redirect::find()->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('redirects', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание/редактирование редиректа
     */
    public function actionRedirectEdit($id = null)
    {
        $model = $id ? Redirect::findOne($id) : new Redirect();

        if (!$model) {
            throw new NotFoundHttpException('Редирект не найден');
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->updated_at = time();
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Редирект сохранён');
                return $this->redirect(['redirects']);
            }
        }

        return $this->render('redirect-edit', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление редиректа
     */
    public function actionRedirectDelete($id)
    {
        $model = Redirect::findOne($id);
        if ($model) {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Редирект удалён');
        }
        return $this->redirect(['redirects']);
    }

    /**
     * Массовое редактирование meta-тегов
     */
    public function actionBulkMeta()
    {
        $form = new SeoMetaBulkForm();
        $results = [];

        if (Yii::$app->request->isPost && $form->load(Yii::$app->request->post())) {
            $results = $form->apply();
        }

        // Получаем статистику
        $products = Product::find()
            ->select(['id', 'name', 'meta_title', 'meta_description'])
            ->where(['is_active' => true])
            ->limit(50)
            ->all();

        $categories = Category::find()
            ->select(['id', 'name', 'meta_title', 'meta_description'])
            ->where(['is_active' => true])
            ->all();

        return $this->render('bulk-meta', [
            'form' => $form,
            'products' => $products,
            'categories' => $categories,
            'results' => $results,
        ]);
    }

    /**
     * Генерация sitemap.xml
     */
    public function actionSitemap()
    {
        $generator = new \app\backend\modules\seo\components\SitemapGenerator();
        
        if (Yii::$app->request->post('regenerate')) {
            $result = $generator->generate();
            Yii::$app->session->setFlash('success', 
                "Sitemap сгенерирован: {$result['urls']} URL, файл: {$result['file']}"
            );
        }

        $sitemapExists = file_exists(Yii::getAlias('@frontend/web/sitemap.xml'));
        $lastModified = $sitemapExists 
            ? date('d.m.Y H:i', filemtime(Yii::getAlias('@frontend/web/sitemap.xml')))
            : null;

        return $this->render('sitemap', [
            'exists' => $sitemapExists,
            'lastModified' => $lastModified,
        ]);
    }

    /**
     * Управление robots.txt
     */
    public function actionRobots()
    {
        $robotsPath = Yii::getAlias('@frontend/web/robots.txt');
        $defaultContent = "User-agent: *\nDisallow: /admin/\nDisallow: /cart/\nAllow: /\n\nSitemap: /sitemap.xml";

        if (Yii::$app->request->isPost) {
            $content = Yii::$app->request->post('robots_content', $defaultContent);
            file_put_contents($robotsPath, $content);
            Yii::$app->session->setFlash('success', 'robots.txt обновлён');
        }

        $content = file_exists($robotsPath) 
            ? file_get_contents($robotsPath) 
            : $defaultContent;

        return $this->render('robots', [
            'content' => $content,
        ]);
    }

    /**
     * AJAX: Обновить meta тег для товара
     */
    public function actionUpdateProductMeta()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');

        $product = Product::findOne($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        if (in_array($field, ['meta_title', 'meta_description', 'meta_keywords'])) {
            $product->$field = $value;
            if ($product->save(false, [$field])) {
                return ['success' => true];
            }
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    /**
     * Управление ALT текстами изображений
     */
    public function actionAltTexts()
    {
        $products = Product::find()
            ->where(['is_active' => true])
            ->with(['images'])
            ->limit(50)
            ->all();

        $stats = [
            'total_images' => 0,
            'with_alt' => 0,
            'without_alt' => 0,
        ];

        foreach ($products as $product) {
            foreach ($product->images as $image) {
                $stats['total_images']++;
                if (!empty($image->alt_text)) {
                    $stats['with_alt']++;
                } else {
                    $stats['without_alt']++;
                }
            }
        }

        return $this->render('alt-texts', [
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX: Обновить ALT текст изображения
     */
    public function actionUpdateImageAlt()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $altText = Yii::$app->request->post('alt_text');

        $image = \app\backend\modules\catalog\models\ProductImage::findOne($id);
        if (!$image) {
            return ['success' => false, 'message' => 'Изображение не найдено'];
        }

        $image->alt_text = $altText;
        if ($image->save(false, ['alt_text'])) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }
}
