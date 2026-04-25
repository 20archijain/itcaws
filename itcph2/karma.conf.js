// Karma configuration file, see link for more information
// https://karma-runner.github.io/1.0/config/configuration-file.html

module.exports = function (config) {
  config.set({
    basePath: '',
    frameworks: ['jasmine', '@angular-devkit/build-angular'],
    plugins: [
      require('karma-jasmine'),
      require('karma-chrome-launcher'),
      require('karma-spec-reporter'),
      // require('karma-jasmine-html-reporter'),
      require('karma-coverage'),
      require('@angular-devkit/build-angular/plugins/karma')
    ],
    client: {
      jasmine: {},
      clearContext: false // leave Jasmine Spec Runner output visible in browser
    },
    // reporters: ['spec', 'kjhtml', 'coverage'],
    reporters: ['spec', 'coverage'],
    specReporter: {
      suppressSummary: false,
      suppressErrorSummary: true,
      suppressPassed: false,
      suppressFailed: false,
      suppressSkipped: true,
      showSpecTiming: true,
      failFast: false,
    },
    coverageReporter: {
      dir: require('path').join(__dirname, './coverage'),
      subdir: '.',
      reporters: [
        { type: 'html' },
        { type: 'text-summary' }
      ]
    },
    jasmineHtmlReporter: {
      suppressAll: true // removes the duplicated traces
    },
    port: 9876,
    colors: true,
    logLevel: config.LOG_INFO,
    autoWatch: false,
    // browsers: ['Chrome'],
    browsers: ['ChromeHeadless'],
    singleRun: true,
    restartOnFileChange: false
  });
};
