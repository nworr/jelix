;<?php die(''); ?>
;for security reasons , don't remove or modify the first line

[coordplugins]
auth=auth_ws.coord.ini.php
jacl2=1

[responses]
soap="jsoap~jResponseSoap"


[jacl2]
on_error=1
error_message="jacl2~errors.action.right.needed"
on_error_action="jelix~error:badright"
