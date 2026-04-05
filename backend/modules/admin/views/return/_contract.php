<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\returns\models\ReturnRequest $model */

use yii\helpers\Html;

$company = Yii::$app->settings->getCompany() ?? [];

$companyName    = $company['name']         ?? 'СНИКЕРХЭД';
$companyUnp     = $company['unp']          ?? '___________';
$companyAddress = $company['address']      ?? '___________';
$companyPhone   = $company['phone']        ?? '___________';
$companyEmail   = $company['email']        ?? '___________';
$bankDetails    = $company['bank_details'] ?? '___________';

$clientName     = $model->order->client_name  ?? '___________';
$clientEmail    = $model->order->client_email ?? '';
$clientPhone    = $model->order->client_phone ?? '';

$today          = date('«d» F Y г.', $model->created_at);
$contractNumber = 'КД-' . $model->return_number;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Комиссионный договор <?= Html::encode($contractNumber) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: #fff;
            padding: 2cm 2.5cm;
        }
        h1 {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 2rem;
            color: #333;
        }
        .parties {
            margin-bottom: 1.5rem;
        }
        .parties p { margin-bottom: 0.5rem; }
        h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 1.5rem 0 0.75rem;
        }
        p { margin-bottom: 0.5rem; }
        ol { padding-left: 1.5rem; margin-bottom: 0.75rem; }
        ol li { margin-bottom: 0.35rem; }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 3rem;
        }
        .sig-block { border-top: 1px solid #000; padding-top: 0.5rem; }
        .sig-block p { font-size: 10pt; margin-bottom: 0.25rem; }
        .sig-line {
            margin-top: 2rem;
            border-bottom: 1px solid #000;
            width: 60%;
            height: 1.5rem;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 11pt;
        }
        .item-table th, .item-table td {
            border: 1px solid #000;
            padding: 0.4rem 0.6rem;
        }
        .item-table th { background: #eee; font-weight: bold; }
        .stamp-box {
            border: 1px dashed #999;
            width: 120px; height: 80px;
            display: flex; align-items: center; justify-content: center;
            font-size: 9pt; color: #999; margin-top: 1rem;
        }
        @media print {
            body { padding: 1.5cm 2cm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="background:#f0f4ff;border:1px solid #93c5fd;border-radius:6px;padding:12px 20px;margin-bottom:20px;font-family:sans-serif;font-size:13px;display:flex;align-items:center;justify-content:space-between;">
    <span><i>Предварительный просмотр договора — не является официальным документом.</i></span>
    <button onclick="window.print()" style="padding:6px 18px;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">
        Печать / Сохранить PDF
    </button>
</div>

<h1>Комиссионный договор</h1>
<div class="subtitle">№ <?= Html::encode($contractNumber) ?> от <?= $today ?></div>

<div class="parties">
    <p>
        <strong><?= Html::encode($companyName) ?></strong>,
        УНП <?= Html::encode($companyUnp) ?>,
        расположенный по адресу: <?= Html::encode($companyAddress) ?>,
        именуемый в дальнейшем <strong>«Комиссионер»</strong>,
        с одной стороны, и
    </p>
    <p>
        <strong><?= Html::encode($clientName) ?></strong>
        <?= $clientPhone ? ', тел.: ' . Html::encode($clientPhone) : '' ?>
        <?= $clientEmail ? ', email: ' . Html::encode($clientEmail) : '' ?>,
        именуемый в дальнейшем <strong>«Комитент»</strong>,
        с другой стороны,
    </p>
    <p>совместно именуемые «Стороны», заключили настоящий договор о нижеследующем:</p>
</div>

<h2>1. Предмет договора</h2>
<ol>
    <li>Комитент поручает, а Комиссионер принимает на себя обязательство от своего имени, но за счёт и в интересах Комитента, совершить сделку по продаже товара, указанного в Приложении № 1 к настоящему договору.</li>
    <li>Право собственности на товар сохраняется за Комитентом до момента его реализации третьему лицу.</li>
</ol>

<h2>2. Товар</h2>
<table class="item-table">
    <thead>
        <tr>
            <th>№</th>
            <th>Наименование товара</th>
            <th>Размер / цвет</th>
            <th>Кол-во</th>
            <th>Оценочная стоимость, BYN</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($model->order && $model->order->orderItems): ?>
            <?php $i = 1; foreach ($model->order->orderItems as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= Html::encode($item->product_name) ?></td>
                <td><?= Html::encode(implode(' / ', array_filter([$item->size, $item->color]))) ?: '—' ?></td>
                <td><?= Html::encode($item->quantity) ?></td>
                <td><?= number_format($item->price, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;color:#999;">Позиции товаров не указаны</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>3. Вознаграждение Комиссионера</h2>
<ol>
    <li>За выполнение поручения по настоящему договору Комиссионер получает вознаграждение в размере <strong>_______ %</strong> от фактической цены продажи товара.</li>
    <li>Вознаграждение удерживается Комиссионером из суммы, поступившей за реализованный товар.</li>
</ol>

<h2>4. Обязанности сторон</h2>
<ol>
    <li>Комиссионер обязуется: принять товар, разместить его для продажи, отчитаться перед Комитентом о результатах реализации в течение 30 (тридцати) дней.</li>
    <li>Комитент обязуется: передать товар в надлежащем состоянии, подтвердить его подлинность, своевременно предоставить документы.</li>
</ol>

<h2>5. Срок действия договора</h2>
<ol>
    <li>Договор вступает в силу с момента его подписания обеими Сторонами и действует до полного исполнения принятых обязательств.</li>
    <li>По истечении 90 (девяноста) дней нереализованный товар возвращается Комитенту.</li>
</ol>

<h2>6. Прочие условия</h2>
<ol>
    <li>Все споры, возникающие из настоящего договора, разрешаются путём переговоров, а при недостижении согласия — в суде по месту нахождения Комиссионера.</li>
    <li>Договор составлен в двух экземплярах, имеющих равную юридическую силу.</li>
</ol>

<h2>7. Реквизиты сторон</h2>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:0.75rem;font-size:11pt;">
    <div>
        <strong>Комиссионер:</strong><br>
        <?= Html::encode($companyName) ?><br>
        УНП: <?= Html::encode($companyUnp) ?><br>
        Адрес: <?= Html::encode($companyAddress) ?><br>
        Тел.: <?= Html::encode($companyPhone) ?><br>
        Email: <?= Html::encode($companyEmail) ?><br>
        <?php if ($bankDetails): ?>
        <br><?= nl2br(Html::encode($bankDetails)) ?>
        <?php endif; ?>
    </div>
    <div>
        <strong>Комитент:</strong><br>
        <?= Html::encode($clientName) ?><br>
        <?= $clientPhone ? 'Тел.: ' . Html::encode($clientPhone) . '<br>' : '' ?>
        <?= $clientEmail ? 'Email: ' . Html::encode($clientEmail) . '<br>' : '' ?>
        <br>Паспорт: _______________________<br>
        Выдан: _______________________
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        <p><strong>Комиссионер</strong></p>
        <p>_________________ / <?= Html::encode($companyName) ?></p>
        <div class="sig-line"></div>
        <p style="margin-top:0.25rem;font-size:9pt;color:#555;">Подпись / Дата</p>
        <div class="stamp-box">М.П.</div>
    </div>
    <div class="sig-block">
        <p><strong>Комитент</strong></p>
        <p>_________________ / <?= Html::encode($clientName) ?></p>
        <div class="sig-line"></div>
        <p style="margin-top:0.25rem;font-size:9pt;color:#555;">Подпись / Дата</p>
    </div>
</div>

</body>
</html>
