<?php

namespace FpDbTest;

enum SpecifiersEnum: string
{
    case INT = '?d';
    case FLOAT = '?f';
    case MIXED = '?';
    case ARR = '?a';
    case ID = '?#';
}
