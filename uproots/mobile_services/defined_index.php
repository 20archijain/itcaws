<?php

if (!isset($I_am_req_always) or $I_am_req_always !== "I @m req @lway$ tokeniZer") {
    header("HTTP/1.0 404 Not Found");
    die;
}
