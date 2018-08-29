<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2017-2018 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Installer;


use \Jelix\IniFile\IniReader;

/**
 * @since 1.7.0
 */
class GlobalSetup {

    /**
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $configIni;

    /**
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $localConfigIni;

    /**
     * @var \Jelix\IniFile\IniModifierArray
     */
    protected $liveConfigIni;

    /**
     * @var \Jelix\Routing\UrlMapping\XmlMapModifier
     */
    protected $urlMapModifier;

    /**
     *  @var \Jelix\IniFile\IniModifier it represents the profiles.ini.php file.
     */
    protected $profilesIni = null;

    /**
     *  @var \Jelix\IniFile\IniModifier it represents the installer.ini.php file.
     */
    protected $installerIni = null;


    /**
     *  @var \Jelix\IniFile\IniModifier it represents the install/uninstall/uninstaller.ini.php file.
     */
    protected $uninstallerIni = null;


    /**
     * list of entry point and their properties
     * @var EntryPoint[]  keys are entry point id.
     */
    protected $entryPoints = array();

    /**
     * @var EntryPoint
     */
    protected $mainEntryPoint = null;

    /**
     * list of modules
     * @var ModuleInstallerLauncher[] key: module name
     */
    protected $modules = array();

    /**
     * list of ghost modules
     *
     * ghost module is a module for which we have only its uninstaller
     *
     * @var ModuleInstallerLauncher[] key: module name
     */
    protected $ghostModules = array();

    protected $projectXmlPath;

    /**
     * @var \Jelix\Core\Infos\AppInfos
     */
    protected $projectInfos;

    /**
     * GlobalSetup constructor.
     * @param string|\Jelix\Core\Infos\AppInfos|null $projectXmlFileName
     * @param string|null $mainConfigFileName
     * @param string|null $localConfigFileName
     * @param string|null $urlXmlFileName
     */
    function __construct(
        $projectXmlFileName = null,
        $mainConfigFileName = null,
        $localConfigFileName = null,
        $urlXmlFileName = null)
    {

        if ($projectXmlFileName instanceof \Jelix\Core\Infos\AppInfos) {
            $this->projectInfos = $projectXmlFileName;
            $this->projectXmlPath = $this->projectInfos->getFilePath();
        }
        else {
            if (!$projectXmlFileName) {
                $projectXmlFileName = \jApp::appPath('project.xml');
            }
            $this->projectXmlPath = $projectXmlFileName;
            $parser = new \Jelix\Core\Infos\ProjectXmlParser($projectXmlFileName);
            $this->projectInfos = $parser->parse();
        }

        $profileIniFileName = \jApp::varConfigPath('profiles.ini.php');
        if (!file_exists($profileIniFileName)) {
            $profileIniDist = \jApp::varConfigPath('profiles.ini.php.dist');
            if (file_exists($profileIniDist)) {
                copy($profileIniDist, $profileIniFileName);
            }
            else {
                file_put_contents($profileIniFileName, ';<'.'?php die(\'\');?'.'> ');
            }
        }

        $this->profilesIni = new \Jelix\IniFile\IniModifier($profileIniFileName);

        if (!$mainConfigFileName) {
            $mainConfigFileName = \jApp::mainConfigFile();
        }

        if (!$localConfigFileName) {
            $localConfigFileName = \jApp::varConfigPath('localconfig.ini.php');
            if (!file_exists($localConfigFileName)) {
                $localConfigDist = \jApp::varConfigPath('localconfig.ini.php.dist');
                if (file_exists($localConfigDist)) {
                    copy($localConfigDist, $localConfigFileName);
                }
                else {
                    file_put_contents($localConfigFileName, ';<'.'?php die(\'\');?'.'> static local configuration');
                }
            }
        }

        $liveConfigFileName = \jApp::varConfigPath('liveconfig.ini.php');
        if (!file_exists($liveConfigFileName)) {
            file_put_contents($liveConfigFileName, ';<'.'?php die(\'\');?'.'> live local configuration');
        }

        $defaultConfig = new IniReader(\jConfig::getDefaultConfigFile());

        $this->configIni = new \Jelix\IniFile\IniModifierArray(array(
            'default'=> $defaultConfig,
            'main' => $mainConfigFileName,
        ));
        $this->localConfigIni = clone $this->configIni;
        $this->localConfigIni['local'] = $localConfigFileName;

        $this->liveConfigIni = clone $this->localConfigIni;
        $this->liveConfigIni['live'] = $liveConfigFileName;

        $this->installerIni = $this->loadInstallerIni();

        \jFile::createDir(\jApp::appPath('install/uninstall'));
        $this->uninstallerIni = new \Jelix\IniFile\IniModifier(
            \jApp::appPath('install/uninstall/uninstaller.ini.php'),
            ";<?php die(''); ?>
; for security reasons , don't remove or modify the first line
; don't modify this file if you don't know what you do. it is generated automatically by jInstaller

");

        if (!$urlXmlFileName) {
            $urlXmlFileName = \jApp::appConfigPath($this->localConfigIni->getValue('significantFile', 'urlengine'));
        }
        $this->urlMapModifier = new \Jelix\Routing\UrlMapping\XmlMapModifier($urlXmlFileName, true);


        $this->readEntryPointData();
        $this->readModuleInfos();

        // be sure temp path is ready
        $chmod = $this->configIni->getValue('chmodDir');
        \jFile::createDir(\jApp::tempPath(), intval($chmod, 8));
    }

    /**
     * read the list of entrypoint from the project.xml file
     * and read all modules data used by each entry point
     * @throws \Exception
     */
    protected function readEntryPointData() {

        $configFileList = array();

        if (!count($this->projectInfos->entrypoints)) {
            throw new \Exception("Entrypoint declaration is missing into project.xml");
        }

        // read all entry points data
        foreach ($this->projectInfos->entrypoints as $entrypoint) {

            // ignore entry point which have the same config file of an other one
            // FIXME: what about installer.ini ?
            if (isset($configFileList[$entrypoint->configFile]))
                continue;

            $configFileList[$entrypoint->configFile] = true;

            // we create an object corresponding to the entry point
            $ep = $this->createEntryPointObject($entrypoint->configFile,
                $entrypoint->id.'.php', $entrypoint->type);
            $epId = $ep->getEpId();

            if (!$this->mainEntryPoint || $epId == 'index') {
                $this->mainEntryPoint = $ep;
            }

            $this->entryPoints[$epId] = $ep;
        }
    }

    /**
     * @internal for tests
     */
    protected function createEntryPointObject($configFile, $file, $type) {
        return new EntryPoint($this, $configFile, $file, $type);
    }

    protected function readModuleInfos() {
        // now let's read all modules properties
        $modulesList = $this->mainEntryPoint->getModulesList();

        foreach ($modulesList as $name=>$path) {
            $compModule = $this->createComponentModule($name, $path);
            $this->addModuleComponent($compModule);
        }

        // load ghost modules we have to uninstall
        $uninstallersDir = \jApp::appPath('install/uninstall');
        if (file_exists($uninstallersDir)) {
            $dir = new \DirectoryIterator($uninstallersDir);
            $modulesInfos = $this->uninstallerIni->getValues('modules');
            foreach ($dir as $dirContent) {
                if ($dirContent->isDot() || !$dirContent->isDir()) {
                    continue;
                }

                $moduleName = $dirContent->getFilename();

                if (
                    isset($this->modules[$moduleName]) ||
                    !$this->installerIni->getValue($moduleName.'.installed', 'modules') ||
                    !isset($modulesInfos[$moduleName.'.enabled'])
                ) {
                    continue;
                }

                $modulesInfos[$moduleName.'.installed'] = 1;
                $modulesInfos[$moduleName.'.version'] = $this->installerIni->getValue($moduleName.'.version', 'modules');
                $modulesInfos[$moduleName.'.enabled'] = false;

                $moduleInfos = new ModuleStatus($moduleName,
                    $dirContent->getPathname(), $modulesInfos);

                $this->ghostModules[$moduleName] = new ModuleInstallerLauncher($moduleInfos, $this);
                $this->ghostModules[$moduleName]->init();

            }
        }


        // remove informations about modules that don't exist anymore
        $modules = $this->installerIni->getValues('modules');
        foreach($modules as $key=>$value) {
            $l = explode('.', $key);
            if (count($l)<=1) {
                continue;
            }
            if (!isset($modulesList[$l[0]]) && !isset($this->ghostModules[$l[0]])) {
                $this->installerIni->removeValue($key, 'modules');
            }
        }
    }

    public function addModuleComponent(ModuleInstallerLauncher $compModule) {
        $name = $compModule->getName();
        $this->modules[$name] = $compModule;
        $compModule->init();
        $this->installerIni->setValue($name.'.installed', $compModule->isInstalled(), 'modules');
        $this->installerIni->setValue($name.'.version', $compModule->getInstalledVersion(), 'modules');
    }

    /**
     * @internal for tests
     * @return ModuleInstallerLauncher
     */
    protected function createComponentModule($name, $path) {
        $moduleSetupList = $this->mainEntryPoint->getConfigObj()->modules;
        $moduleInfos = new ModuleStatus($name, $path, $moduleSetupList);
        return new ModuleInstallerLauncher($moduleInfos, $this);
    }

    /**
     * @param string $name
     * @return ModuleInstallerLauncher|null
     */
    public function getModuleComponent($name) {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }
        return null;
    }

    /**
     * @return ModuleInstallerLauncher[]
     */
    public function getModuleComponentsList() {
        return $this->modules;
    }

    /**
     * List of modules that should be uninstall and we
     * have only their uninstaller into install/uninstall/
     * @return ModuleInstallerLauncher[]
     */
    public function getGhostModuleComponents() {
        return $this->ghostModules;
    }

    /**
     * @return EntryPoint
     */
    public function getMainEntryPoint() {
        return $this->mainEntryPoint;
    }

    /**
     * @return EntryPoint[]
     */
    public function getEntryPointsList() {
        return $this->entryPoints;
    }

    /**
     * @return EntryPoint
     */
    public function getEntryPointById($epId) {
        if (isset($this->entryPoints[$epId])) {
            return $this->entryPoints[$epId];
        }
        return null;
    }

    /**
     * @return EntryPoint[]
     */
    public function getEntryPointsByType($type = 'classic') {
        $list = [];
        foreach($this->entryPoints as $id =>$ep) {
            if ($ep->getType() == $type) {
                $list[$id] = $ep;
            }
        }
        return $list;
    }

    /**
     * the combined global config files, defaultconfig.ini.php and mainconfig.ini.php
     * @return \Jelix\IniFile\IniModifierArray
     */
    public function getConfigIni() {
        return $this->configIni;
    }

    /**
     * the combined global config files, defaultconfig.ini.php and mainconfig.ini.php,
     * with localconfig.ini.php
     * @return \Jelix\IniFile\IniModifierArray
     */
    public function getLocalConfigIni() {
        return $this->localConfigIni;
    }

    /**
     * the combined config files defaultconfig.ini.php and mainconfig.ini.php
     * with localconfig.ini.php and liveconfig.ini.php
     * @return \Jelix\IniFile\IniModifierArray
     */
    public function getLiveConfigIni() {
        return $this->liveConfigIni;
    }

    /**
     * the profiles.ini.php file
     * @return \Jelix\IniFile\IniModifier
     */
    public function getProfilesIni() {
        return $this->profilesIni;
    }

    /**
     * the installer.ini.php
     * @return \Jelix\IniFile\IniModifier
     */
    public function getInstallerIni() {
        return $this->installerIni;
    }

    /**
     * the uninstaller.ini.php
     * @return \Jelix\IniFile\IniModifier
     */
    public function getUninstallerIni() {
        return $this->uninstallerIni;
    }

    /**
     * @return \Jelix\IniFile\IniModifier the modifier for the installer.ini.php file
     * @throws \Exception
     */
    protected function loadInstallerIni() {
        if (!file_exists(\jApp::varConfigPath('installer.ini.php'))) {
            if (false === @file_put_contents(\jApp::varConfigPath('installer.ini.php'), ";<?php die(''); ?>
; for security reasons , don't remove or modify the first line
; don't modify this file if you don't know what you do. it is generated automatically by jInstaller

")) {
                throw new \Exception('impossible to create var/config/installer.ini.php');
            }
        }
        else {
            copy(\jApp::varConfigPath('installer.ini.php'), \jApp::varConfigPath('installer.bak.ini.php'));
        }
        return new \Jelix\IniFile\IniModifier(\jApp::varConfigPath('installer.ini.php'));
    }

    /**
     * @return \Jelix\Routing\UrlMapping\XmlMapModifier
     */
    public function getUrlModifier() {
        return $this->urlMapModifier;
    }

    /**
     * Declare a new entry point
     *
     * @param string $epId
     * @param string $epType
     * @param string $configFileName
     * @throws \Exception
     */
    public function declareNewEntryPoint($epId, $epType, $configFileName) {

        $this->urlMapModifier->addEntryPoint($epId, $epType);

        if (isset($this->projectInfos->entrypoints[$epId])) {
            throw new \Exception("There is already an entrypoint with the same name but with another type ($epId, $epType)");
        }

        $this->projectInfos->entrypoints[$epId] = new \Jelix\Core\Infos\EntryPoint($epId, $configFileName, $epType);

        $writer = new \Jelix\Core\Infos\ProjectXmlWriter($this->projectInfos->getFilePath());
        $writer->write($this->projectInfos);
    }

    /**
     *
     */
    protected $installerContexts = array();

    public function getInstallerContexts($moduleName) {
        $contexts = $this->installerIni->getValue($moduleName.'.contexts','__modules_data');
        if ($contexts !== null && $contexts !== "") {
            $contexts = explode(',', $contexts);
        }
        else {
            $contexts = array();
        }
        return $contexts;
    }

    public function updateInstallerContexts($moduleName, $contexts) {
        $this->installerIni->setValue($moduleName.'.contexts', implode(',',$contexts), '__modules_data');
    }

    public function removeInstallerContexts($moduleName) {
        $this->installerIni->removeValue($moduleName.'.contexts', '__modules_data');
    }

    /**
     * @param \Jelix\IniFile\IniModifier $config
     * @param string $name the name of webassets
     * @param array $values
     * @param string $collection the name of the webassets collection
     * @param boolean $force
     */
    public function declareWebAssetsInConfig(\Jelix\IniFile\IniModifier $config,
                                             $name, array $values, $collection, $force)
    {
        $section = 'webassets_'.$collection;
        if (!$force && (
                $config->getValue($name.'.css', $section) ||
                $config->getValue($name.'.js', $section) ||
                $config->getValue($name.'.require', $section)
            )) {
            return;
        }

        if (isset($values['css'])) {
            $config->setValue($name.'.css', $values['css'], $section);
        }
        else {
            $config->removeValue($name.'.css', $section);
        }
        if (isset($values['js'])) {
            $config->setValue($name.'.js', $values['js'], $section);
        }
        else {
            $config->removeValue($name.'.js', $section);
        }
        if (isset($values['require'])) {
            $config->setValue($name.'.require', $values['require'], $section);
        }
        else {
            $config->removeValue($name.'.require', $section);
        }
    }

    /**
     * @param \Jelix\IniFile\IniModifier $config
     * @param string $name the name of webassets
     * @param string $collection the name of the webassets collection
     */
    public function removeWebAssetsFromConfig(\Jelix\IniFile\IniModifier $config,
                                              $name, $collection)
    {
        $section = 'webassets_'.$collection;
        $config->removeValue($name.'.css', $section);
        $config->removeValue($name.'.js', $section);
        $config->removeValue($name.'.require', $section);
    }


    /**
     * return the section name of configuration of a plugin for the coordinator
     * or the IniModifier for the configuration file of the plugin if it exists.
     * @param \Jelix\IniFile\IniModifier $config  the global configuration content
     * @param string $pluginName
     * @return array|null null if plugin is unknown, else array($iniModifier, $section)
     * @throws Exception when the configuration filename is not found
     */
    public function getCoordPluginConf(\Jelix\IniFile\IniModifierInterface $config,
                                       $pluginName)
    {
        $conf = $config->getValue($pluginName, 'coordplugins');
        if (!$conf) {
            return null;
        }
        if ($conf == '1') {
            $pluginConf = $config->getValues($pluginName);
            if ($pluginConf) {
                return array($config, $pluginName);
            }
            else {
                // old section naming. deprecated
                $pluginConf = $config->getValues('coordplugin_' . $pluginName);
                if ($pluginConf) {
                    return array($config, 'coordplugin_' . $pluginName);
                }
            }
            return null;
        }
        // the configuration value is a filename
        $confpath = \jApp::appConfigPath($conf);
        if (!file_exists($confpath)) {
            $confpath = \jApp::varConfigPath($conf);
            if (!file_exists($confpath)) {
                return null;
            }
        }
        return array(new \Jelix\IniFile\IniModifier($confpath), 0);
    }
}