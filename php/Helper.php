<?php


namespace xml;

const cp = \emoji\cp; //PHP code for UTF-8 encoding (for syntactic sugar)

class Helper {

    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'core';
    protected const XML_EXT = '.xml';
    protected const LANG_SEPARATOR = ',';

    protected const LATIN_AMERICA_ES = ['es_AR', 'es_BO', 'es_CL', 'es_CO', 'es_EC', 'es_PY', 'es_PE', 'es_UY', 'es_VE']; //countries that use Latin-American Spanish

    protected $path;

    /**
     * Helper constructor.
     * @param string|null $path (optional; default: self::DEFAULT_DATA_DIR) Path where CLDR data are stored.
     */
    public function __construct(string $path = null) {
        $this->path = $path ?? self::DEFAULT_DATA_DIR;
    }

    /**
     * Get filename of an XML file
     *
     * @param string $name (required) Name of annotation (language code, 'root', etc.)
     * @param string $folder (required) Folder from which to load the file
     * @param string $path (optional, default: common\properties) Change if you want to load file from a different folder.
     * @return string
     */
    protected function getFileName(string $name, string $folder, string $path = 'common') : string {
        $file = realpath($this->path . DIRECTORY_SEPARATOR . ($path ? $path . DIRECTORY_SEPARATOR : '') . $folder . DIRECTORY_SEPARATOR . $name . self::XML_EXT);

        if (!file_exists($file)) {
            return false; //file is optional, it's OK if it does not exist
        }

        return $file;
    }

    /**
     * @param string $langs
     * @param array $folders
     * @return array
     */
    protected function getLanguageList(string $langs, array $folders) {
        $langs = explode(self::LANG_SEPARATOR, $langs);
        $files = [];
        foreach ($langs as $lang) {
            $files[$lang] = $this->getLanguageChain($lang, $folders);
        }

        return $files;
    }

    protected function getLanguageChain(string $lang, array $folders, bool $useBaseEnglish = true) {
        $short = substr($lang, 0, 2); //parse basic language code from regionalized code

        $files = []; //list of files (will be reverse order and with FALSE for non-existing files)

        //Get language files for given language and country (if specified)
        if ($short !== $lang) {
            foreach ($folders as $folder) {
                $files[] = $this->getFileName($lang, $folder);
            }
        }

        //For Latin-American languages there is special file es_419 (419 is code for South America)
        if (in_array($lang, self::LATIN_AMERICA_ES, true)) {
            foreach ($folders as $folder) {
                $files[] = $this->getFileName('es_419', $folder);
            }
        }

        //Get language files for general language (except English which is loaded below)
        if ('en' !== $short) {
            foreach ($folders as $folder) {
                $files[] = $this->getFileName($short, $folder);
            }
        }

        //Get english language files which should be used as a base for all untranslated emoji
        if ($useBaseEnglish) {
            foreach ($folders as $folder) {
                $files[] = $this->getFileName('en_001', $folder);
            }
            foreach ($folders as $folder) {
                $files[] = $this->getFileName('en', $folder);
            }
        }

        //also try to load root files (but those are usually empty)
        foreach ($folders as $folder) {
            $files[] = $this->getFileName('root', $folder);
        }

        $files = array_reverse($files); //get correct order of files
        $files = array_filter($files); //remove all false elements (i.e. non-existing files)

        return $files;
    }

}
