<?php

use yii\helpers\Url;

?>

<p>Правильно выбрать <strong>размер кроссовок</strong> — важнее, чем выбрать модель. Мы собрали актуальные <strong>таблицы размеров</strong> для трёх самых популярных брендов.</p>

<h2 style="font-size:1.4rem;font-weight:600;margin:32px 0 12px;">Как измерить стопу</h2>
<p>Встаньте на лист бумаги, обведите стопу карандашом. Измерьте расстояние от пятки до самого длинного пальца — это ваша длина стопы в сантиметрах. Всегда измеряйте вечером: стопа расширяется в течение дня.</p>

<h2 style="font-size:1.4rem;font-weight:600;margin:32px 0 12px;">Таблица размеров Nike</h2>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:.9rem;">
    <thead>
        <tr style="background:#1a1a1a;color:#fff;">
            <th style="padding:10px 14px;text-align:left;">Длина стопы (см)</th>
            <th style="padding:10px 14px;">EU</th>
            <th style="padding:10px 14px;">US (муж.)</th>
            <th style="padding:10px 14px;">US (жен.)</th>
            <th style="padding:10px 14px;">UK</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $nikeTable = [
            ['23.5','37.5','5','6','4'],
            ['24.0','38','5.5','6.5','4.5'],
            ['24.5','38.5','6','7','5'],
            ['25.0','39','6.5','7.5','5.5'],
            ['25.5','40','7','8','6'],
            ['26.0','41','7.5','8.5','6.5'],
            ['26.5','42','8','9','7'],
            ['27.0','42.5','8.5','9.5','7.5'],
            ['27.5','43','9','10','8'],
            ['28.0','44','9.5','10.5','8.5'],
            ['28.5','44.5','10','11','9'],
            ['29.0','45','10.5','11.5','9.5'],
            ['29.5','46','11','12','10'],
        ];
        foreach ($nikeTable as $i => $row) :
            $bg = $i % 2 === 0 ? '#f9f9f9' : '#fff';
            ?>
        <tr style="background:<?= $bg ?>;">
            <td style="padding:8px 14px;font-weight:500;"><?= $row[0] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[1] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[2] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[3] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[4] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2 style="font-size:1.4rem;font-weight:600;margin:32px 0 12px;">Таблица размеров Adidas</h2>
<p>Adidas традиционно делает обувь немного уже, чем Nike. Если у вас широкая стопа — рассмотрите широкую линейку (Wide fit).</p>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:.9rem;">
    <thead>
        <tr style="background:#1a1a1a;color:#fff;">
            <th style="padding:10px 14px;text-align:left;">Длина стопы (см)</th>
            <th style="padding:10px 14px;">EU</th>
            <th style="padding:10px 14px;">UK</th>
            <th style="padding:10px 14px;">US</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $adidasTable = [
            ['23.0','36','3.5','4'],
            ['23.5','37','4','4.5'],
            ['24.0','38','4.5','5'],
            ['24.5','38.5','5','5.5'],
            ['25.0','39','5.5','6'],
            ['25.5','40','6','6.5'],
            ['26.0','40.5','6.5','7'],
            ['26.5','41','7','7.5'],
            ['27.0','42','7.5','8'],
            ['27.5','42.5','8','8.5'],
            ['28.0','43','8.5','9'],
            ['28.5','44','9','9.5'],
            ['29.0','44.5','9.5','10'],
            ['29.5','45','10','10.5'],
        ];
        foreach ($adidasTable as $i => $row) :
            $bg = $i % 2 === 0 ? '#f9f9f9' : '#fff';
            ?>
        <tr style="background:<?= $bg ?>;">
            <td style="padding:8px 14px;font-weight:500;"><?= $row[0] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[1] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[2] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[3] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2 style="font-size:1.4rem;font-weight:600;margin:32px 0 12px;">Таблица размеров New Balance</h2>
<p>New Balance известен широкими колодками. Размерная линейка: D (стандарт), 2E (широкая), 4E (очень широкая).</p>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:.9rem;">
    <thead>
        <tr style="background:#1a1a1a;color:#fff;">
            <th style="padding:10px 14px;text-align:left;">Длина стопы (см)</th>
            <th style="padding:10px 14px;">EU</th>
            <th style="padding:10px 14px;">US (муж.)</th>
            <th style="padding:10px 14px;">US (жен.)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $nbTable = [
            ['23.5','37','5','6.5'],
            ['24.0','38','5.5','7'],
            ['24.5','38.5','6','7.5'],
            ['25.0','39','6.5','8'],
            ['25.5','40','7','8.5'],
            ['26.0','41','7.5','9'],
            ['26.5','42','8','9.5'],
            ['27.0','42.5','8.5','10'],
            ['27.5','43','9','10.5'],
            ['28.0','44','9.5','11'],
            ['28.5','44.5','10','11.5'],
            ['29.0','45','10.5','12'],
        ];
        foreach ($nbTable as $i => $row) :
            $bg = $i % 2 === 0 ? '#f9f9f9' : '#fff';
            ?>
        <tr style="background:<?= $bg ?>;">
            <td style="padding:8px 14px;font-weight:500;"><?= $row[0] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[1] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[2] ?></td>
            <td style="padding:8px 14px;text-align:center;"><?= $row[3] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2 style="font-size:1.4rem;font-weight:600;margin:32px 0 12px;">Частые вопросы о размерах кроссовок</h2>

<p><strong>Брать в размер или на размер больше?</strong><br>
Для беговых кроссовок — на полразмера больше. При беге пальцы ударяются о мысок, поэтому небольшой запас убережёт ногти.</p>

<p><strong>Детские размеры кроссовок</strong><br>
Детская стопа растёт на 1–1.5 размера в год. Выбирайте с запасом 1 см от реальной длины стопы ребёнка.</p>

<p style="margin-top:24px;">Все <strong>размеры кроссовок</strong> указаны на странице каждого товара в нашем <a href="<?= Url::to(['/catalog/catalog/index']) ?>" style="color:#2563eb;">каталоге</a>. При необходимости наши консультанты помогут с выбором.</p>
