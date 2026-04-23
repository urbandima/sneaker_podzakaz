<?php

namespace app\models;

// Canonical implementation lives in account module (secure HMAC cookie, sameSite: Lax).
// Bootstrap aliases ensure app\models\CustomerLoginForm resolves there; this file is dead
// code kept only as a namespace placeholder.
class_alias(\app\backend\modules\account\models\CustomerLoginForm::class, __NAMESPACE__ . '\CustomerLoginFormAlias');
