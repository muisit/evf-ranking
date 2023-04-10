<?php

namespace Fixtures;

class RoleFixture
{
    const ROLE_ID_1 = 12;
    const ROLE_ID_2 = 38;

    private static $models = [
        [
            "role_id" => self::ROLE_ID_1,
            "role_name" => "role name",
            "role_type" => RoleTypeFixture::ROLETYPE_ID_1
        ]
    ];

    public static function init()
    {
        global $DB;
        $DB->onQuery(
            "SELECT COUNT(*) as cnt FROM TD_Role " .
            "WHERE role_type = " .
            RoleTypeFixture::ROLETYPE_ID_1,
            [(object)['cnt' => 1]]
        );
        $DB->onQuery(
            "SELECT COUNT(*) as cnt FROM TD_Role " .
            "WHERE role_type = " .
            RoleTypeFixture::ROLETYPE_ID_2,
            [(object)['cnt' => 0]]
        );
    }
}
