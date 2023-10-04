<?php
namespace Traker;

use MongoDB\Client;
use Device\Tk103;

class Trakerperson
{

    public $data, $collection;


    public function connectDB()
    {
        $mongoClient = new Client('mongodb://localhost:27017');
        // Select the database
        $db = $mongoClient->selectDatabase('trackerDB');
        return $db;
    }
    public function insertDB($db, $collection, $document)
    {
        // Select a collection
        $this->collection = $db->selectCollection($collection);
        // Insert a document
        $this->collection->insertOne($document);
        return true;
    }

    public function addDataPerson(): string
    {
        $tk103 = new Tk103;
        $this->data = $tk103->getdata();
        $db = $this->connectDB();
        $insert = $this->insertDB($db, "data", $this->data);
        if ($insert) {
            return "data added";
        }
        return "Error";
    }
    public function deleting(): string
    {
        // Empty the collection by deleting all documents
        $result = $this->collection->deleteMany([]);

        // Check if the deletion was successful
        if ($result->getDeletedCount() > 0) {
            return "Collection emptied successfully.";
        } else {
            return "Collection is already empty.";
        }
    }
}