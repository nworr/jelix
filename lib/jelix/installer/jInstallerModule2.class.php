<?php
/**
* @package     jelix
* @subpackage  installer
* @author      Laurent Jouanneau
* @copyright   2008-2018 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * A class that does processing to install a module into
 * an instance of the application. A module should have a class that inherits
 * from it in order to setup itself into the application.
 *
 * @package     jelix
 * @subpackage  installer
 * @since 1.7
 */
class jInstallerModule2 extends jInstallerModule2Abstract implements jIInstallerComponent2 {

    /**
     * @inheritdoc
     */
    function preInstall() {

    }

    /**
     * @inheritdoc
     */
    function install() {

    }

    /**
     * @inheritdoc
     */
    function postInstall() {

    }

    /**
     * the versions for which the installer should be called.
     * Useful for an upgrade which target multiple branches of a project.
     * Put the version for multiple branches. The installer will be called
     * only once, for the needed version.
     * If you don't fill it, the name of the class file should contain the
     * target version (deprecated behavior though)
     * @var array $targetVersions list of version by asc order
     * @since 1.2.6
     */
    protected $targetVersions = array();

    /**
     * @var string the date of the release of the update. format: yyyy-mm-dd hh:ii
     * @since 1.2.6
     */
    protected $date = '';

    /**
     * @var string the version for which the installer is called
     */
    protected $version = '0';


    function getTargetVersions() {
        return $this->targetVersions;
    }

    function setTargetVersions($versions) {
        $this->targetVersions = $versions;
    }

    function getDate() {
        return $this->date;
    }

    function getVersion() {
        return $this->version;
    }

    function setVersion($version) {
        $this->version = $version;
    }


    /**
     * import a sql script into the current profile.
     *
     * The name of the script should be store in install/$name.databasetype.sql
     * in the directory of the component. (replace databasetype by mysql, pgsql etc.)
     * You can however provide a script compatible with all databases, but then
     * you should indicate the full name of the script, with a .sql extension.
     *
     * @param string $name the name of the script
     * @param string $module the module from which we should take the sql file. null for the current module
     * @param boolean $inTransaction indicate if queries should be executed inside a transaction
     * @throws Exception
     */
    final protected function execSQLScript ($name, $module = null, $inTransaction = true)
    {
        $conn = $this->dbConnection();
        $tools = $this->dbTool();

        if ($module) {
            $conf = $this->globalSetup->getMainEntryPoint()->getConfigObj()->_modulesPathList;
            if (!isset($conf[$module])) {
                throw new Exception('execSQLScript : invalid module name');
            }
            $path = $conf[$module];
        }
        else {
            $path = $this->path;
        }

        $file = $path.'install/'.$name;
        if (substr($name, -4) != '.sql')
            $file .= '.'.$conn->dbms.'.sql';

        if ($inTransaction)
            $conn->beginTransaction();
        try {
            $tools->execSQLScript($file);
            if ($inTransaction) {
                $conn->commit();
            }
        }
        catch(Exception $e) {
            if ($inTransaction)
                $conn->rollback();
            throw $e;
        }
    }

    /**
     * Insert data into a database, from a json file, using a DAO mapping
     * @param string $relativeSourcePath name of the json file into the install directory
     * @param integer $option one of jDbTools::IBD_* const
     * @return integer number of records inserted/updated
     * @throws Exception
     * @since 1.6.16
     */
    final protected function insertDaoData($relativeSourcePath, $option, $module = null) {

        if ($module) {
            $conf = $this->globalSetup->getMainEntryPoint()->getModulesList();
            if (!isset($conf[$module])) {
                throw new Exception('insertDaoData : invalid module name');
            }
            $path = $conf[$module];
        }
        else {
            $path = $this->path;
        }

        $file = $path.'install/'.$relativeSourcePath;
        $dataToInsert = json_decode(file_get_contents($file), true);
        if (!$dataToInsert) {
            throw new Exception("Bad format for dao data file.");
        }
        if (is_object($dataToInsert)) {
            $dataToInsert = array($dataToInsert);
        }
        $daoMapper = new jDaoDbMapper($this->dbProfile);
        $count = 0;
        foreach($dataToInsert as $daoData) {
            if (!isset($daoData['dao']) ||
                !isset($daoData['properties']) ||
                !isset($daoData['data'])
            ) {
               throw new Exception("Bad format for dao data file.");
            }
            $count += $daoMapper->insertDaoData($daoData['dao'],
                $daoData['properties'], $daoData['data'], $option);
        }
        return $count;
    }


    /**
     * copy the whole content of a directory existing in the install/ directory
     * of the component, to the given directory
     * @param string $relativeSourcePath relative path to the install/ directory of the component
     * @param string $targetPath the full path where to copy the content
     */
    final protected function copyDirectoryContent($relativeSourcePath, $targetPath, $overwrite = false) {
        $targetPath = $this->expandPath($targetPath);
        $this->_copyDirectoryContent ($this->path.'install/'.$relativeSourcePath, $targetPath, $overwrite);
    }

    /**
     * private function which copy the content of a directory to an other
     *
     * @param string $sourcePath
     * @param string $targetPath
     */
    private function _copyDirectoryContent($sourcePath, $targetPath, $overwrite) {
        jFile::createDir($targetPath);
        $dir = new DirectoryIterator($sourcePath);
        foreach ($dir as $dirContent) {
            if ($dirContent->isFile()) {
                $p = $targetPath.substr($dirContent->getPathName(), strlen($dirContent->getPath()));
                if ($overwrite || !file_exists($p))
                    copy($dirContent->getPathName(), $p);
            } else {
                if (!$dirContent->isDot() && $dirContent->isDir()) {
                    $newTarget = $targetPath.substr($dirContent->getPathName(), strlen($dirContent->getPath()));
                    $this->_copyDirectoryContent($dirContent->getPathName(),$newTarget, $overwrite);
                }
            }
        }
    }


    /**
     * copy a file from the install/ directory to an other
     * @param string $relativeSourcePath relative path to the install/ directory of the file to copy
     * @param string $targetPath the full path where to copy the file
     */
    final protected function copyFile($relativeSourcePath, $targetPath, $overwrite = false) {
        $targetPath = $this->expandPath($targetPath);
        if (!$overwrite && file_exists($targetPath))
            return;
        $dir = dirname($targetPath);
        jFile::createDir($dir);
        copy ($this->path.'install/'.$relativeSourcePath, $targetPath);
    }

    /**
     * declare a new db profile. if the content of the section is not given,
     * it will declare an alias to the default profile
     * @param string $name  the name of the new section/alias
     * @param null|string|array  $sectionContent the content of the new section, or null
     *     to create an alias.
     * @param boolean $force true:erase the existing profile
     * @return boolean true if the ini file has been changed
     */
    protected function declareDbProfile($name, $sectionContent = null, $force = true ) {

        $profiles = $this->globalSetup->getProfilesIni();
        if ($sectionContent == null) {
            if (!$profiles->isSection('jdb:'.$name)) {
                // no section
                if ($profiles->getValue($name, 'jdb') && !$force) {
                    // already a name
                    return false;
                }
            }
            else if ($force) {
                // existing section, and no content provided : we erase the section
                // and add an alias
                $profiles->removeValue('', 'jdb:'.$name);
            }
            else {
                return false;
            }
            $default = $profiles->getValue('default', 'jdb');
            if($default) {
                $profiles->setValue($name, $default, 'jdb');
            }
            else // default is a section
                $profiles->setValue($name, 'default', 'jdb');
        }
        else {
            if ($profiles->getValue($name, 'jdb') !== null) {
                if (!$force)
                    return false;
                $profiles->removeValue($name, 'jdb');
            }
            if (is_array($sectionContent)) {
                foreach($sectionContent as $k=>$v) {
                    if ($force || !$profiles->getValue($k, 'jdb:'.$name)) {
                        $profiles->setValue($k,$v, 'jdb:'.$name);
                    }
                }
            }
            else {
                $profile = $profiles->getValue($sectionContent, 'jdb');
                if ($profile !== null) {
                    $profiles->setValue($name, $profile, 'jdb');
                }
                else
                    $profiles->setValue($name, $sectionContent, 'jdb');
            }
        }
        $profiles->save();
        jProfiles::clear();
        return true;
    }

    /**
     * declare web assets into the main configuration
     * @param string $name the name of webassets
     * @param array $values should be an array with one or more of these keys 'css' (array), 'js'  (array), 'require' (string)
     * @param string $collection the name of the webassets collection
     * @param bool $force
     */
    public function declareGlobalWebAssets($name, array $values, $collection, $force)
    {
        $config = $this->globalSetup->getConfigIni();
        $this->globalSetup->declareWebAssetsInConfig($config['main'], $name, $values, $collection, $force);
    }
}

