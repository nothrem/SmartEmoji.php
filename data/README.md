# JSON Data
Data stored in JSON files are used by the client-side script to show the list of emoji, display their translated names
and search and filter them by their (internationalised) keywords.

JSON files are generated from the Unicode CLDR XML files.

## Groups
File named `groups.json` contains list of groups in order in which they should be sorted and the list of emoji belonging
into each group.

Object `groups` should contain ordered list of objects representing groups. Each group should define:
* `id` defining string or number under which its translation can be found in `emoji.xx.json` in `groups` object,
* `list` defining ordered array of strings containing an emoji (one string for each emoji) or objects representing groups.
```
//groups.json example
{
	"groups": [
		{
			"id": "smileys_people",
			"list": [
				{
					"id": "face-positive",
					"list": [
						"☺",
						"\uD83D\uDE00",
						"\uD83D\uDE01",
						"\uD83D\uDE02",
						"\uD83D\uDE03",
						"\uD83D\uDE04",
						"\uD83D\uDE05",
						"\uD83D\uDE06"
					]
				}
			]
		}
	]
}
```

## Emoji
Files named `emoji.xx.json` contain list of emoji characters and their names and keywords in given language.
Also contains list of groups and their translations in given language.

Each `emoji.xx.json` file should define objects `emoji` and `groups`.

Object `emoji` should have keys defined in `groups.json` file in `list` properties as strings. Each key should contain
object with properties `name` defining translated name of the emoji and `keywords` with translated list of words that
match the emoji when searched. When only `keywords` are known for an emoji (i.e. there is no `name` or other properties),
instead of an object the key can contain string with the keywords.

Emoji with empty key (`""`) can define translated `name` for emoji without own name.

Object `groups` should have keys defined in `groups.json` file in `id` properties. Each key should contain an object with
property `name` specifying translated name of that group. When only a name is known (i.e. there are no other properties),
instead of an object the key can contain a string with the translation.

```
//emoji.en.json example
{
	"emoji": {
		"": {
			"name": "Unknown"
		},
		"☺": {
			"name": "Smiling face",
			"keywords": " face | outlined | relaxed | smile | smiling face "
		},
		"😀": {
			"name": "Grinning face",
			"keywords": "face | grin | grinning face"
		}
	},
	"groups": {
		"smileys_people": {
			"name": "Smileys & People"
		}
	}
}
```

## TODO
_How to define variants (e.g. gender or skin-color) of each emoji to create filter?_

# Core Data
Core data are from _Unicode Common Locale Data Repository_.

_Data not related to emoji has been removed from this repository._

The Core package of CLDR contains:
A) list of all emoji characters defined by the UNICODE organization, their names, categories and keywords and 
B) translations for all the texts collected from various Organizations (goverments, companies and other national sources).

Find the latest version on http://cldr.unicode.org/.

Currently included version is http://unicode.org/Public/cldr/36/core.zip

These data are not required to run the SmartEmoji, they are only used to prepare the JSON files used by it.

# CLDR help
## Annotations
Contains translated names and keywords of all basic emoji symbols.

Attribute `cp` of each annotation define the emoji for the record. Annotation with attribute `type=tts` defines the
human-readable name of the emoji. The annotation without this attribute defines a list of keywords that describe
the emoji.

## AnnotationsDerived
Contains translated names and keywords of all emoji that serve as variants of the basic set. Derived emoji include
emoji with different skin color or various genders and their combination (e.g. emoji "_light-skin toned woman kissing
dark-skin toned man_").

Derived emoji consist of a set of basic emoji and each font that support emoji must know how to compile the final image.
For example the above emoji consists of 6 basic emoji: _woman, light-skin, heart, mouth, man, dark-skin_.

## Properties - Labels
File `labels.txt` contains lists of emoji sorted by their categories (e.g. "_Smileys - Positive faces_").

These lists can be used to generate the Emoji picker with emoji ordered in their relevant order.

Each list (row) contains list of emoji in square brackets (array). Each UTF-8 character in the list is one emoji.
A dash character (`-`) means that all UTF-8 characters between the left and right character belong into this list.
UTF-8 characters in curly brackets (`{}`) define derived emoji and must be considered as one emoji.

Category (label) and sub-category (second-level label) of the list is separated by a semi-colon (`;`) and 
optional white-spaces.

Category _Smileys & People - skin-tone_ lists variants for derived emoji and should be used for variant selection.

Translations for Categories can be found in `main` XMLs under `characterLabels`. To get the label type a category its
name must be convert: make all letters lower-case, remove all white-spaces and replace "`&`" with "`_`".
e.g. translation for group "_Smileys & People_" can be found in `characterLabel` with `type=smileys_people`.

## Collations
Collations define how UNICODE characters should be sorted in general (file `root.xml`) and in each language (nationalized
XMLs). Specifically for emoji the collation of `type=emoji` defines in which order should be the emoji sorted.

Each Collation node contains CDATA with sorting rule definitions:
* It does not matter if a rule is defined on separate row or in a single row. Characters are always ordered left-to-right
  regardless of line separators.
* `&` before a character defines start of sorting rule meaning all following characters should be sorted after this one.
   Each character can be listed multiple times with `&` which means the following rules should be added into the order
   list.
   e.g. "`& a`" means the letter `A` is to be the first in order and all other should follow it.
* `<` before a character means that this character should be sorted after the previous one.
   Multiple `<` means that the character should be sorted before other characters with less `<` in their rule.
   e.g. "`& a < b & a << ä & a <<< A`" defines sorting order "`a A ä b`" (i.e. `b` follows `a` and all its variants).
* `=` means that these two characters are equal and they can be sorted either way.
   e.g. "`& v = w`" means the letters `V` and `W` are equal and can be ordered either `vw` or `wv`.
* List of characters after any above rule definer means that they must be considered and sorted as one character.
   e.g. "`& h < ch`" means that `ch` is considered single character that should be sorted after `h`.
        "`& s < ss = ß`" means that `ss` is equal to `ß` and should be sorted after the letter `s`.
* `*` after any above rule definer means that each character in the list is to be considered a separate character
  sorted in given order with the same importance.
   e.g. "`& a <<\* äáâ`" equals to "`& a << ä << á << â`" and means that `ä`, `á` and `â` must be ordered after `a`.
* When a character that is already in the list is listed again after `<` or `=` it means it must be moved in the list.

# Variantion selector
Emoji may contain hidden character `U+FE0F`. This character means that the previous character is normally not considered
emoji but here should be considered a part of the previous emoji (or create an emoji with the preceding character)
for which is defines a variant.
e.g. square symbol is not part of emoji list, however when printed after a number and followed by `U+FE0F` it should
be displayed as "_a number in square_" emoji.
