<?php

namespace Fixtures;

class AccreditationFixture
{
    const ACCREDITATION_ID = 302;

    private static $models = [
        [
            "id" => self::ACCREDITATION_ID,
            "fencer_id" => FencerFixture::FENCER_ID_1,
            "event_id" => EventFixture::EVENT_ID,
            "data" => "{}",
            "hash" => "hash:none",
            "file_hash" => "filehash:none",
            "template_id" => AccreditationTemplateFixture::ACCREDITATIONTEMPLATE_ID,
            "file_id" => '21',
            "generated" => "2022-01-02 10:21:13",
            "is_dirty" => '0',
            "fe_id" => '792'
        ]
        ];

    public static function init()
    {
        global $DB;
        $DB->onQuery(
            "SELECT * FROM TD_Accreditation " .
            "WHERE exists(SELECT * FROM TD_Registration WHERE " .
            "registration_fencer=TD_Accreditation.fencer_id AND registration_mainevent=TD_Accreditation.event_id " .
            "AND registration_role = " .
            RoleFixture::ROLE_ID_1 .
            ") AND template_id IN ('" .
            AccreditationTemplateFixture::ACCREDITATIONTEMPLATE_ID .
            "') AND event_id = " .
            EventFixture::EVENT_ID,
            [
                self::$models[0]
            ]
        );
    }
}
