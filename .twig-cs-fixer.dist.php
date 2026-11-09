<?php

use TwigCsFixer\Config\Config;
use TwigCsFixer\Rules\Punctuation\PunctuationSpacingRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->overrideRule(new PunctuationSpacingRule(
    beforeOverride: ['}' => 1],
    afterOverride: ['{' => 1],
));

$config = new Config();
$config->setRuleset($ruleset);

return $config;
