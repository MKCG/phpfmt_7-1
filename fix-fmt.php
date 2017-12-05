<?php

// replace this line if necessary
$pharPath = "~/.config/sublime-text-3/Packages/phpfmt/fmt.phar";
$tmpDir = __DIR__;

(new Phar($pharPath))->extractTo($tmpDir);

file_put_contents(
    'fmt.stub.php',
    str_replace(
        "\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON)));",
        "\$this->appendCode(' ' . \$text . \$this->getSpace(!\$this->rightTokenIs(ST_COLON) && (\$id !== ST_QUESTION || !\$this->rightTokenIs(T_STRING))));",
        file_get_contents('fmt.stub.php')
    )
);

$phar = new Phar($pharPath);
$phar->setDefaultStub('fmt.stub.php', 'index.php');
$phar->addFile('fmt.stub.php');

unlink('fmt.stub.php');
