<?php
session_destroy();
header("Location: " . BASE_URL . "?page=login");
exit;
