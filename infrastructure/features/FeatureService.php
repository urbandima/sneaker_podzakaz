<?php

namespace app\infrastructure\features;

/**
 * Сервис управления Feature Flags
 */
class FeatureService
{
    private array $flags;
    private array $groups;

    public function __construct(array $flags = [], array $groups = [])
    {
        $this->flags = $flags;
        $this->groups = $groups;
    }

    /**
     * Проверить, включена ли фича
     */
    public function isEnabled(string $flagName, ?int $userId = null): bool
    {
        $flag = $this->flags[$flagName] ?? null;

        if (!$flag || !($flag['enabled'] ?? false)) {
            return false;
        }

        // Проверка rollout
        if (isset($flag['rollout'])) {
            return $this->checkRollout($flagName, $flag['rollout'], $userId);
        }

        return true;
    }

    /**
     * Получить вариант A/B теста
     */
    public function getVariant(string $flagName, ?int $userId = null): string
    {
        $flag = $this->flags[$flagName] ?? null;

        if (!$flag || !($flag['variants'] ?? false)) {
            return 'default';
        }

        // Детерминированное распределение по userId
        $hash = crc32($flagName . $userId) % 100;
        $cumulative = 0;

        foreach ($flag['distribution'] as $i => $percent) {
            $cumulative += $percent;
            if ($hash < $cumulative) {
                return $flag['variants'][$i];
            }
        }

        return $flag['variants'][0];
    }

    /**
     * Проверить, пользователь в группе
     */
    public function inGroup(string $groupName, ?int $userId = null, ?string $email = null): bool
    {
        $group = $this->groups[$groupName] ?? [];

        if ($userId && in_array($userId, $group['user_ids'] ?? [])) {
            return true;
        }

        if ($email && in_array($email, $group['emails'] ?? [])) {
            return true;
        }

        return false;
    }

    /**
     * Включить фичу для пользователя
     */
    public function enableForUser(string $flagName, int $userId): void
    {
        // Сохраняем в Redis или БД
        $key = "feature:{$flagName}:user:{$userId}";
        \Yii::$app->redis->set($key, 1);
    }

    /**
     * Выключить фичу для пользователя
     */
    public function disableForUser(string $flagName, int $userId): void
    {
        $key = "feature:{$flagName}:user:{$userId}";
        \Yii::$app->redis->del($key);
    }

    /**
     * Проверка rollout
     */
    private function checkRollout(string $flagName, int $percent, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        // Детерминированное распределение
        $hash = crc32($flagName . $userId) % 100;
        return $hash < $percent;
    }
}
