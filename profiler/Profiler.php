<?php

/*
 * Класс для измерения времени выполнения в разных точках скрипта
 */

/**
 * Description of Profiler
 *
 * @author mihanentalpo
 */
class Profiler
{
	/**
	 * @var type Массив со счётчиками
	 */
	private static $counters=array();
	private static $measuring = false;
	private static $prevTime = 0;
	private static $prevName = "";
	private static $prevMem = 0;
	private static $enabled=true;

	/**
	 * Сбрасывает все счётчики
	 */
	public static function init()
	{
		self::$counters = array();
		$prevName="";
		$prevTime=0;
		$prevMem=0;
		$measuring=false;
		$enabled = true;
	}

	public static function switchOff()
	{
		self::$enabled=false;
	}

	private static function delta()
	{
		return microtime(true) - self::$prevTime;
	}

	private static function memDelta()
	{
		return memory_get_usage(false) - self::$prevMem;
	}

	private static function getArr($name=null)
	{
		if (is_null($name)) $name = self::$prevName;
		return H::gisset( self::$counters[$name],array('time'=>0,'count'=>0,'mem'=>0));
	}

	private static function setArr($arr,$name=null)
	{
		if (is_null($name)) $name = self::$prevName;
		self::$counters[$name] = $arr;
	}

	/**
	 * Начать измерять время, или закончить
	 */
	public static function measure($name = null,$echoIt=false)
	{
		if (!self::$enabled) return;
		//Если имя задано
		if ($name)
		{
			//Если идёт измерение
			if (self::$measuring)
			{
				//Предыдущий массив:
				$arr = self::getArr();
				$arr['count']++;
				$arr['time']+=self::delta();
				$arr['mem']+=self::memDelta();
				self::setArr($arr);
				self::$prevName = $name;
				self::$prevTime = microtime(true);
				self::$prevMem = memory_get_usage(false);
				self::setArr(self::getArr());
			}
			else//Если измерение не идет
			{
				self::$prevName = $name;
				self::$prevTime = microtime(true);
				self::$measuring = true;
				self::$prevMem = memory_get_usage(false);
				self::setArr(self::getArr());
			}
		}
		else
		{
			if (self::$measuring)
			{
				$arr = self::getArr();
				$arr['count']++;
				$arr['time']+=self::delta();
				$arr['mem']+=self::memDelta();
				self::$prevTime = microtime(true);
				self::$prevMem = microtime(true);
				self::setArr($arr);
				self::$measuring = false;
			}
			else
			{
				//nothing to do
			}
		}
		if ($echoIt) echo $name ."\n<br>";
	}

	public static function getMeasures()
	{
		if (!self::$enabled) return;
		return self::$counters;
	}

	/**
	 * Выводит все измерения в "красивом" виде
	 * @param boolean $console приспособить ли вывод для консоли? Если нет, то будет приспособлен для браузера
	 */
	public static function printMeasures($console=false)
	{
		if ($console)
		{
			echo "\n";
			foreach(self::$counters as $name=>$counter)
			{
				$mem = $counter['mem'];
				if ($counter['mem']>0)
				{
					$do = " used";
				}
				else
				{
					$do = "freed";
					$mem = -$mem;
				}
				echo $name . ": runned " . $counter['count'] ." times, for " . self::formatSeconds($counter['time']) . " sec., {$do}: " . self::formatMem($mem) . "\n";
			}
			echo "\n";
		}
		else
		{
			?>
			<style>
				.profiler
				{
					border:1px solid grey ;
					border-radius:3px;
					padding:3px;
					display:inline-block;
					margin:5px;
				}
				.profiler table tr td
				{
					padding-top:0px;
					padding-bottom:0px;
				}
				.profiler table  tr.header td
				{
					border-bottom:1px solid grey;
				}
				.bottom-row td
				{
					border-top:1px solid grey;
				}

			</style>
			<?php
			echo "<div class='profiler'>";
			echo "<b>Proflier::\$counters</b>:<br>";
			?><table class='measures'>
				<tr class='header'>
					<td>Operation name</td>
					<td>Iterations</td>
					<td>Time, sec</td>
					<td>Memory</td>
				</tr>

			<?php
			$maxMem=0;
			$minMem=0;
			$maxTime=0;
			foreach(self::$counters as $name=>$counter)
			{
				if ($counter['mem']>0 && $maxMem < $counter['mem']) $maxMem = $counter['mem'];
				if ($counter['mem']<0 && $minMem < abs($counter['mem'])) $minMem = abs($counter['mem']);
				if ($counter['time']>$maxTime) $maxTime = $counter['time'];
			}
			$fulltime = 0;
			$fullmem = 0;
			foreach(self::$counters as $name=>$counter):
				$fulltime += $counter['time'];
				$fullmem += $counter['mem'];

				$mem = abs($counter['mem']);
				if ($maxMem == 0)
				{
					$memcolor='#fff';
				}
				else	if ($counter['mem']>0)
				{
					$h = H::dec2hex( 127-(int) (127 * $counter['mem'] / ($maxMem)) ,2);
					$memcolor = "#AF{$h}00";
				}
				else
				{
					$h = H::dec2hex( 127-(int) (127 * abs($counter['mem']) / ($maxMem)) ,2);
					$memcolor = "#{$h}AF00";
				}
				$do = $counter['mem']>0 ? " used" : "freed";
				if ($maxTime==0)
				{
					$timecolor = "#fff";
				}
				else
				{
					$timecolor = "#" . H::dec2hex( (int)(200 * $counter['time']/$maxTime)) . "0000";
				}
				?>
				<tr>
					<td><?=$name?></td>
					<td><?=$counter['count']?></td>
					<td style='color:<?=$timecolor?>;'><?=self::formatSeconds($counter['time'])?></td>
					<td style='color:<?=$memcolor?>;'><?=$do . ":" . self::formatMem($mem)?></td>
				</tr>
				<?php

			endforeach;
			?>
			<tr class="bottom-row">
				<td colspan="2">Итого</td>
				<td style='color:black;'><?=self::formatSeconds($fulltime)?></td>
				<td style='color:black;'><?=self::formatMem($fullmem)?></td>
			</tr>
			</table>
				<?php
			echo "</div><br>";

		}
	}

	public static function formatSeconds($time)
	{
		if ($time < 1) $time = round($time,5);
		if ($time > 1) $time = round($time,3);
		if ($time > 10) $time = round($time,2);
		return $time;
 	}

	/**
	 * Функция красиво форматирующая память (т.е. представляющая её в человеко-читаемом формате)
	 * @param integer $mem
	 * @param до скольки округлять $round если =100, будет округляться до 0.01, если 1000 то до 0.001
	 * @return string
	 */
	public static function formatMem($mem,$round = 100)
	{
		$kb = 1024;
		$mb = $kb*$kb;
		if ($mem > $mb)
		{
			return (round($round*$mem / $mb) / $round) . " MiB";
		}
		if ($mem > $kb)
		{
			return (round($round * $mem / $kb)/$round) . " KiB";
		}
		return $mem . " Bytes";
	}

	/**
	 * Возвращает количество использованной памяти в человеко-читаемом формате
	 */
	public static function usedMem()
	{

		$mem = memory_get_usage();


		return self::formatMem($mem);
	}

}

?>
