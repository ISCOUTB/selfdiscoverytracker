<?php
$observers = array(
    array(
        'eventname'   => '\core\event\course_viewed',
        'callback'    => '\mod_selfdiscoverytracker\observer::course_viewed',
    ),
);
