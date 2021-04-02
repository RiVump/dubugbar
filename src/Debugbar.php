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

	public static function checkProd()
	{
		return Config::get('main.mode', 'dev') == 'prod';
	}

	/**
	 * @throws DebugBarException
	 */
	public static function start($pathToScripts = null)
	{
		self::$pathToScripts = $pathToScripts;
		/*$pdo = new PDO\TraceablePDO(new \PDO('sqlite::memory:'));
		self::getInstance('debugbar')->addCollector(new PDO\PDOCollector($pdo));*/

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

	public static function bitrixInit() {
		\Bitrix\Main\Page\Asset::getInstance()->addString(self::getInstance('debugbarRenderer')->renderHead());
		\Bitrix\Main\Page\Asset::getInstance()->addString(
			self::getInstance('debugbarRenderer')->render(),
			false,
			\Bitrix\Main\Page\AssetLocation::BODY_END
		);
	}

	/**
	 * @throws DebugBarException
	 */
	public static function render()
	{
		echo self::getInstance('debugbarRenderer')->render();
	}

	/**
	 * @param string $message
	 * @param string $label
	 * @throws DebugBarException
	 */
	public static function log(string $message, $label = 'info')
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
