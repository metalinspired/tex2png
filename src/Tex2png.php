<?php

namespace Tex2png;

use RuntimeException;

use function file_exists;
use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use function serialize;
use function sha1;
use function shell_exec;
use function sprintf;

/**
 * Helper to generate PNG from LaTeX formula
 *
 * @author Grégoire Passault <g.passault@gmail.com>
 * @author Milan Divkovic
 */
class Tex2png
{
    /**
     * Where is the LaTex ?
     */
    public const LATEX = "/usr/bin/latex";

    /**
     * Where is the DVIPNG ?
     */
    public const DVIPNG = "/usr/bin/dvipng";

    /**
     * LaTeX packages
     */
    public array $packages = ['amssymb,amsmath', 'color', 'amsfonts', 'amssymb', 'pst-plot'];

    /**
     * Temporary directory used to write temporary files needed for generation
     */
    public string $tmpDir = '/tmp';

    /**
     * Target file
     */
    public ?string $file = null;

    /**
     * Hash
     */
    public string $hash;

    /**
     * LaTeX formula
     */
    public string $formula;

    /**
     * Target density
     */
    public int $density;

    public function __construct(string $formula, int $density = 155)
    {
        $this->formula = $formula;
        $this->density = $density;
        $this->hash = sha1(serialize([$formula, $density]));
    }

    /**
     * Sets the target directory
     */
    public function saveTo(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Generates the image
     */
    public function generate(): static
    {
        if (
            !file_exists($this->tmpDir)
            && !mkdir($this->tmpDir)
            && !is_dir($this->tmpDir)
        ) {
            throw new \RuntimeException(sprintf('Temporary directory "%s" was not created', $this->tmpDir));
        }

        $target = $this->file ?? ($this->tmpDir . '/' . $this->hash . '.png');

        try {
            // Generates the LaTeX file
            $this->createFile();

            // Compile the latexFile
            $this->latexFile();

            // Converts the DVI file to PNG
            $this->dvi2png($target);
        } finally {
            $this->clean();
        }

        return $this;
    }

    /**
     * Sets the temporary directory
     */
    public function setTempDirectory(string $directory): static
    {
        $this->tmpDir = $directory;

        return $this;
    }

    /**
     * Returns the PNG file
     */
    public function getFile(): string
    {
        return $this->hookFile($this->file);
    }

    /**
     * Hook that helps to extend this class (eg: adding a prefix or suffix)
     */
    public function hookFile(string $filename): string
    {
        return $filename;
    }

    /**
     * The string representation is the cache file
     */
    public function __toString()
    {
        return $this->getFile();
    }

    /**
     * Create the LaTeX file
     */
    protected function createFile(): void
    {
        $tmpFile = $this->tmpDir . '/' . $this->hash . '.tex';
        $packages = '\usepackage{' . implode("}\n\usepackage{", $this->packages) . '}';

        $tex = <<<TEX
\documentclass[12pt]{article}
\usepackage[utf8]{inputenc}
$packages
\begin{document}
\pagestyle{empty}
\begin{displaymath}
$this->formula
\\end{displaymath}
\\end{document}
TEX;

        if (file_put_contents($tmpFile, $tex) === false) {
            throw new RuntimeException('Failed to open target file');
        }
    }

    /**
     * Compiles the LaTeX to DVI
     */
    protected function latexFile(): void
    {
        $command = 'cd ' . $this->tmpDir . '; ' . static::LATEX . ' ' . $this->hash . '.tex < /dev/null |grep ^!|grep -v Emergency > ' . $this->tmpDir . '/' . $this->hash . '.err 2> /dev/null 2>&1';

        shell_exec($command);

        if (!file_exists($this->tmpDir . '/' . $this->hash . '.dvi')) {
            throw new RuntimeException('Unable to compile LaTeX formula (is latex installed? check syntax)');
        }
    }

    /**
     * Converts the DVI file to PNG
     */
    protected function dvi2png(string $target): void
    {
        // XXX background: -bg 'rgb 0.5 0.5 0.5'
        $command = static::DVIPNG . ' -q -T tight -D ' . $this->density . ' -o ' . $target . ' ' . $this->tmpDir . '/' . $this->hash . '.dvi 2>&1';

        if (shell_exec($command) === null) {
            throw new RuntimeException('Unable to convert the DVI file to PNG (is dvipng installed?)');
        }
    }

    /**
     * Cleaning
     */
    protected function clean(): void
    {
        @shell_exec('rm -f ' . $this->tmpDir . '/' . $this->hash . '.* 2>&1');
    }
}
