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
        "\t\t\t\t\t\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON) && (\$id !== ST_QUESTION || !\$this->rightTokenIs(T_STRING))));",
    ],
    // FIX pass ClassToStatic : https://github.com/nanch/phpfmt_stable/issues/31
    [
        "\t\t\t\t\$this->tkns[\$i] = [T_STRING, self::PLACEHOLDER];",
        "\t\t\t\t\$this->tkns[\$i] = [T_STRING, static::PLACEHOLDER];",
    ],
];

foreach ($patchs as list($search, $replacement)) {
    $lineNumber = array_search($search, $lines);
    if ($lineNumber) {
        $lines[$lineNumber] = $replacement;
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
