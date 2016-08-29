<?php
/**
 * Класс для работы с хэшами изображений
 *
 * @author mihanentalpo
 */
class ImageHash
{
    /**
	 * Построить хэш изображения из файла
	 *
	 * @param string $imageFile Файл изображения
	 * @param integer $pHashSizeRoot Квадратный корень из размера хэша. От 4 и больше. Чем больше - тем точнее сравнение.
	 * @param integer $pHashDetalization детализация хэша, от 2 до 6. Чем больше - тем точнее сравнение.
     * @param boolean $toBase64 Преобразовать результат в base64?
     * @return string хэш изображения
	 */
    public function createHashFromFile($imageFile, $pHashSizeRoot = 10, $pHashDetalization = 3, $toBase64 = true)
    {
        return $this->createHashFromFileContents(file_get_contents($imageFile), $pHashSizeRoot, $pHashDetalization, $toBase64);
    }

    /**
     * Построить хэш изображения из строки, содержащей содержимое загруженного файла изображения
     *
     * @param string $imageFileContents содержимое файла
     * @param integer $pHashSizeRoot Квадратный корень из размера хэша. От 4 и больше. Чем больше - тем точнее сравнение.
	 * @param integer $pHashDetalization детализация хэша, от 2 до 6. Чем больше - тем точнее сравнение.
     * @param boolean $toBase64 Преобразовать результат в base64?
	 * @return string хэш изображения
     */
    public function createHashFromFileContents($imageFileContents, $pHashSizeRoot = 10, $pHashDetalization = 3, $toBase64 = true)
    {
        $image = imagecreatefromstring($imageFileContents);
        return $this->createHash($image, $pHashSizeRoot, $pHashDetalization, $toBase64);
    }

    /**
     * Ограничить значение минимумом и максимумом
     * @param integer $value
     * @param integer $min
     * @param integer $max
     * @return integer
     */
    protected function limit($value, $min, $max)
    {
        return $value > $max ? $max : ($value < $min ? $min : $value);
    }

    /**
     * Проверить, является ли переданная переменная изображением
     * @param resource $image Изображение
     * @param string $type Переменная, куда будет записано название типа переменной, если она будет не изображением
     * @return type
     */
    protected function isImage($image, &$type)
    {
        $x = @imagesx($image);
        if (!$x)
        {
            $type = "";
            switch(true){
                case is_string($image):
                    $type="string '" . substr($image, 0, 30) . "...'";
                    break;
                case is_numeric($image):
                    $type="number '" . (float)$image . "'";
                    break;
                case is_object($image):
                    $type="object of class '" . get_class($image) . "'";
                    break;
                case is_resource($image):
                    $type="some other resource";
                    break;
                case is_array($image):
                    $type="array of " . count($image) . " elements";
                    break;
                case true:
                    $type="not";
                    break;
            }
        }
        return !!$x;
    }


    /**
	 * Построить хэш изображения для быстрого сравнения схожести изображений.
	 * Позволяет быстро определять схожесть изображений, которые являются одним
	 * и тем-же изображением, но, например, с изменённым размером и пропорциями,
	 * или с немного подкорректированными цветами.
	 *
	 * @param string $image Изображение
	 * @param integer $pHashSizeRoot Квадратный корень из размера хэша. От 4 и больше. Чем больше - тем точнее сравнение.
	 * @param integer $pHashDetalization детализация хэша, от 2 до 6. Чем больше - тем точнее сравнение.
	 */
    public function createHash($image, $pHashSizeRoot = 10, $pHashDetalization = 3, $toBase64 = true)
    {
        if (!$this->isImage($image, $type))
        {
            throw new Exception("\$image argument should be image resource, but it's " . $type);
        }

        $hashDetalization = $this->limit($pHashDetalization, 2, 6, true);
        $hashSizeRoot = $this->limit($pHashSizeRoot, 4, 50, true);
        $width = imagesx($image);
		$height = imagesy($image);

        $size = array($width, $height);
		$littleSize = $hashSizeRoot;
        //Цветов на один пиксел (число от 8 до 216)
		$colorsPerPixel = pow($hashDetalization, 3);
        //Цветов на один канал
		$colorsPerChannel = $hashDetalization;
        //Отрезок цветового канала, пропорциональный единице из упрощённого цветового канала
		$channelDivision = 256 / $colorsPerChannel;

		$colorSimplify = function($color) use($colorsPerPixel, $colorsPerChannel, $channelDivision) {
			//Разбиваем цвет на красный, синий и зелёный
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            //Получаем упрощённые значения цветовых каналов
            $simpleR = floor($r / $channelDivision);
            $simpleG = floor($g / $channelDivision);
            $simpleB = floor($b / $channelDivision);

			$simpleColor = (int)($simpleR + $simpleG * $colorsPerChannel + $simpleB * $colorsPerChannel * $colorsPerChannel);

			if ($simpleColor < 0) $simpleColor = 0;
			if ($simpleColor >= $colorsPerPixel) $simpleColor = $colorsPerPixel - 1;

			return (int)$simpleColor;
		};

		$littleImg = imagecreatetruecolor($littleSize, $littleSize);
		imagecopyresampled($littleImg, $image, 0, 0, 0, 0, $littleSize, $littleSize, $size[0], $size[1]);
		$hash = "";

		for($i=0;$i<$littleSize; $i++)
		{
			for ($j=0;$j<$littleSize;$j++)
			{
                $color = imagecolorat($littleImg, $i, $j);
				$simpleColor = $colorSimplify($color);
				$hash .= chr($simpleColor);
			}
		}

		imagedestroy($littleImg);
		imagedestroy($image);

        if ($toBase64)
        {
            $result = base64_encode($hash);
        }
        else
        {
            $result = $hash;
        }

		return $result;
    }

    /**
	 * Сравнить два хэша изображений
	 * @param string $hash1 хэш первого изображения в формате base64
	 * @param string $hash2 хэш второго изображения в таком же формате
	 * @param float $epsilon Максимальная относительная ошибка. 1 это 100%, 0.5 это 50% и так далее.
	 * @param boolean $error Ссылка на переменную, в которую будет записана величина ошибки (число от 0 до 1)
	 * @param boolean $base64decoded Флаг, указывающий, что переданные хэши уже декодированы из base64
	 * @return boolean возвращает true/false в зависимости от того, соответствуют ли друг другу хэши
	 */
	public function compareImageHashes($hash1, $hash2, $epsilon = 0.01, &$error = 0, $base64decoded = false)
	{
		$error = 1;
		if ($epsilon == 0)
		{
			return $hash1 == $hash2;
		}
		else
		{
            if ($hash1 == $hash2) return true;
			if (!$base64decoded)
			{
				$h1 = base64_decode($hash1);
				$h2 = base64_decode($hash2);
			}
			else
			{
				$h1 = $hash1;
				$h2 = $hash2;
			}

			if (strlen($h1) != strlen($h2)) return false;
			$l = strlen($h1);
			$error = 0;
			$bytes1 = unpack("C*", $h1);
			$bytes2 = unpack("C*", $h2);

			for ($i=0;$i<$l;$i++)
			{
				$b1 = $bytes1[$i+1];
				$b2 = $bytes2[$i+1];
				if ($b1 != $b2)
				{
					$delta = abs($b1 - $b2);
					$mid = ($b1 + $b2) / 2;
					if ($delta > 0)
					{
						$e = $delta / $mid;
						$error += $e / $l;
						if ($error > $epsilon) return false;
					}
				}
			}

			return $error <= $epsilon;
		}
	}

}
