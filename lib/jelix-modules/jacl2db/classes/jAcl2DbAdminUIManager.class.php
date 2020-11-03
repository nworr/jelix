<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Julien Issler, Olivier Demah
 *
 * @copyright   2008-2019 Laurent Jouanneau
 * @copyright   2009 Julien Issler
 * @copyright   2010 Olivier Demah
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public Licence, see LICENCE file
 */
class jAcl2DbAdminUIManager
{
    const FILTER_GROUP_ALL_USERS = -2;
    const FILTER_USERS_NO_IN_GROUP = -1;
    const FILTER_BY_GROUP = 0;

    protected function getLabel($id, $labelKey)
    {
        if ($labelKey) {
            try {
                return jLocale::get($labelKey);
            } catch (Exception $e) {
            }
        }

        return $id;
    }

    /**
     * @return array
     *               'groups' : list of jacl2group objects (id_aclgrp, name, grouptype, ownerlogin)
     *               'rights' : array( <role> => array( <id_aclgrp> => 'y' or 'n' or ''))
     *               'rolegroups_localized' : list of labels of each roles groups
     *               'roles' : array( <role> => array( 'grp' => <id_aclsbjgrp>, 'label' => <label>))
     *               'sbjgroups_localized' : same as 'rolegroups_localized', depreacted
     *               'subjects' :same as 'roles', deprecated
     *               'rightsWithResources':  array(<role> => array( <id_aclgrp> => <number of rights>))
     */
    public function getGroupRights()
    {
        $gid = array('__anonymous');
        $o = new StdClass();
        $o->id_aclgrp = '__anonymous';

        try {
            $o->name = jLocale::get('jacl2db_admin~acl2.anonymous.group.name');
        } catch (Exception $e) {
            $o->name = 'Anonymous';
        }
        $o->grouptype = jAcl2DbUserGroup::GROUPTYPE_NORMAL;
        $o->ownerlogin = null;

        $daorights = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');
        $rightsWithResources = array();
        $hiddenRights = $this->getHiddenRights();

        // retrieve the list of groups and the number of existing rights with
        // resource for each groups
        $groups = array($o);
        $grouprights = array('__anonymous' => false);
        foreach (jAcl2DbUserGroup::getGroupList() as $grp) {
            $gid[] = $grp->id_aclgrp;
            $groups[] = $grp;
            $grouprights[$grp->id_aclgrp] = '';

            $rs = $daorights->getRightsHavingRes($grp->id_aclgrp);
            foreach ($rs as $rec) {
                if (in_array($rec->id_aclsbj, $hiddenRights)) {
                    continue ;
                }
                if (!isset($rightsWithResources[$rec->id_aclsbj])) {
                    $rightsWithResources[$rec->id_aclsbj] = array();
                }
                if (!isset($rightsWithResources[$rec->id_aclsbj][$grp->id_aclgrp])) {
                    $rightsWithResources[$rec->id_aclsbj][$grp->id_aclgrp] = 0;
                }
                ++$rightsWithResources[$rec->id_aclsbj][$grp->id_aclgrp];
            }
        }

        // retrieve the number of existing rights with
        // resource for the anonymous group
        $rs = $daorights->getRightsHavingRes('__anonymous');
        foreach ($rs as $rec) {
            if (!isset($rightsWithResources[$rec->id_aclsbj])) {
                $rightsWithResources[$rec->id_aclsbj] = array();
            }
            if (!isset($rightsWithResources[$rec->id_aclsbj]['__anonymous'])) {
                $rightsWithResources[$rec->id_aclsbj]['__anonymous'] = 0;
            }
            ++$rightsWithResources[$rec->id_aclsbj]['__anonymous'];
        }

        // create the list of subjects and their labels
        $rights = array();
        $sbjgroups_localized = array();
        $subjects = array();
        $rs = jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->findAllSubject();
        $hiddenRights = $this->getHiddenRights();
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            $rights[$rec->id_aclsbj] = $grouprights;
            $subjects[$rec->id_aclsbj] = array(
                'grp' => $rec->id_aclsbjgrp,
                'label' => $this->getLabel($rec->id_aclsbj, $rec->label_key),
            );
            if ($rec->id_aclsbjgrp && !isset($sbjgroups_localized[$rec->id_aclsbjgrp])) {
                $sbjgroups_localized[$rec->id_aclsbjgrp] = $this->getLabel($rec->id_aclsbjgrp, $rec->label_group_key);
            }
            if (!isset($rightsWithResources[$rec->id_aclsbj])) {
                $rightsWithResources[$rec->id_aclsbj] = array();
            }
        }

        // retrieve existing rights
        $rs = jDao::get('jacl2db~jacl2rights', 'jacl2_profile')->getRightsByGroups($gid);
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            $rights[$rec->id_aclsbj][$rec->id_aclgrp] = ($rec->canceled ? 'n' : 'y');
        }

        $roles = $subjects;
        $rolegroups_localized = $sbjgroups_localized;

        return compact('groups', 'rights', 'sbjgroups_localized', 'subjects', 'roles', 'rolegroups_localized', 'rightsWithResources');
    }

    /**
     * @param mixed $groupid
     *
     * @return array
     *               'roles_localized' : list of labels of each roles
     *               'subjects_localized' : same as 'roles_localized', deprecated
     *               'rightsWithResources':  array(<role> => array( <jacl2rights objects (id_aclsbj, id_aclgrp, id_aclres, canceled>))
     *               'hasRightsOnResources' : true if there are some resources
     */
    public function getGroupRightsWithResources($groupid)
    {
        $rightsWithResources = array();
        $hiddenRights = $this->getHiddenRights();
        $daorights = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');

        $rs = $daorights->getRightsHavingRes($groupid);
        $hasRightsOnResources = false;
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            if (!isset($rightsWithResources[$rec->id_aclsbj])) {
                $rightsWithResources[$rec->id_aclsbj] = array();
            }
            $rightsWithResources[$rec->id_aclsbj][] = $rec;
            $hasRightsOnResources = true;
        }
        $subjects_localized = array();
        if (!empty($rightsWithResources)) {
            $conditions = jDao::createConditions();
            $conditions->addCondition('id_aclsbj', 'in', array_keys($rightsWithResources));
            foreach (jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->findBy($conditions) as $rec) {
                $subjects_localized[$rec->id_aclsbj] = $this->getLabel($rec->id_aclsbj, $rec->label_key);
            }
        }
        $roles_localized = $subjects_localized;

        return compact('roles_localized', 'subjects_localized', 'rightsWithResources', 'hasRightsOnResources');
    }

    /**
     * @param array      $rights
     *                                array(<id_aclgrp> => array( <id_aclsbj> => (bool, 'y', 'n' or '')))
     * @param null|mixed $sessionUser
     *
     * @see jAcl2DbManager::setRightsOnGroup()
     */
    public function saveGroupRights($rights, $sessionUser = null)
    {
        $rights = $this->addHiddenRightsValues($rights);
        $checking = jAcl2DbManager::checkAclAdminRightsChanges($rights, $sessionUser);
        if ($checking === jAcl2DbManager::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM) {
            throw new jAcl2DbAdminUIException("Changes cannot be applied: You won't be able to change some rights", 3);
        }
        if ($checking === jAcl2DbManager::ACL_ADMIN_RIGHTS_NOT_ASSIGNED) {
            throw new jAcl2DbAdminUIException('Changes cannot be applied: nobody will be able to change some rights', 2);
        }

        foreach (jAcl2DbUserGroup::getGroupList() as $grp) {
            $id = $grp->id_aclgrp;
            jAcl2DbManager::setRightsOnGroup($id, (isset($rights[$id]) ? $rights[$id] : array()));
        }

        jAcl2DbManager::setRightsOnGroup('__anonymous', (isset($rights['__anonymous']) ? $rights['__anonymous'] : array()));
    }

    /**
     * @param string $groupid
     * @param array  $roles   array( <id_aclsbj> => (true (remove), 'on'(remove) or '' (not touch))
     *                        true or 'on' means 'to remove'
     */
    public function removeGroupRightsWithResources($groupid, $roles)
    {
        $rolesToRemove = array();

        foreach ($roles as $sbj => $val) {
            if ($val != '' || $val == true) {
                $rolesToRemove[] = $sbj;
            }
        }
        if (count($rolesToRemove)) {
            jDao::get('jacl2db~jacl2rights', 'jacl2_profile')
                ->deleteRightsOnResource($groupid, $rolesToRemove)
            ;
        }
    }

    /**
     * @param int      $groupFilter  one of FILTER_* const
     * @param null|int $groupId
     * @param string   $userFilter
     * @param int      $offset
     * @param int      $listPageSize
     *
     * @return array 'users': list of objects representing users ( login, and his groups in groups)
     *               'usersCount': total number of users
     */
    public function getUsersList($groupFilter, $groupId = null, $userFilter = '', $offset = 0, $listPageSize = 15)
    {
        $p = 'jacl2_profile';

        // get the number of users and the recordset to retrieve users
        if ($groupFilter == self::FILTER_GROUP_ALL_USERS) {
            //all users
            $dao = jDao::get('jacl2db~jacl2groupsofuser', $p);
            $cond = jDao::createConditions();
            $cond->addCondition('grouptype', '=', jAcl2DbUserGroup::GROUPTYPE_PRIVATE);
            if ($userFilter) {
                $cond->addCondition('login', 'LIKE', '%'.$userFilter.'%');
            }
            $cond->addItemOrder('login', 'asc');
            $rs = $dao->findBy($cond, $offset, $listPageSize);
            $resultsCount = $dao->countBy($cond);
        } elseif ($groupFilter == self::FILTER_USERS_NO_IN_GROUP) {
            //only those who have no groups
            $cnx = jDb::getConnection($p);
            $sql = 'SELECT login, count(id_aclgrp) as nbgrp FROM '.$cnx->prefixTable('jacl2_user_group');
            if ($userFilter) {
                $sql .= ' WHERE login LIKE '.$cnx->quote('%'.$userFilter.'%');
            }

            if ($cnx->dbms != 'pgsql') {
                // with MYSQL 4.0.12, you must use an alias with the count to use it with HAVING
                $sql .= ' GROUP BY login HAVING nbgrp < 2 ORDER BY login';
            } else {
                // But PgSQL doesn't support the HAVING structure with an alias.
                $sql .= ' GROUP BY login HAVING count(id_aclgrp) < 2 ORDER BY login';
            }

            $rs = $cnx->query($sql);
            $resultsCount = $rs->rowCount();
        } else {
            //in a specific group
            $dao = jDao::get('jacl2db~jacl2usergroup', $p);
            if ($userFilter) {
                $rs = $dao->getUsersGroupLimitAndFilter($groupId, '%'.$userFilter.'%', $offset, $listPageSize);
                $resultsCount = $dao->getUsersGroupCountAndFilter($groupId, '%'.$userFilter.'%');
            } else {
                $rs = $dao->getUsersGroupLimit($groupId, $offset, $listPageSize);
                $resultsCount = $dao->getUsersGroupCount($groupId);
            }
        }

        $results = array();
        $dao2 = jDao::get('jacl2db~jacl2groupsofuser', $p);
        foreach ($rs as $u) {
            $u->type = 'user';
            $u->groups = array();
            $gl = $dao2->getGroupsUser($u->login);
            foreach ($gl as $g) {
                if ($g->grouptype != jAcl2DbUserGroup::GROUPTYPE_PRIVATE) {
                    $u->groups[] = $g;
                }
            }
            $u->last = count($u->groups) - 1;
            $results[] = $u;
        }

        return compact('results', 'resultsCount');
    }

    public function getGroupByFilter($filter)
    {
        $filter = '%'.$filter.'%';
        $groups = jDao::get('jacl2db~jacl2group', 'jacl2_profile')->findGroupByFilter($filter)->fetchAll();
        $results = array();
        foreach($groups as $group) {
            $group->login = $group->name;
            $group->type = 'group';
            $group->groups = array();
            $results[] = $group;
        }
        $resultsCount = count($results);

        return compact('results', 'resultsCount');
    }

    /**
     * @param string $user
     *
     * @throws jAcl2DbAdminUIException
     *
     * @return array
     */
    public function getUserRights($user)
    {

        // retrieve user
        $dao = jDao::get('jacl2db~jacl2groupsofuser', 'jacl2_profile');
        $cond = jDao::createConditions();
        $cond->addCondition('login', '=', $user);
        $cond->addCondition('grouptype', '=', jAcl2DbUserGroup::GROUPTYPE_PRIVATE);
        if ($dao->countBy($cond) == 0) {
            throw new jAcl2DbAdminUIException('Invalid user', 1);
        }

        // retrieve groups of the user
        $hisgroup = null;
        $groupsuser = array();
        foreach (jAcl2DbUserGroup::getGroupList($user) as $grp) {
            if ($grp->grouptype == jAcl2DbUserGroup::GROUPTYPE_PRIVATE) {
                $hisgroup = $grp;
            } else {
                $groupsuser[$grp->id_aclgrp] = $grp;
            }
        }

        // retrieve all groups
        $gid = array($hisgroup->id_aclgrp);
        $groups = array();
        $grouprights = array($hisgroup->id_aclgrp => false);
        foreach (jAcl2DbUserGroup::getGroupList() as $grp) {
            $gid[] = $grp->id_aclgrp;
            $groups[] = $grp;
            $grouprights[$grp->id_aclgrp] = '';
        }

        // create the list of subjects and their labels
        $rights = array();
        $subjects = array();
        $sbjgroups_localized = array();
        $rs = jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->findAllSubject();
        $hiddenRights = $this->getHiddenRights();
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            $rights[$rec->id_aclsbj] = $grouprights;
            $subjects[$rec->id_aclsbj] = array(
                'grp' => $rec->id_aclsbjgrp,
                'label' => $this->getLabel($rec->id_aclsbj, $rec->label_key), );
            if ($rec->id_aclsbjgrp && !isset($sbjgroups_localized[$rec->id_aclsbjgrp])) {
                $sbjgroups_localized[$rec->id_aclsbjgrp] =
                    $this->getLabel($rec->id_aclsbjgrp, $rec->label_group_key);
            }
        }

        $rightsWithResources = array_fill_keys(array_keys($rights), 0);
        $daorights = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');
        $hiddenRights = $this->getHiddenRights();

        $rs = $daorights->getRightsHavingRes($hisgroup->id_aclgrp);
        $hasRightsOnResources = false;
        foreach ($rs as $rec) {
            ++$rightsWithResources[$rec->id_aclsbj];
            $hasRightsOnResources = true;
        }

        $rs = $daorights->getRightsByGroups($gid);
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            $rights[$rec->id_aclsbj][$rec->id_aclgrp] = ($rec->canceled ? 'n' : 'y');
        }

        $roles = $subjects;
        $rolegroups_localized = $sbjgroups_localized;

        return compact(
            'hisgroup',
            'groupsuser',
            'groups',
            'rights',
            'user',
            'subjects',
            'roles',
            'sbjgroups_localized',
            'rolegroups_localized',
            'rightsWithResources',
            'hasRightsOnResources'
        );
    }

    public function saveUserRights($login, $userRights, $sessionUser = null)
    {
        $dao = jDao::get('jacl2db~jacl2groupsofuser', 'jacl2_profile');
        $grp = $dao->getPrivateGroup($login);

        $rights = array($grp->id_aclgrp => $userRights);
        $rights = $this->addHiddenRightsValues($rights);

        $checking = jAcl2DbManager::checkAclAdminRightsChanges($rights, $sessionUser, false, true);
        if ($checking === jAcl2DbManager::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM) {
            throw new jAcl2DbAdminUIException("Changes cannot be applied: You won't be able to change some rights", 3);
        }
        if ($checking === jAcl2DbManager::ACL_ADMIN_RIGHTS_NOT_ASSIGNED) {
            throw new jAcl2DbAdminUIException('Changes cannot be applied: nobody will be able to change some rights', 2);
        }
        $userRights = $rights[$grp->id_aclgrp];
        jAcl2DbManager::setRightsOnGroup($grp->id_aclgrp, $userRights);
    }

    public function getUserRessourceRights($user)
    {
        $daogroup = jDao::get('jacl2db~jacl2group', 'jacl2_profile');

        $group = $daogroup->getPrivateGroup($user);

        $rightsWithResources = array();
        $hiddenRights = $this->getHiddenRights();
        $daorights = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');

        $rs = $daorights->getRightsHavingRes($group->id_aclgrp);
        $hasRightsOnResources = false;
        foreach ($rs as $rec) {
            if (in_array($rec->id_aclsbj, $hiddenRights)) {
                continue ;
            }
            if (!isset($rightsWithResources[$rec->id_aclsbj])) {
                $rightsWithResources[$rec->id_aclsbj] = array();
            }
            $rightsWithResources[$rec->id_aclsbj][] = $rec;
            $hasRightsOnResources = true;
        }
        $subjects_localized = array();
        if (!empty($rightsWithResources)) {
            $conditions = jDao::createConditions();
            $conditions->addCondition('id_aclsbj', 'in', array_keys($rightsWithResources));
            foreach (jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->findBy($conditions) as $rec) {
                $subjects_localized[$rec->id_aclsbj] = $this->getLabel($rec->id_aclsbj, $rec->label_key);
            }
        }
        $roles_localized = $subjects_localized;

        return compact('user', 'subjects_localized', 'roles_localized', 'rightsWithResources', 'hasRightsOnResources');
    }

    /**
     * @param $user
     * @param array $roles <id_aclsbj> => (true (remove), 'on'(remove) or '' (not touch)
     */
    public function removeUserRessourceRights($user, $roles)
    {
        $daogroup = jDao::get('jacl2db~jacl2group', 'jacl2_profile');
        $grp = $daogroup->getPrivateGroup($user);

        $rolesToRemove = array();

        foreach ($roles as $sbj => $val) {
            if ($val != '' || $val == true) {
                $rolesToRemove[] = $sbj;
            }
        }

        if (count($rolesToRemove)) {
            jDao::get('jacl2db~jacl2rights', 'jacl2_profile')
                ->deleteRightsOnResource($grp->id_aclgrp, $rolesToRemove)
            ;
        }
    }

    public function removeGroup($groupId, $sessionUser = null)
    {
        $rights = array($groupId => array());
        $checking = jAcl2DbManager::checkAclAdminRightsChanges($rights, $sessionUser, false, true);

        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM) {
            throw new jAcl2DbAdminUIException("Group cannot be removed, else you wouldn't manage acl anymore", 3);
        }
        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_NOT_ASSIGNED) {
            throw new jAcl2DbAdminUIException('Group cannot be removed, else acl management is not possible anymore', 2);
        }
        jAcl2DbUserGroup::removeGroup($groupId);
    }

    public function removeUserFromGroup($login, $groupId, $sessionUser = null)
    {
        $checking = jAcl2DbManager::checkAclAdminRightsChanges(array(), $sessionUser, false, true, $login, $groupId);

        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM) {
            throw new jAcl2DbAdminUIException("User cannot be removed from group, else you wouldn't manage acl anymore", 3);
        }
        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_NOT_ASSIGNED) {
            throw new jAcl2DbAdminUIException('User cannot be removed from group, else acl management is not possible anymore', 2);
        }
        jAcl2DbUserGroup::removeUserFromGroup($login, $groupId);
    }

    public function addUserToGroup($login, $groupId, $sessionUser = null)
    {
        $rightsChanged = array();
        $groupRights = $this->getGroupRights();
        foreach (jAcl2DbManager::$ACL_ADMIN_RIGHTS as $right) {
            if (jAcl2::check($right) && in_array($groupRights['rights'][$right][$groupId], array(null, false))) {
                $rightsChanged[$groupId][$right] = 'n';
            }
        }
        $checking = jAcl2DbManager::checkAclAdminRightsChanges($rightsChanged, $sessionUser);

        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM) {
            throw new jAcl2DbAdminUIException("User cannot be add to group, else you wouldn't manage acl anymore", 3);
        }
        if ($checking == jAcl2DbManager::ACL_ADMIN_RIGHTS_NOT_ASSIGNED) {
            throw new jAcl2DbAdminUIException('User cannot be add to group, else acl management is not possible anymore', 2);
        }
        jAcl2DbUserGroup::addUserToGroup($login, $groupId);
    }

    public function canRemoveUser($login)
    {
        $checking = jAcl2DbManager::checkAclAdminRightsChanges(array(), null, false, true, $login);

        return $checking === jAcl2DbManager::ACL_ADMIN_RIGHTS_STILL_USED;
    }

    public function getHiddenRights() {
        $config = jApp::config();

        if (!$config->jacl2['hideRights']) {
            return array();
        }

        $hiddenRights = $config->jacl2['hiddenRights'];

        if (!is_array($hiddenRights)) {
            return array($hiddenRights);
        }

        return $hiddenRights;
    }

    public function addHiddenRightsValues($rights) {
        $hiddenRights = $this->getHiddenRights();

        if (empty($hiddenRights)) {
            return $rights;
        }
        $hiddenRightsValues = jDao::get('jacl2db~jacl2rights')->getHiddenRightsByGroup($hiddenRights)->fetchAll();
        foreach ($hiddenRightsValues as $value) {
            if (!isset($rights[$value->id_aclgrp])) {
                continue ;
            }
            $rights[$value->id_aclgrp][$value->id_aclsbj] = $value->canceled ? 'n' : 'y';
        }
        return $rights;
    }
}
