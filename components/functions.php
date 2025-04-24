<?php
// components/functions.php

function getActivePage() {
    $currentFile = basename($_SERVER['PHP_SELF']);
    return pathinfo($currentFile, PATHINFO_FILENAME);
}

function isActiveLink($page) {
    return getActivePage() === $page ? 'active' : '';
}
?>