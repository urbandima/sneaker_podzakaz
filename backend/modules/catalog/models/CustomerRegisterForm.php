<?php

namespace app\models;

// Canonical implementation lives in account module.
// Bootstrap aliases ensure app\models\CustomerRegisterForm resolves there; this file is dead
// code kept only as a namespace placeholder.
class_alias(\app\backend\modules\account\models\CustomerRegisterForm::class, __NAMESPACE__ . '\CustomerRegisterFormAlias');
