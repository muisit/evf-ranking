<?php

namespace Fixtures;

class RegistrationFixture
{
    public static function init()
    {
        global $DB;
        $DB->onQuery("SELECT count(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_1, 0);
        $DB->onQuery("SELECT count(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_2, 0);
    }
}
