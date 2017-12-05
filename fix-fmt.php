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

// FIX PHP7.1 nullable types : https://github.com/nanch/phpfmt_stable/issues/36
$nullableLine = array_search("\t\t\t\t\t\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON)));", $lines);
if ($nullableLine !== false) {
    $lines[$nullableLine] = "\t\t\t\t\t\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON) && (\$id !== ST_QUESTION || !\$this->rightTokenIs(T_STRING))));";
}

file_put_contents(
    'fmt.stub.php',
    implode("\n", $lines)
);

$phar = new Phar($pharPath);
$phar->setDefaultStub('fmt.stub.php', 'index.php');
$phar->addFile('fmt.stub.php');

unlink('fmt.stub.php');
