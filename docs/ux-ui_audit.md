#6 - Консолидация и унификация дизайн-системы
What & Why
Главная проблема текущего состояния — полный хаос в CSS-архитектуре. При аудите обнаружено, что одни и те же файлы существуют в 3–4 разных местах одновременно: design-tokens.css, design-system.css, critical.css, container-system.css — каждый дублируется в frontend/css/core/, frontend/css/css/, frontend/css/features/, frontend/css/layout/. Это создаёт:
* Конфликты специфичности (один и тот же CSS-свойство перезаписывает себя несколько раз)
* Раздутый CSS-payload (один файл bundle весит больше, чем нужно)
* Невозможность поддержки — непонятно, какой файл является «истиной»
Вторая критическая проблема — несогласованность именования токенов. В проекте используется минимум 4 разные конвенции:
* --color-primary (design-tokens.css)
* --color-primary-900 (design-system.css)
* --bg-primary (dark-mode.css)
* --accent-primary (dark-mode.css)
Это означает, что тёмная тема не работает корректно: dark-mode.css переопределяет переменные --bg-primary, которые не существуют в design-tokens.css. Компоненты, использующие --surface-primary, не получают тёмные значения.
Третья проблема — базовый размер шрифта 15px (0.9375rem). По требованию UI/UX Pro Max (правило readable-font-size), минимум для мобильного — 16px, иначе iOS автоматически зумирует страницу при фокусе на input.
Цель задачи: создать единственный источник правды (Single Source of Truth) для всей дизайн-системы.
Done looks like
* Существует ровно один канонический файл для каждого типа токенов: design-tokens.css — исходник, dist/ — только сгенерированные минифицированные бандлы.
* Все дублирующие файлы в frontend/css/css/, frontend/css/features/, frontend/css/layout/ удалены или заменены импортами канонических файлов.
* Все CSS-переменные следуют единому стилю: --color-* для цветов, --space-* для отступов, --radius-* для скруглений, --shadow-* для теней, --font-* для типографики.
* Тёмная тема использует те же переменные, что и светлая (--surface-primary, --text-primary, --color-accent) — просто переопределяет их значения в [data-theme="dark"].
* Базовый размер шрифта — 16px (1rem) на всех платформах.
* CSS-бандл для публичной части не превышает 60KB (gzip), для каталога — 40KB.
Out of scope
* Переход на CSS-in-JS или Tailwind.
* Изменение визуального дизайна компонентов (только токены и архитектура файлов).
* Изменения в JS-логике.
Tasks
1. Аудит и мэппинг всех CSS-файлов — составить полный список дублирующих файлов, определить канонические версии, создать план удаления.
2. Унификация токенов — привести все переменные к единой конвенции именования. Определить полную карту: семантические роли (surface, text, border, interactive) → значения для light и dark темы. Обновить все вхождения в CSS-файлах.
3. Удаление дублирующих файлов — убрать дубликаты из frontend/css/css/, frontend/css/features/, frontend/css/layout/. В asset-бандлах заменить ссылки на канонические пути.
4. Исправление базового размера шрифта — поднять --font-size-base до 1rem (16px). Проверить и скорректировать все компоненты, использовавшие 15px как базу.
5. Обновление Gulp-пайплайна — исправить gulpfile.js: сборка должна брать файлы только из канонических директорий, а не из всех папок подряд. Бандлы генерируются только в dist/.
Relevant files
* frontend/css/core/design-tokens.css
* frontend/css/core/design-system.css
* frontend/css/features/dark-mode.css
* frontend/css/features/accessibility.css
* frontend/css/core/critical.css
* frontend/css/core/container-system.css
* frontend/css/layout/public-layout.css
* frontend/css/css/
* frontend/gulpfile.js
* frontend/assets/


#7 - Типографика, шрифты и фирменный стиль 2026
What & Why
Сайт премиального магазина кроссовок использует исключительно системные шрифты (-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto). В 2026 году для e-commerce уровня "premium sneaker" это выглядит дёшево: нет уникального голоса бренда, нет запоминаемого визуального ритма. Конкуренты уровня KITH, END Clothing, Sneakersnstuff используют кастомные веб-шрифты как ключевой элемент бренда.
Дополнительно обнаружены проблемы типографической шкалы:
* --font-size-xs: 11px — ниже минимально допустимого значения 12px (правило font-scale UI/UX Pro Max)
* Нет явного letter-spacing для заголовков (по правилу letter-spacing — нужно слегка увеличивать для uppercase-заголовков)
* Нет font-variant-numeric: tabular-numsдля цен (правило number-tabular — цены должны выравниваться в таблицах)
* Отсутствует явная система heading hierarchy в CSS (h1–h6 не скованы типографической шкалой)
* Нет поддержки font-display: optionalдля оптимизации FOIT
Также в разных частях сайта brand name называется по-разному: "СникерКультура", "Sneaker Store", "СНИКЕРХЭД" — это нарушает правило consistency и снижает узнаваемость.
Done looks like
* Подключён Google Font Inter (Variable font, WOFF2) — ведущий выбор для premium e-commerce 2026, с отличной читаемостью на всех размерах.
* Для заголовков используется Bebas Neueили Inter Tight с letter-spacing для premium-ощущения.
* Шрифты подключены с font-display: swapи <link rel="preload"> для критических весов (400, 600, 700).
* --font-size-xs поднят до минимум 12px; минимум body — 16px.
* Цены отображаются с font-variant-numeric: tabular-nums lining-numsдля идеального выравнивания.
* H1–H6 имеют явные размеры из типографической шкалы с правильным line-height и letter-spacing.
* Бренд-название унифицировано как СНИКЕРХЭД во всех местах: заголовке браузера, Open Graph, шапке, подвале, manifest.json.
Out of scope
* Создание кастомного шрифта/логотипа.
* Изменение логики ценообразования.
Tasks
1. Выбор и подключение веб-шрифтов — подключить Inter Variable WOFF2 (Google Fonts или self-hosted в frontend/web/fonts/). Добавить <link rel="preload"> для весов 400/600/700 в layout. Настроить font-display: swap.
2. Обновление typographic scale — обновить design-tokens.css: шрифтовые размеры от 12px до 60px с правильным line-height(1.5–1.75 для body, 1.1–1.25 для заголовков). Добавить переменные --tracking-tight, --tracking-wide для letter-spacing.
3. Стилизация H1–H6 — создать раздел Typography в design-system.css с правилами для всех уровней заголовков: размер, вес, межстрочный интервал, letter-spacing. Заголовки должны образовывать чёткую визуальную иерархию.
4. Цены и числовые данные — применить font-variant-numeric: tabular-nums lining-numsко всем ценам, счётчикам корзины, артикулам.
5. Унификация бренд-нейминга — сделать глобальный поиск и замену всех вариантов названия бренда. Обновить manifest.json, layout, параметры приложения, мета-теги.
Relevant files
* frontend/css/core/design-tokens.css
* frontend/css/core/design-system.css
* frontend/views/layouts/main.php
* frontend/web/manifest.json
* frontend/css/pages/catalog.css
* frontend/css/pages/product.css
* infrastructure/config/params.php

#8 - Доступность (WCAG AA) и управление фокусом
What & Why
Сайт имеет файл accessibility.css, но при аудите кода обнаружен ряд критических проблем доступности:
Проблема 1 — Skip link не подключён в layout.Файл accessibility.css определяет класс .skip-link, но в frontend/views/layouts/main.php нет HTML-элемента <a class="skip-link" href="#main-content">Перейти к содержимому</a>в начале <body>. Пользователи с клавиатурой вынуждены проходить через весь хедер при каждой навигации.
Проблема 2 — Счётчики корзины и избранного невидимы для скрин-ридеров. В layout: <span class="cart-counter sr-only" style="display: none;">0 товаров в корзине</span>— display: none скрывает элемент от скрин-ридеров полностью. Нужно использовать aria-label на кнопке или aria-live="polite" для динамических счётчиков.
Проблема 3 — Фокус-кольцо использует цвет #6366f1 (indigo). Это не совпадает с брендовой палитрой (черный #111827, акцент #3b82f6). По правилу focus-states кольцо должно контрастировать с фоном (4.5:1) И быть частью дизайн-системы.
Проблема 4 — Icon-only кнопки в header.Кнопки поиска, корзины, избранного, аккаунта используют только иконки. В layout у них есть aria-label, что хорошо, но нет проверки, что JS не перезаписывает их.
Проблема 5 — Отсутствие role="alert" / aria-live для уведомлений. Toast-уведомления (добавление в корзину, ошибки формы) должны объявляться через aria-live="polite" или role="alert".
Проблема 6 — Нет ARIA для фильтров каталога. Фильтры — это по сути группы чекбоксов. Им нужны fieldset/legend или role="group" + aria-labelledby.
Done looks like
* WCAG 2.1 AA соответствие для всех ключевых user flows (просмотр каталога, добавление в корзину, оформление заказа).
* Skip link присутствует в layout и работает при нажатии Tab первой клавишей.
* Счётчики корзины/избранного объявляются скрин-ридером при изменении через aria-live="polite".
* Цвет фокус-кольца — #111827 (2px solid) или #3b82f6 в зависимости от контекста, всегда с 4:1+ контрастом к фону.
* Все icon-only кнопки имеют уникальные aria-label на уровне HTML (не только CSS).
* Toast/alert-контейнер имеет role="status"или role="alert" с соответствующим aria-live.
* Фильтры каталога обёрнуты в role="group"с aria-labelledby.
* Формы авторизации/регистрации/заказа: каждый input имеет явный <label>, ошибки валидации появляются рядом с полем с role="alert".
Out of scope
* WCAG AAA соответствие (7:1 контраст) — только AA (4.5:1).
* Поддержка очень старых скрин-ридеров (только NVDA/VoiceOver/JAWS актуальных версий).
Tasks
1. Skip link в layout — добавить <a class="skip-link" href="#main-content">Перейти к основному содержимому</a>как первый дочерний элемент <body> в main.php. Убедиться, что #main-contentявляется правильным id для <main>.
2. Исправление счётчиков корзины/избранного — заменить display: none на visibility: hidden; position: absolute;(CSS-скрытие сохраняет доступность). Добавить aria-live="polite" на контейнер, чтобы скрин-ридер объявлял изменение счётчика.
3. Унификация focus-ring — обновить accessibility.css: заменить #6366f1 на var(--color-accent) из дизайн-системы. Добавить outline-offset: 3px для кнопок с тёмным фоном (белое кольцо через box-shadow).
4. ARIA для уведомлений — создать глобальный toast-контейнер с role="status" aria-live="polite" aria-atomic="true"в layout. Обновить JS-логику добавления в корзину/избранное для использования этого контейнера.
5. ARIA для фильтров каталога — обернуть каждую группу фильтров (бренд, размер, цвет) в <div role="group" aria-labelledby="filter-brand-label">. Добавить соответствующие id для заголовков фильтров.
6. Аудит и исправление форм — проверить все формы (логин, регистрация, оформление заказа, обратная связь): каждый input должен иметь явный <label for="">, ошибки — role="alert", успех — role="status".
Relevant files
* frontend/views/layouts/main.php
* frontend/css/features/accessibility.css
* frontend/views/catalog/_active_filters.php
* frontend/views/catalog/index.php
* frontend/views/account/login.php
* frontend/views/account/register.php
* frontend/js/


#9 - Компонентная система: состояния, анимации и интерактивность
What & Why
Аудит выявил несколько проблем в интерактивном слое компонентов:
Проблема 1 — transition: all 0.2s anti-pattern. В micro-interactions.css кнопки используют transition: all. Это анимирует ВСЕ CSS-свойства (включая width, height, border, color), что вызывает дорогостоящие layout reflows. По правилу transform-performance — анимировать можно только transform и opacity.
Проблема 2 — Hover на карточках — translateY(-8px). Подъём карточки на 8px создаёт layout shift, анимирует box-shadow(paint, а не composite). Для premium e-commerce 2026 это выглядит слишком резко. Правильно: translateY(-4px) + box-shadow через filter: drop-shadow (compositor-only).
Проблема 3 — Нет @media (prefers-reduced-motion). В micro-interactions.css и catalog.css ни одна анимация не обёрнута в prefers-reduced-motion: no-preference. Пользователи с вестибулярными нарушениями или нейровосприимчивостью не могут отключить анимации. Это нарушение правила reduced-motion (CRITICAL).
Проблема 4 — Отсутствие состояний disabled/loading для кнопок. Кнопка "Добавить в корзину" не имеет CSS-состояния .is-loading / .is-disabled. При асинхронном запросе нет визуального фидбека, нет предотвращения двойного клика.
Проблема 5 — Карточка товара не имеет keyboard-доступных quick actions. Quick actions (лайк, быстрый просмотр) появляются только при hover. Правило hover-vs-tap — hover-only контент недоступен на мобильных и клавиатурных пользователях.
Проблема 6 — Skeleton screens не покрывают все async состояния. Skeleton loading существует, но применяется непоследовательно. Фильтры каталога не имеют skeleton при загрузке.
Проблема 7 — Нет empty state для корзины, избранного, результатов поиска. При пустой корзине или 0 результатах поиска — вероятно, просто пустое пространство без guidance для пользователя.
Done looks like
* Все transitions используют только transformи opacity; нигде нет transition: all.
* Все анимации обёрнуты в @media (prefers-reduced-motion: no-preference)— при включённом reduced-motion анимации убраны.
* Кнопка "В корзину" имеет три явных CSS-состояния: default, .is-loading (спиннер + disabled), .is-success (галочка + зелёный, 1.5 сек.).
* Quick actions на карточке товара доступны с клавиатуры через :focus-within (а не только :hover).
* Каждый async-контент (фильтры, результаты поиска, рекомендации) имеет skeleton-состояние.
* Пустые состояния для корзины, избранного, результатов поиска — с иллюстрацией, заголовком и CTA-кнопкой.
* Длительность всех micro-interactions: 150–250ms; complex transitions: 300–350ms максимум.
Out of scope
* Shared element transitions между страницами (требует JS-роутер).
* Введение GSAP или Framer Motion.
Tasks
1. Исправление transition anti-patterns — заменить все transition: all на transition: transform, opacity, box-shadowс правильными GPU-совместимыми значениями. Проверить все CSS-файлы.
2. Обёртка анимаций в prefers-reduced-motion— создать @media (prefers-reduced-motion: no-preference)обёртку для всех анимаций в micro-interactions.css, catalog.css, product.css. Вне обёртки: только instant state changes.
3. Состояния кнопки "В корзину" — в catalog.css и design-tokens.cssдобавить CSS-классы .btn-add-to-cart.is-loading (спиннер, pointer-events: none) и .btn-add-to-cart.is-success (иконка check, зелёный фон, временная). Обновить JS добавления в корзину.
4. Keyboard-accessible quick actions — добавить CSS для .product-card:focus-within .quick-actions { opacity: 1; }наряду с hover-версией. Убедиться, что кнопки быстрых действий focusable и имеют aria-label.
5. Skeleton states для фильтров — добавить CSS-класс .filter-skeleton и HTML-шаблон skeleton для панели фильтров. Отображать при первоначальной загрузке каталога.
6. Empty states — создать переиспользуемый компонент empty state: иконка SVG + заголовок + описание + кнопка. Применить к корзине (нет товаров), избранному, результатам поиска (0 товаров), история заказов.
Relevant files
* frontend/css/features/micro-interactions.css
* frontend/css/pages/catalog.css
* frontend/css/pages/product.css
* frontend/css/pages/cart.css
* frontend/views/catalog/_product_card.php
* frontend/js/
* frontend/css/layout/skeleton-loading.css

#10 - Тёмная тема: полное покрытие и корректный маппинг токенов
What & Why
Тёмная тема задекларирована в файле dark-mode.css и JS-переключателе, но при аудите токенов обнаружена фундаментальная рассинхронизация:
dark-mode.css переопределяет переменные --bg-primary, --bg-secondary, --accent-primary, --surface, --surface-hover. Но дизайн-токены (design-tokens.css) определяют --surface-primary, --surface-secondary, --color-accent, --color-primary. Это РАЗНЫЕ имена переменных — тёмная тема не влияет на компоненты, использующие design-tokens.css.
Результат: при переключении на dark mode:
* Header, карточки товаров, кнопки, формы — остаются светлыми (используют design-tokens токены)
* Только часть компонентов, которая случайно использует --bg-primary — переключается
* Это неконсистентный, "сломанный" dark mode
Дополнительно: для тёмной темы в e-commerce важно правильно настроить изображения (товары на белом фоне на тёмном фоне выглядят странно — нужен mix-blend-mode: multiply или тёмный фон карточки).
По правилу dark-mode-pairing (UI/UX Pro Max): "Design light/dark variants together to keep brand, contrast, and style consistent."
Done looks like
* Переключение на dark mode корректно применяется к 100% компонентов: header, footer, карточки, фильтры, формы, модалы, кнопки, sidebar.
* Все CSS-переменные в dark mode файле совпадают по именам с canonical design-tokens.css.
* Изображения товаров в тёмном режиме: карточки имеют background: #1a1a1a(вместо белого), изображения отображаются корректно.
* Контраст всех текст/фон пар в dark mode соответствует WCAG AA (4.5:1) — проверено инструментами.
* Тёмный режим автоматически активируется при prefers-color-scheme: darkсистемного уровня.
* Иконки и SVG адаптируются к темной теме через currentColor или CSS-фильтры.
* Dark mode переключатель имеет три состояния: light / dark / auto (system) — запоминается в localStorage.
Out of scope
* Создание отдельного dark mode дизайна для каждого компонента с нуля.
* Поддержка custom color themes (кроме light и dark).
Tasks
1. Ревизия и синхронизация токенов — после Task #1 (Консолидация дизайн-системы) обновить dark-mode.css так, чтобы он переопределял ТОЛЬКО те переменные, которые реально определены в canonical design-tokens.css. Удалить все "ложные" переопределения.
2. Полное покрытие компонентов — проверить каждый компонент (header, product card, filter sidebar, cart, modal, form, footer) в dark mode. Для компонентов с жёстко прописанными hex-цветами заменить их на токены.
3. Dark mode для изображений товаров — добавить в [data-theme="dark"] .product-cardправило с тёмным фоном карточки и при необходимости mix-blend-mode для изображений. Тестировать на разных типах изображений (белый фон, прозрачный, цветной).
4. Проверка контраста — для dark mode вручную или автоматически проверить контраст всех критичных пар: body text, заголовки, метки кнопок, placeholder, badges. Исправить все несоответствия WCAG AA.
5. Три-позиционный переключатель темы — обновить JS переключателя dark mode: добавить состояние "auto" (следить за prefers-color-scheme). Использовать иконки Sun/Moon/System. Сохранять выбор в localStorage.
Relevant files
* frontend/css/features/dark-mode.css
* frontend/css/css/dark-mode.css
* frontend/css/core/design-tokens.css
* frontend/js/dark-mode.js
* frontend/views/layouts/main.php
* frontend/css/pages/catalog.css
* frontend/css/pages/product.css


#11 - Mobile UX: touch-оптимизация, навигация и responsive-система
What & Why
Аудит мобильной части выявил несколько несоответствий стандартам 2026:
Проблема 1 — Мобильное меню скрыто через display: none !important с left: -9999px.Это очень грубое решение. Элемент с display: none полностью исключается из дерева доступности. При открытии меню нет smooth-анимации (правило state-transition). Правильный подход — visibility: hidden + transform: translateX(-100%) для анимированного slideIn + сохранение в DOM для скрин-ридеров.
Проблема 2 — Размеры touch-targets не проверены. Правило touch-target-size — минимум 44×44px. Кнопки фильтров (padding: 0.5rem 1rem = ~8px вертикально + шрифт 14px = ~30px высоты) не соответствуют. Значки быстрых действий на карточке также вероятно ниже 44px.
Проблема 3 — Нет touch-action: manipulation на кнопках. Это убирает 300ms задержку клика на мобильных (правило tap-delay).
Проблема 4 — Sticky catalog toolbar вызывает repaints. Toolbar с backdrop-filter: blur(10px) + position: sticky + transition: all 0.2s — это тройной удар по производительности scroll. backdrop-filter должен быть изолирован в will-change: transform контейнере.
Проблема 5 — Нет адаптивной типографики.Заголовки продуктов, цены — фиксированные px-значения, не масштабируются между мобильным (375px) и десктопом (1400px). Правило dynamic-type — текст должен масштабироваться. clamp() — идеальное решение для 2026.
Проблема 6 — Горизонтальный скролл на фильтрах-тегах. На мобильных активные фильтры (теги) скорее всего выходят за край экрана. Нет -webkit-overflow-scrolling: touch для плавности.
Проблема 7 — Нет bottom sheet для фильтров на мобильном. Правило adaptive-navigation: на мобильных (< 768px) боковая панель фильтров должна появляться как bottom sheet (выдвигается снизу), а не как overlay сбоку. Bottom sheets — 2026 standard для мобильного e-commerce.
Done looks like
* Mobile menu анимируется через transform: translateX (smooth slide-in, 250ms ease-out).
* Все touch targets ≥ 44×44px — кнопки фильтров, иконки действий, пагинация, кнопки форм.
* touch-action: manipulation применён ко всем кнопкам и интерактивным элементам.
* Sticky toolbar не вызывает repaints: использует will-change: transform, backdrop-filterв отдельном pseudo-element.
* Заголовки используют clamp() для адаптивного масштабирования между мобильным и десктопом.
* Фильтры на мобильном появляются как bottom sheet с border-radius: 20px 20px 0 0 снизу и drag-to-close.
* Все скроллируемые горизонтальные области имеют -webkit-overflow-scrolling: touchи скрытый scrollbar на мобильном.
* Нет горизонтального переполнения ни на одной странице при 375px ширине viewport.
Out of scope
* Нативное мобильное приложение.
* PWA push notifications.
* Введение JS-фреймворка для анимаций.
Tasks
1. Переработка mobile menu animation — заменить display: none / left: -9999pxна visibility: hidden + transform: translateX(-100%) с transition. Добавить aria-expanded на toggle-кнопку. При открытии: focus на первый элемент меню.
2. Touch target audit и исправление — проверить все интерактивные элементы на мобильном (кнопки фильтров, быстрые действия, пагинация, иконки header). Добавить минимальный min-height: 44px; min-width: 44px + padding для всех.
3. Адаптивная типографика с clamp() — заменить фиксированные px-размеры заголовков на clamp(min, preferred, max). Например: clamp(1.5rem, 4vw, 3rem) для H1 на главной. Применить к заголовку товара, ценам, hero-тексту.
4. Bottom sheet для фильтров на мобильном — создать CSS-компонент bottom sheet (slide-up из нижнего края). На мобильных (< 768px) панель фильтров переключается на bottom sheet вместо sidebar. Добавить drag indicator + swipe-to-close.
5. Оптимизация sticky toolbar — вынести backdrop-filter в ::before pseudo-element с position: absolute; z-index: -1. Убрать transition: all с toolbar. Добавить will-change: transform только при активном скролле (JS intersection observer).
6. Горизонтальные скролл-области — добавить плавный горизонтальный scroll с scroll-snap-type: x mandatory для слайдеров фотографий товаров, тегов фильтров, рекомендаций.
Relevant files
* frontend/css/css/mobile-menu.css
* frontend/css/css/mobile-first.css
* frontend/css/css/responsive-fixes.css
* frontend/css/css/pages-mobile.css
* frontend/views/layouts/main.php
* frontend/css/pages/catalog.css
* frontend/css/css/mega-menu.css
* frontend/js/