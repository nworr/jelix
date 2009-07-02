<?php
/**
* @package     jelix-scripts
* @author      Jouanneau Laurent
* @contributor Loic Mathaud
* @copyright   2007 Jouanneau laurent, 2008 Loic Mathaud, 2009 Bastien Jaillot
* @link        http://www.jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/

#if ENABLE_OPTIMIZED_SOURCE
require(JELIX_LIB_PATH.'dao/jDaoCompiler.class.php'); // jDaoParser is in jDaoCompiler file
#endif

class createformCommand extends JelixScriptCommand {

    public  $name = 'createform';
    public  $allowed_options=array();
    public  $allowed_parameters=array('module'=>true,'form'=>true, 'dao'=>false);

    public  $syntaxhelp = "MODULE FORM [DAO]";
    public  $help=array(
        'fr'=>"
    Crée un nouveau fichier jforms, soit vide, soit un formulaire à partir d'un fichier dao

    MODULE: nom du module concerné.
    FORM : nom du formulaire.
    DAO   : sélecteur du dao concerné. Si non indiqué, le fichier jforms sera vide.",

        'en'=>"
    Create a new jforms file, from a jdao file.

    MODULE : module name where to create the form
    FORM : name of the form
    DAO    : selector of the dao on which the form will be based. If not given, the jforms file will be empty",
    );


    public function run(){

        jxs_init_jelix_env();

        $path= $this->getModulePath($this->_parameters['module']);

        $filename= $path.'forms/';
        $this->createDir($filename);

        $filename.=strtolower($this->_parameters['form']).'.form.xml';

        $submit="\n\n<submit ref=\"_submit\">\n\t<label>ok</label>\n</submit>";

        if(($dao = $this->getParam('dao')) === null) {
            $this->createFile($filename,'form.xml.tpl', array('content'=>'<!-- add control declaration here -->'.$submit));
            return;
        }
        global $gJConfig;
        $gJConfig->startModule = $this->_parameters['module'];
        jContext::push($this->_parameters['module']);

        $tools = jDb::getTools();
        
        // we're going to parse the dao
        $selector = new jSelectorDao($dao,'');

        $doc = new DOMDocument();
        $daoPath = $selector->getPath();
        
        if(!$doc->load($daoPath)){
           throw new jException('jelix~daoxml.file.unknow', $daoPath);
        }

        if($doc->documentElement->namespaceURI != JELIX_NAMESPACE_BASE.'dao/1.0'){
           throw new jException('jelix~daoxml.namespace.wrong',array($daoPath, $doc->namespaceURI));
        }

        $parser = new jDaoParser ($selector);
        $parser->parse(simplexml_import_dom($doc), $tools);

        // know we generate the form file

        $properties = $parser->GetProperties();
        $table = $parser->GetPrimaryTable();

        $content = '';

        foreach($properties as $name=>$property){
            if( !$property->ofPrimaryTable) {
                continue;
            }
            if($property->isPK && $property->autoIncrement) {
                continue;
            }

            $attr='';
            if($property->required)
                $attr.=' required="true"';

            if($property->defaultValue !== null)
                $attr.=' defaultvalue="'.htmlspecialchars($property->defaultValue).'"';

            if($property->maxlength !== null)
                $attr.=' maxlength="'.$property->maxlength.'"';

            if($property->minlength !== null)
                $attr.=' minlength="'.$property->minlength.'"';

            //if(false)
            //    $attr.=' defaultvalue=""';
            $datatype='';
            $tag = 'input';
            switch($property->unifiedType){
                case 'integer':
                case 'numeric':
                    $datatype='integer';
                    break;
                case 'datetime':
                    $datatype='datetime';
                    break;
                case 'time':
                    $datatype='time';
                    break;
                case 'date':
                    $datatype='date';
                    break;
                case 'double':
                case 'float':
                    $datatype='decimal';
                    break;
                case 'text':
                case 'blob':
                    $tag='textarea';
                    break;
                case 'boolean':
                    $tag='checkbox';
                    break;
            }
            if($datatype != '')
                $attr.=' type="'.$datatype.'"';

            $content.="\n\n<$tag ref=\"$name\"$attr>\n\t<label>".ucwords(str_replace('_',' ',$name))."</label>\n</$tag>";
        }
        $this->createFile($filename,'form.xml.tpl', array('content'=>$content.$submit));

    }
}

