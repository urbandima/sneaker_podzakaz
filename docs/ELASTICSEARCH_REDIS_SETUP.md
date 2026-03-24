# 🚀 Установка и настройка Elasticsearch и Redis

## 📋 Содержание

1. [Быстрый старт](#быстрый-старт)
2. [Установка через Docker](#установка-через-docker)
3. [Установка на сервер](#установка-на-сервер)
4. [Настройка проекта](#настройка-проекта)
5. [Первичная индексация](#первичная-индексация)
6. [Проверка работы](#проверка-работы)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Быстрый старт

### 1. Запустить сервисы через Docker

```bash
# Запустить Elasticsearch и Redis
docker-compose -f docker-compose.elasticsearch-redis.yml up -d

# Проверить статус
docker-compose -f docker-compose.elasticsearch-redis.yml ps
```

### 2. Установить зависимости PHP

```bash
composer update
```

### 3. Настроить .env

```bash
# Скопировать .env.example если ещё не сделано
cp .env.example .env

# Добавить/проверить настройки
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DATABASE=0

ELASTICSEARCH_HOST=localhost:9200
```

### 4. Создать индекс и индексировать товары

```bash
# Создать индекс
php yii elasticsearch/create-index

# Индексировать все товары
php yii elasticsearch/index-all
```

### 5. Готово! 🎉

Теперь поиск работает через Elasticsearch, а кеш через Redis.

---

## 🐳 Установка через Docker

### Вариант 1: Использовать готовый docker-compose.yml

```bash
# Запустить сервисы
docker-compose -f docker-compose.elasticsearch-redis.yml up -d

# Остановить сервисы
docker-compose -f docker-compose.elasticsearch-redis.yml down

# Остановить и удалить данные
docker-compose -f docker-compose.elasticsearch-redis.yml down -v
```

### Вариант 2: Запустить вручную

#### Elasticsearch:

```bash
docker run -d \
  --name sneakerhead_elasticsearch \
  -p 9200:9200 \
  -p 9300:9300 \
  -e "discovery.type=single-node" \
  -e "xpack.security.enabled=false" \
  -e "ES_JAVA_OPTS=-Xms512m -Xmx512m" \
  -v es_data:/usr/share/elasticsearch/data \
  elasticsearch:8.11.0
```

#### Redis:

```bash
docker run -d \
  --name sneakerhead_redis \
  -p 6379:6379 \
  -v redis_data:/data \
  redis:7-alpine redis-server --appendonly yes
```

### Проверка запуска:

```bash
# Elasticsearch
curl http://localhost:9200

# Redis
redis-cli ping
# Должно вернуть: PONG
```

---

## 💻 Установка на сервер (Ubuntu/Debian)

### Elasticsearch

```bash
# Установить Java
sudo apt update
sudo apt install openjdk-11-jdk -y

# Добавить репозиторий Elasticsearch
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list

# Установить Elasticsearch
sudo apt update
sudo apt install elasticsearch -y

# Настроить
sudo nano /etc/elasticsearch/elasticsearch.yml
# Раскомментировать и изменить:
# network.host: localhost
# http.port: 9200
# xpack.security.enabled: false

# Запустить
sudo systemctl start elasticsearch
sudo systemctl enable elasticsearch

# Проверить
curl http://localhost:9200
```

### Redis

```bash
# Установить Redis
sudo apt update
sudo apt install redis-server -y

# Настроить
sudo nano /etc/redis/redis.conf
# Изменить:
# supervised systemd
# bind 127.0.0.1 ::1

# Перезапустить
sudo systemctl restart redis
sudo systemctl enable redis

# Проверить
redis-cli ping
```

---

## ⚙️ Настройка проекта

### 1. Обновить composer.json

Уже добавлено:
```json
{
  "require": {
    "elasticsearch/elasticsearch": "^8.0",
    "yiisoft/yii2-redis": "^2.0.20"
  }
}
```

### 2. Установить зависимости

```bash
composer update
```

### 3. Настроить .env

```env
# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Elasticsearch
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_USERNAME=
ELASTICSEARCH_PASSWORD=
```

### 4. Проверить конфигурацию

Файл `/infrastructure/config/web.php` уже содержит:

```php
'redis' => [
    'class' => 'yii\redis\Connection',
    'hostname' => env('REDIS_HOST', 'localhost'),
    'port' => (int) env('REDIS_PORT', 6379),
    'database' => (int) env('REDIS_DATABASE', 0),
],
'elasticsearch' => [
    'class' => 'app\infrastructure\services\ElasticsearchService',
],
```

---

## 📊 Первичная индексация

### Создать индекс

```bash
php yii elasticsearch/create-index
```

Вывод:
```
Создание индекса Elasticsearch...
✅ Индекс создан успешно
```

### Индексировать все товары

```bash
php yii elasticsearch/index-all
```

Вывод:
```
Индексация всех товаров...
✅ Индексация завершена
   Успешно: 150
   Ошибок: 0
```

### Пересоздать индекс (если нужно)

```bash
php yii elasticsearch/reindex
```

### Индексировать один товар

```bash
php yii elasticsearch/index-product 123
```

---

## ✅ Проверка работы

### 1. Проверить Elasticsearch

```bash
# Проверить индекс
curl http://localhost:9200/products/_count

# Поиск товара
curl -X GET "localhost:9200/products/_search?q=nike"

# Получить маппинг
curl http://localhost:9200/products/_mapping
```

### 2. Проверить Redis

```bash
# Подключиться к Redis
redis-cli

# Посмотреть все ключи
KEYS *

# Посмотреть ключи каталога
KEYS catalog:*

# Посмотреть ключи корзины
KEYS cart:guest:*

# Получить значение
GET catalog:search:nike

# Выйти
exit
```

### 3. Проверить в коде

```php
// Тест Elasticsearch
$es = Yii::$app->elasticsearch;
$results = $es->search('nike');
var_dump($results);

// Тест Redis
$cache = new \app\infrastructure\services\RedisCacheService();
$stats = $cache->getCacheStats();
var_dump($stats);
```

---

## 🔧 Troubleshooting

### Elasticsearch не запускается

**Проблема:** `max virtual memory areas vm.max_map_count [65530] is too low`

**Решение:**
```bash
# Временно
sudo sysctl -w vm.max_map_count=262144

# Постоянно
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### Redis connection refused

**Проблема:** `Connection refused [tcp://localhost:6379]`

**Решение:**
```bash
# Проверить запущен ли Redis
sudo systemctl status redis

# Запустить
sudo systemctl start redis

# Проверить порт
sudo netstat -tulpn | grep 6379
```

### Elasticsearch out of memory

**Проблема:** `OutOfMemoryError: Java heap space`

**Решение:**
```bash
# Увеличить heap size
sudo nano /etc/elasticsearch/jvm.options
# Изменить:
# -Xms1g
# -Xmx1g

# Перезапустить
sudo systemctl restart elasticsearch
```

### PHP extension redis not found

**Проблема:** `Class 'Redis' not found`

**Решение:**
```bash
# Установить расширение
sudo apt install php-redis

# Или через PECL
sudo pecl install redis

# Перезапустить PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 📈 Мониторинг

### Elasticsearch

```bash
# Статус кластера
curl http://localhost:9200/_cluster/health?pretty

# Статистика индекса
curl http://localhost:9200/products/_stats?pretty

# Использование памяти
curl http://localhost:9200/_nodes/stats/jvm?pretty
```

### Redis

```bash
redis-cli INFO memory
redis-cli INFO stats
redis-cli INFO keyspace
```

---

## 🚀 Production рекомендации

### Elasticsearch

1. **Включить безопасность:**
```yaml
xpack.security.enabled: true
```

2. **Настроить репликацию:**
```yaml
number_of_replicas: 1
```

3. **Настроить снапшоты:**
```bash
curl -X PUT "localhost:9200/_snapshot/my_backup" -H 'Content-Type: application/json' -d'
{
  "type": "fs",
  "settings": {
    "location": "/mount/backups/elasticsearch"
  }
}'
```

### Redis

1. **Включить persistence:**
```conf
appendonly yes
appendfsync everysec
```

2. **Настроить пароль:**
```conf
requirepass your_strong_password
```

3. **Ограничить память:**
```conf
maxmemory 2gb
maxmemory-policy allkeys-lru
```

---

## 📚 Дополнительные ресурсы

- [Elasticsearch Documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Redis Documentation](https://redis.io/documentation)
- [Yii2 Redis Extension](https://www.yiiframework.com/extension/yiisoft/yii2-redis)

---

*Документация создана: 24.03.2026*
*Автор: Cascade AI Assistant*
