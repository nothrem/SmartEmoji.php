# SmartEmoji
Client-side JS Emoji picker with server side emoji list and I18n.

## Installation into NPM
Run command:

```> npm install nothrem/SmartEmoji```

Installing the package via NPM will also automatically install required build tools and will recompile all JS and CSS files.

When you install the package into NPM, you may need to copy or link the `css`, `js` and `data` folders
into your `www` folder. 

## Installation as a Bower package
Run command:

```> bower install git://github.com/nothrem/SmartEmoji```

# Usaging SmartEmoji in HTML

# Standalone javascript

Include in HTML header or on the end of body:

```<script type="application/javascript" src="/vendor/SmartEmoji/js/index.js">```

_Please, replace the path to the file with the actual folder where you have installed the package._

# jQuery plugin
jQuery plugin is not available yet.

# Parsing CLDR data into JSON
This repository contain some precompiled emoji list based on latest version at the time or release and also languages
files for most use files in EU and America- If you need to update the emoji list and/or add more languages you must
update the Core CLDR package in the data folder and use the included parse script. 

## NPM parser
Parsing using only NPM and Javascript is not available yet.
Please install PHP 7.1 if you want to parse new emoji or add more languages.

##NPM and PHP parser
To update the existing list of emoji and language files, you can run NPM script from the root folder of this project.

### Linux & MacOS X
```> npm run-script php-parse```

This will parse the emoji and translations for predefined list of languages.

_PHP 7.1 or newer must be installed and configured in PATH._

### Windows
On Windows use script:

```> npm run-script win-parse```

# PHP parser and additional languages
If you want to add more languages not included in this repository, run this command from the root folder of this project:

## Linux and MacOS X
```> php php/parse.php xx_XX,yy_YY,zz_ZZ &> /dev/null```

## Windows
```> php php/parse.php xx_XX,yy_YY,zz_ZZ > NUL```

This will parse the data from the included CLDR package, update file `groups.json` and create files for languages
`xx_XX`, `yy_YY` and `zz_ZZ` (_please, replace these placeholders with relevant language codes_).

## Verbose parsing
For verbose output use:

```> php php/parse.php xx_XX,yy_YY,zz_ZZ```

To dump the verbose output into file (to check the emoji) use:

```> php php/parse.php xx_XX,yy_YY,zz_ZZ > parse_dump.txt```
