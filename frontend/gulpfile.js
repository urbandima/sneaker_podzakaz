const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const terser = require('gulp-terser');
const concat = require('gulp-concat');
const rename = require('gulp-rename');

// Пути к файлам
const paths = {
    css: {
        // Критические стили (инлайнятся в head)
        critical: [
            'frontend/css/core/design-tokens.css',
            'frontend/css/core/critical.css',
            'frontend/css/css/container-system.css',
            'frontend/css/css/skeleton-loading.css'
        ],
        // Публичные стили (основной сайт)
        public: [
            'frontend/css/core/design-system.css',
            'frontend/css/css/site.css',
            'frontend/css/css/public-layout.css',
            'frontend/css/features/accessibility.css',
            'frontend/css/features/micro-interactions.css',
            'frontend/css/features/dark-mode.css',
            'frontend/css/css/mobile-first.css',
            'frontend/css/css/header-adaptive.css',
            'frontend/css/features/mobile-menu.css',
            'frontend/css/css/responsive-fixes.css'
        ],
        // Каталог
        catalog: [
            'web/css/features/catalog-layout.css',
            'web/css/features/catalog-inline.css',
            'web/css/features/catalog-card.css',
            'web/css/catalog-mobile-fixes.css'
        ],
        // Страница товара (отдельно - большой файл)
        product: [
            'frontend/css/css/dist/product-page.min.css'
        ],
        // Админка
        admin: [
            'css/admin-bundle.min.css',
            'css/pages/admin.css'
        ],
        // Vendor CSS (Bootstrap и другие библиотеки)
        vendor: [
            'web/assets/vendor/bootstrap/css/bootstrap.min.css'
        ],
        dest: 'web/css/dist/'
    },
    js: {
        // Core (загружается везде)
        core: [
            'web/assets/vendor/jquery/jquery.min.js',
            'web/assets/vendor/yii2/js/yii.js',
            'web/assets/vendor/yii2/js/yii.activeForm.js',
            'web/js/global-helpers.js',
            'web/js/notifications.js',
            'web/js/lazy-load.js'
        ],
        // Bootstrap JS
        bootstrap: [
            'web/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'
        ],
        // Каталог
        catalog: [
            'web/js/catalog.js',
            'web/js/price-slider.js'
        ],
        // Страница товара
        product: [
            'web/js/product-page.js',
            'web/js/product-modals.js'
        ],
        // UI улучшения
        ui: [
            'web/js/ui-enhancements.js',
            'web/js/sticky-bar.js',
            'web/js/header-enhancements.js'
        ],
        // Корзина
        cart: [
            'web/js/cart.js',
            'web/js/cart-mobile.js'
        ],
        // Мобильные
        mobile: [
            'web/js/mobile-menu.js',
            'web/js/view-history.js'
        ],
        dest: 'web/js/dist/'
    }
};

// === CSS BUNDLES ===

// Критические стили (inline в head)
gulp.task('critical-css', function () {
    return gulp.src(paths.css.critical)
        .pipe(concat('critical.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Публичные стили
gulp.task('public-css', function () {
    return gulp.src(paths.css.public)
        .pipe(concat('public-bundle.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Каталог
gulp.task('catalog-css', function () {
    return gulp.src(paths.css.catalog)
        .pipe(concat('catalog-bundle.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Страница товара
gulp.task('product-css', function () {
    return gulp.src(paths.css.product)
        .pipe(concat('product-bundle.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Админка
gulp.task('admin-css', function () {
    return gulp.src(paths.css.admin)
        .pipe(concat('admin-bundle.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Vendor CSS (Bootstrap)
gulp.task('vendor-css', function () {
    return gulp.src(paths.css.vendor)
        .pipe(concat('vendor-bundle.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// === JS BUNDLES ===

// Core JS
gulp.task('core-js', function () {
    return gulp.src(paths.js.core)
        .pipe(concat('core-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// Каталог JS
gulp.task('catalog-js', function () {
    return gulp.src(paths.js.catalog)
        .pipe(concat('catalog-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// Продукт JS
gulp.task('product-js', function () {
    return gulp.src(paths.js.product)
        .pipe(concat('product-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// UI JS
gulp.task('ui-js', function () {
    return gulp.src(paths.js.ui)
        .pipe(concat('ui-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// Cart JS
gulp.task('cart-js', function () {
    return gulp.src(paths.js.cart)
        .pipe(concat('cart-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// Mobile JS
gulp.task('mobile-js', function () {
    return gulp.src(paths.js.mobile)
        .pipe(concat('mobile-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// Bootstrap JS
gulp.task('bootstrap-js', function () {
    return gulp.src(paths.js.bootstrap)
        .pipe(concat('bootstrap-bundle.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.js.dest));
});

// === COMPOSITE TASKS ===

// Все CSS бандлы
gulp.task('css-bundles', gulp.parallel(
    'critical-css',
    'public-css',
    'catalog-css',
    'product-css',
    'admin-css',
    'vendor-css'
));

// Все JS бандлы
gulp.task('js-bundles', gulp.parallel(
    'core-js',
    'bootstrap-js',
    'catalog-js',
    'product-js',
    'ui-js',
    'cart-js',
    'mobile-js'
));

// Production сборка
gulp.task('production', gulp.parallel('css-bundles', 'js-bundles'));

// Алиасы для совместимости
gulp.task('build', gulp.series('production'));
gulp.task('bundle', gulp.series('production'));
gulp.task('minify', gulp.series('production'));
gulp.task('minify-css', gulp.series('css-bundles'));
gulp.task('minify-js', gulp.series('js-bundles'));
gulp.task('bundle-css', gulp.series('css-bundles'));
gulp.task('bundle-js', gulp.series('js-bundles'));

// Watch для разработки
gulp.task('watch', function () {
    gulp.watch('web/css/**/*.css', gulp.series('css-bundles'));
    gulp.watch('web/js/**/*.js', gulp.series('js-bundles'));
});

// Задача по умолчанию
gulp.task('default', gulp.series('production'));
