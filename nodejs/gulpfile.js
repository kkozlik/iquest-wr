var gulp        = require('gulp');
var browserSync = require('browser-sync').create();
var sass        = require('gulp-sass');
var sourcemaps  = require('gulp-sourcemaps');
var rename      = require("gulp-rename");
var del         = require('del');
var cleanCSS    = require('gulp-clean-css');

sass.compiler = require('node-sass');

const OUTPUT_CSS_DIR =  '../pages/styles';
const OUTPUT_FONT_DIR = '../pages/styles/fonts';
const OUTPUT_JS_DIR =   '../pages/js';

// Default environment
process.env.NODE_ENV = 'production';

gulp.task('env:dev', (done) =>
{
    process.env.NODE_ENV = 'development';
    done();
});

gulp.task('env:prod', (done) =>
{
    process.env.NODE_ENV = 'production';
    done();
});

function copy(src, dest){
    return gulp.src(src)
        .pipe(rename(function (path) {
            if (process.env.NODE_ENV == 'production'){
                // remove the '.min' from the filename
                if (path.extname == '.min.js') path.extname = '.js';
            }
        }, { multiExt: true }))
        .pipe(gulp.dest(dest));
}

gulp.task('clean', function() {
    return del([
        // sass
        OUTPUT_CSS_DIR+'/styles.css',
        OUTPUT_CSS_DIR+'/styles.css.map',

        // js
        OUTPUT_JS_DIR+'/bootstrap.min.js',
        OUTPUT_JS_DIR+'/jquery.min.js',
        OUTPUT_JS_DIR+'/popper.min.js',
        OUTPUT_JS_DIR+'/bootstrap.min.js.map',
        OUTPUT_JS_DIR+'/jquery.js',
        OUTPUT_JS_DIR+'/jquery.min.map',
        OUTPUT_JS_DIR+'/popper.min.js.map',
        OUTPUT_JS_DIR+'/bootstrap-select.min.js',
        OUTPUT_JS_DIR+'/bootstrap-select.min.js.map',
        OUTPUT_JS_DIR+'/tempusdominus-bootstrap-4.js',
        OUTPUT_JS_DIR+'/moment-with-locales.js',
        OUTPUT_JS_DIR+'/leaflet.js',
        OUTPUT_JS_DIR+'/leaflet.js.map',

        // images
        OUTPUT_CSS_DIR+'/images/*',

        // fonts
        OUTPUT_FONT_DIR+'/*'
    ],
    { force: true });
});

// Compile sass into CSS & auto-inject into browsers
gulp.task('sass', function() {
    let stream = gulp.src(['src/scss/styles.scss']);

    if (process.env.NODE_ENV == 'development') stream=stream.pipe(sourcemaps.init({loadMaps: true}));
    stream=stream.pipe(sass().on('error', sass.logError));
    stream=stream.pipe(cleanCSS({level: {1: {specialComments: false}}}));
    if (process.env.NODE_ENV == 'development') stream=stream.pipe(sourcemaps.write('.'));

    return stream.pipe(gulp.dest(OUTPUT_CSS_DIR))
                 .pipe(browserSync.stream());
});

// Move the javascript files with map files into OUTPUT_JS_DIR folder
gulp.task('js', function() {
    let src = [
            'node_modules/bootstrap/dist/js/bootstrap.min.js',
            'node_modules/jquery/dist/jquery.min.js',
            'node_modules/popper.js/dist/umd/popper.min.js',
            'node_modules/bootstrap-select/dist/js/bootstrap-select.min.js',
            'node_modules/tempusdominus-bootstrap-4/build/js/tempusdominus-bootstrap-4.js',
            'node_modules/moment/min/moment-with-locales.js',
            'node_modules/leaflet/dist/leaflet.js'
    ];

    if (process.env.NODE_ENV == 'development'){
        src.push('node_modules/bootstrap/dist/js/bootstrap.min.js.map');
        src.push('node_modules/jquery/dist/jquery.js');
        src.push('node_modules/jquery/dist/jquery.min.map');
        src.push('node_modules/popper.js/dist/umd/popper.min.js.map');
        src.push('node_modules/bootstrap-select/dist/js/bootstrap-select.min.js.map');
        src.push('node_modules/leaflet/dist/leaflet.js.map');
    }

    return gulp.src(src)
        .pipe(gulp.dest(OUTPUT_JS_DIR))
        .pipe(browserSync.stream());
});

gulp.task('images', function() {
    return copy(['src/images/*',
                 'node_modules/leaflet/dist/images/*'],
                 OUTPUT_CSS_DIR+'/images')
           .pipe(browserSync.stream());
});

gulp.task('fonts', function() {
    return gulp.src(['src/fonts/*', 'node_modules/@fortawesome/fontawesome-free/webfonts/*'])
        .pipe(gulp.dest(OUTPUT_FONT_DIR))
        .pipe(browserSync.stream());
});

// Static Server + watching scss/html files
gulp.task('serve', function() {

    browserSync.init({
        proxy     : process.env.WR_URL || 'https://localhost:443',
        https     : true,
        ghostMode : false,
        open      : false
    });

    gulp.watch(['src/scss/*.scss'], gulp.series('sass'));
    gulp.watch(['src/fonts/*'], gulp.series('fonts'));
    gulp.watch(['src/images/*'], gulp.series('images'));
    // gulp.watch("src/*.html").on('change', browserSync.reload);
});

gulp.task('prod', gulp.series(
    'env:prod',
    'clean',
    'sass',
    'js',
    'images',
    'fonts'
));

gulp.task('dev', gulp.series(
    'env:dev',
    'clean',
    'sass',
    'js',
    'images',
    'fonts'
));

gulp.task('live', gulp.series(
    'dev',
    'serve'
));

gulp.task('default', gulp.series('prod'));

