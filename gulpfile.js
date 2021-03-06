const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const gulp = require('gulp');
const mergeMediaQueries = require('gulp-merge-media-queries');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const sort = require('gulp-sort');
const uglify = require('gulp-uglify');
const wp_pot = require('gulp-wp-pot');

// Define the source paths for each file type.
const src = {
	bundle: [
		'./**/*',
		'!vendor',
		'!vendor/**/*',
		'!node_modules',
		'!node_modules/**/*',
		'!composer.json',
		'!composer.lock',
		'!package.json',
		'!package-lock.json',
		'!gulpfile.js',
		'!assets/src',
		'!assets/src/**/*',
		'!languages', // TODO: Need to copy if have files
		'!languages/**/*', // TODO: Need to copy if have files
		'!README.md',
		'!my-multi-author-plugin',
		'!my-multi-author-plugin/**/*'
	],
	js: ['assets/src/js/**/*'],
	php: ['**/*.php','!vendor/**','!node_modules/**','!my-multi-author-plugin/**'],
	sass: ['assets/src/scss/*.scss']
};

// Define the destination paths for each file type.
const dest = {
	bundle: './my-multi-author-plugin',
	js: './assets/js',
	sass: './assets/css',
	translations: 'languages/my-multi-author.pot'
};

// Make sure autocomplete files are copied.
gulp.task('autocomplete', function(done) {
	gulp.src('./node_modules/select2/dist/css/select2.min.css')
		.pipe(gulp.dest(dest.sass));
	gulp.src('./node_modules/select2/dist/js/select2.min.js')
		.pipe(gulp.dest(dest.js));
	return done();
});

// Take care of SASS.
gulp.task('sass', function(done) {
	return gulp.src(src.sass)
		.pipe(sass({
			outputStyle: 'expanded'
		}).on('error', sass.logError))
		.pipe(mergeMediaQueries())
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(cleanCSS({
			compatibility: 'ie8'
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.sass))
		.on('end',done);
});

// Minify the JS.
gulp.task('js', function(done) {
	return gulp.src(src.js)
		.pipe(uglify({
			mangle: false
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.js))
		.on('end',done);
});

// Create the .pot translation file.
gulp.task('translate', function(done) {
	return gulp.src(src.php)
		.pipe(sort())
		.pipe(wp_pot( {
			domain: 'my-multi-author',
			package: 'my-multi-author-plugin',
			bugReport: 'https://github.com/bamadesigner/my-multi-author-plugin/issues',
			lastTranslator: 'Rachel Cherry <bamadesigner@gmail.com>',
			headers: false
		}))
		.pipe(gulp.dest(dest.translations))
		.on('end',done);
});

// Compile all the things.
gulp.task('compile',gulp.series('sass','js'));

// I've got my eyes on you(r file changes).
gulp.task('watch', function(done) {
	gulp.watch(src.js, ['js']);
	gulp.watch(src.php,['translate']);
	gulp.watch(src.sass,['sass']);
	return done();
});

const bundlePlugin = (done) => {
	return gulp.src(src.bundle)
		.pipe(gulp.dest(dest.bundle))
		.on('end',done);
}

// Bundle into a folder for use.
gulp.task('bundle', gulp.series('autocomplete','compile','translate',bundlePlugin));

// Let's get this party started.
gulp.task('default', gulp.series('autocomplete','compile','translate'));
