<?php

namespace Fixtures;

class EventFixture
{
    const EVENT_ID = 800;

    private static $models = [
        [
            "event_id" => self::EVENT_ID,
            "event_name" => "Test Event",
            "event_open" => "2020-01-01",
            "event_year" => "2020",
            "event_type" => "",
            "event_location" => "",
            "event_country" => "",
            "event_in_ranking" => "",
            "event_factor" => "",
            "event_frontend" => "",
            "event_feed" => "",
            "event_config" => ""
        ]
    ];

    public static function init()
    {
    }
}
