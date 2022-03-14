<?php
/**
 * @package      jelix
 * @subpackage   core_config_plugin
 *
 * @author       Laurent Jouanneau
 * @copyright    2022 Laurent Jouanneau
 *
 * @link         https://jelix.org
 * @licence      GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */


class assetsrevisionConfigCompilerPlugin implements \jelix\core\ConfigCompilerPluginInterface {

    function getPriority()
    {
        return 17;
    }

    function atStart($config)
    {
        if ($config->urlengine['assetsRevision'] == 'autoconfig') {
            $config->urlengine['assetsRevision'] = date('ymdHis');
        }
        if ($config->urlengine['assetsRevision'] != '') {
            $config->urlengine['assetsRevQueryUrl'] = $config->urlengine['assetsRevisionParameter'] .'=' . $config->urlengine['assetsRevision'];
        }
        else {
            $config->urlengine['assetsRevQueryUrl'] = '';
        }
    }

    function onModule($config, $moduleName, $modulePath, $xml)
    {

    }

    function atEnd($config)
    {

    }
}
