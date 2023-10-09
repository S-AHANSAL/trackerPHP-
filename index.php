<?php
require 'vendor/autoload.php';
use Device\Tk103;
use \Traker\Trakerperson as Traker;
$ip_address = "0.0.0.0";
$port = "9001";

// open a server on port 7331
$server = stream_socket_server("tcp://$ip_address:$port", $errno, $errorMessage);
if ($server === false) {
    die("stream_socket_server error: $errorMessage");
}

$client_sockets = array();

while (true) {
    // prepare readable sockets
    $read_sockets = $client_sockets;
    $read_sockets[] = $server;

    // start reading and use a large timeout
    if (!stream_select($read_sockets, $write, $except, 300000)) {
        die('stream_select error.');
    }

    // new client
    if (in_array($server, $read_sockets)) {
        $new_client = stream_socket_accept($server);

        if ($new_client) {
            //print remote client information, ip and port number
            echo 'new connection: ' . stream_socket_get_name($new_client, true) . "\n";

            $client_sockets[] = $new_client;
            echo "total clients: " . count($client_sockets) . "\n";

            // $output = "hello new client.\n";
            // fwrite($new_client, $output);
        }

        //delete the server socket from the read sockets
        unset($read_sockets[array_search($server, $read_sockets)]);
    }

    // message from existing client
    foreach ($read_sockets as $socket) {
        $data = fread($socket, 128);

        echo "data: " . $data . "\n";

        $tk103_data = explode(',', $data);
        $response = "";
        switch (count($tk103_data)) {
            case 1: // 359710049095095 -> heartbeat requires "ON" response
                $response = "ON";
                echo "sent ON to client\n";
                break;
            case 3: // ##,imei:359710049095095,A -> this requires a "LOAD" response
                if ($tk103_data[0] == "##") {
                    $response = "LOAD";
                    echo "sent LOAD to client\n";
                }
                break;
            case 19: // imei:359710049095095,tracker,151006012336,,F,172337.000,A,5105.9792,N,11404.9599,W,0.01,322.56,,0,0,,,  -> this is our gps data
                $imei = substr($tk103_data[0], 5);
                $alarm = $tk103_data[1];
                $gps_time = nmea_to_mysql_time($tk103_data[2]);
                $latitude = degree_to_decimal($tk103_data[7], $tk103_data[8]);
                $longitude = degree_to_decimal($tk103_data[9], $tk103_data[10]);
                $speed_in_knots = $tk103_data[11];
                $speed_in_mph = 1.15078 * $speed_in_knots;
                $bearing = $tk103_data[12];


                $tk103 = new Tk103;
                $id_device = $tk103->getIdDevice($imei);
                $data = [
                    "id_device" => $id_device,
                    "datetime" => $gps_time,
                    "x" => $latitude,
                    "y" => $longitude,
                    "speed_in_knots" => $tk103_data[11],
                    "speed_in_mph" => $speed_in_mph,
                    "bearing" => $bearing,
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ];
                $trakerPerson = new Traker;

                $msg = $trakerPerson->addDataPerson($data);

                echo $msg;

                if ($alarm == "help me") {
                    $response = "**,imei:" + $imei + ",E;";
                }
                break;
        }

        if (!$data) {
            unset($client_sockets[array_search($socket, $client_sockets)]);
            @fclose($socket);
            echo "client disconnected. total clients: " . count($client_sockets) . "\n";
            continue;
        }

        //send the message back to client
        if (sizeof($response) > 0) {
            fwrite($socket, $response);
        }

    }


}


function nmea_to_mysql_time($date_time)
{
    $year = substr($date_time, 0, 2);
    $month = substr($date_time, 2, 2);
    $day = substr($date_time, 4, 2);
    $hour = substr($date_time, 6, 2);
    $minute = substr($date_time, 8, 2);
    $second = substr($date_time, 10, 2);

    return date("Y-m-d H:i:s", mktime($hour, $minute, $second, $month, $day, $year));
}

function degree_to_decimal($coordinates_in_degrees, $direction)
{
    $degrees = (int) ($coordinates_in_degrees / 100);
    $minutes = $coordinates_in_degrees - ($degrees * 100);
    $seconds = $minutes / 60;
    $coordinates_in_decimal = $degrees + $seconds;

    if (($direction == "S") || ($direction == "W")) {
        $coordinates_in_decimal = $coordinates_in_decimal * (-1);
    }

    return number_format($coordinates_in_decimal, 6, '.', '');
}

print_r($server);

echo "hello";
echo "\n";
?>