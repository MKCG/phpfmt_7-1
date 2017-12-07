<?php

// replace this line if necessary
$pharPath = "/home/mkcg/.config/sublime-text-3/Packages/phpfmt/fmt.phar";
$tmpDir = __DIR__;

(new Phar($pharPath))->extractTo($tmpDir);

$lines = explode("\n", file_get_contents('fmt.stub.php'));

// FIX PHP7.1 yield from : https://github.com/nanch/phpfmt_stable/issues/37
$toAdd = [
    "",
    "\t\t\tcase T_YIELD_FROM:",
    "\t\t\t\t\$this->appendCode(\$text . \$this->getSpace(\$this->rightTokenIs(T_STRING)));",
    "\t\t\t\tbreak;",
];

if (array_search($toAdd[1], $lines) === false) {
    foreach ($toAdd as $key => $value) {
        array_splice($lines, 5227 + $key, 0, $value);
    }
}

$patchs = [
    // FIX PHP7.1 nullable types : https://github.com/nanch/phpfmt_stable/issues/36
    [
        "\t\t\t\t\t\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON)));",
        "\t\t\t\t\t\$this->appendCode(\$this->getSpace(\$id !== ST_QUESTION || !\$this->leftMemoUsefulTokenIs(ST_PARENTHESES_OPEN)) . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON) && (\$id !== ST_QUESTION || !\$this->rightTokenIs(T_STRING))));",
    ],
    // FIX pass ClassToStatic : https://github.com/nanch/phpfmt_stable/issues/31
    [
        "\t\t\t\t\$this->tkns[\$i] = [T_STRING, self::PLACEHOLDER];",
        "\t\t\t\t\$this->tkns[\$i] = [T_STRING, static::PLACEHOLDER];",
    ],
    // Fix : PHP keyword used as function names must not be lowercased since PHP7
    [
        "\t\t\t\tT_CONSTANT_ENCAPSED_STRING == \$id",
        "\t\t\t\tT_CONSTANT_ENCAPSED_STRING == \$id || T_STRING == \$id"
    ]
];

foreach ($patchs as list($search, $replacement)) {
    $lineNumber = array_search($search, $lines);
    if ($lineNumber) {
        $lines[$lineNumber] = $replacement;
    }
}

$pathTokenParse = [
    // Fix : PHP keyword used as function names must not be lowercased since PHP7
    [
        "final class PSR2KeywordsLowerCase extends FormatterPass {",
        "\tpublic function format(\$source) {",
        "\t\t\$this->tkns = token_get_all(\$source, TOKEN_PARSE);"
    ],
    // Fix : visibility modifier is preserved when method names is a PHP keyword :
    // - https://github.com/nanch/phpfmt_stable/issues/18
    // - https://github.com/nanch/phpfmt_stable/issues/19
    [
        "final class PSR2ModifierVisibilityStaticOrder extends FormatterPass {",
        "\tpublic function format(\$source) {",
        "\t\t\$this->tkns = token_get_all(\$source, TOKEN_PARSE);"
    ],
];

foreach ($pathTokenParse as list($search, $methodStart, $replacement)) {
    $lineNumber = array_search($search, $lines);

    for ($i = $lineNumber; isset($lines[$i]); $i++) {
        if ($lines[$i] === $methodStart) {
            $lines[$i+1] = $replacement;
            break;
        }
    }
}

// Fix replace is_null when preceded by an exclamation to negate it : https://github.com/nanch/phpfmt_stable/issues/11
$inject = [
    [
        'insert_after',
        "\t\t\$this->useCache = true;",
        [
            "\t\t\$isEqual = true;"
        ]
    ],
    [
        'insert_after',
        "\t\t\t\$this->cache = [];",
        [
            "",
            "\t\t\tif (\$id == ST_EXCLAMATION) {",
            "\t\t\t\tlist(\$nextToken, \$nextText) = \$this->rightUsefulToken();",
            "\t\t\t\tif (\$nextToken === T_STRING && strtolower(\$nextText) === 'is_null') {",
            "\t\t\t\t\t\$isEqual = false;",
            "\t\t\t\t\tcontinue;",
            "\t\t\t\t}",
            "\t\t\t}",
            "",
        ]
    ],
    [
        'replace',
        "\t\t\t\t\$this->appendCode('===');",
        "\t\t\t\t\$this->appendCode(\$isEqual ? '===' : '!==');"
    ],
    [
        'insert_after',
        "\t\t\t\t\$this->printAndStopAt(ST_PARENTHESES_CLOSE);",
        [
            "\t\t\t\t\$isEqual = true;"
        ]
    ]
];


$isNullBegin = array_search("final class ReplaceIsNull extends AdditionalPass {", $lines);

if ($isNullBegin) {
    $i = $isNullBegin;
    foreach ($inject as list($type, $search, $replacement)) {
        for (; isset($lines[$i]); $i++) {
            if ($lines[$i] === $search) {
                if ($type === 'insert_after') {
                    array_splice($lines, $i+1, 0, $replacement);
                } elseif ($type === 'replace') {
                    $lines[$i] = $replacement;
                }
                break;
            }
        }
    }
}


file_put_contents(
    'fmt.stub.php',
    implode("\n", $lines)
);

$phar = new Phar($pharPath);
$phar->setDefaultStub('fmt.stub.php', 'index.php');
$phar->addFile('fmt.stub.php');

unlink('fmt.stub.php');
