const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const terser = require('gulp-terser');
const concat = require('gulp-concat');
const rename = require('gulp-rename');

// Пути к файлам
const paths = {
    css: {
        // Основные стили
        src: [
            '../../frontend/web/css/app.css',
            '../../frontend/web/css/components.css',
            '../../frontend/web/css/pages.css',
            '../../frontend/web/css/catalog.css',
            '../../frontend/web/css/cart.css',
            '../../frontend/web/css/utilities.css',
            '../../frontend/web/css/design-tokens.css'
        ],
        dest: '../../frontend/web/css/dist/'
    },
    js: {
        src: [
            '../../frontend/web/js/global-helpers.js',
            '../../frontend/web/js/notifications.js',
            '../../frontend/web/js/favorites.js',
            '../../frontend/web/js/cart.js',
            '../../frontend/web/js/catalog.js',
            '../../frontend/web/js/product-page.js',
            '../../frontend/web/js/lazy-load.js',
            '../../frontend/web/js/mobile-menu.js',
            '../../frontend/web/js/app.js',
            '../../frontend/web/js/ui-enhancements.js'
        ],
        dest: '../../frontend/web/js/dist/'
    }
};

// Минификация CSS
gulp.task('minify-css', function () {
    return gulp.src(paths.css.src)
        .pipe(cleanCSS({ compatibility: 'ie11' }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.css.dest));
});

// Объединение и минификация CSS в один файл
gulp.task('bundle-css', function () {
    return gulp.src(paths.css.src)
        .pipe(concat('bundle.css'))
        .pipe(cleanCSS({ compatibility: 'ie11' }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.css.dest));
});

// Минификация JS
gulp.task('minify-js', function () {
    return gulp.src(paths.js.src)
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.js.dest));
});

// Объединение и минификация JS в один файл
gulp.task('bundle-js', function () {
    return gulp.src(paths.js.src)
        .pipe(concat('bundle.js'))
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.js.dest));
});

// Сборка всего
gulp.task('build', gulp.parallel('minify-css', 'minify-js'));

// Сборка бандлов
gulp.task('bundle', gulp.parallel('bundle-css', 'bundle-js'));

// Критические стили (инлайнятся в head)
gulp.task('critical-css', function () {
    return gulp.src(paths.css.critical)
        .pipe(concat('critical.min.css'))
        .pipe(cleanCSS({ compatibility: 'ie11', level: 2 }))
        .pipe(gulp.dest(paths.css.dest));
});

// Минификация (алиас для build)
gulp.task('minify', gulp.parallel('build'));

// Production сборка (с бандлами)
gulp.task('production', gulp.series(
    gulp.parallel('bundle-css', 'bundle-js', 'critical-css')
));

// Watch для разработки
gulp.task('watch', function () {
    gulp.watch('web/css/**/*.css', gulp.series('minify-css'));
    gulp.watch('web/js/**/*.js', gulp.series('minify-js'));
});

// Задача по умолчанию
gulp.task('default', gulp.series('build'));
