# 🐧 LinuxTest — Веб-додаток для тестування знань Linux

Повноцінний fullstack веб-додаток для проходження тестів з різних галузей знань про систему Linux.

## Технологічний стек

| Компонент        | Технологія           |
|------------------|----------------------|
| Веб-сервер       | nginx 1.25           |
| Backend          | PHP 8.2 (PHP-FPM)    |
| Frontend         | Vanilla JS, HTML, CSS|
| База даних       | MySQL 8.0            |
| Адмінування БД   | phpMyAdmin 5.2       |
| Деплой           | Docker Compose       |

---

## Функціональність

- **Реєстрація та вхід** — спрощена система з bcrypt-хешуванням паролів
- **Ролі**: `user` (проходить тести, CRUD своїх результатів) / `admin` (управління всім)
- **Тести** — 4 варіанти відповідей, підсвічування зеленим/червоним, пояснення
- **Категорії** — Файлова система, Мережа/iptables, Права доступу, Процеси тощо
- **Вікі** — вбудована вікіпедія Linux (стаття про iptables включена)
- **Адмін-панель** — CRUD запитань, категорій, вікі-сторінок, користувачів
- **REST API** — для зовнішніх парсерів (JSON, захист API-ключем)
- **Темна/світла тема** — перемикач, збереження в localStorage
- **Адаптивний дизайн** — mobile-first

---

## 🚀 Запуск через Docker Compose

### Передумови

- [Docker](https://docs.docker.com/get-docker/) >= 24.0
- [Docker Compose](https://docs.docker.com/compose/) >= 2.0

### Крок 1 — Клонуйте репозиторій

```bash
git clone https://github.com/plamagnum/lern_net.git
cd lern_net
```

### Крок 2 — Запустіть усі сервіси

```bash
docker compose up -d
```

> Перший запуск займе 1-3 хвилини (завантаження образів, ініціалізація БД).

### Крок 3 — Перевірте статус контейнерів

```bash
docker compose ps
```

Усі 4 контейнери мають бути у стані `running`:
- `lernnet_nginx`
- `lernnet_php`
- `lernnet_mysql`
- `lernnet_phpmyadmin`

### Крок 4 — Відкрийте додаток

| Сервіс          | URL                        |
|-----------------|----------------------------|
| Веб-додаток     | http://localhost:8086       |
| phpMyAdmin      | http://localhost:8080       |

---

## 🔑 Стандартні облікові дані

### Адмін-акаунт

| Поле     | Значення           |
|----------|--------------------|
| Логін    | `admin`            |
| Пароль   | `admin123`         |
| Роль     | admin              |

### Тестовий користувач

| Поле     | Значення           |
|----------|--------------------|
| Логін    | `testuser`         |
| Пароль   | `user123`          |
| Роль     | user               |

### phpMyAdmin

| Поле     | Значення           |
|----------|--------------------|
| Хост     | `mysql`            |
| Логін    | `root`             |
| Пароль   | `root_password`    |
| БД       | `lernnet`          |

---

## 🔌 REST API

### Автентифікація

Всі API-запити потребують ключа адміна. Передавайте його одним із способів:

- **HTTP-заголовок**: `Authorization: ******
- **GET-параметр**: `?api_key=super_secret_admin_key_2024`

> 💡 Ключ можна змінити у `docker-compose.yml` (параметр `ADMIN_API_KEY`).

### Ендпоінти

#### `GET /api/questions.php` — Список запитань

```bash
curl -H "Authorization: ******" \
     "http://localhost:8086/api/questions.php?limit=5"
```

#### `POST /api/questions.php` — Додати нове запитання

```bash
curl -X POST http://localhost:8086/api/questions.php \
  -H "Authorization: ******" \
  -H "Content-Type: application/json" \
  -d '{
    "question":    "Яка команда показує вміст директорії?",
    "category":    "Файлова система",
    "explanation": "Команда ls (list) виводить вміст поточної або вказаної директорії",
    "answers": [
      {"text": "ls",    "correct": true},
      {"text": "dir",   "correct": false},
      {"text": "cat",   "correct": false},
      {"text": "show",  "correct": false}
    ]
  }'
```

**Відповідь (201 Created):**

```json
{
  "success": true,
  "question_id": 16,
  "category_id": 1,
  "message": "Запитання успішно додано"
}
```

### Вимоги до тіла POST-запиту

| Поле          | Тип      | Обов'язкове | Опис |
|---------------|----------|-------------|------|
| `question`    | string   | ✅          | Текст запитання |
| `category`    | string   | ✅          | Назва категорії (автостворюється якщо нова) |
| `answers`     | array[4] | ✅          | Рівно 4 варіанти відповідей |
| `explanation` | string   | ❌          | Пояснення правильної відповіді |

Кожен елемент `answers`:

| Поле      | Тип     | Опис |
|-----------|---------|------|
| `text`    | string  | Текст варіанту відповіді |
| `correct` | boolean | `true` для правильної відповіді (рівно 1 з 4) |

---

## 📁 Структура проєкту

```
lern_net/
├── docker-compose.yml          # Docker Compose конфігурація
├── nginx/
│   └── default.conf            # Nginx конфігурація
├── php/
│   └── Dockerfile              # PHP-FPM Dockerfile
├── mysql/
│   └── init.sql                # SQL схема + seed-дані
├── src/
│   ├── includes/               # PHP бекенд-модулі
│   │   ├── config.php          # Конфігурація (ENV)
│   │   ├── db.php              # PDO підключення до БД
│   │   ├── auth.php            # Автентифікація/авторизація
│   │   └── functions.php       # Допоміжні функції
│   └── public/                 # Web-root (доступно через nginx)
│       ├── index.php           # Головна сторінка
│       ├── login.php           # Вхід
│       ├── register.php        # Реєстрація
│       ├── logout.php          # Вихід
│       ├── quiz.php            # Тест
│       ├── results.php         # Результати
│       ├── wiki.php            # Список вікі-статей
│       ├── wiki-article.php    # Перегляд вікі-статті
│       ├── admin/              # Адмін-панель
│       │   ├── index.php       # Дашборд
│       │   ├── questions.php   # CRUD запитань
│       │   ├── categories.php  # CRUD категорій
│       │   ├── wiki.php        # CRUD вікі
│       │   ├── users.php       # CRUD користувачів
│       │   └── sidebar.php     # Навігація адмін-панелі
│       ├── api/
│       │   ├── questions.php   # REST API (POST/GET)
│       │   └── quiz.php        # Quiz API (внутрішній)
│       ├── templates/
│       │   ├── header.php      # Шаблон шапки
│       │   └── footer.php      # Шаблон підвалу
│       ├── css/
│       │   └── style.css       # Стилі (темна/світла тема)
│       └── js/
│           └── app.js          # JavaScript (quiz, теми, UI)
└── README.md                   # Ця документація
```

---

## 🛑 Зупинка та очищення

```bash
# Зупинити контейнери (зберегти дані)
docker compose down

# Зупинити та видалити всі дані (включно з БД)
docker compose down -v
```

---

## 🔧 Налаштування

### Зміна API-ключа

У файлі `docker-compose.yml`, секція `php` > `environment`:

```yaml
- ADMIN_API_KEY=your_new_secret_key_here
```

### Зміна паролю БД

Змініть значення у `docker-compose.yml` у секціях `mysql` та `php`:

```yaml
# mysql:
MYSQL_PASSWORD: your_db_password

# php:
- DB_PASS=your_db_password
```

---

## 📦 Seed-дані (включені за замовчуванням)

| Тип           | Що включено |
|---------------|-------------|
| Вікі          | Стаття «iptables: основні команди та комбінації» (укр.) |
| Тест          | 15 запитань про iptables з 4 варіантами відповідей |
| Категорії     | 6 категорій: Файлова система, Мережа/iptables, Права доступу, Процеси, Текстові утиліти, Системне адмінів. |
| Користувачі   | `admin` (admin123) + `testuser` (user123) |
