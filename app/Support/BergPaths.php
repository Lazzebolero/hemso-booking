<?php

namespace App\Support;

class BergPaths
{
    public static function presence(): string
    {
        return '/berget';
    }

    public static function numbers(): string
    {
        return '/berget/nummer';
    }

    public static function log(): string
    {
        return '/berget/logg';
    }

    public static function people(): string
    {
        return '/berget/personer';
    }

    public static function poll(): string
    {
        return '/berget/poll';
    }

    public static function stamp(): string
    {
        return '/berget/stampa';
    }

    public static function stampPerson(int|string $personId): string
    {
        return '/berget/personer/'.$personId.'/stampa';
    }

    public static function depart(int|string $personId): string
    {
        return '/berget/personer/'.$personId.'/utrest';
    }

    public static function restore(int|string $personId): string
    {
        return '/berget/personer/'.$personId.'/aterstall';
    }

    public static function peopleImport(): string
    {
        return '/berget/personer/import';
    }

    public static function peopleEdit(int|string $personId): string
    {
        return '/berget/personer/'.$personId.'/redigera';
    }

    public static function peopleUpdate(int|string $personId): string
    {
        return '/berget/personer/'.$personId;
    }
}
