let pkg = require('./package.json'),
    plugin_slug = "wb-woo-eut",
    paths = {
        builddir: "./builds",
        scripts: ['./assets/src/js/**/*.js'],
        mainjs: ['./assets/src/js/main.js'],
        bundlejs: ['./assets/dist/js/bundle.js'],
        mainscss: './assets/src/scss/main.scss',
        maincss: './assets/src/css/main.css',
        build: [
            "**/*",
            "!.*" ,
            "!Gruntfile.js",
            "!gulpfile.js",
            "!package.json",
            "!bower.json",
            "!composer.json",
            "!composer.lock",
            "!{builds,builds/**}",
            "!{node_modules,node_modules/**}",
            "!{bower_components,bower_components/**}",
            "!{vendor,vendor/**}",
        ]
    },
    node_env = 'development';

let gulp = require('gulp'),
    rename = require("gulp-rename"),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify'),
    sass = require('gulp-sass'),
    browserify = require('browserify'),
    source = require('vinyl-source-stream'), //https://www.npmjs.com/package/vinyl-source-stream
    buffer = require('vinyl-buffer'), //https://www.npmjs.com/package/vinyl-buffer
    babelify = require('babelify'),
    zip = require('gulp-zip'),
    copy = require('gulp-copy'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer'),
    cssnano = require('cssnano'),
    wpPot = require('gulp-wp-pot'),
    sort = require('gulp-sort');

/**
 * Browserify magic! Creates bundle.js
 */
function compileJsBundle(){
    return browserify(paths.mainjs,{
        insertGlobals : true,
        debug: true
    })
        .transform("babelify", {presets: ["@babel/preset-env"]})
        .bundle()
        .pipe(source('bundle.js'))
        .pipe(buffer()) //This might be not required, it works even if commented
        .pipe(gulp.dest('./assets/dist/js'));
}

/**
 * Creates and minimize bundle.js into <pluginslug>.min.js
 */
function minifyJs() {
    return gulp.src(paths.bundlejs)
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(rename(plugin_slug+'.min.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./assets/dist/js'));
}

/**
 * Compile .scss into <pluginslug>.min.css
 */
function compileCss(){
    var processors = [
        autoprefixer({browsers: ['last 1 version']}),
        cssnano()
    ];
    return gulp.src(paths.mainscss)
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss(processors))
        .pipe(rename(plugin_slug+'.min.css'))
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest('./assets/dist/css'));
}

/**
 * Creates the plugin package
 */
function makePackage(){
    return gulp.src(paths.build)
        .pipe(copy(paths.builddir+"/pkg/"+"waboot-woo-eu-taxation"));
}

/**
 * Compress che package directory
 */
function archive(){
    return gulp.src(paths.builddir+"/pkg/**")
        .pipe(zip("waboot-woo-eu-taxation"+'-'+pkg.version+'.zip'))
        .pipe(gulp.dest("./builds"));
}

/*
  * Make the pot file
 */
function makePot() {
    return gulp.src(['*.php', 'src/**/*.php'])
        .pipe(sort())
        .pipe(wpPot( {
            domain: "waboot-woo-eu-taxation",
            destFile: 'waboot-woo-eu-taxation.pot',
            team: 'Waga <info@waga.it>'
        } ))
        .pipe(gulp.dest('languages/'));
}

/**
 * Rerun the task when a file changes
 */
function watch() {
    gulp.watch(paths.scripts, compileJs);
    gulp.watch(paths.mainscss, compileCss);
}

let compileJs = gulp.series(compileJsBundle,minifyJs);
let build = gulp.series(makePot,compileJsBundle,minifyJs,compileCss,makePackage,archive);

exports.compile_css = compileCss;
exports.compile_js = compileJs;
exports.watch = watch;
exports.build = build;
exports.default = watch;