<?php
/**
* @author     Laurent Jouanneau
* @copyright  2014-2018 Laurent Jouanneau
* @link       http://jelix.org
* @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/
namespace Jelix\Core\Infos;

abstract class InfosAbstract {

    /** @var string the path to the module/app information file */
    protected $path = '';
    protected $isXml = false;
    protected $_exists = false;

    /** @var string unique id (e.g. 'name@company') */
    public $id = '';

    /** @var string the name of the module, used as identifier in jelix selectors or other part of the code */
    public $name = '';

    /** @var string the birth date of the module/app. optional */
    public $createDate = '';

    /** @var string version of the module/app. required for modules */
    public $version = '';
    /** @var string the release date of the module/app. required for modules */
    public $versionDate = '';
    
    public $versionStability = '';

    /**
     * @var string[]   key is the locale code
     */
    public $label = array();

    /**
     * @var string[]   key is the locale code
     */
    public $description = array();

    /**
     * @var Author[]
     */
    public $author = array();

    public $notes = '';
    public $homepageURL = '';
    public $updateURL = '';
    public $license = '';
    public $licenseURL = '';
    public $copyright = '';

    function __construct($filePath, $isXml)
    {
        $this->path = $filePath;
        $this->isXml = $isXml;
        $this->_exists = file_exists($filePath);
    }

    /**
     * @return string the path of the component, with trailing slash
     */
    public function getFilePath() {
        return $this->path;
    }

    public function getItemPath() {
        return dirname($this->path).'/';
    }

    /**
     * @return bool
     */
    public function isXmlFile() {
        return $this->isXml;
    }

    /**
     * @return bool
     */
    public function exists() {
        return $this->_exists;
    }

    public function getLabel($locale = '') {
        $locale = $this->getLocale($locale);
        if (isset($this->labels[$locale])) {
            return $this->label[$locale];
        }
        reset($this->label);
        return current($this->label);
    }

    public function getDescription($locale = '') {
        $locale = $this->getLocale($locale);
        if (isset($this->description[$locale])) {
            return $this->description[$locale];
        }
        reset($this->description);
        return current($this->description);
    }

    protected function getLocale($locale) {
        if ($locale) {
            return substr($locale, 0, 2);
        }
        $config = \jApp::config();
        if ($config) {
            return $config->locale;
        }
        return 'en';
    }

    /**
     * save the informations into the original file
     */
    abstract public function save();

}