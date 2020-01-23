module.exports = function (grunt) {
    var filterFile;

    grunt.initConfig({

        //compile LESS files with required options
        less: {
            options: {
                compress:          true,
                optimization:      1,
                sourceMap:         true,
                sourceMapRootpath: '../../../',
                sourceMapBasepath: 'www', //remove this from sourceMappingURL link in CSS file (should be replaced for each subtask to make relative paths)
                outputSourceFiles: true,
	            verbose: grunt.option('verbose'),
                plugins:           [
                    new (require('less-plugin-clean-css'))({
                        advanced: true, compatibility: 'ie8'
                    })
                ]
            },
            style: {
                options: '<% less.options.sourceMapBasepath = "css"; less.options; %>',
                files: [{
                    expand: true,
                    cwd:    'less',
                    src:    [
                        '*.less'
                    ],
                    dest:   'css',
                    ext:    '.min.css',
                    extDot: 'last',
                    filter: function(file) { return filterFile(file); }
                }]
            }
        },

        //minify JS files
        uglify: { //task is loaded from grunt-contrib-uglify-es which uses uglify-es instead of uglify-js -> supports ES6+ features
            options: {
                sourceMap: true, //create sourcemap for debugging
                compress:  {      //remove redundant spaces, etc.
                    drop_console:  true,  //remove all console.* calls to reduce size
                    drop_debugger: true, //remove all debugger calls to reduce size
                    dead_code:     true,     //remove unreachable code (e.g. code after return or inside if(false){...})
                    global_defs:   {
                        'window.console': false, //remove conditions if(window.console) { ... }
                        'self.console': false,   //remove console conditions from Workers
                        'this.console': false    //remove console conditions from files used both in modules and Workera
                    },

                    //note: following properties are mostly TRUE by default
                    // and are listed just for the comment and option to disable them if there is a problem
                    properties:   true,    //rewrite array properties (a['b']) to objects (a.b)
                    comparisons:  true,  //optimize conditions (e.g. 'a >= b' to 'a<b')
                    booleans:     true,      //remove operator !! where not needed
                    if_return:    true,     //remove 'else' part of IF after return or continue
                    join_vars:    true,     //remove duplicate 'var' from consecutive variable definitions
                    reduce_vars:  true,   //replace constant variables with actual value (magic numbers)
                    unsafe_proto: true,  //use [] and {} instead of Array and Object prototype

                    passes: 4            //repeat the compression of already optimized code to remove glitches created by consecutive rules (e.g. remove redundant parenthesis)
                },
                mangle:    {        //rename defined variables to make them shorter
                    properties: false, //do NOT mangle object properties (because they may be used in other files)
                    reserved:   [  //list of variables that must not be renamed (because they are used elsewhere)
                    ]
                },
                beautify:  {
                    beautify:     false,    //do NOT beautify, but apply the other options to the minified code
                    max_line_len: 160,  //limit line length of the minified file (makes it better comparable in versioning)
                    width:        160          //alternative name for max_line_len in some versions

                },
                output:    {
                    comments:    'some', //keep comments starting with @preserve or @license); note that such comments inside dead code are still removed!
                    quote_style: 0    //optimize quotes to prevent escaping where possible
                },
                ie8:       true,       //preserve compatibility with old IE (makes files slightly larger)
                report:    'gzip'   //calculate how big the file will be after gzipping; works only with '--verbose' parameter
            },
            code:     {
                files: [
                    {
                        expand: true,
                        cwd:    'js/',
                        src:    [
                            '**/*.js',      //compile all JS files...
                            '!**/*.min.js',  //...except for already minified ones
                        ],
                        dest:   'js/',
                        ext:    '.min.js',
                        extDot: 'last',
                        filter: function(file) { return filterFile(file); }
                    }
                ]
            }
        },

        //Replace texts in files
        replace: {
            minify: {
                options: {
                    patterns: [
                        {
                            match: /(['"])(.*?)(\.m?js)\1/gi, //find imports of non-minified modules
                            replacement: '$1$2.min$3$1'         //and link to minified files
                        }
                    ]
                },
                files: [
                    {
                        expand: true,
                        cwd:    'js',
                        src:    [
                            '**/*.min.js'
                        ],
                        dest:   'js',
                        filter: function(file) { return filterFile(file); }
                    }
                ]
            }
        },
    });

    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    grunt.loadNpmTasks('grunt-replace');

    grunt.registerTask('less:compile', [
        'less:style'
    ]);

    grunt.registerTask('js:compile', [
        'uglify:code',
        'replace:minify'
    ]);

    grunt.registerTask('js', [
        'js:compile',
    ]);

    grunt.registerTask('compile', [
        'less:compile',
        'js:compile'
    ]);

    /**
     * Limit task only to a file matching name of command parameter --file
     *
     * @param file
     * @returns {boolean} True for the file that match
     */
    filterFile = function(file) {
        if (typeof grunt.option('file') === 'undefined') {
            return true;
        }
        let match = require('minimatch');
        let mask = grunt.option('file').replace('\\', '/');
        file = file.replace('\\', '/');
        return (match(file, mask, {matchBase: true}));
    };

    if (grunt.option('file') || '' === grunt.option('file')) {
        if (true === grunt.option('file') || '' === grunt.option('file')) {
            let path = require('path');
            grunt.log.writeln('The File parameter will limit some tasks only to specified file or files.');
            grunt.log.writeln('You can use GLOB masks to match the file.');
            grunt.log.writeln('Examples:');
            grunt.log.writeln('    --file=logo*.jpg           //will match any logo file in any folder');
            grunt.log.writeln('    --file=img/logo*.jpg       //will match only logo files inside IMG folder');
            grunt.log.writeln('    --file=img/**/logo*.jpg    //will match logo files inside IMG folder and its subfolders');
        }
        else {
            grunt.log.writeln('Looking for file "' + grunt.option('file') + '".');
        }
    }
};
