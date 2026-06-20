<?php

function onlyNumbers(mixed $value): string
{
    return preg_replace('/\D/', '', (string) $value) ?? '';
}
