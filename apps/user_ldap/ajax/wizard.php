<?php

/**
 * ownCloud - user_ldap
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$l=OC_L10N::get('user_ldap');

if(!isset($_POST['action'])) {
	\OCP\JSON::error(array('message' => $l->t('No action specified')));
}
$action = $_POST['action'];


if(!isset($_POST['ldap_serverconfig_chooser'])) {
	\OCP\JSON::error(array('message' => $l->t('No configuration specified')));
}
$prefix = $_POST['ldap_serverconfig_chooser'];

$ldapWrapper = new OCA\user_ldap\lib\LDAP();
$configuration = new \OCA\user_ldap\lib\Configuration($prefix);
$wizard = new \OCA\user_ldap\lib\Wizard($configuration, $ldapWrapper);

switch($action) {
	case 'guessPortAndTLS':
	case 'guessBaseDN':
	case 'determineObjectClasses':
	case 'determineGroups':
		try {
			$result = $wizard->$action();
			if($result !== false) {
				OCP\JSON::success($result->getResultArray());
				exit;
			}
		} catch (\Exception $e) {
			\OCP\JSON::error(array('message' => $e->getMessage()));
			exit;
		}
		\OCP\JSON::error();
		exit;
		break;

	case 'save':
		$key = isset($_POST['cfgkey']) ? $_POST['cfgkey'] : false;
		$val = isset($_POST['cfgval']) ? $_POST['cfgval'] : null;
		if($key === false || is_null($val)) {
			\OCP\JSON::error(array('message' => $l->t('No data specified')));
			exit;
		}
		$cfg = array($key => $val);
		$setParameters = array();
		$configuration->setConfiguration($cfg, $setParameters);
		if(!in_array($key, $setParameters)) {
			\OCP\JSON::error(array('message' => $l->t($key.' Could not set configuration '.$setParameters[0])));
			exit;
		}
		$configuration->saveConfiguration();
		OCP\JSON::success();
		break;
	default:
		//TODO: return 4xx error
		break;
}
