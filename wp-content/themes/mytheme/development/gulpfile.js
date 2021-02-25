const
    {series, parallel, src, dest, watch} = require('gulp'),
    sass = require('gulp-sass'),
    browserSync = require('browser-sync'),
    cleanCss = require('gulp-clean-css'),
    autoprefixer = require('gulp-autoprefixer'),
    changed = require('gulp-changed'),
    gcmq = require('gulp-group-css-media-queries'),
    cssnano = require('gulp-cssnano'),
    env = require('gulp-env'),
    notify = require('gulp-notify'),
    stylelint = require('gulp-stylelint');

// load variables
env(".env");

const
    USE_LINTER = (process.env.USE_LINTER === 'true'),
    SITE_HOST = process.env.SITE_HOST,
    SITE_URL = process.env.SITE_URL,
    SRC = process.env.SRC,
    DEST = process.env.DEST;

function getError(error) {
    console.log(error.toString());
    this.emit('end');
}

function scss() {
    let stream = src(SRC);

    if (USE_LINTER) {
        stream = stream
            .pipe(stylelint({
                failAfterError: true,
                emitErrors: true,
                debug: true,
                "plugins": [
                    "stylelint-statement-max-nesting-depth"
                ],
                reporters: [
                    {
                        formatter: 'string',
                        console: true
                    }
                ]
            }).on('error', notify.onError({
                wait: true,
                message: 'There is a CSS error, please look the console for details'
            })))
    }

    stream = stream.pipe(changed(DEST))
        .pipe(sass().on('error', getError))
        .pipe(autoprefixer(['last 5 versions'], {cascade: true}))
        .pipe(gcmq())
        .pipe(cssnano())
        .pipe(cleanCss())
        .pipe(dest(DEST));

    return stream;
}

function scsslive() {
    return scss().pipe(browserSync.stream());
}

function watcher() {
    return watch(SRC, series(scsslive));
}

function serve(done) {
    browserSync({
        proxy: SITE_URL,
        host: SITE_HOST,
        open: 'external',
        notify: false
    });
    done();
}

exports.scss = scss;
exports.watch = series(scsslive, parallel(serve, watcher));