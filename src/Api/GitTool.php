<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Api;

use Exception;

final class GitTool
{
    public const int SHORT = 1;

    private function __construct() {}

    public static function getCommitSha(string $dir, int $mode = 0): string
    {
        $gitDir = self::resolveGitDir($dir);
        $sha = self::resolveCommitSha($gitDir);
        if (($mode & (self::SHORT)) === self::SHORT) {
            $sha = substr($sha, 0, 7);
        }
        return $sha;
    }

    public static function isCommitSha(string $sha): bool
    {
        return preg_match('/^[0-9a-f]{40}$/', $sha) !== false;
    }

    private static function appendPath(string $dir, string $more): string
    {
        if ($more === '') {
            return $dir;
        }
        if ($more[0] === '/' || $more[0] === '\\') {
            // absolute path
            return $more;
        }
        if (preg_match('/^[a-zA-Z]:[\/\\\\]/', $more) === 1) {
            // windows absolute path
            return $more;
        }
        return $dir . DIRECTORY_SEPARATOR . $more;
    }

    private static function resolveDir(string $dir): ?string
    {
        $realDir = realpath($dir);
        if ($realDir === false || !is_dir($realDir)) {
            return null;
        }
        return $realDir;
    }

    private static function readFileContent(string $path): string
    {
        return trim((string) @file_get_contents($path));
    }

    private static function parseGitFileFor(string $tag, string $content): ?string
    {
        $tag .= ': ';
        if (!str_starts_with($content, $tag)) {
            return null;
        }
        return trim(substr($content, strlen($tag)));
    }

    private static function resolveGitDir(string $dir): string
    {
        $realDir = self::resolveDir($dir);
        if ($realDir !== null) {
            $dotGitPath = self::appendPath($realDir, '.git');
            if (is_dir($dotGitPath)) {
                return $dotGitPath;
            }
            if (is_file($dotGitPath)) {
                // worktree or submodule
                $dotGit = self::readFileContent($dotGitPath);
                $gitDir = self::parseGitFileFor('gitdir', $dotGit);
                if ($gitDir !== null) {
                    $gitDir = self::appendPath($realDir, $gitDir);
                }
                if ($gitDir !== null) {
                    return $gitDir;
                }
            }
        }
        throw new Exception('Not a Git repository');
    }

    private static function readHeadFile(string $dir): string
    {
        $headPath = self::appendPath($dir, 'HEAD');
        if (!is_file($headPath)) {
            throw new Exception('HEAD does not exist');
        }
        $head = self::readFileContent($headPath);
        if ($head === '') {
            throw new Exception('HEAD is not valid');
        }
        return $head;
    }

    private static function resolveCommitSha(string $dir): string
    {
        $head = self::readHeadFile($dir);
        $ref = self::parseGitFileFor('ref', $head);
        if ($ref === null) {
            $sha = $head;
        } else {
            $commonDir = self::resolveCommonDir($dir);
            $sha = self::resolveLooseRef($commonDir, $ref)
                ?? self::resolvePackedRefs($commonDir, $ref);
        }
        if ((string) $sha === '') {
            throw new Exception('Commit SHA is not found');
        }
        if (!self::isCommitSha($sha)) {
            throw new Exception('Commit SHA is not valid');
        }
        return $sha;
    }

    private static function resolveCommonDir(string $dir): string
    {
        $commonDirPath = self::appendPath($dir, 'commondir');
        if (!is_file($commonDirPath)) {
            return $dir;
        }
        // commondir
        $content = self::readFileContent($commonDirPath);
        if ($content !== '') {
            $commonDir = self::resolveDir(self::appendPath($dir, $content));
            if ($commonDir !== null) {
                return $commonDir;
            }
        }
        throw new Exception('commondir is not valid');
    }

    private static function resolveLooseRef(string $commonDir, string $ref): ?string
    {
        $refPath = self::appendPath($commonDir, $ref);
        if (!is_file($refPath)) {
            return null;
        }
        $sha = self::readFileContent($refPath);
        if ($sha !== '') {
            return $sha;
        }
        return null;
    }

    private static function resolvePackedRefs(string $commonDir, string $ref): ?string
    {
        $packedRefPath = self::appendPath($commonDir, 'packed-refs');
        if (!is_file($packedRefPath)) {
            return null;
        }
        $fp = fopen($packedRefPath, 'r');
        if ($fp === false) {
            return null;
        }
        try {
            $pattern = '/^\s*([0-9a-f]+)\s+' . preg_quote($ref, '/') . '\s*$/';
            while (($line = fgets($fp)) !== false) {
                if (preg_match($pattern, $line, $matches) === 1) {
                    return $matches[1];
                }
            }
        } finally {
            fclose($fp);
        }
        return null;
    }
}
