<?php

namespace Fixtures;

class AccreditationTemplateFixture
{
    const ACCREDITATIONTEMPLATE_ID = 9002;

    private static $models = [];

    public static function init()
    {
        self::$models = [
            [
                "id" => self::ACCREDITATIONTEMPLATE_ID,
                "event_id" => EventFixture::EVENT_ID,
                'name' => "AccreditationTemplate",
                "content" => json_encode([
                    "roles" => [RoleFixture::ROLE_ID_1]
                ])
            ]
        ];
        global $DB;
        $DB->onQuery("SELECT * FROM TD_Accreditation_Template WHERE event_id = " . EventFixture::EVENT_ID . " ORDER BY id asc LIMIT 100000", [
            self::$models[0]
        ]);
    }
}
