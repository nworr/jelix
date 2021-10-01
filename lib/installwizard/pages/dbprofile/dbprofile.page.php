<?php

/**
* page for Installation wizard
*
* @package     InstallWizard
* @subpackage  pages
* @author      Laurent Jouanneau
* @copyright   2010-2015 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/


/**
 * page for a wizard, to configure database access for a jelix application
 *
 * Configuration parameters:
 * - ignoreProfiles: list of profile name to ignore from the existing profiles.ini.php(.dist)
 * - availabledDrivers: list of jdb driver that are allowed
 * - passwordRequired: if true, a password should be given
 */
class dbprofileWizPage extends installWizardPage {

    /**
     * action to display the page
     * @param \Jelix\Castor\Castor $tpl the template container
     */
    function show (\Jelix\Castor\Castor $tpl) {
        if (!isset($_SESSION['dbprofiles'])) {
            $this->loadProfiles();
        }

        $sections = $_SESSION['dbprofiles']['profiles'];
        $data = $_SESSION['dbprofiles']['data'];

        $ignoreProfiles = isset($this->config['ignoreProfiles'])?$this->config['ignoreProfiles']:'';
        $ignoreProfiles = preg_split("/ *, */", $ignoreProfiles);

        if (count($ignoreProfiles)) {
            $newsections = array();
            foreach($sections as $profile) {
                if(!in_array(substr($profile,4), $ignoreProfiles))
                    $newsections[] = $profile;
            }
            $tpl->assign('profiles', $newsections);
            $_SESSION['dbprofiles']['profiles'] = $newsections;
        }
        else {
            $tpl->assign('profiles', $sections);
        }

        $tpl->assign($data);

        //$preferPDO = isset($this->config['preferpdo'])?$this->config['preferpdo']:false;

        $tpl->assign('drivers', $this->getDriversList());

        return true;
    }

    protected function getDriversList(){
        $driversInfos = \Jelix\Database\AccessParameters::getDriversInfosList();

        $drivers = isset($this->config['availabledDrivers'])?$this->config['availabledDrivers']:'mysqli,sqlite3,pgsql';
        $list = preg_split("/ *, */",$drivers);
        $drivers = array();

        foreach($driversInfos as $drinfos) {
            if (in_array($drinfos[3], $list)) {
                $drv = $drinfos[3];
                if (extension_loaded($drinfos[1])) {
                    $drivers[$drv] = array($drv, $drinfos[0]) ;
                }
                if (class_exists('PDO') && extension_loaded($drinfos[2])) {
                    $drivers[$drv.':pdo'] = array($drv.' (PDO)', $drinfos[0]);
                }
            }
        }
        return $drivers;
    }

    function process() {

        $ini = new \Jelix\IniFile\IniModifier(jApp::varConfigPath('profiles.ini.php'));
        $hasErrors = false;
        $_SESSION['dbprofiles']['data'] = $_POST;

        foreach ($_SESSION['dbprofiles']['profiles'] as $profile) {
            $errors = array();
            $params = array();
            $driver = $_POST['driver'][$profile];
            $usepdo = false;
            if(substr($driver, -4) == ':pdo') {
                $ini->setValue('usepdo', true, $profile);
                $usepdo = true;
                $realdriver = substr($driver, 0, -4);
            }
            else {
                $ini->removeValue('usepdo', $profile);
                $realdriver = $driver;
            }
            $ini->removeValue('dsn', $profile);

            if(isset($_POST['persistent'][$profile]) && $_POST['persistent'][$profile] == 'on') {
                $ini->setValue('persistent', true, $profile);
            }
            else
                $ini->removeValue('persistent', $profile);

            if(isset($_POST['force_encoding'][$profile]) && $_POST['force_encoding'][$profile] == 'on') {
                $ini->setValue('force_encoding', true, $profile);
            }
            else
                $ini->removeValue('force_encoding', $profile);

            $ini->setValue('table_prefix', $_POST['table_prefix'][$profile], $profile);

            $database = trim($_POST['database'][$profile]);
            if ($database == '') {
                $errors[] = $this->locales['error.missing.database'];
                continue;
            }
            $params['database'] = $database;
            $ini->setValue('database', $database, $profile);

            $params['driver'] = $realdriver;
            $ini->setValue('driver', $realdriver, $profile);
            if ($_POST['dbtype'][$profile] != 'sqlite') {

                $host = trim($_POST['host'][$profile]);
                if ($host == '' && $realdriver != 'pgsql') {
                    $errors[] = $this->locales['error.missing.host'];
                }
                else {
                    $ini->setValue('host', $host, $profile);
                    $params['host'] = $host;
                }

                $port = trim($_POST['port'][$profile]);
                if ($port != '') {
                    $ini->setValue('port', $port, $profile);
                    $params['port'] = $port;
                }

                $user = trim($_POST['user'][$profile]);
                if ($user == '') {
                    $errors[] = $this->locales['error.missing.user'];
                }
                else {
                    $ini->setValue('user', $user, $profile);
                    $params['user'] = $user;
                }

                $password = trim($_POST['password'][$profile]);
                $passwordRequired =  (isset($this->config['passwordRequired']) && $this->config['passwordRequired']);
                if ($password == '' && $passwordRequired) {
                    $errors[] = $this->locales['error.missing.password'];
                }
                else {
                    $ini->setValue('password', $password, $profile);
                    $params['password'] = $password;
                }

                if (trim($_POST['passwordconfirm'][$profile]) != $password) {
                    $errors[] = $this->locales['error.invalid.confirm.password'];
                }

                if ($_POST['dbtype'][$profile] == 'pgsql') {
                    $search_path = trim($_POST['search_path'][$profile]);
                    $params['search_path'] = $search_path;
                    if ($search_path != '') {
                        $ini->setValue('search_path', $search_path, $profile);
                    }
                }
            }

            if (!count($errors)) {
                $options = $ini->getValues($profile);
                $dbparam = new \Jelix\Database\AccessParameters($options);
                $options = $dbparam->getNormalizedParameters();
                try {
                    if ($usepdo) {
                        $m = 'check_PDO';
                    }
                    else {
                        $m = 'check_'.$options['driver'];
                    }
                    $this->$m($options);
                }
                catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if (count($errors))
                $hasErrors = true;

            $_SESSION['dbprofiles']['data']['errors'][$profile] = $errors;
        }

        if ($hasErrors)
            return false;

        $ini->save();
        unset($_SESSION['dbprofiles']);
        return 0;
    }

    protected function loadProfiles () {
        $file = jApp::varConfigPath('profiles.ini.php');

        if (file_exists($file)) {

        }
        elseif (file_exists(jApp::varConfigPath('profiles.ini.php.dist'))) {
             copy(jApp::varConfigPath('profiles.ini.php.dist'), $file);
        }
        else {
            file_put_contents($file, ";<?php die(''); ?>
;for security reasons, don't remove or modify the first line

[jdb:default]
driver=mysqli
database=
host=localhost
user=
password=
persistent = on
force_encoding = on
table_prefix=
");
        }

        $ini = new \Jelix\IniFile\IniModifier($file);

        $data = array(
            'dbtype'=>array(),
            'driver'=>array(),
            'database'=>array(),
            'host'=>array(),
            'port'=>array(),
            'user'=>array(),
            'password'=>array(),
            'passwordconfirm'=>array(),
            'persistent'=>array(),
            'table_prefix'=>array(),
            'force_encoding'=>array(),
            'search_path'=>array(),
            'errors'=>array()
        );

        $profiles = $ini->getSectionList();
        $dbprofileslist = array();
        foreach($profiles as $profile) {
            if (strpos($profile,'jdb:') !== 0)
                continue;
            $dbprofileslist[] = $profile;
            $options = $ini->getValues($profile);
            $dbparam = new \Jelix\Database\AccessParameters($options);
            $options = $dbparam->getNormalizedParameters();

            $data['dbtype'][$profile] = $options['dbtype'];
            $driver =$options['driver'];
            if ($options['usepdo']) {
                $dsn = $ini->getValue('dsn', $profile);
                $data['driver'][$profile] = $driver.':pdo';
                if (preg_match("/host=([^;]*)(;|$)/", $dsn, $m)) {
                    $data['host'][$profile] = $m[1];
                }
                else {
                    $host = $ini->getValue('host', $profile);
                    $data['host'][$profile] = ($host===null?'':$host);
                }
                if (preg_match("/dbname=([^;]*)(;|$)/", $dsn, $m)) {
                    $data['database'][$profile] = $m[1];
                }
                else {
                    $host = $ini->getValue('database', $profile);
                    $data['database'][$profile] = ($host===null?'':$host);
                }
                if (preg_match("/port=([^;]*)(;|$)/", $dsn, $m)) {
                    $data['port'][$profile] = $m[1];
                }
                else {
                    $port = $ini->getValue('port', $profile);
                    $data['port'][$profile] = ($port===null?'':$port);
                }
            }
            else {
                $data['driver'][$profile] = $driver.($options['usepdo']?':pdo':'');
                $data['database'][$profile] = $ini->getValue('database', $profile);
                $data['host'][$profile] = $ini->getValue('host', $profile);
                $data['port'][$profile] = $ini->getValue('port', $profile);
            }

            $data['user'][$profile] = $ini->getValue('user', $profile);
            $data['password'][$profile] = $ini->getValue('password', $profile);
            $data['passwordconfirm'][$profile] = $data['password'][$profile];
            $data['persistent'][$profile] = $options['persistent'];
            $data['force_encoding'][$profile]= $options['force_encoding'];

            $data['table_prefix'][$profile] = $ini->getValue('table_prefix', $profile);
            $data['search_path'][$profile] = $ini->getValue('search_path', $profile);
            $data['errors'][$profile] = array();
        }

        $_SESSION['dbprofiles']['profiles'] = $dbprofileslist;
        $_SESSION['dbprofiles']['data'] = $data;
    }

    protected function check_mssql($params) {
        if(!function_exists('mssql_connect')) {
            throw new Exception($this->locales['error.extension.mssql.not.installed']);
        }
        $host = $params['host'];
        if(isset($params['port']) && $params['port']) {
            if(DIRECTORY_SEPARATOR === '\\')
                $host.=','.$params['port'];
            else
                $host.=':'.$params['port'];
        }
        if ($cnx = @mssql_connect ($host, $params['user'], $params['password'])) {
            if(!mssql_select_db ($params['database'], $cnx))
                throw new Exception($this->locales['error.no.database']);
            mssql_close($cnx);
        }
        else {
            throw new Exception($this->locales['error.no.connection']);
        }
        return true;
    }

    protected function check_mysql($params) {
        if(!function_exists('mysql_connect')) {
            throw new Exception($this->locales['error.extension.mysql.not.installed']);
        }
        $host = $params['host'];
        if(isset($params['port']) && $params['port']) {
            $host.=':'.$params['port'];
        }
        if ($cnx = @mysql_connect ($host, $params['user'], $params['password'])) {
            if(!mysql_select_db ($params['database'], $cnx))
                throw new Exception($this->locales['error.no.database']);
            mysql_close($cnx);
        }
        else {
            throw new Exception($this->locales['error.no.connection']);
        }
        return true;
    }

    protected function check_mysqli($params) {
        $host = ($params['persistent']) ? 'p:'.$params['host'] : $params['host'];
        $cnx = @new mysqli ($host, $params['user'], $params['password'], $params['database']);
        if ($cnx->connect_errno) {
            throw new Exception($this->locales['error.no.connection']);
        }
        $cnx->close();
        return true;
    }

    protected function check_oci($params) {
        throw new Exception('oci not supported');
    }

    protected function check_pgsql($params) {
        if(!function_exists('pg_connect')) {
            throw new Exception($this->locales['error.extension.pgsql.not.installed']);
        }

        $str = '';

        // we do a distinction because if the host is given == TCP/IP connection else unix socket
        if($params['host'] != '') {
            $str = 'host=\''.$params['host'].'\''.$str;
            if (isset($params['port']) && $params['port']) {
                $str .= ' port=\''.$params['port'].'\'';
            }
        }

        if ($params['database'] != '') {
            $str .= ' dbname=\''.$params['database'].'\'';
        }

        // we do isset instead of equality test against an empty string, to allow to specify
        // that we want to use configuration set in environment variables
        if (isset($params['user'])) {
            $str .= ' user=\''.$params['user'].'\'';
        }

        if (isset($params['password'])) {
            $str .= ' password=\''.$params['password'].'\'';
        }

        if (isset($params['timeout']) && $params['timeout'] != '') {
            $str .= ' connect_timeout=\''.$params['timeout'].'\'';
        }

        if ($cnx = @pg_connect ($str)) {
            pg_close($cnx);
        }
        else {
            throw new Exception($this->locales['error.no.connection']);
        }
        return true;
    }

    protected function check_sqlite($params) {
        if(!function_exists('sqlite_open')) {
            throw new Exception($this->locales['error.extension.sqlite.not.installed']);
        }
        if ($cnx = @sqlite_open (jApp::varPath('db/sqlite/'.$params['database']))) {
            sqlite_close($cnx);
        }
        else {
            throw new Exception($this->locales['error.no.connection']);
        }
        return true;
    }

    protected function check_sqlite3($params) {
        $db = $params['database'];
        if ($db[0] == '/') {
            $path = $db;
        }
        else if (preg_match('/^(app|lib|var)\:/', $db)) {
            $path = jFile::parseJelixPath( $db );
        }
        else {
            $path = jApp::varPath('db/sqlite3/'.$db);
        }

        try {
            $sqlite = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $sqlite->close();
        }
        catch(Exception $e) {
            throw new Exception($this->locales['error.no.connection']);
        }
        return true;
    }

    protected function check_PDO($params) {
        if ($params['dbtype'] == 'sqlite') {
            $user = '';
            $password = '';
        }
        else {
            if (isset($params['port']) && $params['port'])
                $dsn.= ';port='.$params['port'];
            $user = $params['user'];
            $password = $params['password'];
        }

        $pdo = new PDO($params['dsn'], $user, $password, $params['pdooptions']);
        $pdo = null;
    }

}
