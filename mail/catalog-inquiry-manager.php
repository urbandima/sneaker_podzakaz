<?php
/**
 * Email шаблон: Уведомление менеджеру о новой заявке из каталога
 * 
 * @var $inquiry app\models\CatalogInquiry
 * @var $product app\models\Product
 * @var $order app\models\Order (если создан)
 */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #000000;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-top: none;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .alert {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
        }
        .product-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .customer-info {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            min-width: 120px;
            color: #666;
        }
        .info-value {
            flex: 1;
            color: #000;
        }
        .button {
            display: inline-block;
            background: #000000;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background: #333333;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">🛍️ Новая заявка из каталога</h1>
    </div>
    
    <div class="content">
        <div class="alert">
            <strong>⚠️ Требуется действие!</strong><br>
            Получена новая заявка из каталога товаров. Пожалуйста, свяжитесь с клиентом в ближайшее время.
        </div>

        <h2 style="color: #000;">Информация о товаре</h2>
        <div class="product-info">
            <div class="info-row">
                <div class="info-label">Товар:</div>
                <div class="info-value"><strong><?= Html::encode($product->name) ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Бренд:</div>
                <div class="info-value"><?= Html::encode($product->brand->name) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Категория:</div>
                <div class="info-value"><?= Html::encode($product->category->name) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Цена:</div>
                <div class="info-value"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
            </div>
            <?php if ($inquiry->size): ?>
            <div class="info-row">
                <div class="info-label">Размер:</div>
                <div class="info-value"><?= Html::encode($inquiry->size) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($inquiry->color): ?>
            <div class="info-row">
                <div class="info-label">Цвет:</div>
                <div class="info-value"><?= Html::encode($inquiry->color) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <h2 style="color: #000;">Контактные данные клиента</h2>
        <div class="customer-info">
            <div class="info-row">
                <div class="info-label">Имя:</div>
                <div class="info-value"><strong><?= Html::encode($inquiry->name) ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Телефон:</div>
                <div class="info-value">
                    <a href="tel:<?= Html::encode($inquiry->phone) ?>" style="color: #000; text-decoration: none;">
                        <?= Html::encode($inquiry->phone) ?>
                    </a>
                </div>
            </div>
            <?php if ($inquiry->email): ?>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">
                    <a href="mailto:<?= Html::encode($inquiry->email) ?>" style="color: #000;">
                        <?= Html::encode($inquiry->email) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($inquiry->message): ?>
            <div class="info-row">
                <div class="info-label">Комментарий:</div>
                <div class="info-value"><?= Html::encode($inquiry->message) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($order)): ?>
        <div style="text-align: center;">
            <p><strong>✅ Заказ автоматически создан</strong></p>
            <p>Номер заказа: <strong><?= Html::encode($order->order_number) ?></strong></p>
            <a href="<?= Url::to(['admin/view-order', 'id' => $order->id], true) ?>" class="button">
                Открыть заказ в CRM
            </a>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Дата заявки: <?= Yii::$app->formatter->asDatetime($inquiry->created_at) ?></p>
            <p>ID заявки: #<?= $inquiry->id ?></p>
            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 15px 0;">
            <p>СНИКЕРХЭД - Система управления заказами</p>
        </div>
    </div>
</body>
</html>
