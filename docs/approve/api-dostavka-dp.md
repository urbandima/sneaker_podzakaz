# Shipment API — Документация

## Обзор

В данном документе представлено подробное описание конечных точек API для работы с отправками, включая структуры запросов и ответов, а также объяснения для каждого поля.

---

## 1. Get Token

**Endpoint**

- **URL:** `https://api.dobropost.com/api/shipment/sign-in`
- **Method:** `POST`

**Request Body**

```json
{
  "email": "email",
  "password": "string"
}
```

**Response Body**

```json
{
  "token": "string"
}
```

> **ПРИМЕЧАНИЕ:** Срок действия токена составляет 12 часов.

---

## 2. Create Shipment

**Endpoint**

- **URL:** `https://api.dobropost.com/api/shipment`
- **Method:** `POST`

**Request Headers**

```
Authorization: Bearer {token}
```

**Request Body**

```json
{
  "totalAmount": 0,
  "consigneeFamilyName": "string",
  "consigneeMiddleName": "string",
  "consigneeName": "string",
  "consigneeBirthDate": "2024-12-17",
  "consigneePassportSerial": "string",
  "consigneePassportNumber": "string",
  "passportIssueDate": "2024-12-17",
  "vatIdentificationNumber": "string",
  "consigneeFullAddress": "string",
  "consigneeCity": "string",
  "consigneeState": "string",
  "consigneeZipCode": "string",
  "consigneePhoneNumber": "string",
  "consigneeEmail": "string",
  "itemDescription": "string",
  "numberOfItemPieces": 0,
  "itemPrice": 0,
  "itemStoreLink": "string",
  "dpTariffId": 0,
  "incomingDeclaration": "string",
  "comment": "string"
}
```

**Response Body**

```json
{
  "id": 0,
  "totalAmount": 0,
  "currency": "string",
  "consigneeFamilyName": "string",
  "consigneeMiddleName": "string",
  "consigneeName": "string",
  "consigneeBirthDate": "2024-12-17",
  "consigneePassportSerial": "string",
  "consigneePassportNumber": "string",
  "passportIssueDate": "2024-12-17",
  "consigneeFullAddress": "string",
  "consigneeCity": "string",
  "consigneeState": "string",
  "consigneeZipCode": "string",
  "consigneePhoneNumber": "string",
  "consigneeEmail": "string",
  "itemDescription": "string",
  "numberOfItemPieces": 0,
  "itemPrice": 0,
  "itemWeight": 0,
  "itemStoreLink": "string",
  "statusDate": "2024-12-17T20:39:34.663Z",
  "deliveryTariff": {
    "id": 0,
    "measureQty": 0,
    "pricePerUnit": 0,
    "country": {
      "code": 0,
      "name": "string",
      "a2": "string",
      "a3": "string",
      "priority": 0
    },
    "currency": {
      "code": 0,
      "ccy": "string",
      "base": true
    },
    "name": "string",
    "description": "string",
    "minTariffPerMeasureQty": 0,
    "startDate": "2024-12-17T20:39:34.663Z",
    "amountUnits": {
      "id": 0,
      "name": "string",
      "caption": "string"
    }
  },
  "status": {
    "id": 0,
    "name": "string"
  },
  "vatidentificationNumber": "string",
  "incomingDeclaration": "string",
  "dptrackNumber": "string"
}
```

### Описание полей Request Body

| Поле | Тип | Описание |
|------|-----|----------|
| `totalAmount` | Number | Общая стоимость товаров шипмента в юанях. Должно быть числовым значением. |
| `consigneeFamilyName` | String | Фамилия получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeName` | String | Имя получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeMiddleName` | String | Отчество получателя для таможни. Необязательное поле. |
| `consigneeBirthDate` | Date | Дата рождения получателя. Обязательное только для тарифа DP Ultra. |
| `consigneePassportSerial` | String | Серия паспорта получателя. Не должно быть пустым или нулевым. Длина — ровно 4 символа. |
| `consigneePassportNumber` | String | Номер паспорта получателя. Не должно быть пустым или нулевым. Длина — ровно 6 символов. |
| `passportIssueDate` | Date | Дата выдачи паспорта получателя. Не должно быть пустым или нулевым. |
| `vatIdentificationNumber` | String | ИНН получателя. Не должно быть пустым или нулевым. Длина — ровно 12 символов. |
| `consigneeFullAddress` | String | Полный адрес получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeCity` | String | Город проживания получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeState` | String | Область проживания получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeZipCode` | String | Почтовый индекс адреса получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneePhoneNumber` | String | Номер телефона получателя для таможни. Не должно быть пустым или нулевым. |
| `consigneeEmail` | String | Адрес электронной почты получателя для таможни. Не должно быть пустым или нулевым. Должен быть корректным email-адресом. |
| `itemDescription` | String | Описание товара. Не должно быть пустым или нулевым. Длина — менее 60 символов. |
| `numberOfItemPieces` | Number | Количество единиц товара. Не должно быть пустым или нулевым, рекомендуем не более 4-х товаров. |
| `itemPrice` | Number | Цена за одну единицу товара в юанях. Не должно быть пустым или нулевым. |
| `itemStoreLink` | String | URL-адрес, ведущий на страницу товара, где был куплен товар. Не должно быть пустым или нулевым. Должна быть корректной ссылкой (URL). |
| `dpTariffId` | Number | Тариф доставки шипмента. Не должно быть пустым или нулевым. |
| `incomingDeclaration` | String | Трек-номер шипмента по Китаю. Не должно быть пустым или нулевым. Длина — менее 16 символов. |
| `comment` | String | Комментарий партнера к шипменту, отображается на этикетке шипмента. Длина — менее 60 символов. |

### Описание полей Response Body

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | Number | Уникальный идентификатор шипмента. |
| `statusDate` | Date | Дата последнего обновления статуса. |
| `dptrackNumber` | String | Трек-номер Таможня:ДП для отслеживания шипмента. |
| `itemWeight` | Number | Вес одной единицы товара в килограммах. |
| `totalWeightKG` | Number | Общий вес шипмента в килограммах. |

---

## 3. Get Shipments

**Endpoint**

- **URL:** `https://api.dobropost.com/api/shipment`
- **Method:** `GET`

**Request Headers**

```
Authorization: Bearer {token}
```

**Query Parameters**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `page` | Number | Номер страницы для постраничного отображения. |
| `offset` | Number | Количество записей, которые необходимо пропустить для постраничного отображения. |
| `statusId` | Number | Идентификатор статуса отправления. |

**Response Body**

Обратитесь к ответу «Create Shipment» для получения подробной структуры отдельных отправлений в массиве `content`.

---

## 4. Update Shipment

**Endpoint**

- **URL:** `https://api.dobropost.com/api/shipment`
- **Method:** `PUT`

**Request Headers**

```
Authorization: Bearer {token}
```

**Request Body**

```json
{
  "totalAmount": 0,
  "consigneeFamilyName": "string",
  "consigneeMiddleName": "string",
  "consigneeName": "string",
  "consigneeBirthDate": "2024-12-17",
  "consigneePassportSerial": "string",
  "consigneePassportNumber": "string",
  "passportIssueDate": "2024-12-17",
  "vatIdentificationNumber": "string",
  "consigneeFullAddress": "string",
  "consigneeCity": "string",
  "consigneeState": "string",
  "consigneeZipCode": "string",
  "consigneePhoneNumber": "string",
  "consigneeEmail": "string",
  "itemDescription": "string",
  "numberOfItemPieces": 0,
  "itemPrice": 0,
  "itemStoreLink": "string",
  "dpTariffId": 0,
  "incomingDeclaration": "string",
  "comment": "string"
}
```

**Response Body**

Обратитесь к ответу «Create Shipment» для получения подробной структуры.

---

## 5. Delete Shipment

**Endpoint**

- **URL:** `https://api.dobropost.com/api/shipment/{id}`
- **Method:** `DELETE`

**Request Headers**

```
Authorization: Bearer {token}
```

---

## 6. Документация Webhook API

### Обзор

Эта конечная точка API используется для получения уведомлений о доставке через webhook. В зависимости от события может иметь одну из двух структур:

- Проверка актуальности паспорта по базе данных DaData
- Обновление статуса шипмента

### Детали конечной точки

- **URL:** `https://yourdomain.com/webhook` *(Замените на фактический URL вашей конечной точки.)*
- **Метод:** `POST`
- **Content-Type:** `application/json`

---

### 6.1 Проверка паспорта

**Описание:** Эта полезная нагрузка используется, когда происходит обновление статуса проверки паспорта шипмента.

**Пример тела запроса:**

```json
{
  "shipmentId": 0,
  "statusDate": "string",
  "passportValidationStatus": true
}
```

**Описание полей:**

| Поле | Тип | Описание |
|------|-----|----------|
| `shipmentId` | Integer | Уникальный идентификатор отправления. |
| `statusDate` | String | Дата и время обновления статуса. Используйте формат ISO 8601 (например, `"2025-02-04T14:30:00Z"`). |
| `passportValidationStatus` | Boolean | Указывает, прошел паспорт проверку на актуальность (`true`) или нет (`false`). |

---

### 6.2 Обновление статуса отправления

**Описание:** Используется при общем обновлении статуса отправления.

**Пример тела запроса:**

```json
{
  "shipmentId": 0,
  "DPTrackNumber": "string",
  "statusDate": "string",
  "status": "string"
}
```

**Описание полей:**

| Поле | Тип | Описание |
|------|-----|----------|
| `shipmentId` | Integer | Уникальный идентификатор шипмента. |
| `DPTrackNumber` | String | Номер отслеживания, предоставленный Таможня:ДП для шипмента. |
| `statusDate` | String | Дата и время обновления статуса. Используйте формат ISO 8601. |
| `status` | String | Текстовое представление текущего статуса отправления (например, «В пути», «Доставлено», «Задерживается»). |

---

### Ответы Webhook

**Успешный ответ:** Конечная точка webhook возвращает код статуса `200 OK` с пустым телом при успешном получении и обработке запроса.

**Ответы при ошибках:**

| Код | Описание |
|-----|----------|
| `400 Bad Request` | Возвращается, если в запросе отсутствуют обязательные поля или запрос неправильного формата. |
| `401 Unauthorized` | Возвращается, если токен не прошел валидацию. |
| `500 Internal Server Error` | Возвращается, если на стороне сервера происходит непредвиденная ошибка. |

---

## Список статусов

| ID | Название |
|----|----------|
| 1 | Ожидается на складе |
| 2 | Получен от курьера |
| 3 | Обработан на складе |
| 4 | Добавлен в мешок |
| 5 | Добавлен в реестр |
| 6 | Покинул склад в Китае |
| 7 | Поступил на таможню в Китае |
| 8 | Поступил на таможню в России |
| 9 | Передан партнеру |
| 270 | Запрос на редактирование данных посылки |
| 271 | Запрос на редактирование данных посылки отклонен |
| 272 | Произведено редактирование данных посылки |
| 500 | Начало таможенного оформления |
| 510 | Требуется уплатить таможенные пошлины |
| 520 | Выпуск товаров без уплаты таможенных платежей |
| 521 | Выпуск товаров без уплаты таможенных платежей |
| 530 | Выпуск товаров (таможенные платежи уплачены) |
| 531 | Требуется уплатить таможенные пошлины |
| 532 | Выпуск товаров (таможенные платежи уплачены) |
| 540 | Ожидание обязательной оплаты таможенной пошлины |
| 541 | Отказ в выпуске посылки по причине признания партии коммерческой или не относящейся к товарам для личного пользования |
| 542 | Отказ в выпуске посылки в связи с отсутствием необходимых документов для целей таможенного контроля либо отсутствием документов, подтверждающих оплату таможенной пошлины |
| 543 | Отказ в выпуске посылки по причине некорректного заполнения информации о характеристиках товара |
| 544 | Отказ в выпуске посылки по причине отсутствия корректных паспортных данных |
| 545 | Отказ в выпуске посылки по причине отсутствия заявленных паспортных данных в списке достоверных паспортов |
| 546 | Отказ в выпуске посылки по другим причинам |
| 570 | Продление времени обработки |
| 591 | Начало таможенного оформления |
| 600 | Посылка не пришла |
| 648 | Подготовлено к отгрузке в доставку последней мили |
| 649 | Покинула таможню и передана на доставку по РФ |
| 590204 | Отказ в выпуске товаров с указанием кода причины отказа |
| 590401 | Отказ в выпуске товаров с указанием кода причины отказа |
| 590404 | Отказ в выпуске товаров с указанием кода причины отказа. Не представлены документы и сведения |
| 590405 | Отказ в выпуске товаров с указанием кода причины отказа |
| 590409 | Отказ в выпуске товаров с указанием кода причины отказа |
| 590410 | Отказ в выпуске товаров с указанием кода причины отказа. Товар входит в перечень категорий товаров |
| 590413 | Отказ в выпуске товаров с указанием кода причины отказа. Не подана корректировка пп.2 п.1 ст.125 ТК ЕАЭС |
| 590420 | Отказ в выпуске товаров с указанием кода причины отказа |
| 590592 | Отказ в выпуске товаров с указанием кода причины отказа |
