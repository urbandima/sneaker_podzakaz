#1 - Исправление критических ошибок индексации
What & Why
Исправить три критических SEO-проблемы, которые мешают корректной индексации сайта поисковыми системами.
Проблема 1 — Sitemap с localhost-адресами.Файл frontend/web/sitemap.xml содержит жёстко прописанные URL вида http://localhost:8080/.... Поисковый робот, получив такой sitemap, не сможет проиндексировать реальные страницы. Сервис SitemapGenerator существует, но статичный файл не обновляется. Нужно подключить динамическую генерацию sitemap через контроллер (или консольную команду), а robots.txt обновить с реальным доменом.
Проблема 2 — JSON-LD не рендерится в HTML.Метод registerJsonLd в CatalogSeoTraitсохраняет данные в $this->view->params['jsonLdSchemas'], но основной шаблон main.php не содержит кода для вывода этого массива в <head>. Разметка Schema.org просто нигде не появляется на странице.
Проблема 3 — robots.txt с дублирующими директивами. Директива Crawl-delay: 1объявлена дважды (в глобальном блоке и в конце файла). Дублирование Disallow: /admin и /admin/ создаёт неоднозначность. Также /cart и /accountприсутствуют в sitemap.xml, хотя они закрыты в robots.txt — противоречие, которое путает краулеры.
Done looks like
* При запросе /sitemap.xml возвращается динамически сгенерированный файл с реальными URL сайта (не localhost) по всем товарам, категориям и брендам из БД.
* В <head> каждой страницы каталога/товара присутствует тег <script type="application/ld+json"> с валидными данными Schema.org (проверяется через Rich Results Test).
* robots.txt не содержит дублирующих директив; /cart и /account убраны из sitemap; ссылка на sitemap в robots.txt указывает на динамический endpoint.
Out of scope
* Создание sitemap-images.xml (отдельная задача).
* Изменение структуры URL или UrlManager.
Tasks
1. Динамический sitemap — Создать action SitemapController::actionIndex(), который вызывает SitemapGenerator и отдаёт XML с заголовком Content-Type: application/xml. Настроить роут /sitemap.xml в web.php. Удалить статичный файл frontend/web/sitemap.xmlили сделать его заглушкой с редиректом.
2. Вывод JSON-LD в layout — В frontend/views/layouts/main.phpдобавить цикл, который читает $this->params['jsonLdSchemas'] и рендерит каждый элемент как <script type="application/ld+json">перед закрывающим </head>.
3. Очистка robots.txt — Убрать дублирующий Crawl-delay, нормализовать блоки Disallow, обновить ссылку на sitemap на динамический URL.
Relevant files
* frontend/web/sitemap.xml
* frontend/web/robots.txt
* frontend/views/layouts/main.php
* backend/shared/traits/CatalogSeoTrait.php
* backend/services/Sitemap/SitemapGenerator.php
* infrastructure/config/web.php


#2 - SEO главной страницы и статических страниц
What & Why
Главная страница — самая важная страница сайта с точки зрения SEO, но сейчас она содержит лишь placeholder-контент без динамического мета, без Schema.org разметки Organization/WebSite, без уникального описания и без реального контента (популярные товары, категории, преимущества). Это приводит к тому, что Google видит пустую страницу без сигналов релевантности.
Дополнительно: статические страницы (о нас, контакты, доставка, оплата) вероятно не имеют уникальных <title> и <meta description> — стандартная SEO-ошибка для Yii2 проектов, где контроллер не задаёт эти поля.
Несоответствие бренда. Title главной страницы — "СникерКультура", тогда как бренд в шаблоне и manifest.json называется "Sneaker Store" / "СНИКЕРХЭД". Нужна единая брендовая формулировка во всех тегах.
Done looks like
* <title> главной страницы содержит ключевое слово + бренд (например: "Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД"), не превышает 60 символов.
* <meta name="description"> главной страницы уникален, 150–160 символов, содержит ключевые слова и призыв к действию.
* В <head> главной страницы присутствует JSON-LD с типами Organization и WebSite(включая SearchAction для поиска по сайту).
* На главной странице отображается реальный контент: хотя бы блок "Популярные товары" (4–8 товаров из БД) и блок категорий/брендов.
* Все статические страницы (доставка, оплата, о нас, контакты) имеют уникальный <title>и <meta description>, задаваемые в соответствующих action-методах контроллеров.
* Название бренда унифицировано во всех мета-тегах, manifest.json и шаблонах.
Out of scope
* Полный редизайн главной страницы.
* Создание блога или статейного раздела.
* Настройка Google Search Console.
Tasks
1. SEO-метаданные главной страницы — В SiteController::actionIndex() задать $this->view->title, зарегистрировать meta description, Open Graph теги (og:title, og:description, og:image, og:type=website) и canonical URL.
2. Schema.org Organization + WebSite — Добавить в SiteController::actionIndex()регистрацию JSON-LD со схемами Organization (название, URL, логотип, контакты, соцсети) и WebSite с SearchAction (потенциальная подсказка поиска в Google).
3. Блок реального контента — Загрузить в actionIndex() 6–8 популярных/новых товаров из БД (через существующий репозиторий) и передать в вид. Отобразить карточки товаров с ценой, изображением, alt-текстом и ссылкой.
4. Мета-теги статических страниц — Для каждой страницы (доставка, оплата, о нас, контакты, возврат) добавить уникальные title и meta description в соответствующие action-методы.
5. Унификация бренда — Исправить название бренда во всех местах (title главной, manifest.json, alt логотипа в layout) на единое значение из настроек приложения.
Relevant files
* frontend/controllers/SiteController.php
* frontend/views/site/index.php
* frontend/views/layouts/main.php
* frontend/web/manifest.json
* backend/shared/traits/CatalogSeoTrait.php
* backend/shared/components/SchemaOrgGenerator.php
* infrastructure/config/params.php


#3 - SEO пагинации и фильтров каталога
What & Why
Каталог использует бесконечную прокрутку (Infinite Scroll) для загрузки товаров. Поисковые роботы не выполняют JS-скроллинг и не видят товары, загруженные динамически, — для них каталог выглядит как страница с 12–24 товарами, а остальные сотни товаров «невидимы».
Также фильтрация через SmartFilterгенерирует множество URL-вариантов одного и того же набора товаров (например, /catalog?brand=nike&color=red и /catalog?color=red&brand=nike). Без правильного canonical это создаёт тысячи дублирующихся страниц.
Дополнительно в catalog/index.php явно прописаны <meta http-equiv="Cache-Control" content="no-cache">— это мешает браузерному кэшированию и ухудшает Core Web Vitals.
Done looks like
* Страницы пагинации каталога (/catalog?page=2, /catalog?page=3) доступны по прямой ссылке и содержат реальный HTML с товарами (статическая пагинация как резервный вариант для краулеров).
* Каждая страница каталога с фильтрами имеет canonical URL, указывающий на «главный» вариант страницы (без несущественных параметров).
* Комбинации параметров фильтров нормализованы в алфавитном порядке до записи canonical (предотвращает дубли brand=nike&color=red vs color=red&brand=nike).
* Мета-теги Cache-Control убраны из шаблона каталога (это не место для HTTP-заголовков).
* AJAX-endpoint /catalog/load-more закрыт в robots.txt от индексации (уже есть, проверить).
Out of scope
* Переработка алгоритма фильтрации SmartFilter.
* Создание отдельных посадочных страниц под каждую комбинацию фильтров (programmatic SEO — отдельная задача).
Tasks
1. Статическая пагинация для краулеров — Реализовать серверный fallback: если запрос выполняется без JS (нет заголовка X-Requested-With) или если пришёл ?page=N, рендерить полноценный HTML с товарами вместо ответа для infinite scroll. Добавить rel="next" / rel="prev" ссылки в <head> для многостраничного каталога.
2. Нормализация canonical для фильтров — В CatalogSeoTrait::registerMetaTags()сортировать параметры запроса перед формированием canonical URL. Исключить из canonical несущественные параметры (сортировка, CSRF-токены и т.д.).
3. Удаление no-cache мета-тегов — Убрать из catalog/index.php блок с http-equiv Cache-Control / Pragma / Expiresв мета-тегах. Перенести управление кэшем на уровень HTTP-заголовков в контроллере (только для dev-режима, если необходимо).
Relevant files
* frontend/views/catalog/index.php
* frontend/controllers/CatalogController.php
* backend/shared/traits/CatalogSeoTrait.php
* backend/shared/components/SmartFilter.php
* frontend/web/robots.txt

#4 - On-page SEO: изображения, hreflang и карточки товаров
What & Why
Несколько on-page проблем, выявленных при аудите:
Изображения без alt-текстов. Карточки товаров (_product_card.php) вероятно используют alt из названия товара, но нужно убедиться, что alt содержит ключевые слова (название + бренд + тип), а не просто технический идентификатор. Изображения без alt — прямая потеря трафика из Google Images + штраф за доступность.
Отсутствие hreflang. Сайт на русском языке ориентирован на Беларусь (домен .by), но не имеет <link rel="alternate" hreflang="ru-BY">. Это сигнал для Google о языке и регионе.
Карточки товаров без Schema.org Product. На страницах листинга каталога Schema.org ItemList регистрируется через CatalogSeoTrait, но на отдельных страницах товара (/catalog/product/{slug}) нужно убедиться, что выводится полная разметка Product со схемами Offer, AggregateRating, Brand.
Missing meta на категориях и брендах.Страницы /catalog/category/{slug} и /catalog/brand/{slug} генерируют динамический title, но может отсутствовать уникальный H1, совпадающий с title, и описание категории/бренда в теле страницы.
Done looks like
* Каждое изображение товара в карточке имеет alt в формате "[Название товара] [Бренд] — купить в Беларуси".
* В <head> каждой страницы присутствует <link rel="alternate" hreflang="ru-BY">и <link rel="alternate" hreflang="x-default">.
* Страница товара проходит Google Rich Results Test с типом Product (включая цену, наличие, бренд, рейтинг).
* Страницы категорий и брендов имеют видимый H1, соответствующий title, и хотя бы краткое текстовое описание (даже если оно автогенерируется).
Out of scope
* Оптимизация изображений (WebP, сжатие) — в отдельной задаче по Core Web Vitals.
* Создание текстового контента для категорий вручную — только автогенерация.
Tasks
1. Alt-тексты изображений товаров — В шаблоне карточки товара и на странице товара проверить и исправить атрибуты altдля всех <img>: использовать формулу {product.name} {brand.name} вместо пустых или технических значений.
2. hreflang в layout — В frontend/views/layouts/main.phpдобавить в <head> тег <link rel="alternate" hreflang="ru-BY" href="{currentUrl}">и <link rel="alternate" hreflang="x-default" href="{currentUrl}">.
3. Проверка Schema.org Product на странице товара — Найти view и controller страницы отдельного товара, убедиться, что SchemaOrgGenerator вызывается и JSON-LD с типом Product регистрируется через registerJsonLd. Добавить при необходимости.
4. H1 и описание на страницах категорий/брендов — Убедиться, что в шаблонах category.php и brand.php выводится <h1>с названием категории/бренда, совпадающим с $this->title. Добавить автогенерируемый текстовый абзац-описание если его нет.
Relevant files
* frontend/views/catalog/_product_card.php
* frontend/views/catalog/category.php
* frontend/views/catalog/brand.php
* frontend/views/layouts/main.php
* frontend/controllers/CatalogController.php
* backend/shared/components/SchemaOrgGenerator.php
* backend/shared/traits/CatalogSeoTrait.php

#5 - Core Web Vitals и скорость загрузки
What & Why
Core Web Vitals (LCP, INP, CLS) являются прямым фактором ранжирования Google с 2021 года. Анализ кода выявил несколько проблем производительности:
* Нет <link rel="preload"> для критических ресурсов (шрифты, LCP-изображение, основной CSS).
* Bootstrap Icons загружается как иконочный шрифт — шрифты рендер-блокирующие, особенно при первой загрузке.
* Нет WebP-версий изображений товаров — все изображения, судя по коду, подаются в исходном формате (вероятно JPEG/PNG). WebP даёт 25–35% экономию размера.
* Нет loading="lazy" на изображениях ниже fold — все изображения каталога загружаются сразу.
* Отсутствует ресурсный hint <link rel="dns-prefetch"> для внешних CDN и шрифтов.
* LCP-элемент неизвестен — главный баннер или первая карточка товара должны иметь fetchpriority="high" и loading="eager".
Done looks like
* PageSpeed Insights (мобильная версия) показывает Performance score ≥ 70 для главной страницы и страницы каталога.
* LCP < 2.5 с на мобильных устройствах (проверяется через PageSpeed Insights).
* В <head> layout присутствуют <link rel="preload"> для основного CSS-файла и <link rel="dns-prefetch"> для внешних ресурсов.
* Изображения товаров в карточках имеют атрибут loading="lazy" (кроме первых 4 карточек, которые имеют loading="eager").
* При наличии WebP-версии изображения, браузер получает WebP (через <picture> или на уровне сервера).
Out of scope
* Внедрение полноценного CDN (BunnyCDN, CloudFlare) — требует production-конфигурации.
* Переход на HTTP/2 Push или Server-Side Rendering.
* Полный перевод на современный JS-бандлер (Vite/esbuild).
Tasks
1. Preload и DNS prefetch в layout — В main.phpдобавить <link rel="preload"> для основного CSS-файла (с as="style") и LCP-изображения на главной (as="image"). Добавить <link rel="dns-prefetch"> для внешних ресурсов (шрифты, иконки).
2. Lazy loading изображений — В шаблоне карточки товара (_product_card.php) добавить loading="lazy" ко всем <img>. На главной странице первые 4 карточки и баннер получают loading="eager" fetchpriority="high".
3. WebP-конвертация через Gulp — Добавить в gulpfile.js задачу конвертации изображений из frontend/images в WebP с помощью gulp-webp. Обновить шаблоны для использования <picture> с WebP-источником и JPEG/PNG fallback.
4. Оптимизация загрузки иконочного шрифта— Добавить font-display: swap в CSS для Bootstrap Icons. Рассмотреть замену иконочного шрифта на SVG-спрайт для иконок header/footer (10–15 иконок), чтобы убрать рендер-блокирующий шрифт.
Relevant files
* frontend/views/layouts/main.php
* frontend/views/catalog/_product_card.php
* frontend/gulpfile.js
* frontend/css/
* frontend/assets/
