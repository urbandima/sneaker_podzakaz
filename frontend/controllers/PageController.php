<?php

namespace app\frontend\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;

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
        $this->view->title = 'Условия оплаты';
        return $this->render('pages/payment-terms');
    }

    /**
     * Условия доставки
     */
    public function actionDeliveryTerms()
    {
        $this->view->title = 'Условия доставки';
        return $this->render('pages/delivery-terms');
    }

    /**
     * Условия возврата и обмена
     */
    public function actionReturnPolicy()
    {
        $this->view->title = 'Условия возврата и обмена';
        return $this->render('pages/return-policy');
    }

    /**
     * Политика конфиденциальности
     */
    public function actionPrivacy()
    {
        $this->view->title = 'Политика конфиденциальности';
        return $this->render('pages/privacy');
    }

    /**
     * О компании (для юридической информации)
     */
    public function actionAbout()
    {
        $this->view->title = 'О компании';
        return $this->render('pages/about');
    }

    /**
     * Контакты
     */
    public function actionContacts()
    {
        $this->view->title = 'Контакты';
        return $this->render('pages/contacts');
    }
}
