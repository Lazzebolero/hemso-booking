<?php

namespace App\Support;

class OpeningCheckpoints
{
    public const MAIN_ENTRANCE = 'main_entrance';

    public const EXITS_OPENABLE = 'exits_openable';

    public const EXITS_OPERATED = 'exits_operated';

    public const PATHS_CLEAR = 'paths_clear';

    public const OUTSIDE_CLEAR = 'outside_clear';

    public const STAIRS_CLEAR = 'stairs_clear';

    public const SIGNS_VISIBLE = 'signs_visible';

    public const EMERGENCY_LIGHTING = 'emergency_lighting';

    public const FIRE_DOORS = 'fire_doors';

    public const EXTINGUISHERS = 'extinguishers';

    public const ALARM_INDICATORS = 'alarm_indicators';

    public const COMMUNICATION = 'communication';

    public const ASSEMBLY_POINT = 'assembly_point';

    public const TEMPORARY_WORK = 'temporary_work';

    public const OUTCOME_OK = 'ok';

    public const OUTCOME_DEVIATION = 'deviation';

    public const OUTCOME_NOT_APPLICABLE = 'not_applicable';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MAIN_ENTRANCE => 'Huvudentré/ordinarie inpassering är säker och fri.',
            self::EXITS_OPENABLE => 'Samtliga utrymningsdörrar/nödutgångar för dagens verksamhet är upplåsta eller omedelbart öppningsbara från insidan.',
            self::EXITS_OPERATED => 'Varje utrymningsdörr provas genom faktisk manövrering av relevant beslag/öppningsfunktion.',
            self::PATHS_CLEAR => 'Utrymningsvägar och passager fram till utgångarna är fria från möbler, material, kablar, emballage och andra hinder.',
            self::OUTSIDE_CLEAR => 'Ytan direkt utanför respektive nödutgång är framkomlig och dörren kan öppnas fullt.',
            self::STAIRS_CLEAR => 'Trappor, ramper och nivåskillnader i utrymningsväg är fria och utan uppenbar halk-/snubbelrisk.',
            self::SIGNS_VISIBLE => 'Utrymningsskyltar är synliga och inte skymda.',
            self::EMERGENCY_LIGHTING => 'Nöd-/utrymningsbelysning visar inga uppenbara felindikeringar och armaturer är inte skadade/skymda.',
            self::FIRE_DOORS => 'Branddörrar och dörrstängare fungerar och dörrar är inte otillåtet uppställda.',
            self::EXTINGUISHERS => 'Handbrandsläckare/annan släckutrustning på ordinarie kontrollpunkter är åtkomlig och inte blockerad.',
            self::ALARM_INDICATORS => 'Brandlarm/utrymningslarm eller annan säkerhetsutrustning visar inga kända felindikeringar.',
            self::COMMUNICATION => 'Kommunikationsmöjlighet finns enligt lokal rutin (telefon/radio) och guider känner till dagens ansvarsfördelning.',
            self::ASSEMBLY_POINT => 'Samlingsplats och vägen dit är tillgänglig.',
            self::TEMPORARY_WORK => 'Inga tillfälliga arbeten, byggmaterial, fordon eller andra förändringar påverkar utrymningsvägar eller räddningstjänstens åtkomst.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return list<string>
     */
    public static function outcomes(): array
    {
        return [
            self::OUTCOME_OK,
            self::OUTCOME_DEVIATION,
            self::OUTCOME_NOT_APPLICABLE,
        ];
    }

    public static function outcomeLabel(string $outcome): string
    {
        return match ($outcome) {
            self::OUTCOME_OK => 'OK',
            self::OUTCOME_DEVIATION => 'Avvikelse',
            self::OUTCOME_NOT_APPLICABLE => 'Ej aktuell',
            default => $outcome,
        };
    }
}
