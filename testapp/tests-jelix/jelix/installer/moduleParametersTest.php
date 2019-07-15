<?php
require_once (JELIX_LIB_PATH.'installer/jInstallerApplication.class.php');
require_once (JELIX_LIB_PATH.'core/jConfigCompiler.class.php');


class moduleParametersTest extends PHPUnit_Framework_TestCase
{

    function getSerializedToUnserialized() {
        return array(
            array(
                '',
                array()
            ),
            array(
                'foo',
                array('foo'=>true)
            ),
            array(
                'foo=',
                array('foo'=>'')
            ),
            array(
                'foo;bar',
                array('foo'=>true, 'bar'=>true)
            ),
            array(
                'foo=abc',
                array('foo'=>'abc')
            ),
            array(
                'foo=abc;bar;baz=2',
                array('foo'=>'abc', 'bar'=>true, 'baz'=>2)
            ),
            array(
                'foo=abc;bar=a,b,c;baz=2',
                array('foo'=>'abc', 'bar'=>array('a','b','c'), 'baz'=>2)
            ),
            array(
                'foo=abc;bar=[a,b,c];baz=2',
                array('foo'=>'abc', 'bar'=>array('a','b','c'), 'baz'=>2)
            ),
            array(
                'foo=abc;bar=[a];baz=2',
                array('foo'=>'abc', 'bar'=>array('a'), 'baz'=>2)
            ),
            array(
                'foo=false',
                array('foo'=>false)
            ),
            array(
                array('foo'=>'true', 'bar'=>true),
                array('foo'=>true, 'bar'=>true)
            ),
            array(
                array('foo'=>'abc'),
                array('foo'=>'abc')
            ),
            array(
                array('foo'=>'abc', 'bar'=>true, 'baz'=>2),
                array('foo'=>'abc', 'bar'=>true, 'baz'=>2)
            ),
            array(
                array('foo'=>'abc', 'bar'=>'[a,b,c]', 'baz'=>2),
                array('foo'=>'abc', 'bar'=>array('a','b','c'), 'baz'=>2)
            ),
            array(
                array('foo'=>'false'),
                array('foo'=>false)
            ),
        );
    }


    /**
     * @dataProvider getSerializedToUnserialized
     */
    function testUnserialize($serialized, $expected) {
        $this->assertEquals(
            $expected,
            \Jelix\Installer\ModuleStatus::unserializeParameters($serialized)
        );
    }

    function getUnserializedToSerializedAsString() {
        return array(
            array(
                array(),
                ''
            ),
            array(
                array('foo'=>true),
                'foo'
            ),
            array(
                array('foo'=>''),
                'foo='
            ),
            array(
                array('foo'=>true, 'bar'=>true),
                'foo;bar'
            ),
            array(
                array('foo'=>true, 'bar'=>'true'),
                'foo;bar',
            ),
            array(
                array('foo'=>'abc'),
                'foo=abc'
            ),
            array(
                array('foo'=>'abc', 'bar'=>true, 'baz'=>2),
                'foo=abc;bar;baz=2',
            ),
            array(
                array('foo'=>'abc', 'bar'=>array('a','b','c'), 'baz'=>2),
                'foo=abc;bar=[a,b,c];baz=2'
            ),
            array(
                array('foo'=>'abc', 'bar'=>array('a'), 'baz'=>2),
                'foo=abc;bar=[a];baz=2',
            ),
            array(
                array('foo'=>false),
                'foo=false',
            ),
        );
    }

    /**
     * @dataProvider getUnserializedToSerializedAsString
     */
    function testSerializeAsString($data, $expectedSerialized) {
        $this->assertEquals(
            $expectedSerialized,
            \Jelix\Installer\ModuleStatus::serializeParametersAsString($data)
        );
    }

    function getUnserializedToSerializedAsArray() {
        return array(
            array(
                array(),
                array(),
            ),
            array(
                array('foo'=>true),
                array('foo'=>'true'),
            ),
            array(
                array('foo'=>''),
                array('foo'=>''),
            ),
            array(
                array('foo'=>true, 'bar'=>true),
                array('foo'=>'true', 'bar'=>'true'),
            ),
            array(
                array('foo'=>true, 'bar'=>'true'),
                array('foo'=>'true', 'bar'=>'true'),
            ),
            array(
                array('foo'=>'abc'),
                array('foo'=>'abc'),
            ),
            array(
                array('foo'=>'abc', 'bar'=>true, 'baz'=>2),
                array('foo'=>'abc', 'bar'=>'true', 'baz'=>2),
            ),
            array(
                array('foo'=>'abc', 'bar'=>array('a','b','c'), 'baz'=>2),
                array('foo'=>'abc', 'bar'=>'[a,b,c]', 'baz'=>2),
            ),
            array(
                array('foo'=>'abc', 'bar'=>array('a'), 'baz'=>2),
                array('foo'=>'abc', 'bar'=>'[a]', 'baz'=>2),
            ),
            array(
                array('foo'=>false),
                array('foo'=>'false'),
            ),
        );
    }

    /**
     * @dataProvider getUnserializedToSerializedAsArray
     */
    function testSerializeAsArray($data, $expectedSerialized) {
        $this->assertEquals(
            $expectedSerialized,
            \Jelix\Installer\ModuleStatus::serializeParametersAsArray($data)
        );
    }
}