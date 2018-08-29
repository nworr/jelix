<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2009-2018 Laurent Jouanneau
 * @link        http://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Installer;

use Jelix\Routing\UrlMapping\XmlEntryPoint;
use Jelix\IniFile\IniModifier;

/**
 * container for entry points properties, for installers
 *
 * @since 1.7
 */
class EntryPoint
{
    /**
     * @var \StdClass   configuration parameters. compiled content of config files
     *  result of the merge of entry point config, localconfig.ini.php,
     *  mainconfig.ini.php and defaultconfig.ini.php
     */
    protected $config;

    /**
     * @var string the filename of the configuration file dedicated to the entry point
     *       ex: <apppath>/app/config/index/config.ini.php
     */
    protected $configFileName;

    /**
     * all original configuration files combined
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $appConfigIni;

    /**
     * all local configuration files combined with original configuration file
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $localConfigIni;

    /**
     * the live configuration file combined with all other configuration files
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $liveConfigIni;


    /**
     * @var boolean true if the script corresponding to the configuration
     *                is a script for CLI
     */
    protected $_isCliScript;

    /**
     * @var string the url path of the entry point
     */
    protected $scriptName;

    /**
     * @var string the filename of the entry point
     */
    protected $file;

    /**
     * @var string the type of entry point
     */
    protected $type;

    /**
     * @var XmlEntryPoint
     */
    protected $urlMap;

    /**
     * @var GlobalSetup
     */
    protected $globalSetup;

    /**
     * @var \jInstallerEntryPoint
     */
    public $legacyInstallerEntryPoint = null;

    /**
     * @param GlobalSetup $globalSetup
     * @param string $configFile the path of the configuration file, relative
     *                           to the app/config directory
     * @param string $file the filename of the entry point
     * @param string $type type of the entry point ('classic', 'cli', 'xmlrpc'....)
     */
    function __construct(GlobalSetup $globalSetup,
                         $configFile, $file, $type)
    {
        $this->type = $type;
        $this->_isCliScript = ($type == 'cmdline');
        $this->configFileName = $configFile;
        $this->scriptName = ($this->_isCliScript ? $file : '/' . $file);
        $this->file = $file;
        $this->globalSetup = $globalSetup;

        $appConfigPath = \jApp::appConfigPath($configFile);
        if (!file_exists($appConfigPath)) {
            \jFile::createDir(dirname($appConfigPath));
            file_put_contents($appConfigPath, ';<' . '?php die(\'\');?' . '>');
        }

        $this->appConfigIni = clone $globalSetup->getConfigIni();
        $this->appConfigIni['entrypoint'] = new IniModifier($appConfigPath);

        $varConfigPath = \jApp::varConfigPath($configFile);
        $localEpConfigIni = new IniModifier($varConfigPath, ';<' . '?php die(\'\');?' . '>');
        $this->localConfigIni = clone $this->appConfigIni;
        $this->localConfigIni['local'] = $globalSetup->getLocalConfigIni()['local'];
        $this->localConfigIni['localentrypoint'] = $localEpConfigIni;

        $this->liveConfigIni = clone $this->localConfigIni;
        $this->liveConfigIni['live'] = $globalSetup->getLiveConfigIni()['live'];

        $this->config = \jConfigCompiler::read($configFile, true,
            $this->_isCliScript,
            $this->scriptName);

        $this->urlMap = $globalSetup->getUrlModifier()
            ->addEntryPoint($this->getEpId(), $type);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getScriptName()
    {
        return $this->scriptName;
    }

    public function getFileName()
    {
        return $this->file;
    }

    public function isCliScript()
    {
        return $this->_isCliScript;
    }

    public function getUrlMap()
    {
        return $this->urlMap;
    }

    /**
     * @return string the entry point id
     */
    function getEpId()
    {
        return $this->config->urlengine['urlScriptId'];
    }

    /**
     * @return array the list of modules and their path, as stored in the
     * compiled configuration file
     */
    function getModulesList()
    {
        return $this->config->_allModulesPathList;
    }


    /**
     * the full original configuration of the entry point
     *
     * combination of
     *  - "default" => defaultconfig.ini.php
     *  - "main" => mainconfig.ini.php
     *  - "entrypoint" => app/config/$entrypointConfigFile
     *
     * @return \Jelix\IniFile\IniModifierArray
     */
    function getAppConfigIni()
    {
        return $this->appConfigIni;
    }

    /*
     * the local entry point config (in var/config) combined with the original configuration
     *
     * combination of
     *  - "default" => defaultconfig.ini.php
     *  - "main" => mainconfig.ini.php
     *  - "entrypoint" => app/config/$entrypointConfigFile
     *  - "local" => localconfig.ini.php
     *  - "localentrypoint" => var/config/$entrypointConfigFile
     *
     * @return \Jelix\IniFile\IniModifierArray
     */
    function getLocalConfigIni()
    {
        return $this->localConfigIni;
    }

    /*
     * the live config combined with other configuration files
     *
     * combination of
     *  - "default" => defaultconfig.ini.php
     *  - "main" => mainconfig.ini.php
     *  - "entrypoint" => app/config/$entrypointConfigFile
     *  - "local" => localconfig.ini.php
     *  - "localentrypoint" => var/config/$entrypointConfigFile
     *  - "live" => var/config/liveconfig.ini.php
     * @return \Jelix\IniFile\IniModifierArray
     */
    function getLiveConfigIni()
    {
        return $this->liveConfigIni;
    }

    /**
     * @return string the config file name of the entry point
     */
    function getConfigFileName()
    {
        return $this->configFileName;
    }

    /**
     * @return \StdClass the config content of the entry point, as seen when
     * calling jApp::config()
     */
    function getConfigObj()
    {
        return $this->config;
    }

    /**
     * @param \StdClass $config
     */
    function setConfigObj($config)
    {
        $this->config = $config;
    }

}