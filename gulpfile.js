// Require all the things (that we need).
var autoprefixer = require('gulp-autoprefixer');
var gulp = require('gulp');
var minify = require('gulp-minify');
var phpcs = require('gulp-phpcs');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var watch = require('gulp-watch');

// Define the source paths for each file type.
var src = {
	scss: ['assets/scss/*.scss'],
	js: ['assets/js/*.js','!assets/js/*.min.js'],
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Define the destination paths for each file type.
var dest = {
	scss: './assets/css',
	js: './assets/js'
};

// Sass is fun.
gulp.task('sass', function() {
	return gulp.src(src.scss)
		.pipe(sass({
			outputStyle: 'compressed'
		})
		.on('error', sass.logError))
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest(dest.scss));
});

// Minify the JS.
gulp.task('js',function() {
	gulp.src(src.js)
		.pipe(minify({
			mangle: false,
			ext:{
				min:'.min.js'
			}
		}))
		.pipe(gulp.dest(dest.js))
});

// Sniff our code.
gulp.task('php',function () {
	return gulp.src(src.php)
		.pipe(phpcs({
			bin: './vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		// Log all problems that was found
		.pipe(phpcs.reporter('log'));
});

// Make sure autocomplete files are copied.
gulp.task('autocomplete', function () {
	gulp.src('./node_modules/select2/dist/css/select2.min.css')
		.pipe(gulp.dest(dest.scss));
	gulp.src('./node_modules/select2/dist/js/select2.min.js')
			.pipe(gulp.dest(dest.js));
});

// Let's get this party started.
gulp.task('default',['autocomplete','compile','test']);

// Compile all the things.
gulp.task('compile',['sass','js']);

// Test all the things.
gulp.task('test',['php']);

// I've got my eyes on you(r file changes).
gulp.task('watch',function() {
	gulp.watch(src.scss,['sass']);
	gulp.watch(src.js,['js']);
	gulp.watch(src.php,['php']);
});