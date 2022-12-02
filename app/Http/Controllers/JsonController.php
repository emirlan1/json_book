<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\Storage;

class JsonController
{
    //Сохраняем ссылки на файлы в константах
    const FIRST_RESPONSE = 'json/first_response.json';
    const SECOND_RESPONSE = 'json/second_response.json';

    //Массив для проверки полей
    const PROPS = [
        'name' => [
            'title',
            'name'
        ],
        'description' => [
            'desc',
            'descr',
            'description',
            'text'
        ],
        'created_at' => [
            'createdat',
            'create_at'
        ],
        'author' => [
            'author'
        ]
    ];

    //В это свойство мы сохраняем все возможные варианты ключей
    public $active_keys = [];

    private function getFirstResponse() {
        $json = Storage::disk('local')->get(self::FIRST_RESPONSE);
        return json_decode($json)->data;
    }

    private function getSecondResponse() {
        $json = Storage::disk('local')->get(self::SECOND_RESPONSE);
        return json_decode($json);
    }

    public function showJson() {
        //Получаем данные из JSON файлов
        $firstResponse = $this->getFirstResponse();
        $secondResponse = $this->getSecondResponse();

        //Соединяем два массива в один
        $data = [];
        $response = array_merge($firstResponse, $secondResponse);

        //Создаем массив с правильными названиями полей
        $i=0;
        foreach ($response as $book) {
            foreach ($book as $key => $row) {
                //Если мы нашли поле среди массива PROPS, то сохраняем значение под валидным названием в итоговой массив
                $propKey = $this->props($key);
                if ($propKey) {
                    $data[$i][$propKey] = $row;
                }
            }
            $i++;
        };

        //Получаем все валидные ключи, т.е ключи с правильными названиями без дупликатов
        $validKeys = $this->getValidKeys($firstResponse, $secondResponse);

        //Добавляем недостающие поля в итоговой массив с данными
        $i=0;
        foreach ($data as $row) {
            foreach ($validKeys as $validKey) {
                if (!array_key_exists($validKey, $row)) {
                    $data[$i][$validKey] = null;
                }
            }
            $i++;
        }

        //Сортируем поля, чтобы ключи в каждом элементе массива были в одинаковом порядке
        $i=0;
        foreach ($data as $row) {
            ksort($row);
            $data[$i] = $row;
            $i++;
        }

        //Выводим данные
        dd($data);
    }

    public function props($key) {
        $props = self::PROPS;
        $result = null;

        //Проверяем поля в массиве через наш PROPS
        foreach ($props as $prop => $variants) {
            //если находим соответствие то сохраняем название этого поля
            if (in_array(strtolower($key), $variants)) {
                $result = $prop;
                break;
            }
        }

        //Отправляем название правильного поля
        return $result;
    }

    private function getActiveKeys($array) {
        //Получаем все существующие ключи и сохраняем их в свойство active_keys
        $keys = array();
        foreach ($array as $book) {
            foreach ($book as $key => $value) {
                $keys[] = $key;
                if (is_array($value)) {
                    $keys = array_merge($keys, array_keys_multi($value));
                }
            }
        }
        $keys = array_unique($keys);
        $this->active_keys = array_merge($this->active_keys, $keys);
    }

    private function getValidKeys($firstResponse, $secondResponse) {
        //Проверяем все существующие ключи на их валидность (соостветствие, массиву PROPS)
        $this->getActiveKeys($firstResponse);
        $this->getActiveKeys($secondResponse);

        $keys = $this->active_keys;
        $validKeys = [];
        foreach ($keys as $key) {
            if ($this->props($key)) {
                $validKeys[] = $this->props($key);
            }

        }

        return array_unique($validKeys);
    }
}
