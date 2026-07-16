/**
 * LinuxTest - Головний JavaScript
 * Темна/світла тема, quiz-логіка, AJAX запити, модалки
 */

/* ============================================================
   1. Тема (темна/світла) — збереження у localStorage
   ============================================================ */
(function() {
    // Застосовуємо тему одразу при завантаженні (до рендерингу)
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();

document.addEventListener('DOMContentLoaded', function() {

    /* ---------- Тема ---------- */
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        updateThemeButton();
        themeBtn.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeButton();
        });
    }

    function updateThemeButton() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (themeBtn) themeBtn.textContent = isDark ? '☀️ Світла' : '🌙 Темна';
    }

    /* ---------- Бургер-меню (мобільний) ---------- */
    const burger = document.querySelector('.navbar-burger');
    const menu   = document.querySelector('.navbar-menu');
    if (burger && menu) {
        burger.addEventListener('click', function() {
            menu.classList.toggle('open');
        });
        // Закриваємо при кліку поза меню
        document.addEventListener('click', function(e) {
            if (!burger.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
    }

    /* ---------- Flash-повідомлення (автоприховування) ---------- */
    document.querySelectorAll('.flash-msg').forEach(function(el) {
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity .4s';
            setTimeout(function() { el.remove(); }, 400);
        }, 4000);
    });

    /* ---------- Підтвердження видалення ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(el.getAttribute('data-confirm') || 'Ви впевнені?')) {
                e.preventDefault();
            }
        });
    });

    /* ---------- Модальні вікна ---------- */
    document.querySelectorAll('[data-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = btn.getAttribute('data-modal');
            openModal(id);
        });
    });
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) closeAllModals();
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAllModals();
    });
});

/** Відкриває модальне вікно за ID */
function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.add('open');
}

/** Закриває всі відкриті модальні вікна */
function closeAllModals() {
    document.querySelectorAll('.modal-overlay.open').forEach(function(el) {
        el.classList.remove('open');
    });
}

/* ============================================================
   2. Quiz-логіка
   ============================================================ */
let quizState = {
    questions:    [],    // масив запитань із відповідями
    currentIndex: 0,     // поточний індекс
    correct:      0,     // кількість правильних
    categoryId:   null,  // ID категорії (null = всі)
    resultLog:    [],    // лог відповідей [{questionId, answerId, correct}]
    finished:     false,
};

/**
 * Ініціалізація тесту — завантажує запитання через API
 */
async function initQuiz(categoryId) {
    quizState.categoryId  = categoryId;
    quizState.currentIndex = 0;
    quizState.correct      = 0;
    quizState.resultLog    = [];
    quizState.finished     = false;

    const container = document.getElementById('quiz-container');
    if (!container) return;

    showLoading(container);

    try {
        const url = '/api/quiz.php?action=questions' + (categoryId ? '&category=' + categoryId : '');
        const resp = await fetch(url);
        const data = await resp.json();

        if (!data.success || !data.questions.length) {
            container.innerHTML = '<div class="alert alert-warning">Немає запитань для цієї категорії.</div>';
            return;
        }

        quizState.questions = data.questions;
        renderQuestion();
    } catch (err) {
        container.innerHTML = '<div class="alert alert-danger">Помилка завантаження запитань. Спробуйте пізніше.</div>';
    }
}

/** Відображає поточне запитання */
function renderQuestion() {
    const container = document.getElementById('quiz-container');
    if (!container) return;

    const q     = quizState.questions[quizState.currentIndex];
    const total = quizState.questions.length;
    const idx   = quizState.currentIndex + 1;
    const pct   = Math.round(((idx - 1) / total) * 100);

    container.innerHTML = `
        <div class="quiz-header">
            <span class="quiz-progress-text">Запитання ${idx} з ${total}</span>
            <span class="text-muted text-sm">${q.category_name}</span>
        </div>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:${pct}%"></div>
        </div>
        <div class="card">
            <div class="question-text">${escHtml(q.question)}</div>
            <div id="answers-list">
                ${q.answers.map(a => `
                    <button class="answer-btn"
                            onclick="selectAnswer(${q.id}, ${a.id}, this)"
                            data-id="${a.id}">
                        ${escHtml(a.answer_text)}
                    </button>
                `).join('')}
            </div>
            <div class="explanation-box" id="explanation"></div>
        </div>
    `;
}

/** Обробка вибору відповіді */
async function selectAnswer(questionId, answerId, clickedBtn) {
    // Блокуємо всі кнопки
    document.querySelectorAll('.answer-btn').forEach(b => b.disabled = true);

    try {
        const resp = await fetch('/api/quiz.php?action=check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question_id: questionId, answer_id: answerId })
        });
        const data = await resp.json();

        if (!data.success) return;

        // Підсвічуємо правильну та неправильну
        document.querySelectorAll('.answer-btn').forEach(b => {
            if (parseInt(b.getAttribute('data-id')) === data.correct_id) {
                b.classList.add('correct');
            } else if (b === clickedBtn && !data.is_correct) {
                b.classList.add('incorrect');
            }
        });

        // Показуємо пояснення
        if (data.explanation) {
            const expl = document.getElementById('explanation');
            if (expl) {
                expl.textContent = '💡 ' + data.explanation;
                expl.style.display = 'block';
            }
        }

        // Оновлюємо лічильник
        if (data.is_correct) quizState.correct++;

        quizState.resultLog.push({
            question_id: questionId,
            answer_id:   answerId,
            is_correct:  data.is_correct
        });

        // Кнопка "Далі" / "Завершити"
        const container = document.getElementById('quiz-container');
        const isLast = quizState.currentIndex >= quizState.questions.length - 1;

        const nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn-primary mt-2';
        nextBtn.style.width = '100%';
        nextBtn.textContent = isLast ? '🏁 Завершити тест' : '➡️ Наступне';
        nextBtn.onclick = isLast ? finishQuiz : nextQuestion;

        container.querySelector('.card').appendChild(nextBtn);

    } catch (err) {
        console.error('Помилка перевірки відповіді', err);
    }
}

/** Переходить до наступного запитання */
function nextQuestion() {
    quizState.currentIndex++;
    renderQuestion();
}

/** Завершує тест та зберігає результат */
async function finishQuiz() {
    quizState.finished = true;
    const container = document.getElementById('quiz-container');
    showLoading(container);

    try {
        const resp = await fetch('/api/quiz.php?action=finish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                category_id: quizState.categoryId,
                answers:     quizState.resultLog
            })
        });
        const data = await resp.json();

        const total   = quizState.questions.length;
        const correct = quizState.correct;
        const score   = total > 0 ? Math.round((correct / total) * 100) : 0;
        const cls     = score >= 80 ? 'score-excellent' : score >= 60 ? 'score-good' : score >= 40 ? 'score-average' : 'score-poor';

        container.innerHTML = `
            <div class="card score-card">
                <h2 class="mb-1">Тест завершено!</h2>
                <div class="score-number ${cls}">${score}%</div>
                <p class="text-muted mt-1">Правильних відповідей: <strong>${correct}</strong> з <strong>${total}</strong></p>
                <div class="d-flex gap-2 justify-center mt-3 flex-wrap" style="justify-content:center">
                    <a href="/quiz.php${quizState.categoryId ? '?category=' + quizState.categoryId : ''}" class="btn btn-primary">🔄 Спробувати ще раз</a>
                    <a href="/results.php" class="btn btn-outline">📊 Мої результати</a>
                    <a href="/" class="btn btn-outline">🏠 На головну</a>
                </div>
            </div>
        `;
    } catch (err) {
        container.innerHTML = '<div class="alert alert-danger">Помилка збереження результату.</div>';
    }
}

/* ============================================================
   3. Допоміжні функції
   ============================================================ */

/** Екранує HTML-символи (XSS-захист на клієнті) */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/** Показує спіннер у контейнері */
function showLoading(container) {
    container.innerHTML = '<div class="text-center mt-3"><div class="spinner"></div><p class="text-muted mt-1">Завантаження...</p></div>';
}

/** Показує flash-повідомлення */
function showFlash(message, type = 'success') {
    let fc = document.querySelector('.flash-container');
    if (!fc) {
        fc = document.createElement('div');
        fc.className = 'flash-container';
        document.body.appendChild(fc);
    }
    const msg = document.createElement('div');
    msg.className = `flash-msg ${type}`;
    msg.textContent = message;
    fc.appendChild(msg);
    setTimeout(function() {
        msg.style.opacity = '0';
        msg.style.transition = 'opacity .4s';
        setTimeout(function() { msg.remove(); }, 400);
    }, 3500);
}

/* ============================================================
   4. Адмін: динамічний CRUD запитань
   ============================================================ */

/** Заповнює форму редагування запитання */
function populateEditForm(questionId) {
    fetch('/api/quiz.php?action=get_question&id=' + questionId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const q = data.question;
            document.getElementById('edit-id').value       = q.id;
            document.getElementById('edit-question').value = q.question;
            document.getElementById('edit-category').value = q.category_id;
            document.getElementById('edit-explanation').value = q.explanation || '';
            q.answers.forEach(function(a, i) {
                const input = document.getElementById('edit-answer-' + i);
                const radio = document.getElementById('edit-correct-' + i);
                if (input) input.value = a.answer_text;
                if (radio)  radio.checked = !!a.is_correct;
            });
            openModal('edit-question-modal');
        })
        .catch(console.error);
}
