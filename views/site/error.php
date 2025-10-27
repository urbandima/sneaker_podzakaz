<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <h1 class="display-1 text-danger"><?= Html::encode($name) ?></h1>
                        <div class="alert alert-danger mt-4">
                            <?= nl2br(Html::encode($message)) ?>
                        </div>
                        <p class="text-muted mt-4">
                            Произошла ошибка при обработке вашего запроса.
                        </p>
                        <div class="mt-4">
                            <?= Html::a('← Вернуться на главную', ['/'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
