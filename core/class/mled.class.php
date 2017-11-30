<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class mled extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'mled_update';
		$return['progress_file'] = jeedom::getTmpFolder('mled') . '/dependance';
		if (exec('which mosquitto_pub | wc -l') != 0) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('mled') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	/*     * *********************Méthodes d'instance************************* */

	public function postSave() {
		$cmd = $this->getCmd(null, 'message');
		if (!is_object($cmd)) {
			$cmd = new mledCmd();
			$cmd->setLogicalId('message');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Message', __FILE__));
			$cmd->setOrder(1);
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('message_placeholder', __('Message', __FILE__));
		$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();
	}

	/*     * **********************Getteur Setteur*************************** */
}

class mledCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {
		if ($this->getType() != 'action') {
			return;
		}
		if (!isset($_options['message'])) {
			throw new Exception(__('Aucun message de donné : ', __FILE__) . json_encode($_options));
		}
		$eqLogic = $this->getEqLogic();
		$cmd = 'mosquitto_pub -h localhost -t \'' . $eqLogic->getConfiguration('mqtt::topic') . '\'';
		$args = array();
		if (isset($_options['title'])) {
			$args = arg2array($_options['title']);
		}
		if (!isset($args['priority'])) {
			$args['priority'] = 1;
		}
		if (!isset($args['lum'])) {
			$args['lum'] = 1;
		}
		if (!isset($args['pos'])) {
			$args['pos'] = 0;
		}
		if (!isset($args['eff_in'])) {
			$args['eff_in'] = 1;
		}
		if (!isset($args['eff_out'])) {
			$args['eff_out'] = 1;
		}
		if (!isset($args['speed'])) {
			$args['speed'] = 40;
		}
		if (!isset($args['pause'])) {
			$args['pause'] = 0;
		}
		$args['text'] = $_options['message'];
		$cmd .= ' -m "' . str_replace('"', '\"', json_encode($args, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '"';
		log::add('mled', 'debug', $cmd);
		com_shell::execute($cmd);
	}

	/*     * **********************Getteur Setteur*************************** */
}
