<?php

/**
 * UserController — Управление пользователями админ-панели
 *
 * НАЗНАЧЕНИЕ:
 * CRUD операции для пользователей админ-панели: администраторы,
 * менеджеры, логисты. Управление ролями и правами доступа.
 *
 * ФУНКЦИИ:
 * - Список пользователей (index)
 * - Создание пользователя (create)
 * - Редактирование пользователя (update)
 * - Удаление пользователя (delete)
 * - Смена пароля пользователя (change-password)
 * - Получение списка логистов для AJAX (logists)
 *
 * СВЯЗИ:
 * - User (модель пользователя)
 *
 * ДОСТУП:
 * - Только администраторы
 *
 * РОЛИ:
 * - admin: полный доступ ко всему
 * - manager: управление заказами, товарами, покупателями
 * - logist: работа с назначенными заказами (доставка)
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\backend\modules\admin\models\User;

class UserController extends BaseAdminController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Получение списка логистов для AJAX
     */
    public function actionLogists()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $logists = User::find()
                ->where(['role' => 'logist'])
                ->andWhere(['status' => 'active'])
                ->orderBy(['username' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($logists as $logist) {
                $result[] = [
                    'id' => $logist->id,
                    'username' => $logist->username,
                    'email' => $logist->email,
                ];
            }

            return ['success' => true, 'logists' => $result];
        } catch (\Exception $e) {
            // Демо-данные при отсутствии БД
            return [
                'success' => true,
                'logists' => [
                    ['id' => 4, 'username' => 'logist1', 'email' => 'logist1@sneakerhead.by'],
                    ['id' => 5, 'username' => 'logist2', 'email' => 'logist2@sneakerhead.by'],
                ]
            ];
        }
    }

    /**
     * Список пользователей
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание нового пользователя
     */
    public function actionCreate()
    {
        $model = new User();
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post())) {
            // Устанавливаем статус активен
            $model->status = User::STATUS_ACTIVE;

            // Хешируем пароль
            $model->setPassword($model->password);
            $model->generateAuthKey();

            if ($model->save()) {
                $currentUser = $this->getCurrentUser();
                Yii::info('Создан новый пользователь: ' . $model->username . ' (роль: ' . $model->role . ') админом #' . $currentUser->id, 'user');
                $this->flashSuccess('Пользователь успешно создан!');
                return $this->redirect(['/admin/user/index']);
            } else {
                $this->flashError('Ошибка при создании пользователя: ' . json_encode($model->errors));
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * AJAX: Сброс пароля пользователя
     */
    public function actionResetPassword()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        if (!$id) {
            return ['success' => false, 'message' => 'ID не указан'];
        }

        $currentUser = $this->getCurrentUser();
        if ($id == $currentUser->id) {
            return ['success' => false, 'message' => 'Нельзя сбросить собственный пароль через эту форму'];
        }

        try {
            $user = User::findOne($id);
            if (!$user) {
                return ['success' => false, 'message' => 'Пользователь не найден'];
            }

            // Generate a secure random password
            $newPassword = Yii::$app->security->generateRandomString(10);
            $user->setPassword($newPassword);

            if ($user->save(false)) {
                Yii::info('Сброшен пароль для пользователя #' . $id . ' (admin #' . $currentUser->id . ')', 'user');
                return ['success' => true, 'password' => $newPassword];
            }

            return ['success' => false, 'message' => 'Ошибка сохранения'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Переключить блокировку пользователя
     */
    public function actionToggleBlock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        if (!$id) {
            return ['success' => false, 'message' => 'ID не указан'];
        }

        $currentUser = $this->getCurrentUser();
        if ($id == $currentUser->id) {
            return ['success' => false, 'message' => 'Нельзя заблокировать самого себя'];
        }

        try {
            $user = User::findOne($id);
            if (!$user) {
                return ['success' => false, 'message' => 'Пользователь не найден'];
            }

            $wasActive = ($user->status == User::STATUS_ACTIVE);
            $user->status = $wasActive ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;

            if ($user->save(false)) {
                Yii::info('Статус пользователя #' . $id . ' изменён на ' . $user->status . ' (admin #' . $currentUser->id . ')', 'user');
                return ['success' => true, 'status' => $user->status, 'blocked' => !$wasActive];
            }

            return ['success' => false, 'message' => 'Ошибка сохранения'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Редактирование пользователя
     *
     * @param int $id
     */
    public function actionEdit($id = null)
    {
        if ($id === null) {
            $id = Yii::$app->request->get('id');
        }

        $model = $id ? User::findOne($id) : null;
        if ($model === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        $model->scenario = 'update';

        if ($model->load(Yii::$app->request->post())) {
            if (!empty($model->password)) {
                $model->setPassword($model->password);
            }
            if ($model->save()) {
                $this->flashSuccess('Пользователь обновлён.');
                return $this->redirect(['/admin/user/index']);
            } else {
                $this->flashError('Ошибка: ' . json_encode($model->errors));
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * CMP-430/J: выгрузка сотрудников админки в CSV (по образцу
     * CustomerController::actionExport). Набор колонок другой — у User
     * нет orders_count/default_city, зато есть role.
     */
    public function actionExport()
    {
        $users = User::find()
            ->where(['!=', 'status', User::STATUS_DELETED])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $filename = 'users_' . date('Y-m-d_H-i-s') . '.csv';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/csv; charset=utf-8');
        Yii::$app->response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM для Excel

        fputcsv($output, [
            'ID', 'Логин', 'Email', 'Роль', 'Статус', 'Дата регистрации'
        ], ';', '"', '\\');

        $statusLabels = [
            User::STATUS_ACTIVE => 'Активен',
            User::STATUS_INACTIVE => 'Заблокирован',
        ];

        foreach ($users as $user) {
            fputcsv($output, [
                $user->id, $user->username, $user->email,
                $user->getRoleName(), $statusLabels[$user->status] ?? $user->status,
                date('d.m.Y H:i', $user->created_at),
            ], ';', '"', '\\');
        }

        fclose($output);
        return Yii::$app->response;
    }

    /**
     * Удаление пользователя
     *
     * @param int $id
     */
    public function actionDelete($id)
    {
        $currentUser = $this->getCurrentUser();

        // Нельзя удалить самого себя
        if ($id == $currentUser->id) {
            $this->flashError('Нельзя удалить самого себя.');
            return $this->redirect(['/admin/user/index']);
        }

        $userToDelete = User::findOne($id);
        if ($userToDelete === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        $username = $userToDelete->username;
        if ($userToDelete->delete()) {
            Yii::info('Удален пользователь: ' . $username . ' (ID: ' . $id . ') админом #' . $currentUser->id, 'user');
            $this->flashSuccess('Пользователь успешно удален.');
        } else {
            $this->flashError('Ошибка при удалении пользователя.');
        }

        return $this->redirect(['/admin/user/index']);
    }
}
