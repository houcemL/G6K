<?php

/*
The MIT License (MIT)

Copyright (c) 2015 Jacques Archim�de

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

namespace EUREKA\G6KBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

use EUREKA\G6KBundle\Entity\Database;

class ScriptHandler
{
	public static function installUsers(Event $event) {
		$extras = $event->getComposer()->getPackage()->getExtra();
		$symfonyDir = dirname(dirname(dirname(dirname(__DIR__))));
		$appDir = $symfonyDir . DIRECTORY_SEPARATOR . $extras['symfony-app-dir'];
		$configDir = $appDir . DIRECTORY_SEPARATOR  .'config';
		$databasesDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'databases';
		try {
			$config = Yaml::parse(file_get_contents($configDir . DIRECTORY_SEPARATOR . 'parameters.yml'));
			$parameters = (object)$config['parameters'];
		} catch (ParseException $e) {
			$event->getIO()->write(sprintf("Unable to parse parameters.yml: %s", $e->getMessage()));
			return;
		}
		$driver = $parameters->database_driver;
		switch ($driver) {
			case 'pdo_mysql':
				$database = new Database(null, 1, 'mysqli', $parameters->database_name);
				$database->setHost($parameters->database_name);
				if (isset($parameters->database_port)) {
					$database->setPort($parameters->database_port);
				}
				$database->setUser($parameters->database_user);
				if (isset($parameters->database_password)) {
					$database->setPort($parameters->database_password);
				}
				break;
			case 'pdo_pgsql':
				$database = new Database(null, 1, 'pgsql', $parameters->database_name);
				$database->setHost($parameters->database_name);
				if (isset($parameters->database_port)) {
					$database->setPort($parameters->database_port);
				}
				$database->setUser($parameters->database_user);
				if (isset($parameters->database_password)) {
					$database->setPort($parameters->database_password);
				}
				break;
			case 'pdo_sqlite':
				$name = basename($parameters->database_path);
				$database = new Database(null, 1, 'sqlite', $name);
				break;
			default:
				$event->getIO()->write(sprintf("Unsupported database driver: %s", $driver));
				return;
		}
		if (($script = file_get_contents($databasesDir . DIRECTORY_SEPARATOR . 'fos_user.sql')) === FALSE) {
			$event->getIO()->write(sprintf("Unable to read  %s", $databasesDir . DIRECTORY_SEPARATOR . 'fos_user.sql'));
			return;
		}
		try {
			$database->connect();
		} catch (\Exception $e) {
			$event->getIO()->write("Can't connect to database : " . $e->getMessage());
			return;
		}
		$script = preg_split ("/;\n/", $script, -1,  PREG_SPLIT_NO_EMPTY);
		foreach($script as $sql) {
			try {
				$database->exec($sql);
			} catch (\Exception $e) {
				$event->getIO()->write("Can't execute install users script : " . $e->getMessage());
				break;
			}
		}
	}

}

?>