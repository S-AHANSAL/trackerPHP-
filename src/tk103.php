<?php
namespace Device;

use MongoDB\Client;

class Tk103
{


    public function connectDB()
    {
        $mongoClient = new Client('mongodb://localhost:27017');
        // Select the database
        $db = $mongoClient->selectDatabase('trackerDB');
        return $db;
    }

    public function getIdDevice($imei)
    {
        $db = $this->connectDB();
        $check = $this->checkDevice($imei, $db);
        if (!is_null($check)) {
            $id = $check;
            return $id;
        } else {
            $device = [
                "imei" => $imei,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
            ];
            $id = $this->insertDevice($device, $db);
            return $id;
        }
    }

    public function checkDevice($imei, $db)
    {
        // Select a collection
        $collection = $db->selectCollection("device");
        $query = ["imei" => $imei];
        $document = $collection->findOne($query);
        if (isset($document)) {
            $id = $document['_id'];
            return $id;
        } else {
            return null;
        }
    }
    public function insertDevice($device, $db)
    {
        $collection = $db->selectCollection("device");
        $result = $collection->insertOne($device);
        if ($result->getInsertedCount() === 1) {
            $id = $result->getInsertedId();
            return $id;
        } else {
            return null;
        }
    }
}