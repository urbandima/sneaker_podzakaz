<?php
/**
 * Email шаблон: Подтверждение заявки клиенту
 * 
 * @var $inquiry app\models\CatalogInquiry
 * @var $product app\models\Product
 */

use yii\helpers\Html;

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
            padding: 30px 20px;
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
        .success-message {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .product-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .product-title {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            margin-bottom: 10px;
        }
        .product-brand {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        .product-details {
            padding: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
        }
        .detail-value {
            font-weight: 600;
            color: #000;
        }
        .price {
            font-size: 24px;
            font-weight: 900;
            color: #000;
            text-align: center;
            margin: 20px 0;
        }
        .next-steps {
            background: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #000;
            margin-top: 0;
        }
        .next-steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            padding: 5px 0;
        }
        .contact-info {
            background: #fafafa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .contact-info a {
            color: #000;
            text-decoration: none;
            font-weight: 600;
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
        <h1 style="margin: 0; font-size: 28px;">СНИКЕРХЭД</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;">Ваша заявка принята</p>
    </div>
    
    <div class="content">
        <div class="success-message">
            <strong>✅ Спасибо за заявку!</strong><br>
            Мы получили ваш запрос и свяжемся с вами в ближайшее время.
        </div>

        <p>Здравствуйте, <strong><?= Html::encode($inquiry->name) ?></strong>!</p>
        
        <p>Вы оставили заявку на следующий товар:</p>

        <div class="product-card">
            <div class="product-brand"><?= Html::encode($product->brand->name) ?></div>
            <div class="product-title"><?= Html::encode($product->name) ?></div>
            
            <div class="product-details">
                <?php if ($inquiry->size): ?>
                <div class="detail-row">
                    <span class="detail-label">Размер:</span>
                    <span class="detail-value"><?= Html::encode($inquiry->size) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($inquiry->color): ?>
                <div class="detail-row">
                    <span class="detail-label">Цвет:</span>
                    <span class="detail-value"><?= Html::encode($inquiry->color) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Наличие:</span>
                    <span class="detail-value"><?= $product->getStockStatusLabel() ?></span>
                </div>
            </div>

            <div class="price">
                <?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?>
            </div>
        </div>

        <div class="next-steps">
            <h3>📋 Что дальше?</h3>
            <ol>
                <li>Наш менеджер свяжется с вами в ближайшее время</li>
                <li>Уточним все детали заказа и способ доставки</li>
                <li>Отправим вам счет на оплату</li>
                <li>После оплаты начнем оформление заказа</li>
                <li>Срок доставки: 14-21 день</li>
            </ol>
        </div>

        <div class="contact-info">
            <p><strong>📞 Контакты для связи:</strong></p>
            <p>
                Телефон: <a href="tel:+375447009001">+375 44 700-90-01</a><br>
                Email: <a href="mailto:sneakerkultura@gmail.com">sneakerkultura@gmail.com</a><br>
                Telegram: <a href="https://t.me/sneakerheadbyweb_bot">@sneakerheadbyweb_bot</a>
            </p>
        </div>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            Если у вас возникли вопросы, не стесняйтесь связаться с нами любым удобным способом.
        </p>

        <div class="footer">
            <p>Дата заявки: <?= Yii::$app->formatter->asDatetime($inquiry->created_at) ?></p>
            <p>Номер заявки: #<?= $inquiry->id ?></p>
            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 15px 0;">
            <p><strong>СНИКЕРХЭД</strong></p>
            <p>Оригинальные товары из США и Европы</p>
            <p style="font-size: 12px;">
                УНП 193562995 | ИП Балдов Дмитрий Вячеславович<br>
                220089, г. Минск, ул. Денисовская, 5-102
            </p>
        </div>
    </div>
</body>
</html>
