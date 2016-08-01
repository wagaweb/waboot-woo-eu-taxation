var pkg = require('./package.json');

var gulp = require('gulp'),
    concat = require('gulp-concat'),
    rename = require("gulp-rename"),
    sourcemaps = require('gulp-sourcemaps'),
    jsmin = require('gulp-jsmin'),
    uglify = require('gulp-uglify'),
    sass = require('gulp-sass'),
    browserify = require('browserify'),
    source = require('vinyl-source-stream'), //https://www.npmjs.com/package/vinyl-source-stream
    buffer = require('vinyl-buffer'), //https://www.npmjs.com/package/vinyl-buffer
    babelify = require('babelify'),
    zip = require('gulp-zip'),
    bower = require('gulp-bower'),
    copy = require('gulp-copy'),
    csso = require('gulp-csso'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer'),
    cssnano = require('cssnano'),
    runSequence  = require('run-sequence'),
    wpPot = require('gulp-wp-pot'),
    sort = require('gulp-sort');

var plugin_slug = "wb-woo-fi";

var paths = {
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
        "!{builds,builds/**}",
        "!{node_modules,node_modules/**}",
        "!{bower_components,bower_components/**}"
    ]
};

/**
 * Compile .scss into <pluginslug>.min.css
 */
gulp.task('compile_sass',function(){
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
});

/**
 * Compile .css into <pluginslug>.min.css
 */
gulp.task('compile_css',function(){
    var processors = [
        autoprefixer({browsers: ['last 1 version']}),
        cssnano()
    ];
    return gulp.src(paths.maincss)
        .pipe(sourcemaps.init())
        .pipe(postcss(processors))
        .pipe(rename(plugin_slug+'.min.css'))
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest('./assets/dist/css'));
});

/**
 * Creates and minimize bundle.js into <pluginslug>.min.js
 */
gulp.task('compile_js', ['browserify'] ,function(){
    return gulp.src(paths.bundlejs)
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(rename(plugin_slug+'.min.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./assets/dist/js'));
});

/**
 * Browserify magic! Creates bundle.js
 */
gulp.task('browserify', function(){
    return browserify(paths.mainjs,{
            insertGlobals : true,
            debug: true
        })
        .transform("babelify", {presets: ["es2015"]}).bundle()
        .pipe(source('bundle.js'))
        .pipe(buffer()) //This might be not required, it works even if commented
        .pipe(gulp.dest('./assets/dist/js'));
});

/**
 * Creates the plugin package
 */
gulp.task('make-package', function(){
    return gulp.src(paths.build)
        .pipe(copy(paths.builddir+"/pkg/"+plugin_slug));
});

/**
 * Compress che package directory
 */
gulp.task('archive', function(){
    return gulp.src(paths.builddir+"/pkg/**")
        .pipe(zip(plugin_slug+'-'+pkg.version+'.zip'))
        .pipe(gulp.dest("./builds"));
});

/*
  * Make the pot file
 */
gulp.task('make-pot', function () {
    return gulp.src(['*.php', 'src/**/*.php'])
        .pipe(sort())
        .pipe(wpPot( {
            domain: "wb-woo-fiscalita-italiana",
            destFile: 'wb-woo-fiscalita-italiana.pot',
            team: 'Waga <info@waga.it>'
        } ))
        .pipe(gulp.dest('languages/'));
});

/**
 * Bower vendors Install
 */
gulp.task('bower-install',function(){
    return bower();
});

/**
 * Bower Update
 */
gulp.task('bower-update',function(){
    return bower({cmd: 'update'});
});

/**
 * Runs a build
 */
gulp.task('build', function(callback) {
    runSequence('bower-update', 'make-pot', ['compile_js', 'compile_css'], 'make-package', 'archive', callback);
});

/**
 * Rerun the task when a file changes
 */
gulp.task('watch', function() {
    gulp.watch(paths.mainjs, ['compile_js']);
    gulp.watch(paths.mainscss, ['compile_sass']);
});

/**
 * Default task
 */
gulp.task('default', function(callback){
    runSequence('bower-install', ['compile_js', 'compile_sass'], 'watch', callback);
});