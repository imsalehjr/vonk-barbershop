<?php

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$serviceId = get_int('service');
$barberId = get_int('barber');
$date = get_string('date');
$ignore = 0;

if (get_int('ignore') > 0 && Auth::check()) {
    $appointment = BookingService::find(get_int('ignore'));
    if ($appointment) {
        $ignore = (int) $appointment['id'];
        $serviceId = (int) $appointment['service_id'];
    }
}

session_write_close();

$token = get_string('token');
if ($token !== '') {
    $appointment = BookingService::findByToken($token);
    if ($appointment && BookingService::canCustomerChange($appointment)) {
        $ignore = (int) $appointment['id'];
        $serviceId = (int) $appointment['service_id'];
    }
}

if ($serviceId === 0 || (new Validator())->date('date', $date)->fails()) {
    json_response(['slots' => []], 422);
    return;
}

header('Cache-Control: no-store');
json_response(['slots' => array_keys(Availability::slots($serviceId, $barberId, $date, $ignore))]);
