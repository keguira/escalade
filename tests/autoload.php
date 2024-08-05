<?php

/**
 * -------------------------------------------------------------------------
 * Escalade plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Escalade.
 *
 * Escalade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Escalade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Escalade. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2023 by Escalade plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/escalade
 * -------------------------------------------------------------------------
 */

use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

define('GLPI_ROOT', __DIR__ . '/../../../');
define('GLPI_CONFIG_DIR', __DIR__ . '/../../../tests/config');
define('GLPI_VAR_DIR', __DIR__ . '/files');
define('GLPI_URI', (getenv('GLPI_URI') ?: 'http://localhost:8088'));
define('GLPI_LOG_DIR', GLPI_VAR_DIR . '/_log');
define(
    'PLUGINS_DIRECTORIES',
    [
        GLPI_ROOT . '/plugins',
        GLPI_ROOT . '/tests/fixtures/plugins',
    ]
);

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

global $CFG_GLPI, $GLPI_CACHE;

include(GLPI_ROOT . "/inc/based_config.php");

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    die("\nConfiguration file for tests not found\n\nrun: bin/console glpi:database:install --config-dir=tests/config ...\n\n");
}

// Create subdirectories of GLPI_VAR_DIR based on defined constants
foreach (get_defined_constants() as $constant_name => $constant_value) {
    if (
        preg_match('/^GLPI_[\w]+_DIR$/', $constant_name)
        && preg_match('/^' . preg_quote(GLPI_VAR_DIR, '/') . '\//', $constant_value)
    ) {
        is_dir($constant_value) or mkdir($constant_value, 0755, true);
    }
}

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
    // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
    // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

global $PLUGIN_HOOKS;

include_once GLPI_ROOT . 'inc/includes.php';
include_once GLPI_ROOT . '/plugins/escalade/vendor/autoload.php';
include_once GLPI_ROOT . '/plugins/escalade/setup.php';
include_once GLPI_ROOT . '/plugins/escalade/hook.php';

$auth = new Auth();
$user = new User();
$auth->auth_succeded = true;
$user->getFromDB(2);
$auth->user = $user;
Session::init($auth);
Session::initEntityProfiles(2);
Session::changeProfile(4);
plugin_escalade_install();
plugin_init_escalade();
$plugins = new Plugin();
if (!$plugins->getFromDBbyDir('escalade')) {
    $plugins->add([
        'directory' => 'escalade',
        'name' => 'escalade',
        'version' => PLUGIN_ESCALADE_VERSION,
        'state' => Plugin::ACTIVATED,
    ]);
}

if (!file_exists(GLPI_LOG_DIR . '/php-errors.log')) {
    file_put_contents(GLPI_LOG_DIR . '/php-errors.log', '');
}

if (!file_exists(GLPI_LOG_DIR . '/sql-errors.log')) {
    file_put_contents(GLPI_LOG_DIR . '/sql-errors.log', '');
}

// @codingStandardsIgnoreStart
class GlpitestPHPerror extends \Exception
{
}
class GlpitestPHPwarning extends \Exception
{
}
class GlpitestPHPnotice extends \Exception
{
}
class GlpitestSQLError extends \Exception
{
}
// @codingStandardsIgnoreEnd
