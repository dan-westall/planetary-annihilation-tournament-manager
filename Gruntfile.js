'use strict';
module.exports = function (grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        // package options
        less: {
            mini: {
                options: {
                    cleancss: true, // minify
                    report: 'min' // minification results
                },
                expand: true, // set to true to enable options following options:
                cwd: "assets/less/", // all sources relative to this path
                src: "*.less", // source folder patterns to match, relative to cwd
                dest: "tmp/css/", // destination folder path prefix
                ext: ".css", // replace any existing extension with this value in dest folder
                flatten: true  // flatten folder structure to single level
            }
        },
        express: {
            server: {
                options: {
                    port: 3000,
                    hostname: 'localhost',
                    bases: 'public'
                }
            }
        },
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            },
            all: [
                'Gruntfile.js',
                'js/*.js'
            ]
        },
        concat: {
            angular: {
                src: [
                    'public/assets/js/signup.js',
                ],
                dest: 'tmp/patm.signup.js'
            },
        },
        uglify: {
            options: {
                mangle: false,
                beautify: false,
                sourceMap: false
            },
            build: {
                files: {
                    'public/assets/js/patm.signup.min.js' : 'tmp/patm.signup.js'
                }
            }
        },
        watch: {
            markup: {
                files: ['public/assets/js/*.js'],
                options: {
                    livereload: true,
                }
            },
            options: {
                livereload: true,
            },
            js: {
                files: [
                    'public/assets/js/patm.signup.js'
                ],
                tasks: ['concat',  'uglify:build'],
                options: {
                    livereload: true,
                    atBegin: true
                }
            }
        }
    });

    // Load tasks
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-concat-css');
    grunt.loadNpmTasks('grunt-express');

    // Register default tasks
    grunt.registerTask('default', [
        'express:server',
        'watch'
    ]);


};