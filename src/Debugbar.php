<?

namespace RiVump\Facade;

use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use DebugBar\DataCollector\PDO;
use RiVump\Util\Config;

/**
 * Class Debugbar
 * @package Ilab\Facade
 */
class Debugbar
{
	/**
	 * @var array
	 * @var null|string
	 */
	public static $instances = [];
	public static $pathToScripts = null;

	/**
	 * @param string $object
	 * @return JavascriptRenderer|StandardDebugBar|false
	 * @throws DebugBarException
	 */
	public static function getInstance(string $object)
	{
		if (self::checkProd()) {
			return;
		}

		if ($object !== 'debugbar' && $object !== 'debugbarRenderer') {
			return false;
		}

		if (!isset(self::$instances['debugbar'])) {
			self::$instances['debugbar'] = new StandardDebugBar();
			self::$instances['debugbarRenderer'] = self::$instances['debugbar']->getJavascriptRenderer(
				self::$pathToScripts
			);
		}

		return self::$instances[$object];
	}

	/**
	 * @return bool
	 */
	public static function checkProd()
	{
		return Config::get('main.mode', 'dev') == 'prod';
	}

	/**
	 * @throws DebugBarException
	 */
	public static function start($pathToScripts = null)
	{
		if (self::checkProd()) {
			return;
		}
		
		self::$pathToScripts = $pathToScripts;

		if(extension_loaded('pdo') && extension_loaded('pdo_sqlite') && extension_loaded('pdo_mysql')) {
			$pdo = new PDO\TraceablePDO(new \PDO('sqlite::memory:'));
			self::getInstance('debugbar')->addCollector(new PDO\PDOCollector($pdo));
		}

		if (Config::get('main.bitrix', false)) {
			$bitrix_users = Config::get('main.bitrix_users', []);

			if (is_array($bitrix_users) && !empty($bitrix_users)) {
				if (in_array((new \CUser)->GetID(), $bitrix_users)) {
					self::bitrixInit();
				}
			} elseif (is_array($bitrix_users) && empty($bitrix_users)) {
				if ((new \CUser)->IsAdmin()) {
					self::bitrixInit();
				}
			}		
		} else {
			echo self::getInstance('debugbarRenderer')->renderHead();
		}
	}

	/**
	 * @throws DebugBarException
	 */
	public static function bitrixInit() {
		if (self::checkProd()) {
			return;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->addEventHandler('main', 'onProlog', ['RiVump\\Facade\\Debugbar', 'renderHead']);
		$eventManager->addEventHandler('main', 'onEpilog', ['RiVump\\Facade\\Debugbar', 'render']);
	}
	
	public static function fastInit() {
		if (self::checkProd()) {
			return;
		}

		echo self::getInstance('debugbarRenderer')->renderHead();

		echo '<body style="min-height: 1px">';
		echo self::getInstance('debugbarRenderer')->renderOnShutdown(false);
	}

	/**
	 * @throws DebugBarException
	 */
	public static function renderHead()
	{
		echo self::getInstance('debugbarRenderer')->renderHead();
	}

	/**
	 * @throws DebugBarException
	 */
	public static function render()
	{
		echo self::getInstance('debugbarRenderer')->render();
	}

	/**
	 * @param $message
	 * @param string $label
	 * @throws DebugBarException
	 */
	public static function log($message, $label = 'info')
	{
		if (self::checkProd()) {
			return;
		}

		self::getInstance('debugbar')['messages']->addMessage($message, $label);
	}

	/**
	 * @param object $exception
	 * @throws DebugBarException
	 */
	public static function error(object $exception)
	{
		if (self::checkProd()) {
			return;
		}
		
		if (!self::checkProd()) {
			self::getInstance('debugbar')['exceptions']->addThrowable($exception);
		} else {
			echo $exception->getMessage();
		}
	}

	/**
	 * @param string $key
	 * @param string $name
	 * @throws DebugBarException
	 */
	public static function timeStart(string $key, string $name)
	{
		if (self::checkProd()) {
			return;
		}

		self::getInstance('debugbar')['time']->startMeasure($key, $name);
	}

	/**
	 * @param string $key
	 * @throws DebugBarException
	 */
	public static function timeEnd(string $key)
	{
		if (self::checkProd()) {
			return;
		}

		self::getInstance('debugbar')['time']->stopMeasure($key);
	}
}
