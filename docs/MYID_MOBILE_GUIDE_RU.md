# MyID — Пошаговое руководство для мобильного разработчика

Базовый URL: `{{url}}` = `http://your-server.com`

---

## Содержание

1. [Общая схема](#общая-схема)
2. [Сценарий 1: Верификация авторизованного пользователя](#сценарий-1-верификация-авторизованного-пользователя)
3. [Сценарий 2: Повторная верификация (reuid)](#сценарий-2-повторная-верификация-reuid)
4. [Сценарий 3: Регистрация нового пользователя через MyID](#сценарий-3-регистрация-нового-пользователя-через-myid)
5. [Сценарий 4: Верификация без авторизации (гость)](#сценарий-4-верификация-без-авторизации-гость)
6. [Проверка статуса верификации](#проверка-статуса-верификации)
7. [Восстановление сессии (если потеряли code)](#восстановление-сессии)
8. [Инициализация SDK (Android / iOS)](#инициализация-sdk)
9. [Обработка ошибок](#обработка-ошибок)
10. [Коды ошибок SDK](#коды-ошибок-sdk)

---

## Общая схема

```
Мобильное приложение          Ваш бэкенд (app)           MyID API
        |                            |                          |
        |  1. POST /create-session   |                          |
        |--------------------------->|  2. Получает токен        |
        |                            |------------------------->|
        |                            |  3. Создает сессию        |
        |                            |------------------------->|
        |  4. Получает session_id    |<-------------------------|
        |<---------------------------|                          |
        |                            |                          |
        |  5. Открывает MyID SDK с session_id ----------------->|
        |  6. Пользователь проходит биометрию ----------------->|
        |  7. SDK возвращает code    |                          |
        |<--------------------------------------------------------|
        |                            |                          |
        |  8. POST /verify с code    |                          |
        |--------------------------->|  9. Получает данные       |
        |                            |------------------------->|
        |                            |<-------------------------|
        | 10. Получает результат     |                          |
        |<---------------------------|                          |
```

**Важно:**
- `client_secret` хранится ТОЛЬКО на бэкенде, мобильное приложение его НЕ знает
- `code` одноразовый, живет **5 минут**
- Сессия живет **10 минут**
- Приложение ДОЛЖНО проверять root-доступ и эмулятор (MyID SDK этого не делает)

---

## Сценарий 1: Верификация авторизованного пользователя

> Пользователь уже вошел в приложение, хочет пройти KYC-верификацию.
> Бэкенд автоматически подставит паспортные данные из базы (если есть).

### Действие 1: Создать сессию

**Postman: `Step 1a. Создать сессию (авторизованный пользователь)`**

**Вариант А — пустое тело (бэкенд подставит данные из базы):**
```http
POST {{url}}/api/myid/create-session
Content-Type: application/json
Authorization: Bearer {user_token}

{}
```

**Вариант Б — с параметрами:**
```http
POST {{url}}/api/myid/create-session
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "threshold": 0.7
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `threshold` | float | Порог сравнения лица (0.5 — 0.99). По умолчанию 0.5 |

> Для авторизованного пользователя бэкенд **автоматически** подставит PINFL, паспорт и дату рождения из базы.
> Мобильное приложение **не должно** отправлять паспортные данные — только `threshold` при необходимости.

**Ответ (200):**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": {
    "session_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

### Действие 2: Инициализировать MyID SDK

Передайте `session_id` в MyID SDK (см. [раздел SDK ниже](#инициализация-sdk)).

SDK откроет экран захвата лица (если паспортные данные были переданы бэкендом) или экран ввода паспорта + захват лица (если данных нет).

### Действие 3: Отправить code на бэкенд

После успешной биометрии SDK вернет `code`. Отправьте его на бэкенд **в течение 5 минут**:

**Postman: `Step 2a. Верификация (с авторизацией)`**

```http
POST {{url}}/api/myid/verify
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "code": "code_from_mobile_sdk",
  "flow": "sdk_new"
}
```

- `flow`: `"sdk_new"` — обязательно для нового потока
- `code` — код из MyID SDK

**Ответ (200):**
```json
{
  "message": "Verification successful",
  "error_code": 0,
  "data": {
    "user": {
      "id": 123,
      "token": "bearer_token",
      "name": "Иван",
      "lastname": "Иванов",
      "middlename": "Иванович",
      "phone": "998901234567",
      "myid_verified": 1
    },
    "verification": {
      "id": 1,
      "user_id": 123,
      "pinfl": "12345678901234",
      "full_name": "Иванов Иван Иванович",
      "full_name_en": "Ivanov Ivan",
      "first_name": "Иван",
      "last_name": "Иванов",
      "middle_name": "Иванович",
      "birth_date": "1990-01-15",
      "gender": 1,
      "gender_label": "Male",
      "nationality": "UZBEK",
      "passport": "AA1234567",
      "comparison_value": 0.92,
      "verification_status": 1,
      "verification_status_label": "Verified",
      "verified_at": "2026-03-04 10:30:00",
      "has_reuid": true
    }
  }
}
```

### Действие 4: Обновить UI

- `verification_status == 1` — верификация пройдена
- `has_reuid == true` — можно использовать повторную верификацию в будущем
- Сохраните `user.myid_verified = 1` локально

---

## Сценарий 2: Повторная верификация (reuid)

> Пользователь уже проходил верификацию ранее. Нужна повторная проверка
> (например, восстановление пароля, вход с нового устройства).
> SDK не будет запрашивать паспорт — только биометрию.

### Действие 1: Создать сессию с reuid

**Postman: `Step 1b. Создать сессию (повторная с reuid)`**

```http
POST {{url}}/api/myid/create-session
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "use_reuid": true
}
```

**Ответ (200):**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": {
    "session_id": "660f9500-f30c-52e5-b827-557766551111"
  }
}
```

> Если reuid истек или отсутствует — бэкенд создаст обычную сессию (primary flow) с паспортными данными из базы.

### Действие 2: Инициализировать SDK и получить code

Аналогично сценарию 1 — передать `session_id` в SDK, дождаться `code`.

### Действие 3: Отправить code

```http
POST {{url}}/api/myid/verify
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "code": "code_from_sdk",
  "flow": "sdk_new"
}
```

**Важно:** В ответе на secondary flow `profile` может быть `null` — MyID возвращает только `comparison_value`, `pass_data` и `job_id`. Полные данные пользователя берутся из базы.

---

## Сценарий 3: Регистрация нового пользователя через MyID

> Новый пользователь хочет зарегистрироваться с верификацией MyID.
> Авторизация не нужна.

### Действие 1: Создать сессию (без авторизации)

**Postman: `Step 1c. Создать сессию (без авторизации — регистрация)`**

```http
POST {{url}}/api/myid/create-session
Content-Type: application/json

{
  "pinfl": "12345678901234",
  "pass_data": "AA1234567",
  "phone_number": "998901234567",
  "birth_date": "1990-01-15",
  "threshold": 0.7
}
```

Все поля **необязательные**. Если ничего не передать — SDK покажет экран ввода паспорта.

| Поле | Тип | Описание |
|------|-----|----------|
| `pinfl` | string(14) | ПИНФЛ (14 цифр) |
| `pass_data` | string(9) | Серия + номер паспорта (напр. AA1234567) |
| `phone_number` | string(12-13) | Номер телефона (998XXXXXXXXX) |
| `birth_date` | string | Дата рождения (ГГГГ-ММ-ДД) |
| `is_resident` | bool | Резидент (по умолчанию true) |
| `threshold` | float | Порог сравнения (0.5 — 0.99) |

**Ответ (200):**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": {
    "session_id": "uuid-here"
  }
}
```

### Действие 2: SDK + биометрия

Передать `session_id` в SDK, получить `code`.

### Действие 3: Зарегистрировать пользователя

**Postman: `Step 2c. Регистрация через MyID`**

```http
POST {{url}}/api/myid/register
Content-Type: application/json

{
  "code": "code_from_mobile_sdk",
  "phone": "998901234567"
}
```

- `code` — обязательный
- `phone` — необязательный (если не передать, будет `myid_{pinfl}`)

**Ответ (200) — новый пользователь:**
```json
{
  "message": "User registered successfully",
  "error_code": 0,
  "data": {
    "user": {
      "id": 456,
      "token": "new_user_bearer_token",
      "name": "Иван",
      "lastname": "Иванов",
      "middlename": "Иванович",
      "phone": "998901234567",
      "myid_verified": 1
    },
    "verification": { ... },
    "is_new_user": true
  }
}
```

**Ответ (200) — пользователь с таким ПИНФЛ уже есть:**
```json
{
  "message": "User already registered with this PINFL",
  "error_code": 0,
  "data": {
    "user": { ... },
    "verification": { ... },
    "is_new_user": false
  }
}
```

Сохраните `user.token` — это Bearer токен для дальнейших запросов.

---

## Сценарий 4: Верификация без авторизации (гость)

> Пользователь вошел через телефон/пароль, но потерял токен,
> или хочет верифицироваться без входа в аккаунт.

### Действие 1: Создать сессию

```http
POST {{url}}/api/myid/create-session
Content-Type: application/json

{}
```

### Действие 2: SDK + биометрия

### Действие 3: Верификация

**Postman: `Step 2b. Верификация (без авторизации)`**

```http
POST {{url}}/api/myid/verify
Content-Type: application/json

{
  "code": "code_from_mobile_sdk",
  "flow": "sdk_new",
  "register": true
}
```

- `register: true` — если ПИНФЛ не привязан к аккаунту, создаст нового пользователя
- `register: false` (по умолчанию) — если ПИНФЛ не привязан, вернет ошибку

---

## Проверка статуса верификации

Используйте для отображения бейджа «Верифицирован» в профиле.

**Postman: `Статус верификации`**

```http
GET {{url}}/api/myid/status
Authorization: Bearer {user_token}
```

**Верифицирован:**
```json
{
  "error_code": 0,
  "data": {
    "verified": true,
    "verified_at": "2026-03-04 10:30:00",
    "pinfl": "12345678901234",
    "full_name": "Иванов Иван Иванович",
    "verification": {
      "id": 1,
      "user_id": 123,
      "pinfl": "12345678901234",
      "full_name": "Иванов Иван Иванович",
      "full_name_en": "Ivanov Ivan",
      "first_name": "Иван",
      "last_name": "Иванов",
      "middle_name": "Иванович",
      "birth_date": "1990-01-15",
      "gender": 1,
      "gender_label": "Male",
      "nationality": "UZBEK",
      "passport": "AA1234567",
      "comparison_value": 0.92,
      "verification_status": 1,
      "verification_status_label": "Verified",
      "verified_at": "2026-03-04 10:30:00",
      "has_reuid": true
    }
  }
}
```

**Не верифицирован:**
```json
{
  "error_code": 0,
  "data": {
    "verified": false
  },
  "message": "User not verified with MyID"
}
```

---

## Восстановление сессии

> Используйте ТОЛЬКО если приложение потеряло связь с SDK и не получило `code`.
> Сессия доступна в течение 10 минут.

**Postman: `Восстановление сессии`**

```http
GET {{url}}/api/myid/session-status?session_id=550e8400-e29b-41d4-a716-446655440000
```

**Ответ:**
```json
{
  "error_code": 0,
  "data": {
    "code": "recovered_code_here",
    "status": "closed",
    "attempts": [
      {
        "job_id": "uuid4",
        "timestamp": "2026-03-04T12:34:56Z",
        "reason": null,
        "reason_code": null
      }
    ]
  }
}
```

| Статус | Описание |
|--------|----------|
| `in_progress` | Сессия активна, пользователь ещё не завершил |
| `closed` | Верификация завершена или 10 минут истекли. Проверьте поле `code` |
| `expired` | Сессия истекла (после 1 часа) |

Если `code` получен — отправьте его на `POST /api/myid/verify` как обычно.

---

## Инициализация SDK

### Android (Kotlin)

```kotlin
// build.gradle
implementation("uz.myid.sdk.v2:myid-sdk-v2:latest")
```

```kotlin
// Инициализация с session_id
val config = MyIdConfig.builder(sessionId = sessionId) // session_id из create-session
    .withLocale(Locale("ru"))                // ru, en, uz
    .withCameraShape(MyIdCameraShape.CIRCLE)
    .withBuildMode(MyIdBuildMode.PRODUCTION) // DEBUG для тестов
    .build()

MyIdSdk.start(activity, config, object : MyIdResultListener {
    override fun onSuccess(result: MyIdResult) {
        // result.code — отправить на POST /api/myid/verify
        sendCodeToBackend(result.code)
    }

    override fun onError(e: MyIdException) {
        // e.code — код ошибки (см. таблицу ниже)
        // e.message — описание
        showError("Ошибка ${e.code}: ${e.message}")
    }

    override fun onUserExited() {
        // Пользователь нажал "Назад"
    }
})
```

**Требования Android:**
- minSdk 21, targetSdk 34+
- Kotlin 1.8+
- Разрешения: `INTERNET`, `CAMERA`

### iOS (Swift)

```swift
// Podfile: pod 'MyIdSDK', '~> 2.0'

import MyIdSDK

let config = MyIdConfig(sessionId: sessionId) // session_id из create-session
config.locale = .ru          // .ru, .en, .uz
config.buildMode = .production // .debug для тестов

MyIdSdk.start(with: config, from: self, delegate: self)

extension ViewController: MyIdSdkDelegate {
    func myidOnSuccess(result: MyIdResult) {
        // result.code — отправить на POST /api/myid/verify
        sendCodeToBackend(result.code)
    }

    func myidOnError(exception: MyIdException) {
        showError("Ошибка \(exception.code): \(exception.message)")
    }

    func myidOnUserExited() {
        // Пользователь отменил
    }
}
```

**Требования iOS:**
- iOS 13+, Xcode 15+, Swift 5.9+
- Info.plist:
```xml
<key>NSCameraUsageDescription</key>
<string>Камера необходима для верификации личности</string>
```

---

## Обработка ошибок

### Ошибки бэкенда (error_code в ответе)

| error_code | Описание | Что делать |
|------------|----------|------------|
| 0 | Успех | — |
| -30 | MyID не настроен на сервере | Сообщите бэкенд-разработчику |
| -31 | Не передан code | Передайте `code` из SDK |
| -32 | Ошибка обмена кода на токен | Повторите верификацию |
| -33 | Не удалось получить данные из MyID | Повторите верификацию |
| -34 | В ответе MyID отсутствует ПИНФЛ | Повторите верификацию |
| -35 | ПИНФЛ уже привязан к другому аккаунту | Покажите сообщение пользователю |
| -36 | Ошибка создания сессии MyID | Повторите через 30 сек |
| -37 | Верификация не пройдена (низкий score или ошибка сохранения) | Покажите сообщение, предложите повторить |
| -12 | Номер телефона уже зарегистрирован | Предложите войти вместо регистрации |
| -13 | Пользователь не найден | Предложите регистрацию (`register: true`) |

### Пример обработки ошибок

```kotlin
// Android
fun handleBackendResponse(response: ApiResponse) {
    when (response.errorCode) {
        0 -> {
            // Успех — обновить UI
            showVerified(response.data)
        }
        -35 -> {
            // ПИНФЛ уже привязан
            showDialog("Этот документ уже привязан к другому аккаунту")
        }
        -36, -32, -33 -> {
            // Ошибка MyID — повторить
            showRetryDialog("Ошибка сервиса. Повторить?")
        }
        -37 -> {
            // Низкий score или ошибка
            showDialog("Верификация не пройдена. Попробуйте ещё раз с хорошим освещением")
        }
        else -> {
            showError(response.message)
        }
    }
}
```

---

## Коды ошибок SDK

Эти ошибки приходят в `onError` callback SDK. Обработайте их на стороне приложения:

| Код | Описание | Рекомендация |
|-----|----------|-------------|
| 2 | Неверные паспортные данные | Попросите ввести заново |
| 3 | Не удалось подтвердить живость | Повторить сканирование |
| 4 | Не удалось распознать | Попросить лучшее освещение |
| 5 | Внешний сервис недоступен | «Попробуйте позже» |
| 6 | Запрашиваемый пользователь умер | Обратиться в поддержку |
| 8 | Внутренняя ошибка MyID | Повторить |
| 9 | Задача истекла | Создать новую сессию |
| 10 | Тайм-аут очереди | Повторить |
| 11-13 | Сервис не может обработать | «Попробуйте позже» |
| 14 | Неправильное фото для liveness | Сделать нормальное селфи |
| 20 | Размытое фото | Держите камеру ровно |
| 21 | Лицо не полностью видно | Центрировать лицо |
| 22 | Несколько лиц в кадре | Только одно лицо |
| 24 | Обнаружены тёмные очки | Снять очки |
| 26 | Глаза закрыты | Открыть глаза |
| 27 | Поворот головы | Смотреть в камеру прямо |
| 28 | Не удалось обнаружить лицо | Центрировать лицо |
| 29 | Блик/палец/отражение | Убрать блики |
| 30 | Лицо частично закрыто | Убрать маску/шарф |
| 34 | Просроченные паспортные данные | Обновить паспорт |
| 101 | Ошибка MyID SDK | Обновить SDK |
| 102 | Нет доступа к камере | Запросить разрешение |
| 103 | Универсальная ошибка — смотрите message | Показать message пользователю |
| 122 | Пользователь заблокирован | Обратиться в поддержку |

---

## Чек-лист интеграции

- [ ] Реализовать root/emulator detection в приложении
- [ ] Добавить MyID SDK зависимость (Android/iOS)
- [ ] Реализовать вызов `POST /api/myid/create-session`
- [ ] Инициализировать SDK с полученным `session_id`
- [ ] Обработать `onSuccess` — отправить `code` на `POST /api/myid/verify`
- [ ] Обработать `onError` — показать сообщение пользователю
- [ ] Обработать `onUserExited` — вернуть на предыдущий экран
- [ ] Реализовать проверку статуса `GET /api/myid/status`
- [ ] Добавить UI бейдж «Верифицирован» по `myid_verified == 1`
- [ ] Реализовать обработку ошибок бэкенда (error_code)
- [ ] Добавить session recovery на случай потери `code`
- [ ] Протестировать на sandbox (`api.devid.example.com`)
- [ ] Переключить на production (`identity.example.com`) перед релизом
