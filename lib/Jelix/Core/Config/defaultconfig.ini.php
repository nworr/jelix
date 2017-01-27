;<?php die(''); ?>
;for security reasons , don't remove or modify the first line

; the default locale used in the application
locale=en_US

; the locales available in the application
availableLocales=en_US

; the locale to fallback when the asked string doesn't exist in the current locale
fallbackLocale=en_US

; the charset used in the application
charset=UTF-8

; the default theme
theme=default

; set "1.0" or "1.1" if you want to force an HTTP version
httpVersion=

; see http://www.php.net/manual/en/timezones.php for supported values
; if empty, jelix will try to get the default timezone
timeZone=

; Default domain name to use with jfullurl for example.
; Let it empty to use $_SERVER['SERVER_NAME'] value instead.
; For cli script, fill it.
domainName=

; indicate HTTP(s) port if it should be forced to a specific value that PHP cannot
; guess (if the application is behind a proxy on a specific port for example)
; true for default port, or a number for a specific port. leave empty to use the
; current server port.
forceHTTPPort=
forceHTTPSPort=

; chmod for files created by Jelix
chmodFile=0664
chmodDir=0775

; ---  don't set the following options to on, except if you know what you do

; disable all installers and the installer.ini.php
; useful only if you manage the installation of modules by hands (not recommanded)
disableInstallers=off
; if set to on, all modules have an access=2, and access values in [modules] are not readed (not recommanded)
enableAllModules=off

[modules]
; modulename.access = x   where x : 0= unused/forbidden, 1 = private access, 2 = public access

jelix.access=2
jelix.path="lib:jelix/core-modules/jelix"

; jacldb is deprecated. keep it uninstall if possible
jacldb.access=0


[coordplugins]

[tplplugins]
defaultJformsBuilder=html

[responses]
html=jResponseHtml
basichtml=jResponseBasicHtml
redirect=jResponseRedirect
redirectUrl=jResponseRedirectUrl
binary=jResponseBinary
text=jResponseText
cmdline=jResponseCmdline
jsonrpc=jResponseJsonrpc
json=jResponseJson
xmlrpc=jResponseXmlrpc
xml=jResponseXml
zip=jResponseZip
css=jResponseCss
htmlfragment=jResponseHtmlFragment
htmlauth=jResponseHtml

[_coreResponses]
html=jResponseHtml
basichtml=jResponseBasicHtml
redirect=jResponseRedirect
redirectUrl=jResponseRedirectUrl
binary=jResponseBinary
text=jResponseText
cmdline=jResponseCmdline
jsonrpc=jResponseJsonrpc
json=jResponseJson
xmlrpc=jResponseXmlrpc
xml=jResponseXml
zip=jResponseZip
css=jResponseCss
htmlfragment=jResponseHtmlFragment
htmlauth=jResponseHtml

[jResponseHtml]
; list of active plugins for jResponseHtml
plugins=

minifyCSS=off
minifyJS=on
minifyExcludeCSS=
minifyExcludeJS="jelix/wymeditor/jquery.wymeditor.js"
minifyEntryPoint=minify.php

[debugbar]
plugins="sqllog,sessiondata,defaultlog"
defaultPosition=right
errors_openon=error

[error_handling]
messageLogFormat="%date%\t%ip%\t[%code%]\t%msg%\t%file%\t%line%\n\t%url%\n%params%\n%trace%\n\n"
errorMessage="A technical error has occured (code: %code%). Sorry for this inconvenience."

[compilation]
checkCacheFiletime=on
force=off

[urlengine]
; enable the parsing of the url. Set it to off if the url is already parsed by another program
; (like mod_rewrite in apache)
enableParser=on

; if multiview is activated in apache, eg, you don't have to indicate the ".php" suffix
; then set this parameter to on
multiview=off

; the name of the variable in $_SERVER which contains the name of the script
; example : if the you call http://mysite.com/foo/index.php, this is the variable
; which contains "/foo/index.php"
; This name can be SCRIPT_NAME, ORIG_SCRIPT_NAME, PHP_SELF or REDIRECT_SCRIPT_URL
; it is detected automatically by jelix but it can fail sometime, so you could have to setup it
scriptNameServerVariable=


; If you have a rewrite rules which move the pathinfo into a queryparameter
; like RewriteRule ^(.*)$ index.php/?jpathinfo=$1 [L,QSA]
; (it is necessary in some CGI configuration)
; then you should set pathInfoInQueryParameter to the name of the parameter
; which contains the pathinfo value ("jpathinfo" for example)
; leave empty if you don't have to create such rewrite rules.
pathInfoInQueryParameter=

; basePath corresponds to the path to the base directory of your application.
; so if the url to access to your application is http://foo.com/aaa/bbb/www/index.php, you should
; set basePath = "/aaa/bbb/www/".
; if it is http://foo.com/index.php, set basePath="/"
; Jelix can guess the basePath, so you can keep basePath empty. But in the case where there are some
; entry points which are not in the same directory (ex: you have two entry point : http://foo.com/aaa/index.php
; and http://foo.com/aaa/bbb/other.php ), you MUST set the basePath (ex here, the higher entry point is index.php so
; : basePath="/aaa/" )
basePath=


; backendBasePath is used when the application is behind a proxy, and when the base path on the frontend
; server doesn't correspond to the base path on the backend server.
; you MUST define basePath when you define backendBasePath
backendBasePath=

; for an app on a simple http server behind an https proxy, the https verification
; should be disabled
checkHttpsOnParsing=on

; this is the url path to the jelix-www content (you can found this content in lib/jelix-www/)
; because the jelix-www directory is outside the yourapp/www/ directory, you should create a link to
; jelix-www, or copy its content in yourapp/www/ (with a name like 'jelix' for example)
; so you should indicate the relative path of this link/directory to the basePath, or an absolute path.
; if you change it, change also all pathes in [htmleditors]
; at runtime, it contains the absolute path (basePath+the value) if you give a relative path
jelixWWWPath="jelix/"

; DEPRECATED. if you modify it, modify also its path into webassets
; see below in webassets sections
jqueryPath="jelix/jquery/"

; action to show the 'page not found' error
notfoundAct="jelix~error:notfound"

significantFile=urls.xml

; filled automatically by jelix
urlScript=
urlScriptPath=
urlScriptName=
urlScriptId=
urlScriptIdenc=
documentRoot=

[logger]
; list of loggers for each categories of log messages
; available loggers : file, syslog, firebug, mail, memory. see plugins for others

; _all category is the category containing loggers executed for any categories
_all=

; default category is the category used when a given category is not declared here
default=file
error=file
warning=file
notice=file
deprecated=
strict=
debug=
sql=
soap=

; log files for categories which have "file"
[fileLogger]
default=messages.log
error=errors.log
warning=errors.log
notice=errors.log
deprecated=errors.log
strict=errors.log
debug=debug.log

[memorylogger]
; number of messages to store in memory for each categories, to avoid memory issues
default=20
error=10
warning=10
notice=10
deprecated=10
strict=10
debug=20
sql=20
soap=20

[mailLogger]
email="root@localhost"
emailHeaders="Content-Type: text/plain; charset=UTF-8\nFrom: webmaster@yoursite.com\nX-Mailer: Jelix\nX-Priority: 1 (Highest)\n"

[syslogLogger]
facility=LOG_LOCAL7
ident="php-%sapi%-%domain%[%pid%]"


[mailer]
webmasterEmail="root@localhost"
webmasterName=

; How to send mail : "mail" (mail()), "sendmail" (call sendmail), "smtp" (send directly to a smtp)
;                   or "file" (store the mail into a file, in filesDir directory)
mailerType=mail
; Sets the hostname to use in Message-Id and Received headers
; and as default HELO string. If empty, the value returned
; by SERVER_NAME is used or 'localhost.localdomain'.
hostname=
sendmailPath="/usr/sbin/sendmail"

; if mailer = file, fill the following parameters
; this should be the directory in the var/ directory, where to store mail as files
filesDir="mails/"

; if mailer = smtp , fill the following parameters

; SMTP hosts.  All hosts must be separated by a semicolon : "smtp1.example.com:25;smtp2.example.com"
smtpHost=localhost
; default SMTP server port
smtpPort=25
; secured connection or not. possible values: "", "ssl", "tls"
smtpSecure=
; SMTP HELO of the message (Default is hostname)
smtpHelo=
; SMTP authentication
smtpAuth=off
smtpUsername=
smtpPassword=
; SMTP server timeout in seconds
smtpTimeout=10

copyToFiles=off

[acl]
; exemple of driver: "db".
driver=

[acl2]
; exemple of driver: "db"
driver=



[sessions]
; to disable sessions, set the following parameter to 0
start=1

; If several applications are installed in the same documentRoot but with
; a different basePath, shared_session indicates if these application
; share the same php session
shared_session=off

; indicate a session name for each applications installed with the same
; domain and basePath, if their respective sessions shouldn't be shared
name=

;
; Use alternative storage engines for sessions
; empty value means the default storage engine of PHP
storage=

; some additionnal options can be set, depending of the type of storage engine
;
; storage = "files"
; files_path = "app:var/sessions/"
;
; or
;
; storage = "dao"
; dao_selector = "jelix~jsession"
; dao_db_profile = ""

; list of selectors of classes to load before the session_start
; @deprecated please use autoload configuration in jelix-app.json/module.xml files instead
loadClasses=

[forms]
; define input type for datetime widgets : "textboxes" or "menulists"
controls.datetime.input=menulists
; define the way month labels are displayed widgets: "numbers", "names" or "shortnames"
controls.datetime.months.labels=names
; define the configuration name to use for datepickers in jforms
; the name is the suffix of the jforms_datepicker_<config> web assets group
; and the suffix of a jelix_datepicker_<config> function from one of the web
; assets js file that initialise the datepicker
datepicker=default

; same as datepicker but for date/time pickers.
; value is suffix for jforms_datetimepicker_<config> web assets group and
; a jelix_datetimepicker_<config> function
datetimepicker=default

[jforms_builder_html]
;control type = plugin name

[htmleditors]
default.engine.name=wymeditor
wymbasic.engine.name=wymeditor
ckdefault.engine.name=ckeditor
ckfull.engine.name=ckeditor
ckbasic.engine.name=ckeditor

[wikieditors]
default.engine.name=wr3
default.wiki.rules=wr3_to_xhtml

[webassets]
useSet=common

[webassets_common]
jquery.js = "$jelix/jquery/jquery.js"

jquery_ui.js = "$jelix/jquery/ui/jquery-ui-core-widg-mous-posi.custom.min.js"
jquery_ui.css = "$jelix/jquery/themes/base/jquery.ui.all.css"
jquery_ui.require = jquery

jforms_html.js[]= "$jelix/jquery/include/jquery.include.js"
jforms_html.js[]= "$jelix/js/jforms_jquery.js"
jforms_html.css= "$jelix/design/jform.css"
jforms_html.require = jquery

jforms_html_light.js= "$jelix/js/jforms_light.js"
jforms_html_light.css= "$jelix/design/jform.css"

jforms_datepicker_default.css=
jforms_datepicker_default.js[]="$jelix/jquery/ui/jquery.ui.datepicker.min.js"
jforms_datepicker_default.js[]="$jelix/jquery/ui/i18n/jquery.ui.datepicker-$lang.js"
jforms_datepicker_default.js[]="$jelix/js/jforms/datepickers/default/init.js"
jforms_datepicker_default.require=jquery_ui

; we don't have yet a real datetime picker
jforms_datetimepicker_default.require=jforms_datepicker_default
jforms_datetimepicker_default.js="$jelix/js/jforms/datetimepickers/default/init.js"

jforms_htmleditor_default.js[]="$jelix/wymeditor/jquery.wymeditor.js"
jforms_htmleditor_default.js[]="$jelix/js/jforms/htmleditors/wymeditor_default.js"
jforms_htmleditor_default.require=jquery

jforms_htmleditor_default.skin.default.css="$jelix/wymeditor/skins/default/skin.css"

jforms_htmleditor_wymbasic.js[]="$jelix/wymeditor/jquery.wymeditor.js"
jforms_htmleditor_wymbasic.js[]="$jelix/js/jforms/htmleditors/wymeditor_basic.js"
jforms_htmleditor_wymbasic.require=jquery

jforms_htmleditor_wymbasic.skin.default.css="$jelix/wymeditor/skins/default/skin.css"


jforms_htmleditor_ckdefault.js[]="$jelix/ckeditor/ckeditor.js"
jforms_htmleditor_ckdefault.js[]="$jelix/js/jforms/htmleditors/ckeditor_default.js"

jforms_htmleditor_ckfull.js[]="$jelix/ckeditor/ckeditor.js"
jforms_htmleditor_ckfull.js[]="$jelix/js/jforms/htmleditors/ckeditor_full.js"

jforms_htmleditor_ckbasic.js[]="$jelix/ckeditor/ckeditor.js"
jforms_htmleditor_ckbasic.js[]="$jelix/js/jforms/htmleditors/ckeditor_basic.js"

jforms_wikieditor_default.js[]="$jelix/markitup/jquery.markitup.js"
jforms_wikieditor_default.js[]="$jelix/markitup/sets/wr3/$locale.js"
jforms_wikieditor_default.css[]="$jelix/markitup/skins/simple/style.css"
jforms_wikieditor_default.css[]="$jelix/markitup/sets/wr3/style.css"
jforms_wikieditor_default.require=jquery

swjs.js = "$jelix/jquery/flash/jquery.flash.js"
swjs.require = jquery

[zones]
; disable zone caching
disableCache=off

[classbindings]
; bindings for class and interfaces : selector_of_class/iface = selector_of_implementation

[imagemodifier]
; set this parameters if images and their cache are on an other website (but on the same server)
; the url from which we can display images (basepath excluded). default = current host
; if you set this parameter, you MUST set src_path
src_url=
; the path on the file system, to the directory where images are stored (the www directory of the other application. default = App::wwwPath()
src_path=
; the url from which we can display images cache. default = current host + basepath + 'cache/images/'
; if you set this parameter, you MUST set cache_path
cache_url=
; the path on the file system, to the directory where images cache are stored. default = App::wwwPath()
cache_path=


[rootUrls]
; This section associates keywords with root URLs.
; A root url starting with "http://" or "https://" or "/" is supposed to be absolute
; Other values will be prefixed by application's basePath
; This will be used by jUrl::getRootUrl() and jTpl's {jrooturl}
jelix.cache="cache/"

[langToLocale]
; overrides of lang_to_locale.ini.php

[disabledListeners]
; list of Jelix\Event listener to not call
; eventname[]="module~listenerName"

[mimeTypes]
;list of mime types for some file extension. ext=mime type

[coordplugin_auth]
; key to use to crypt the password in the cookie
; Warning: the value of this parameter should be stored into localconfig.ini.php
persistant_encryption_key=

