-- =============================================================
-- LinuxTest - Ініціалізація бази даних
-- Схема + seed-дані (вікі про iptables + 15 тестових запитань)
-- =============================================================

SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;

-- Використовуємо нашу базу даних
USE lernnet;

-- -------------------------------------------------------------
-- Таблиця користувачів
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,            -- bcrypt хеш
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця категорій запитань
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця запитань
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS questions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    question    TEXT NOT NULL,
    explanation TEXT,                              -- пояснення правильної відповіді
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця варіантів відповідей
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS answers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,    -- 1 = правильна відповідь
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця результатів тестів
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED,
    total        INT UNSIGNED NOT NULL DEFAULT 0,  -- кількість запитань
    correct      INT UNSIGNED NOT NULL DEFAULT 0,  -- правильних відповідей
    score        DECIMAL(5,2) NOT NULL DEFAULT 0,  -- % правильних
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця детальних відповідей у тестах (для статистики)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_answer_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id   INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_id   INT UNSIGNED,                      -- NULL якщо не відповів
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (result_id)   REFERENCES quiz_results(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)    ON DELETE CASCADE,
    FOREIGN KEY (answer_id)   REFERENCES answers(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця вікі-сторінок
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wiki_pages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL UNIQUE,      -- URL-сумісний ідентифікатор
    content     LONGTEXT NOT NULL,                 -- HTML-контент статті
    author_id   INT UNSIGNED,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Таблиця API-токенів для зовнішніх парсерів
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token       VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(200),
    user_id     INT UNSIGNED NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- SEED-ДАНІ
-- =============================================================

-- -------------------------------------------------------------
-- Адмін-користувач (пароль: admin123)
-- Хеш bcrypt для 'admin123'
-- -------------------------------------------------------------
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@lernnet.local', '$2y$12$UmvAdFywtRmEn1ZlMzfoJeu4sCh4zokw19jRFRG7E2/xDcElatbey', 'admin');

-- -------------------------------------------------------------
-- Тестовий звичайний користувач (пароль: user123)
-- -------------------------------------------------------------
INSERT INTO users (username, email, password, role) VALUES
('testuser', 'user@lernnet.local', '$2y$12$AlzZd4FR1OBTwUIhvHnTFu9DDGTKIeJHEApeC07q3./WtCdXEkdrK', 'user');

-- -------------------------------------------------------------
-- Категорії запитань
-- -------------------------------------------------------------
INSERT INTO categories (name, description) VALUES
('Файлова система',    'Команди для роботи з файлами та директоріями Linux'),
('Мережа / iptables',  'Мережеві команди та управління брандмауером iptables'),
('Права доступу',      'Управління правами доступу до файлів та процесів'),
('Процеси',            'Управління процесами в Linux'),
('Текстові утиліти',   'Утиліти для обробки тексту: grep, sed, awk тощо'),
('Системне адмінів.',  'Адміністрування системи: служби, логи, налаштування');

-- -------------------------------------------------------------
-- Вікі-стаття про iptables
-- -------------------------------------------------------------
INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (
'iptables: основні команди та комбінації',
'iptables-basics',
'<h1>iptables: основні команди та комбінації</h1>

<p><strong>iptables</strong> — це утиліта командного рядка Linux для налаштування правил мережевого брандмауера (фаєрволу). Вона взаємодіє з модулем ядра <code>netfilter</code> для фільтрації мережевих пакетів.</p>

<h2>Структура iptables</h2>
<p>iptables організований у <strong>таблиці</strong>, кожна з яких містить <strong>ланцюжки</strong> правил:</p>
<ul>
  <li><strong>filter</strong> — основна таблиця фільтрації (за замовчуванням): INPUT, OUTPUT, FORWARD</li>
  <li><strong>nat</strong> — трансляція мережевих адрес: PREROUTING, OUTPUT, POSTROUTING</li>
  <li><strong>mangle</strong> — зміна заголовків пакетів</li>
  <li><strong>raw</strong> — обробка до відстеження з''єднань</li>
</ul>

<h2>Основні ланцюжки</h2>
<ul>
  <li><strong>INPUT</strong> — пакети, що надходять на цей хост</li>
  <li><strong>OUTPUT</strong> — пакети, що виходять з цього хоста</li>
  <li><strong>FORWARD</strong> — пакети, що проходять через хост (маршрутизація)</li>
  <li><strong>PREROUTING</strong> — пакети до маршрутизації (NAT)</li>
  <li><strong>POSTROUTING</strong> — пакети після маршрутизації (NAT/Masquerade)</li>
</ul>

<h2>Перегляд правил</h2>
<pre><code># Переглянути всі правила (з лічильниками пакетів)
iptables -L -v -n

# Переглянути правила конкретного ланцюжка
iptables -L INPUT -v -n

# Переглянути правила з номерами рядків
iptables -L INPUT --line-numbers

# Переглянути таблицю NAT
iptables -t nat -L -v -n

# Переглянути всі правила у форматі для збереження
iptables-save</code></pre>

<h2>Додавання правил</h2>
<pre><code># Дозволити вхідні з''єднання по SSH (порт 22)
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Дозволити вхідні по HTTP (порт 80) та HTTPS (порт 443)
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Дозволити вхідний ICMP (ping)
iptables -A INPUT -p icmp -j ACCEPT

# Дозволити пакети встановлених з''єднань (stateful)
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Дозволити весь трафік на loopback інтерфейсі
iptables -A INPUT -i lo -j ACCEPT

# Вставити правило на початок ланцюжка (INSERT)
iptables -I INPUT 1 -s 192.168.1.0/24 -j ACCEPT</code></pre>

<h2>Блокування IP та мереж</h2>
<pre><code># Заблокувати конкретний IP
iptables -A INPUT -s 203.0.113.100 -j DROP

# Заблокувати цілу підмережу
iptables -A INPUT -s 203.0.113.0/24 -j DROP

# Відхилити з'єднання з повідомленням (замість тихого DROP)
iptables -A INPUT -s 203.0.113.100 -j REJECT --reject-with icmp-host-prohibited

# Заблокувати вихідний трафік на певний IP
iptables -A OUTPUT -d 203.0.113.100 -j DROP</code></pre>

<h2>Відкриття та закриття портів</h2>
<pre><code># Відкрити порт (TCP)
iptables -A INPUT -p tcp --dport 8080 -j ACCEPT

# Відкрити порт (UDP)
iptables -A INPUT -p udp --dport 53 -j ACCEPT

# Відкрити діапазон портів
iptables -A INPUT -p tcp --dport 8000:9000 -j ACCEPT

# Закрити порт
iptables -A INPUT -p tcp --dport 23 -j DROP</code></pre>

<h2>Видалення правил</h2>
<pre><code># Видалити правило за номером (спочатку переглянь --line-numbers)
iptables -D INPUT 3

# Видалити конкретне правило (такий самий синтаксис, як при додаванні, але -D замість -A)
iptables -D INPUT -p tcp --dport 8080 -j ACCEPT

# Видалити всі правила у ланцюжку (flush)
iptables -F INPUT

# Видалити всі правила у всіх ланцюжках
iptables -F</code></pre>

<h2>NAT та Port Forwarding</h2>
<pre><code># Увімкнути IP Forwarding (потрібно для NAT/маршрутизації)
echo 1 > /proc/sys/net/ipv4/ip_forward

# Masquerade (SNAT для виходу в інтернет через eth0)
iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE

# Port Forwarding: перенаправити зовнішній порт 80 на внутрішній хост 192.168.1.10:8080
iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination 192.168.1.10:8080
iptables -A FORWARD -p tcp -d 192.168.1.10 --dport 8080 -j ACCEPT

# SNAT: замінити IP джерела на конкретний
iptables -t nat -A POSTROUTING -s 10.0.0.0/8 -j SNAT --to-source 203.0.113.5</code></pre>

<h2>Збереження та відновлення правил</h2>
<pre><code># Зберегти поточні правила у файл
iptables-save > /etc/iptables/rules.v4

# Відновити правила з файлу
iptables-restore < /etc/iptables/rules.v4

# Ubuntu/Debian: автозавантаження через iptables-persistent
apt install iptables-persistent
netfilter-persistent save
netfilter-persistent reload</code></pre>

<h2>Політики за замовчуванням</h2>
<pre><code># Встановити політику за замовчуванням DROP для INPUT та FORWARD
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# УВАГА: перед цим обов''язково дозволь SSH та встановлені з''єднання!</code></pre>

<h2>Корисні комбінації</h2>
<pre><code># Захист від SYN-flood атак
iptables -A INPUT -p tcp --syn -m limit --limit 1/s --limit-burst 3 -j ACCEPT
iptables -A INPUT -p tcp --syn -j DROP

# Обмеження швидкості підключень (захист від brute-force SSH)
iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --set
iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --update --seconds 60 --hitcount 4 -j DROP

# Логування заблокованих пакетів
iptables -A INPUT -j LOG --log-prefix "IPTables-Dropped: " --log-level 4
iptables -A INPUT -j DROP</code></pre>

<h2>ip6tables (IPv6)</h2>
<p>Для IPv6 використовуйте <code>ip6tables</code> з тим самим синтаксисом:</p>
<pre><code>ip6tables -L -v -n
ip6tables -A INPUT -p tcp --dport 22 -j ACCEPT</code></pre>

<p><em>Примітка: у сучасних дистрибутивах (Ubuntu 20.04+, Debian 10+) рекомендується використовувати <strong>nftables</strong> як заміну iptables, однак iptables залишається широко використовуваним і підтримується через шар сумісності.</em></p>',
1
);

-- -------------------------------------------------------------
-- 15 запитань про iptables (категорія: Мережа / iptables)
-- -------------------------------------------------------------

-- Запитання 1
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда виводить усі правила iptables з детальною інформацією та без резолюції імен?',
 'Прапор -L виводить список правил, -v додає детальну інформацію (пакети, байти), -n вимикає резолюцію DNS/портів.');

INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(LAST_INSERT_ID(), 'iptables -L -v -n', 1),
(LAST_INSERT_ID(), 'iptables -show -all', 0),
(LAST_INSERT_ID(), 'iptables --list --verbose', 0),
(LAST_INSERT_ID(), 'iptables -R -v', 0);

-- Запитання 2
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка дія iptables тихо скидає пакет без відповіді відправнику?',
 'DROP тихо ігнорує пакет. REJECT — відповідає повідомленням про помилку. ACCEPT — пропускає. LOG — лише логує.');

SET @q2 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q2, 'DROP', 1),
(@q2, 'REJECT', 0),
(@q2, 'DENY', 0),
(@q2, 'BLOCK', 0);

-- Запитання 3
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда зберігає поточні правила iptables у файл /etc/iptables/rules.v4?',
 'iptables-save виводить правила у форматі, придатному для збереження. Перенаправлення > зберігає їх у файл.');

SET @q3 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q3, 'iptables-save > /etc/iptables/rules.v4', 1),
(@q3, 'iptables --save /etc/iptables/rules.v4', 0),
(@q3, 'iptables -S > /etc/iptables/rules.v4', 0),
(@q3, 'iptables-backup /etc/iptables/rules.v4', 0);

-- Запитання 4
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яким ланцюжком iptables обробляються пакети, що надходять на поточний хост ззовні?',
 'INPUT — ланцюжок для вхідних пакетів, призначених для самого хоста. FORWARD — для пакетів, що проходять крізь хост.');

SET @q4 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q4, 'INPUT', 1),
(@q4, 'FORWARD', 0),
(@q4, 'PREROUTING', 0),
(@q4, 'INCOMING', 0);

-- Запитання 5
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда видаляє ТРЕТЄ правило з ланцюжка INPUT?',
 'Прапор -D видаляє правило. Після назви ланцюжка вказується номер рядка. Нумерацію можна переглянути через --line-numbers.');

SET @q5 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q5, 'iptables -D INPUT 3', 1),
(@q5, 'iptables -R INPUT 3', 0),
(@q5, 'iptables --delete INPUT --line 3', 0),
(@q5, 'iptables -F INPUT 3', 0);

-- Запитання 6
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда дозволяє вхідні TCP-з''єднання на порт 443 (HTTPS)?',
 '-A додає правило до кінця ланцюжка, -p tcp визначає протокол, --dport вказує порт призначення, -j ACCEPT пропускає пакет.');

SET @q6 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q6, 'iptables -A INPUT -p tcp --dport 443 -j ACCEPT', 1),
(@q6, 'iptables -A INPUT -p tcp --sport 443 -j ACCEPT', 0),
(@q6, 'iptables -I OUTPUT -p tcp --dport 443 -j ACCEPT', 0),
(@q6, 'iptables -A INPUT --port 443 -j ALLOW', 0);

-- Запитання 7
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'В якій таблиці iptables налаштовується NAT (трансляція адрес)?',
 'Таблиця nat призначена для NAT операцій: SNAT, DNAT, MASQUERADE. Таблиця filter — для фільтрації пакетів.');

SET @q7 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q7, 'nat', 1),
(@q7, 'filter', 0),
(@q7, 'mangle', 0),
(@q7, 'forward', 0);

-- Запитання 8
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда налаштовує Masquerade (SNAT) для вихідного трафіку через інтерфейс eth0?',
 'MASQUERADE автоматично підставляє IP вихідного інтерфейсу. Використовується в таблиці nat, ланцюжку POSTROUTING.');

SET @q8 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q8, 'iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE', 1),
(@q8, 'iptables -A POSTROUTING -o eth0 -j MASQUERADE', 0),
(@q8, 'iptables -t nat -A PREROUTING -i eth0 -j MASQUERADE', 0),
(@q8, 'iptables -t filter -A OUTPUT -o eth0 -j MASQUERADE', 0);

-- Запитання 9
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда видаляє ВСІ правила у ланцюжку INPUT (flush)?',
 '-F (flush) очищає всі правила у вказаному ланцюжку. Без аргументів очищає всі ланцюжки.');

SET @q9 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q9, 'iptables -F INPUT', 1),
(@q9, 'iptables -D INPUT', 0),
(@q9, 'iptables -X INPUT', 0),
(@q9, 'iptables --clear INPUT', 0);

-- Запитання 10
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда переглядає правила iptables з номерами рядків?',
 'Опція --line-numbers (або --line-numbers) додає номери рядків до виводу, що спрощує видалення конкретних правил.');

SET @q10 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q10, 'iptables -L INPUT --line-numbers', 1),
(@q10, 'iptables -L INPUT -n', 0),
(@q10, 'iptables -L INPUT --numbers', 0),
(@q10, 'iptables -L INPUT -i', 0);

-- Запитання 11
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яку дію треба вказати для перенаправлення порту (Port Forwarding) у таблиці nat?',
 'DNAT (Destination NAT) змінює IP/порт призначення пакету. Використовується в ланцюжку PREROUTING для port forwarding.');

SET @q11 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q11, 'DNAT', 1),
(@q11, 'SNAT', 0),
(@q11, 'MASQUERADE', 0),
(@q11, 'REDIRECT', 0);

-- Запитання 12
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда встановлює політику за замовчуванням DROP для ланцюжка INPUT?',
 '-P встановлює default policy для ланцюжка. DROP відхиляє всі пакети, що не підпадають під жодне правило.');

SET @q12 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q12, 'iptables -P INPUT DROP', 1),
(@q12, 'iptables -A INPUT -j DROP', 0),
(@q12, 'iptables --policy INPUT DROP', 0),
(@q12, 'iptables -D INPUT -j ACCEPT', 0);

-- Запитання 13
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда дозволяє пакети вже встановлених та пов''язаних з''єднань?',
 'Модуль state відстежує стан з''єднань. ESTABLISHED,RELATED дозволяє відповідні пакети, що важливо при DROP-політиці.');

SET @q13 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q13, 'iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT', 1),
(@q13, 'iptables -A INPUT -m state --state NEW -j ACCEPT', 0),
(@q13, 'iptables -A INPUT --state ESTABLISHED -j ACCEPT', 0),
(@q13, 'iptables -A INPUT -p tcp --established -j ACCEPT', 0);

-- Запитання 14
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда заблокує всі вхідні пакети з IP-адреси 192.168.1.50?',
 '-s вказує IP-адресу джерела. DROP тихо скидає пакет. Правило додається до ланцюжка INPUT.');

SET @q14 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q14, 'iptables -A INPUT -s 192.168.1.50 -j DROP', 1),
(@q14, 'iptables -A INPUT -d 192.168.1.50 -j DROP', 0),
(@q14, 'iptables -A OUTPUT -s 192.168.1.50 -j DROP', 0),
(@q14, 'iptables -A INPUT --from 192.168.1.50 -j DROP', 0);

-- Запитання 15
INSERT INTO questions (category_id, question, explanation) VALUES
(2, 'Яка команда відновлює правила iptables із збереженого файлу /etc/iptables/rules.v4?',
 'iptables-restore читає правила у форматі iptables-save з файлу або stdin та застосовує їх до ядра.');

SET @q15 = LAST_INSERT_ID();
INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(@q15, 'iptables-restore < /etc/iptables/rules.v4', 1),
(@q15, 'iptables-load /etc/iptables/rules.v4', 0),
(@q15, 'iptables --restore /etc/iptables/rules.v4', 0),
(@q15, 'iptables -R /etc/iptables/rules.v4', 0);

-- -------------------------------------------------------------
-- API-токен для адміна (для тестування API)
-- -------------------------------------------------------------
INSERT INTO api_tokens (token, description, user_id, is_active) VALUES
('super_secret_admin_key_2024', 'Головний адмін-ключ', 1, 1);
