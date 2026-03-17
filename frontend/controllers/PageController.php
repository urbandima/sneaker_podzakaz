<?php

namespace app\frontend\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * PageController — Контроллер статических страниц
 * 
 * Управление информационными страницами сайта:
 * - Условия оплаты
 * - Условия доставки  
 * - Условия возврата и обмена
 * - Политика конфиденциальности
 */
class PageController extends Controller
{
    public $layout = 'main';

    /**
     * Условия оплаты
     */
    public function actionPaymentTerms()
    {
        $this->view->title = 'Способы оплаты — СНИКЕРХЭД';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Удобные способы оплаты заказов в СНИКЕРХЭД. Принимаем банковские карты, онлайн-платежи, наличные при доставке. Безопасная оплата кроссовок.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Способы оплаты — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Удобные и безопасные способы оплаты заказов кроссовок']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/payment-terms');
    }

    /**
     * Условия доставки
     */
    public function actionDeliveryTerms()
    {
        $this->view->title = 'Доставка кроссовок по Беларуси — СНИКЕРХЭД';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Быстрая доставка кроссовок и обуви по всей Беларуси. Курьерская доставка, почта, самовывоз. Сроки, стоимость, отслеживание заказа.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Доставка кроссовок по Беларуси — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Быстрая и удобная доставка кроссовок по всей Беларуси']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/delivery-terms');
    }

    /**
     * Условия возврата и обмена
     */
    public function actionReturnPolicy()
    {
        $this->view->title = 'Возврат и обмен кроссовок — СНИКЕРХЭД';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Условия возврата и обмена кроссовок в СНИКЕРХЭД. Гарантия качества, 14 дней на возврат, правила обмена. Защита прав покупателей.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Возврат и обмен кроссовок — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Гарантия качества и удобные условия возврата кроссовок']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/return-policy');
    }

    /**
     * Политика конфиденциальности
     */
    public function actionPrivacy()
    {
        $this->view->title = 'Политика конфиденциальности — СНИКЕРХЭД';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Политика конфиденциальности СНИКЕРХЭД. Защита персональных данных, обработка информации, права пользователей. Безопасность ваших данных.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Политика конфиденциальности — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Защита персональных данных и конфиденциальность пользователей']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/privacy');
    }

    /**
     * О компании (для юридической информации)
     */
    public function actionAbout()
    {
        $this->view->title = 'О компании СНИКЕРХЭД — магазин оригинальных кроссовок';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'СНИКЕРХЭД — ведущий магазин оригинальных кроссовок в Беларуси. Наша история, миссия, преимущества. 100% подлинность, гарантия качества.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'О компании СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Ведущий магазин оригинальных кроссовок в Беларуси']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/about');
    }

    /**
     * Контакты
     */
    public function actionContacts()
    {
        $this->view->title = 'Контакты СНИКЕРХЭД — свяжитесь с нами';
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Контактная информация СНИКЕРХЭД. Адреса магазинов, телефоны, email, время работы. Свяжитесь с нами для консультации и заказа кроссовок.'
        ]);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Контакты СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Свяжитесь с нами для заказа оригинальных кроссовок']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
        
        return $this->render('pages/contacts');
    }
}
