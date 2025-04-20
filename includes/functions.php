<?php
// File untuk menyimpan fungsi-fungsi helper

function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
