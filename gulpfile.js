const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const gulp = require('gulp');
const mergeMediaQueries = require('gulp-merge-media-queries');
const notify = require('gulp-notify');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const shell = require('gulp-shell');
const sort = require('gulp-sort');
const uglify = require('gulp-uglify');
const wp_pot = require('gulp-wp-pot');

// Define the source paths for each file type.
const src = {
	js: ['assets/js/my-multi-authors-admin.js'],
	php: ['**/*.php','!vendor/**','!node_modules/**'],
	sass: ['assets/scss/*.scss']
};

// Define the destination paths for each file type.
const dest = {
	js: './assets/js',
	sass: './assets/css',
	translations: 'languages/my-multi-author.pot'
};

// Take care of SASS.
gulp.task('sass', function() {
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
		.pipe(notify('My Multi Author SASS compiled'));
});

// Make sure autocomplete files are copied.
gulp.task('autocomplete', function () {
	gulp.src('./node_modules/select2/dist/css/select2.min.css')
		.pipe(gulp.dest(dest.sass));
	gulp.src('./node_modules/select2/dist/js/select2.min.js')
		.pipe(gulp.dest(dest.js));
});

// Minify the JS.
gulp.task('js', function() {
	gulp.src(src.js)
		.pipe(uglify({
			mangle: false
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.js))
		.pipe(notify('My Multi Author JS compiled'));
});

// "Sniff" our PHP.
gulp.task('php', function() {
	// TODO: Clean up. Want to run command and show notify for sniff errors.
	return gulp.src('my-multi-author-plugin.php', {read: false})
		.pipe(shell(['composer sniff'], {
			ignoreErrors: true,
			verbose: false
		}))
		.pipe(notify('My Multi Author PHP sniffed'), {
			onLast: true,
			emitError: true
		});
});

// Create the .pot translation file.
gulp.task('translate', function () {
	gulp.src('**/*.php')
		.pipe(sort())
		.pipe(wp_pot( {
			domain: 'my-multi-author',
			package: 'my-multi-author-plugin',
			bugReport: 'https://github.com/bamadesigner/my-multi-author-plugin/issues',
			lastTranslator: 'Rachel Cherry <bamadesigner@gmail.com>',
			headers: false
		} ))
		.pipe(gulp.dest(dest.translations))
		.pipe(notify('My Multi Author translated'));
});

// Test our files.
gulp.task('test',['php']);

// Compile all the things.
gulp.task('compile',['sass','js']);

// I've got my eyes on you(r file changes).
gulp.task('watch', function() {
	gulp.watch(src.js, ['js']);
	gulp.watch(src.php,['php','translate']);
	gulp.watch(src.sass,['sass']);
});

// Let's get this party started.
gulp.task('default', ['autocomplete','compile','translate','test','watch']);
