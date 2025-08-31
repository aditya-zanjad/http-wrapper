<?php

http_response_code(200);
header('Content-Type: application/json');

echo json_encode([
    'message' => 'Successfully fetched the user data!',
    
    'data' => [
        'first_name'    =>  'Aditya',
        'last_name'     =>  'Zanjad',
        'email'         =>  'aditya@email.com',
        'gender'        =>  'male',
        'phone_number'  =>  '911234567890'
    ]
]);