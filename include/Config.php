<?php

/**
 * @file include/Config.php
 * 
 *  @brief Contains the class with methods for system configuration
 */


/**
 * @brief Arbitrary sytem configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The Config::get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * There are a few places in the code (such as the admin panel) where boolean
 * configurations need to be fixed as of 10/08/2011.
 */
class Config {

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in global cache
	 * which is available under the global variable $a->config
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @return void
	 */
	public static function load($family) {
		global $a;

		$r = q("SELECT `v`, `k` FROM `config` WHERE `cat` = '%s'", dbesc($family));
		if(count($r)) {
			foreach($r as $rr) {
				$k = $rr['k'];
				if ($family === 'config') {
					$a->config[$k] = $rr['v'];
				} else {
					$a->config[$family][$k] = $rr['v'];
				}
			}
		} else if ($family != 'config') {
			// Negative caching
			$a->config[$family] = "!<unset>!";
		}
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular config value from the given category ($family)
	 * and the $key from a cached storage in $a->config[$uid].
	 * $instore is only used by the set_config function
	 * to determine if the key already exists in the DB
	 * If a key is found in the DB but doesn't exist in
	 * local config cache, pull it into the cache so we don't have
	 * to hit the DB again for this item.
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to query
	 * @param boolean $refresh
	 *  If true the config is loaded from the db and not from the cache
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($family, $key, $refresh = false) {

		global $a;

		if(! $instore) {
			// Looking if the whole family isn't set
			if(isset($a->config[$family])) {
				if($a->config[$family] === '!<unset>!') {
					return null;
				}
			}

			if(isset($a->config[$family][$key])) {
				if($a->config[$family][$key] === '!<unset>!') {
					return null;
				}
				return $a->config[$family][$key];
			}
		}

		// If APC is enabled then fetch the data from there, else try XCache
		/*if (function_exists("apc_fetch") AND function_exists("apc_exists"))
			if (apc_exists($family."|".$key)) {
				$val = apc_fetch($family."|".$key);
				$a->config[$family][$key] = $val;

				if ($val === '!<unset>!')
					return false;
				else
					return $val;
			}
		elseif (function_exists("xcache_fetch") AND function_exists("xcache_isset"))
			if (xcache_isset($family."|".$key)) {
				$val = xcache_fetch($family."|".$key);
				$a->config[$family][$key] = $val;

				if ($val === '!<unset>!')
					return false;
				else
					return $val;
			}
		*/

		$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc($family),
			dbesc($key)
		);
		if(count($ret)) {
			// manage array value
			$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
			$a->config[$family][$key] = $val;

			// If APC is enabled then store the data there, else try XCache
			/*if (function_exists("apc_store"))
				apc_store($family."|".$key, $val, 600);
			elseif (function_exists("xcache_set"))
				xcache_set($family."|".$key, $val, 600);*/

			return $val;
		}
		else {
			$a->config[$family][$key] = '!<unset>!';

			// If APC is enabled then store the data there, else try XCache
			/*if (function_exists("apc_store"))
				apc_store($family."|".$key, '!<unset>!', 600);
			elseif (function_exists("xcache_set"))
				xcache_set($family."|".$key, '!<unset>!', 600);*/
		}
		return null;
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to set
	 * @param string $value
	 *  The value to store
	 * @return mixed Stored $value or false if the database update failed
	 */
	public static function set($family,$key,$value) {
		global $a;

		// If $a->config[$family] has been previously set to '!<unset>!', then
		// $a->config[$family][$key] will evaluate to $a->config[$family][0], and
		// $a->config[$family][$key] = $value will be equivalent to
		// $a->config[$family][0] = $value[0] (this causes infuriating bugs),
		// so unset the family before assigning a value to a family's key
		if($a->config[$family] === '!<unset>!')
			unset($a->config[$family]);

		// manage array value
		$dbvalue = (is_array($value)?serialize($value):$value);
		$dbvalue = (is_bool($dbvalue) ? intval($dbvalue) : $dbvalue);
		if(self::get($family,$key,true) === false) {
			$a->config[$family][$key] = $value;
			$ret = q("INSERT INTO `config` ( `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s' ) ",
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue)
			);
			if($ret)
				return $value;
			return $ret;
		}

		$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s'",
			dbesc($dbvalue),
			dbesc($family),
			dbesc($key)
		);

		$a->config[$family][$key] = $value;

		// If APC is enabled then store the data there, else try XCache
		/*if (function_exists("apc_store"))
			apc_store($family."|".$key, $value, 600);
		elseif (function_exists("xcache_set"))
			xcache_set($family."|".$key, $value, 600);*/

		if($ret)
			return $value;
		return $ret;
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config
	 * and removes it from the database.
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to delete
	 * @return mixed
	 */
	public static function delete($family,$key) {

		global $a;
		if(x($a->config[$family],$key))
			unset($a->config[$family][$key]);
		$ret = q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s'",
			dbesc($family),
			dbesc($key)
		);
		// If APC is enabled then delete the data from there, else try XCache
		/*if (function_exists("apc_delete"))
			apc_delete($family."|".$key);
		elseif (function_exists("xcache_unset"))
			xcache_unset($family."|".$key);*/

		return $ret;
	}

}