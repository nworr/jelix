# Changes into Jelix 2.0

- minimum PHP version is 7.3.0

- Many Jelix classes are now under a namespace, but some classes with old names
  still exist to ease the transition, although it is recommended to use new name
  as these old classes are deprecated.

- `jApp::coord()` is replaced by `\Jelix\Core\App::router()`

- Jelix config is able to read namespaces declaration as in composer.json

- project.xml is replaced by jelix-app.json
- module.xml is replaced by composer.json
- Installer internal API have been changed

- module.xml: 'creator' and 'contributor' elements changed to 'author'
- module.xml: 'minversion' and 'maxversion' are changed to 'version'
    Same syntax in this new attribute as in composer.json

- Composer package: module name are now normalized. The module name is now the
  package name with the `/` replaced by `_`. Except if the module name is
  indicated into the composer file in `composer.extra.jelix.moduleName`.

- Remove support of infoIDSuffix from jelix-scripts.ini files

## changes in jDb

jDb is now relying on [JelixDatabase](https://github.com/jelix/JelixDatabase).
The `jDb` class is still existing, but most of internal classes of jDb
are gone and replaced by classes of JelixDatabase:

- `jDbConnection` and `jDbPDOConnection` are replaced by objects implementing `Jelix\Database\ConnectionInterface`
- `jDbResultSet` and `jDbPDOResultSet` are replaced by objects implementing `Jelix\Database\ResultSetInterface`
- `jDbParameters` is deprecated and replaced by `\Jelix\Database\AccessParameters`
- `jDbTools` is  replaced by objects implementing `Jelix\Database\Schema\SqlToolsInterface`
- `jDbSchema` is replaced by objects implementing `Jelix\Database\Schema\SchemaInterface`
- `jDbIndex`, `jDbConstraint`, `jDbUniqueKey`, `jDbPrimaryKey`, `jDbReference`,
  `jDbColumn`, `jDbTable` are replaced by some classes of the `Jelix\Database\Schema\` namespace.
- `jDbUtils::getTools()` is deprecated and is replaced by `\Jelix\Database\Connection::getTools()` 
- `jDbWidget` is deprecated and replaced by `Jelix\Database\Helpers`
- `jDaoDbMapper::createTableFromDao()` returns an object `\Jelix\Database\Schema\TableInterface` instead of `jTable`

Plugins for jDb (aka "drivers"), implementing connectors etc, are not supported
anymore.

All error messages are now only in english. No more `jelix~db.*` locales.

## changes in jDao

jDao is now relying on [JelixDao](https://github.com/jelix/JelixDao).
The `jDao` class is still the main class to use to load and use Dao.
Some internal classes are gone.

- `jDaoFactoryBase` is replaced by objects implementing `Jelix\Dao\DaoFactoryInterface`
- `jDaoRecordBase` is replaced by objects implementing `Jelix\Dao\DaoRecordInterface`
- `jDaoGenerator` and `jDaoParser` are removed
- `jDaoMethod` is replaced by `Jelix\Dao\Parser\DaoMethod`
- `jDaoProperty` is replaced by `Jelix\Dao\Parser\DaoProperty`
- `jDaoConditions` and `jDaoCondition` are deprecated and replaced by 
  `\Jelix\Dao\DaoConditions` and `\Jelix\Dao\DaoCondition`.
- `jDaoXmlException` is deprecated. The parser generates `Jelix\Dao\Parser\ParserException` instead.

New classes:

- `jDaoContext`
- `jDaoHooks`


Plugins for jDaoCompiler (type 'daobuilder'), are not supported anymore.

All error messages are now only in english. No more `jelix~daoxml.*` and `jelix~dao.*` locales.

## test environment

- Vagrant environment has been removed.
- upgrade PHPUnit to 8.5.0


## internal


## removed classes and methods

- `jJsonRpc`