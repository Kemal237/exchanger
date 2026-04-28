# Telegram Mini App — Панель администратора

**Дата:** 2026-04-27  
**Проект:** Swap (cr873507.tw1.ru)  
**Цель:** Полноценная админ-панель внутри Telegram — управление заявками, пользователями, резервами, тикетами поддержки и просмотр аналитики без выхода из мессенджера.

---

## 1. Архитектура

### Компоненты

| Компонент | Путь | Назначение |
|-----------|------|------------|
| SPA | `miniapp/index.html` | Единый HTML-файл, весь UI и логика на ванильном JS |
| Auth helper | `miniapp/api/auth.php` | Проверка Telegram initData (HMAC-SHA256), include в остальных API |
| Dashboard API | `miniapp/api/dashboard.php` | Метрики: заявки, пользователи, курсы |
| Orders API | `miniapp/api/orders.php` | Список, фильтрация, смена статуса, удаление |
| Users API | `miniapp/api/users.php` | Список, поиск, история заявок пользователя |
| Reserves API | `miniapp/api/reserves.php` | Чтение резервов, пополнение/списание, лимиты |
| Tickets API | `miniapp/api/tickets.php` | Тикеты + сообщения, ответ, смена статуса |

### URL Mini App
```
https://cr873507.tw1.ru/miniapp/index.html
```

### Поток данных
1. Администратор нажимает кнопку **"📊 Открыть панель"** в боте (команда `/start`)
2. Telegram открывает Mini App и инжектирует `window.Telegram.WebApp.initData`
3. Каждый fetch-запрос от SPA отправляет `initData` в заголовке `X-Telegram-Init-Data`
4. PHP `auth.php` верифицирует HMAC-SHA256 подпись, проверяет chat_id по `TG_ALLOWED_CHATS`
5. При успехе — данные из БД; при ошибке — HTTP 403

---

## 2. Аутентификация

**Метод:** Telegram WebApp initData verification (официальный механизм)

**Алгоритм проверки (auth.php):**
```
secret_key = HMAC-SHA256("WebAppData", bot_token)
data_check_string = все поля initData кроме hash, отсортированные по алфавиту, через \n
expected_hash = HMAC-SHA256(data_check_string, secret_key)
valid = (expected_hash === hash из initData)
```

**Дополнительно:**
- Проверка `auth_date` — отклонять initData старше 1 часа (защита от replay-атак)
- Проверка `chat_id` пользователя по константе `TG_ALLOWED_CHATS` из `config.php`
- При невалидном initData — HTTP 403 + JSON `{"error": "Unauthorized"}`

---

## 3. UI и навигация

### Нижний таб-бар (5 разделов)
```
[ 📊 Дашборд ] [ 📋 Заявки ] [ 👥 Пользователи ] [ 💰 Резервы ] [ 🎫 Тикеты ]
```

Активная вкладка подсвечивается. Контент разделов переключается через `display: none/block` без перезагрузки страницы.

### Стиль
- Тёмная тема (соответствует Telegram тёмной теме)
- CSS-переменные из `window.Telegram.WebApp.themeParams` для автоматической адаптации к теме пользователя
- Мобильная вёрстка (Mini App открывается на телефоне)

---

## 4. Разделы

### 4.1 Дашборд
**API:** `GET /miniapp/api/dashboard.php?period=today|7d|30d`

**Данные:**
- Новые пользователи за период (COUNT из таблицы `users`)
- Заявки по статусам: new, in_process, success, canceled (COUNT из `orders`)
- Объём обменов за период (SUM amount_give из `orders` со статусом `success`)
- Открытые тикеты поддержки (COUNT из `support_tickets` где status != 'closed')
- Текущие курсы BTC/ETH/USDT (из `cache_rates.json` или `getCachedRates()`)

**UI:** Карточки метрик + кнопки-переключатели периода

### 4.2 Заявки
**API:** `GET /miniapp/api/orders.php?status=all|new|in_process|success|canceled&page=1`  
**API:** `POST /miniapp/api/orders.php` — тело `{action: "status"|"delete", order_id, new_status?}`

**Данные:** id, дата, give_currency, amount_give, get_currency, amount_get, статус, username пользователя

**UI:**
- Фильтр по статусу (таб-панель)
- Список заявок с пагинацией (20 на страницу)
- Тап на заявку → детали + кнопки смены статуса + кнопка удаления (с подтверждением)

### 4.3 Пользователи
**API:** `GET /miniapp/api/users.php?search=&page=1`  
**API:** `GET /miniapp/api/users.php?history=USER_ID`

**Данные:** id, username, email, telegram, роль, дата регистрации, кол-во заявок

**UI:**
- Поле поиска (по username / email)
- Список пользователей
- Тап на пользователя → модальное окно с историей его заявок (со сменой статуса)

### 4.4 Резервы
**API:** `GET /miniapp/api/reserves.php`  
**API:** `POST /miniapp/api/reserves.php` — тело `{action: "reserve"|"limits", currency, amount?, action_type?: "add"|"subtract", min?, max?}`

**Данные:** currency, amount, min, max, updated_at

**UI:**
- Таблица текущих резервов по каждой валюте
- Кнопки "Пополнить" / "Списать" → форма с суммой
- Кнопка "Лимиты" → форма изменения min/max

### 4.5 Тикеты поддержки
**API:** `GET /miniapp/api/tickets.php?status=open|answered|closed|all&page=1`  
**API:** `GET /miniapp/api/tickets.php?id=TICKET_ID` — тикет + сообщения  
**API:** `POST /miniapp/api/tickets.php` — тело `{action: "reply"|"status", ticket_id, message?, new_status?}`

**Данные:** id, тема, статус, username пользователя, кол-во сообщений, последнее обновление

**UI:**
- Фильтр по статусу
- Список тикетов
- Тап на тикет → чат-просмотр сообщений + поле ответа + кнопки смены статуса

---

## 5. Изменения в bot-cron.php

Команда `/start` и `/help` возвращают кнопку `reply_markup` типа `web_app`:
```json
{
  "keyboard": [[{
    "text": "📊 Открыть панель управления",
    "web_app": {"url": "https://cr873507.tw1.ru/miniapp/index.html"}
  }]],
  "resize_keyboard": true
}
```

---

## 6. Безопасность

- Все API-эндпоинты начинают с проверки initData — без неё 403
- Параметры из запросов строго валидируются (intval, whitelist статусов, prepared statements)
- Mini App находится на том же сервере что и API — CORS не нужен
- Нет прямого доступа к БД-данным без верифицированного initData

---

## 7. Файловая структура

```
d:\exchanger\
  miniapp\
    index.html
    api\
      auth.php
      dashboard.php
      orders.php
      users.php
      reserves.php
      tickets.php
  docs\superpowers\specs\
    2026-04-27-telegram-miniapp-design.md
```

---

## 8. Ограничения и допущения

- Mini App работает только у пользователей из `TG_ALLOWED_CHATS`
- Пагинация: 20 записей на страницу для заявок и пользователей
- Аналитика — агрегированные данные из БД, без внешних систем
- Фронтенд — ванильный JS (без React/Vue), чтобы не требовать сборки
- Хостинг PHP 7.4+ (Timeweb shared), функция `hash_hmac` доступна
