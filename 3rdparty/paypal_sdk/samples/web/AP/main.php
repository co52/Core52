<?php

/**
 * Required PHP extension list.
 */
$reqd_ext = array(
    'curl',
    'openssl'
);

/**
 * Checks.
 */
if ($err = validate_php_env($reqd_ext)) {
    error_message('One or more required PHP extensions is missing: ' . $err);
    exit(1);
}
require_once('docs/README_AP.html');

function validate_php_env($required_extensions)
{
    // Exit-code style - empty string == succeeded.
    $result = '';

    $php_ext = get_loaded_extensions();
    foreach ($required_extensions as $ext) {
        if (!in_array($ext, $php_ext)) {
            $result .= " $ext";
        }
    }

    return trim($result);
}

function error_message($msg)
{
    echo "INSTALLATION ERROR:\n$msg\n";
}

?>