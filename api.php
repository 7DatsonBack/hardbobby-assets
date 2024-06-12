<?php
require 'config.php';

$data = json_decode(file_get_contents('data/program.json'), true);

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = explode('/', trim($_SERVER['PATH_INFO'], '/'));

if (empty($request_uri[0])) {
    respond(400, ["error" => "No endpoint specified."]);
}

$endpoint = $request_uri[0];

switch ($endpoint) {
    case 'items':
        handle_items($request_method, $request_uri, $data);
        break;
    default:
        respond(404, ["error" => "Endpoint not found."]);
}

function handle_items($method, $uri, $data) {
    switch ($method) {
        case 'GET':
            if (isset($uri[1])) {
                $item_id = intval($uri[1]);
                $item = array_filter($data['items'], function($item) use ($item_id) {
                    return $item['id'] === $item_id;
                });
                if ($item) {
                    respond(200, array_values($item)[0]);
                } else {
                    respond(404, ["error" => "Item not found."]);
                }
            } else {
                respond(200, $data['items']);
            }
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['name']) && isset($input['description'])) {
                $new_item = [
                    'id' => end($data['items'])['id'] + 1,
                    'name' => $input['name'],
                    'description' => $input['description']
                ];
                $data['items'][] = $new_item;
                file_put_contents('data/sample_data.json', json_encode($data, JSON_PRETTY_PRINT));
                respond(201, $new_item);
            } else {
                respond(400, ["error" => "Invalid input."]);
            }
            break;
        case 'PUT':
            if (isset($uri[1])) {
                $item_id = intval($uri[1]);
                $input = json_decode(file_get_contents('php://input'), true);
                foreach ($data['items'] as &$item) {
                    if ($item['id'] === $item_id) {
                        if (isset($input['name'])) $item['name'] = $input['name'];
                        if (isset($input['description'])) $item['description'] = $input['description'];
                        file_put_contents('data/sample_data.json', json_encode($data, JSON_PRETTY_PRINT));
                        respond(200, $item);
                    }
                }
                respond(404, ["error" => "Item not found."]);
            } else {
                respond(400, ["error" => "ID is required."]);
            }
            break;
        case 'DELETE':
            if (isset($uri[1])) {
                $item_id = intval($uri[1]);
                $data['items'] = array_filter($data['items'], function($item) use ($item_id) {
                    return $item['id'] !== $item_id;
                });
                file_put_contents('data/sample_data.json', json_encode($data, JSON_PRETTY_PRINT));
                respond(200, ["message" => "Item deleted."]);
            } else {
                respond(400, ["error" => "ID is required."]);
            }
            break;
        default:
            respond(405, ["error" => "Method not allowed."]);
    }
}
?>
