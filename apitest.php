<?php
$connection = curl_init();
$headers = ['Authorization: TOKEN AgAAAA**AQAAAA**aAAAAA**iw8TWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYuhCZaGqQ2dj6x9nY+seQ**sHQDAA**AAMAAA**f3cNXA8w7lUMPulAHw6J7fzqaLCR/z6AJlWBuA0tUTFWmCW0Fm2tvHcLyrOPFWEMBkwKWo2cFjvSth+byX6IbMO8Cm1rcxRCK1ENre0HoBNBd13Jn5BLTj7MBFjQ2eGsS/woOvinYFwKJjZdziJplMJOABLnK8wrdeY1XV/lq4P2RQbkTnV1cSxtCee0VmEJDbwv8+IJjHhll/4Mco/WZK89ocsaPxP20X5/rYtELmwcUPuODXpfeY3iotkgZs5NFNKAzMaufu4f991OqNX/DJWU0XvFMo9TY0ekLiK0Rfc4FfztRfk2YZwrT8L7WP+3n0JeRtUPUmglpTlQ82TqIP/F1d6Bi8hab+eLxoGLsRaxvKsVC1GWWOLcQKJrb8knfb0BGw0yXH+2TNyiQ1DZiKVqxApRWYKCnFASygy0tc1vKnBNOm6VrJ+DliY69ZLLqFi+dfHizbNCuvOspVhDxMgusWEydgYcXBXxciT/z6bGvvuB4BzR1ilFnlTKJs2PU0Y+rKbq+5VWloodumVQ1KTGUPlwDV3MGeaC+8eBdOt+WlIAzb2vrOC07vcQzbEFXw1xM1CDOTz8aZ1DLILHgSOBsRLkQ63Y/2E2gMK37yQwohlBy8ZBXSsw9w+aAtAaIl/ksmbyOPi2t/NL1DTp/B95zja2seVbcmX0gmglFjlN4KZaxXKN059fnNc5czCXtYPN/vHKnNpZbYsC+kYivMwJ2gIwc9jLVu3Q9yxjgTi+cMcxspzuCGQ7oPQyelXm',
    'Content-Type: application/json',
    'Accept: application/json'];
curl_setopt($connection, CURLOPT_URL, 'https://api.ebay.com/post-order/v2/cancellation/5070716738/approve');
//stop CURL from verifying the peer's certificate
curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($connection, CURLOPT_POST, 1);
//curl_setopt($connection, CURLOPT_POSTFIELDS, 'test=test');
curl_setopt($connection,CURLOPT_HEADER,1);
curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
curl_setopt($connection,CURLINFO_HEADER_OUT,true);
//curl_setopt($connection, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
//set it to return the transfer as a string from curl_exec
//curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($connection, CURLOPT_TIMEOUT, 10);
$response = curl_exec($connection);
$httpCode = curl_getinfo($connection,CURLINFO_HTTP_CODE);
var_dump(curl_getinfo($connection,CURLINFO_HEADER_OUT));exit;
curl_close($connection);
echo '<pre>';
var_dump($httpCode);
var_dump($response);