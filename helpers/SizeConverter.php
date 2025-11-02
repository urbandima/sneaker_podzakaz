<?php
namespace app\helpers;

/**
 * Конвертер размеров обуви
 * Поддерживает EU, US, UK, CM с учётом брендов
 */
class SizeConverter
{
    /**
     * Таблица размеров (EU => [US_M, US_W, UK, CM])
     */
    public static $sizeTable = [
        35 => ['3', '5', '2.5', '22'],
        35.5 => ['3.5', '5.5', '3', '22.5'],
        36 => ['4', '6', '3.5', '23'],
        37 => ['5', '7', '4', '23.5'],
        37.5 => ['5.5', '7.5', '4.5', '24'],
        38 => ['6', '8', '5', '24.5'],
        38.5 => ['6.5', '8.5', '5.5', '25'],
        39 => ['7', '9', '6', '25.5'],
        40 => ['7.5', '9.5', '6.5', '26'],
        40.5 => ['8', '10', '7', '26.5'],
        41 => ['8.5', '10.5', '7.5', '27'],
        42 => ['9', '11', '8', '27.5'],
        42.5 => ['9.5', '11.5', '8.5', '28'],
        43 => ['10', '12', '9', '28.5'],
        44 => ['10.5', '12.5', '9.5', '29'],
        44.5 => ['11', '13', '10', '29.5'],
        45 => ['11.5', '13.5', '10.5', '30'],
        46 => ['12', '14', '11', '30.5'],
        47 => ['13', '15', '12', '31.5'],
    ];
    
    /**
     * Корректировки по брендам (добавка к размеру)
     */
    public static $brandAdjustments = [
        'Nike' => ['US' => 0, 'UK' => 0, 'note' => 'Стандартный размер'],
        'Adidas' => ['US' => -0.5, 'UK' => 0, 'note' => 'Маломерят на 0.5 размера'],
        'New Balance' => ['US' => 0.5, 'UK' => 0, 'note' => 'Большемерят на 0.5 размера'],
        'Puma' => ['US' => 0, 'UK' => -0.5, 'note' => 'Стандартный размер'],
        'Reebok' => ['US' => 0, 'UK' => 0, 'note' => 'Стандартный размер'],
        'Converse' => ['US' => -0.5, 'UK' => -0.5, 'note' => 'Маломерят'],
        'Vans' => ['US' => 0, 'UK' => 0, 'note' => 'Стандартный размер'],
        'Asics' => ['US' => 0, 'UK' => 0, 'note' => 'Стандартный размер'],
    ];
    
    /**
     * Конвертировать размер
     * 
     * @param float $euSize Размер EU
     * @param string $brand Бренд
     * @param string $gender Пол (male/female)
     * @return array|null
     */
    public static function convert($euSize, $brand = null, $gender = 'male')
    {
        if (!isset(self::$sizeTable[$euSize])) {
            return null;
        }
        
        $sizes = self::$sizeTable[$euSize];
        
        $result = [
            'EU' => $euSize,
            'US' => $gender === 'male' ? $sizes[0] : $sizes[1],
            'UK' => $sizes[2],
            'CM' => $sizes[3],
        ];
        
        // Применяем корректировку бренда
        if ($brand && isset(self::$brandAdjustments[$brand])) {
            $adj = self::$brandAdjustments[$brand];
            $result['US_ADJ'] = (float)$result['US'] + $adj['US'];
            $result['UK_ADJ'] = (float)$result['UK'] + $adj['UK'];
            $result['NOTE'] = $adj['note'];
        }
        
        return $result;
    }
    
    /**
     * Получить всю таблицу для бренда
     * 
     * @param string $brand Бренд
     * @param string $gender Пол
     * @return array
     */
    public static function getFullTable($brand = null, $gender = 'male')
    {
        $table = [];
        
        foreach (self::$sizeTable as $eu => $data) {
            $table[] = self::convert($eu, $brand, $gender);
        }
        
        return $table;
    }
    
    /**
     * Получить примечание по бренду
     */
    public static function getBrandNote($brand)
    {
        return self::$brandAdjustments[$brand]['note'] ?? 'Стандартный размер';
    }
}
