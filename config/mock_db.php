<?php
    define('USERS_FILE', __DIR__ . '/users.json');

    // reads users from the json file
    function getUsers(){
        if (!file_exists(USERS_FILE)){
            file_put_contents(USERS_FILE, json_encode([]));
        }
        $json = file_get_contents(USERS_FILE);
        return json_decode($json, true);
    }

    // save users back to the JSON file
    function saveUsers($users){
        file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    }
?>
