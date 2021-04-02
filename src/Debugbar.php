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
	 */
	public static $instances = [];

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
				'/svs/vendor/maximebf/debugbar/src/DebugBar/Resources'
			);

			if (Config::get('main.bitrix', false)) {
				$bitrix_users = Config::get('main.bitrix_users', []);

				if (is_array($bitrix_users) && !empty($bitrix_users)) {
					if (in_array(\CUser::GetID())) {
						self::start();
					}
				} elseif (is_array($bitrix_users) && empty($bitrix_users)) {
					if (\CUser::IsAdmin()) {
						self::start();
					}
				}
			}
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
	public static function start()
	{
		$pdo = new PDO\TraceablePDO(new \PDO('sqlite::memory:'));
		self::getInstance('debugbar')->addCollector(new PDO\PDOCollector($pdo));

		if (Config::get('main.bitrix', false)) {
			\Bitrix\Main\Page\Asset::addString(self::getInstance('debugbarRenderer')->renderHead());
			\Bitrix\Main\Page\Asset::addString(
				self::getInstance('debugbarRenderer')->render(),
				false,
				\Bitrix\Main\Page\AssetLocation::BODY_END
			);
		} else {
			echo self::getInstance('debugbarRenderer')->renderHead();
		}
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