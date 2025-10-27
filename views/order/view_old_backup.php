<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . $model->order_number;
$company = Yii::$app->settings->getCompany();
?>

<div class="order-view">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="order-card p-4 p-md-5">
                <!-- Заголовок -->
                <div class="text-center mb-5">
                    <div class="brand-logo mb-3">
                        <i class="bi bi-shop" style="font-size: 3rem; color: #667eea;"></i>
                    </div>
                    <h1 class="display-5 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= Yii::$app->params['senderName'] ?></h1>
                    <div class="order-number-badge mt-3">
                        <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 1.1rem; padding: 0.5rem 1.5rem;">Заказ №<?= Html::encode($model->order_number) ?></span>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="card mb-4 shadow-sm hover-lift">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0"><i class="bi bi-person-circle"></i> Информация о заказе</h5>
                    </div>
                    <div class="card-body bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ФИО:</strong> <?= Html::encode($model->client_name) ?></p>
                                <p><strong>Телефон:</strong> <?= Html::encode($model->client_phone) ?></p>
                                <p><strong>Email:</strong> <?= Html::encode($model->client_email) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Дата заказа:</strong> <?= Yii::$app->formatter->asDate($model->created_at, 'long') ?></p>
                                <p><strong>Ориентировочный срок доставки:</strong><br>
                                   <span class="badge bg-info fs-6"><?= Html::encode($model->delivery_date) ?></span>
                                </p>
                                <p><strong>Текущий статус:</strong><br>
                                   <span class="badge bg-success fs-6"><?= $model->getStatusLabel() ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Товары -->
                <div class="card mb-4 shadow-sm hover-lift">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0"><i class="bi bi-cart3"></i> Состав заказа</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th>
                                        <th>Наименование товара</th>
                                        <th class="text-center">Количество</th>
                                        <th class="text-end">Цена</th>
                                        <th class="text-end">Итого</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($model->orderItems as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= Html::encode($item->product_name) ?></td>
                                        <td class="text-center"><?= $item->quantity ?> шт.</td>
                                        <td class="text-end"><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</td>
                                        <td class="text-end fw-bold"><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> BYN</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th colspan="4" class="text-end fs-5">ИТОГО К ОПЛАТЕ:</th>
                                        <th class="text-end fs-5"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Реквизиты для оплаты -->
                <div class="card mb-4 shadow-lg payment-card">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Реквизиты для оплаты</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-currency-dollar" style="font-size: 2.5rem; margin-right: 1rem;"></i>
                                <div>
                                    <h4 class="mb-0">К оплате: <?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</h4>
                                    <small>Оплата по безналичному расчету</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-detail">
                                    <label class="text-muted small">Наименование организации</label>
                                    <div class="d-flex align-items-center">
                                        <strong class="flex-grow-1"><?= Html::encode($company['name']) ?></strong>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?= Html::encode($company['name']) ?>">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-detail">
                                    <label class="text-muted small">УНП</label>
                                    <div class="d-flex align-items-center">
                                        <strong class="flex-grow-1"><?= Html::encode($company['unp']) ?></strong>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?= Html::encode($company['unp']) ?>">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-detail">
                                    <label class="text-muted small">Банк</label>
                                    <div class="d-flex align-items-center">
                                        <strong class="flex-grow-1"><?= Html::encode($company['bank']) ?></strong>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?= Html::encode($company['bank']) ?>">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-detail">
                                    <label class="text-muted small">БИК</label>
                                    <div class="d-flex align-items-center">
                                        <strong class="flex-grow-1"><?= Html::encode($company['bic']) ?></strong>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?= Html::encode($company['bic']) ?>">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="payment-detail">
                                    <label class="text-muted small">Расчетный счет</label>
                                    <div class="d-flex align-items-center">
                                        <strong class="flex-grow-1" style="font-family: monospace; font-size: 1.1rem;"><?= Html::encode($company['account']) ?></strong>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?= Html::encode($company['account']) ?>">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem; margin-right: 1rem; color: #ff9800;"></i>
                                <div class="flex-grow-1">
                                    <strong>Важно! Назначение платежа:</strong>
                                    <div class="mt-2 p-3 bg-white rounded">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <code style="font-size: 1rem; color: #d63384;">Оплата по договору оферты №<?= Html::encode($model->order_number) ?></code>
                                            <button class="btn btn-warning btn-sm copy-btn" data-copy="Оплата по договору оферты №<?= Html::encode($model->order_number) ?>">
                                                <i class="bi bi-clipboard"></i> Копировать
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($company['offer_url'])): ?>
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?= Html::encode($company['offer_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                                <i class="bi bi-file-text"></i> Публичная оферта
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Загрузка подтверждения оплаты -->
                <?php if (!$model->payment_proof): ?>
                <div class="card mb-4 shadow-lg upload-card">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h5 class="mb-0"><i class="bi bi-upload"></i> Шаг 2: Подтверждение оплаты</h5>
                    </div>
                    <div class="card-body">
                        <div class="upload-instructions mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">1</div>
                                <div class="ms-3">
                                    <strong>Совершите оплату</strong> по указанным выше реквизитам
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="step-number">2</div>
                                <div class="ms-3">
                                    <strong>Загрузите подтверждение:</strong> скриншот или фото чека об оплате
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="<?= Url::to(['upload-payment', 'token' => $model->token]) ?>" enctype="multipart/form-data" id="payment-form">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="bi bi-image"></i> Файл подтверждения оплаты</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" class="form-control form-control-lg" name="payment_proof" accept="image/*,application/pdf" required id="file-input">
                                    <div class="file-preview mt-3" id="file-preview" style="display: none;">
                                        <img id="preview-image" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                </div>
                                <small class="text-muted"><i class="bi bi-info-circle"></i> Форматы: JPG, PNG, PDF. Максимальный размер: 5 МБ</small>
                            </div>

                            <div class="form-check mb-4 p-3 bg-light rounded">
                                <input class="form-check-input" type="checkbox" name="offer_accepted" value="1" id="offer_accepted" required style="width: 1.5rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="offer_accepted" style="cursor: pointer;">
                                    <strong>Я согласен с <a href="#" target="_blank">условиями публичной оферты</a></strong>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-lg w-100 pulse-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none; color: white; padding: 1rem; font-size: 1.2rem; font-weight: bold;">
                                <i class="bi bi-check-circle-fill"></i> Подтвердить оплату
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="card mb-4 shadow-lg">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h5 class="mb-0"><i class="bi bi-check-circle-fill"></i> Оплата подтверждена</h5>
                    </div>
                    <div class="card-body">
                        <div class="success-animation text-center mb-4">
                            <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #38ef7d;"></i>
                        </div>
                        <div class="alert alert-success mb-0">
                            <h5 class="alert-heading"><i class="bi bi-check2-all"></i> Подтверждение оплаты загружено успешно!</h5>
                            <hr>
                            <p class="mb-2"><strong>Дата загрузки:</strong> <?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?></p>
                            <p class="mb-0"><i class="bi bi-info-circle"></i> Ваш заказ находится на проверке. Менеджер свяжется с вами в ближайшее время.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- История изменений -->
                <?php if (!empty($model->history)): ?>
                <div class="card shadow-sm">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> История заказа</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($model->history as $history): ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="badge bg-primary rounded-circle" style="width: 40px; height: 40px; line-height: 30px;">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold fs-5"><?= $history->getNewStatusLabel() ?></div>
                                    <div class="text-muted">
                                        <?= Yii::$app->formatter->asDatetime($history->created_at, 'long') ?>
                                    </div>
                                    <?php if ($history->comment): ?>
                                        <div class="mt-1 text-secondary"><?= Html::encode($history->comment) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Контакты -->
                <div class="text-center mt-5 text-muted">
                    <p class="mb-1">
                        <i class="bi bi-telephone"></i> <?= Html::encode($company['phone']) ?> |
                        <i class="bi bi-envelope"></i> <?= Html::encode($company['email']) ?>
                    </p>
                    <p class="mb-0">
                        <small>По всем вопросам обращайтесь к вашему менеджеру</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Современный API копирования для кнопок copy-btn
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const textToCopy = this.getAttribute('data-copy');
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    showCopySuccess(btn);
                }).catch(function(err) {
                    fallbackCopy(textToCopy, btn);
                });
            } else {
                fallbackCopy(textToCopy, btn);
            }
        });
    });
    
    // Превью загружаемого файла
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('file-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

function showCopySuccess(btn) {
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i>';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary', 'btn-warning');
    
    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        if (originalHTML.includes('Копировать')) {
            btn.classList.add('btn-warning');
        } else {
            btn.classList.add('btn-outline-secondary');
        }
    }, 2000);
}

function fallbackCopy(text, btn) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(btn);
    } catch (err) {
        alert('Не удалось скопировать');
    }
    
    document.body.removeChild(textarea);
}
</script>
