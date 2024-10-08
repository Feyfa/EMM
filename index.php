<?php 

$createCustomer = $stripe->customers->create([
    'name' => $name . $company_name,
    'address_line1' => $address,
    'address_city' => $city,
    'address_state' => $state,
    'address_country' => $country,
    'address_zip' => $zip,
    'phone' => $phone,
    'email' => $email,
    'description' => $_company_name,
    'source' => $tokenid,
], ['stripe_account' => $accConID]);