<?php
/**
 * @package     jelix
 * @subpackage  master_admin
 * @author      Laurent Jouanneau
 * @copyright   2017-2018 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

class master_adminModuleUpgrader_webassets extends \Jelix\Installer\Module\Installer {

    protected $targetVersions = array('1.7.0-beta.2');

    protected $date = '2017-02-07 18:05';

    function install() {
        $this->declareGlobalWebAssets('master_admin', array('css'=>array('design/master_admin.css')), 'common', false);
    }
}
