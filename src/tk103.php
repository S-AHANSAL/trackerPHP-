<?php
namespace Device;

class Tk103
{

    public $data = "imei:864895031874322,ac alarm,231002124957,,F,114957.00,A,3023.58598,N,00931.35082,W,;";

    public function getData()
    {
        $values = explode(',', $this->data);
        $object = [
            'imei' => explode(':', $values[0])[1],
            'status' => $values[1],
            'timestamp' => $values[2],
            'speed' => $values[5],
            'latitude' => $values[7],
            'longitude' => $values[9],
        ];

        return $object;
    }
}