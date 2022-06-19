<?php

namespace ZnTool\Package\Domain\Libs;

use Illuminate\Support\Arr;
use ZnCore\Base\Libs\Text\Helpers\StringHelper;
use ZnCore\Base\Legacy\Yii\Helpers\ArrayHelper;
use ZnCore\Base\Legacy\Yii\Helpers\FileHelper;
use ZnCore\Base\Libs\FileSystem\Helpers\FilePathHelper;
use ZnLib\Console\Domain\Base\BaseShell;
use ZnLib\Console\Domain\Exceptions\ShellException;

class GitShell extends BaseShell
{

    /**
     * Creates a tag.
     * `git tag <name>`
     *
     * @param $name string
     *
     * @throws ShellException
     * @return static
     */
    public function createTag($name)
    {
        return $this->begin()->run('git tag', $name)->end();
    }

    /**
     * Removes tag.
     * `git tag -d <name>`
     *
     * @param $name string
     *
     * @throws ShellException
     * @return static
     */
    public function removeTag($name)
    {
        return $this
            ->begin()
            ->run('git tag', ['-d' => $name,])
            ->end();
    }

    /**
     * Renames tag.
     * `git tag <new> <old>`
     * `git tag -d <old>`
     *
     * @param $oldName string
     * @param $newName string
     *
     * @throws ShellException
     * @return static
     */
    public function renameTag($oldName, $newName)
    {
        return $this
            ->begin()// http://stackoverflow.com/a/1873932
            // create new as alias to old (`git tag NEW OLD`)
            ->run('git tag', $newName, $oldName)// delete old (`git tag -d OLD`)
            ->removeTag($oldName)// WARN! removeTag() calls end() method!!!
            ->end();
    }

    /**
     * Returns list of tags in repo.
     *
     * @return string[]|NULL  NULL => no tags
     */
    public function getTags()
    {
        return $this->extractFromCommand('git tag', 'trim');
    }

    public function getTagsSha($tag = null)
    {
        if (empty($tag)) {
            $tag = $this->getTags();
        }
        $tag = ArrayHelper::toArray($tag);
        $tag = implode(' ', $tag);
        $result = $this->extractFromCommand('git show-ref --tags -d ' . $tag, 'trim');
        if (empty($result)) {
            return [];
        }
        $tagSha = [];
        foreach ($result as $key => $item) {
            list($sha, $tagName) = explode(' ', $item);
            $tagSha[] = [
                'name' => trim($tagName, "^{}"),
                'sha' => $sha,
            ];
        }
        return $tagSha;
    }

    public function getCommits()
    {
        $result = $this->extractFromCommand('git log', 'trim');
        $new = [];
        if ( ! empty($result)) {
            $name = null;
            foreach ($result as $key => $item) {
                if ($key - 1 >= 0 && empty($result[$key - 1]) && empty($result[$key + 1])) {
                    $new[$name]['message'] = $item;
                } else {
                    $arr = explode(' ', $item);
                    if (count($arr) > 1) {
                        $k = $this->getCommitKey($item);
                        array_shift($arr);
                        $v = implode(' ', $arr);
                        if ($k == 'commit') {
                            $name = $v;
                            $k = 'sha';
                        }
                        $new[$name][$k] = $v;
                    }
                }
            }
        }
        return array_values($new);
    }

    private function getCommitKey($item)
    {
        if (empty($item)) {
            return null;
        }
        $arr = explode(' ', $item);
        if (count($arr) > 1) {
            $k = trim($arr[0], ':');
            $k = strtolower($k);
            return $k;
        } else {
            return null;
        }
    }

    /**
     * Merges branches.
     * `git merge <options> <name>`
     *
     * @param $branch string
     * @param $options array|NULL
     *
     * @throws ShellException
     * @return static
     */
    public function merge($branch, $options = null)
    {
        return $this
            ->begin()
            ->run('git merge', $options, $branch)
            ->end();
    }

    /**
     * Creates new branch.
     * `git branch <name>`
     * (optionaly) `git checkout <name>`
     *
     * @param $name string
     * @param $checkout bool
     *
     * @throws ShellException
     * @return static
     */
    public function createBranch($name, $checkout = false)
    {
        $this->begin();
        // git branch $name
        $this->run('git branch', $name);
        if ($checkout) {
            $this->checkout($name);
        }
        return $this->end();
    }

    /**
     * Removes branch.
     * `git branch -d <name>`
     *
     * @param $name string
     *
     * @throws ShellException
     * @return static
     */
    public function removeLocalBranch($name)
    {
        return $this
            ->begin()
            ->run("git branch -D $name")
            ->end();
    }

    public function removeRemoteBranch($name, string $remote = 'origin')
    {
        return $this
            ->begin()
            ->run("git push -d $remote $name")
            ->end();
    }

    /**
     * Gets name of current branch
     * `git branch` + magic
     *
     * @return string
     * @throws ShellException
     */
    public function getCurrentBranchName()
    {
        try {
            $branch = $this->extractFromCommand('git branch -a', function ($value) {
                if (isset($value[0]) && $value[0] === '*') {
                    return trim(substr($value, 1));
                }

                return false;
            });
            if (is_array($branch)) {
                return $branch[0];
            }
        } catch (ShellException $e) {
        }
        throw new ShellException('Getting current branch name failed.');
    }

    /**
     * Returns list of all (local & remote) branches in repo.
     *
     * @return string[]|NULL  NULL => no branches
     */
    public function getBranches()
    {
        return $this->extractFromCommand('git branch -a', function ($value) {
            return trim(substr($value, 1));
        });
    }

    /**
     * Returns list of local branches in repo.
     *
     * @return string[]|NULL  NULL => no branches
     */
    public function getLocalBranches()
    {
        return $this->extractFromCommand('git branch', function ($value) {
            return trim(substr($value, 1));
        });
    }

    /**
     * Checkout branch.
     * `git checkout <branch>`
     *
     * @param $name string
     *
     * @throws ShellException
     * @return static
     */
    public function checkout($name)
    {
        return $this
            ->begin()
            ->run('git checkout ' . $name)
            ->end();
    }

    /**
     * Removes file(s).
     * `git rm <file>`
     *
     * @param $file string|string[]
     *
     * @throws ShellException
     * @return static
     */
    public function removeFile($file)
    {
        if ( ! is_array($file)) {
            $file = func_get_args();
        }
        $this->begin();
        foreach ($file as $item) {
            $this->run('git rm', $item, '-r');
        }
        return $this->end();
    }

    /**
     * Adds file(s).
     * `git add <file>`
     *
     * @param $file string|string[]
     *
     * @throws ShellException
     * @return static
     */
    public function addFile($file)
    {
        if ( ! is_array($file)) {
            $file = func_get_args();
        }
        $this->begin();
        foreach ($file as $item) {
            // TODO: ?? is file($repo . / . $item) ??
            $this->run('git add', $item);
        }
        return $this->end();
    }

    /**
     * Adds all created, modified & removed files.
     * `git add --all`
     *
     * @throws ShellException
     * @return static
     */
    public function addAllChanges()
    {
        return $this
            ->begin()
            ->run('git add --all')
            ->end();
    }

    /**
     * Renames file(s).
     * `git mv <file>`
     *
     * @param $file string|string[] from : array('from' => 'to', ...) || (from, to)
     * @param $to string|NULL
     *
     * @throws ShellException
     * @return static
     */
    public function renameFile($file, $to = null)
    {
        if ( ! is_array($file)) // rename(file, to);
        {
            $file = [
                $file => $to,
            ];
        }
        $this->begin();
        foreach ($file as $from => $to) {
            $this->run('git mv', $from, $to);
        }
        return $this->end();
    }

    /**
     * Commits changes
     * `git commit <params> -m <message>`
     *
     * @param $message          string
     * @param $params string[] param => value
     *
     * @throws ShellException
     * @return static
     */
    public function commit($message, $params = null)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        return $this
            ->begin()
            ->run("git commit", $params, ['-m' => $message,])
            ->end();
    }

    /**
     * Exists changes?
     * `git status` + magic
     *
     * @return bool
     */
    public function hasChanges()
    {
        $this->begin();
        $outputLines = null;
        $lastLine = $this->runExec('git status', $outputLines);
        $this->end();
        //dd($outputLines);
        if($this->searchText($outputLines, ['Changes not staged for commit:', 'Untracked files:', '"git add"'])) {
            return true;
        }
        //dd($outputLines);
        //return $this->searchText($outputLines, ['nothing to commit']);
        //return (strpos($lastLine, 'nothing to commit')) === false; // FALSE => changes
        return false;
    }

    public function status()
    {
        $this->begin();
        $outputLines = null;
        $lastLine = $this->runExec('git status', $outputLines);
        $this->end();
        return $outputLines;
    }

    private function searchText(array $lines, $needles) {
        foreach ($lines as $line) {
            foreach ($needles as $needle) {
                $isHas = strpos(mb_strtolower($line), mb_strtolower($needle)) !== false;
                if($isHas) {
                    return true;
                }
            }
        }
        return false;
    }

    public function searchText2(array $lines, $needles) {
        $needles = ArrayHelper::toArray($needles);
        foreach ($lines as $line) {
            foreach ($needles as $needle) {
                $isHas = strpos(mb_strtolower($line), mb_strtolower($needle)) !== false;
                if($isHas) {
                    return true;
                }
            }
        }
        return false;
    }

    public function matchText(array $lines, $needles) {
        $needles = ArrayHelper::toArray($needles);
        $mathesResult = [];
        foreach ($lines as $line) {
            $line = StringHelper::removeDoubleSpace($line);
            $line = trim($line);
            foreach ($needles as $needleIndex => $needle) {
                $needle = str_replace(' ', '\s', $needle);
                $expression = "/{$needle}/i";
//                dump($expression);
                $isMath = preg_match($expression, $line, $mathes);
                if($isMath) {
                    $mathesResult[$needleIndex] = $mathes;
                }
            }
        }
        return $mathesResult;
    }

    public function matchTextAll(array $lines, $needles) {
        $needles = ArrayHelper::toArray($needles);
        $mathesResult = [];
        //$lines = [implode("\n", $lines)];
        foreach ($lines as $line) {
            $line = StringHelper::removeDoubleSpace($line);
            $line = trim($line);
            foreach ($needles as $needleIndex => $needle) {
                $needle = str_replace(' ', '\s', $needle);
                $expression = "/{$needle}/i";
//                dump($expression);
                $isMath = preg_match_all($expression, $line, $mathes);
                if($isMath) {
                    $mathesResult[] = $mathes;
                }
            }
        }
        return $mathesResult;
    }

    public function clone($remote = null, array $params = null)
    {
        FileHelper::createDirectory($this->getPath());
        if ( ! is_array($params)) {
            $params = [];
        }
        return $this
            ->begin()
            ->run("git clone $remote .", $params)
            ->end();
    }

    /**
     * Pull changes from a remote
     *
     * @param $remote string|NULL
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function pull($remote = null, array $params = null)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        return $this
            ->begin()
            ->run("git pull $remote", $params)
            ->end();
    }

    /**
     * Pull changes from a remote
     *
     * @param $remote string|NULL
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function pullWithInfo($remote = null)
    {
        $result = $this->extractFromCommand("git pull $remote", 'trim');
        if(is_array($result)) {
            $result = implode(PHP_EOL, $result);
        }
        $result = trim($result);
        return $result;
    }

    public function pushWithInfo($remote = null)
    {
        $result = $this->extractFromCommand("git push $remote", 'trim');
        //$result = implode(PHP_EOL, $result);
        //$result = trim($result);
        return $result;
    }

    /**
     * Push changes to a remote
     *
     * @param $remote string|NULL
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function push($remote = null, array $params = null)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        return $this
            ->begin()
            ->run("git push $remote", $params)
            ->end();
    }

    /**
     * Run fetch command to get latest branches
     *
     * @param $remote string|NULL
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function fetch($remote = null, array $params = null)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        return $this
            ->begin()
            ->run("git fetch $remote", $params)
            ->end();
    }

    public function showRemote()
    {
        $array = $this->extractFromCommand('git config --get remote.origin.url', 'trim');
        return ArrayHelper::first($array);
    }

    /**
     * Adds new remote repository
     *
     * @param $name string
     * @param $url string
     * @param $params array|NULL
     *
     * @return static
     */
    public function addRemote($name, $url, array $params = null)
    {
        return $this
            ->begin()
            ->run('git remote add', $params, $name, $url)
            ->end();
    }

    /**
     * Renames remote repository
     *
     * @param $oldName string
     * @param $newName string
     *
     * @return static
     */
    public function renameRemote($oldName, $newName)
    {
        return $this
            ->begin()
            ->run('git remote rename', $oldName, $newName)
            ->end();
    }

    /**
     * Removes remote repository
     *
     * @param $name string
     *
     * @return static
     */
    public function removeRemote($name)
    {
        return $this
            ->begin()
            ->run('git remote remove', $name)
            ->end();
    }

    /**
     * Changes remote repository URL
     *
     * @param $name string
     * @param $url string
     * @param $params array|NULL
     *
     * @return static
     */
    public function setRemoteUrl($name, $url, array $params = null)
    {
        return $this
            ->begin()
            ->run('git remote set-url', $params, $name, $url)
            ->end();
    }

    /**
     * Init repo in directory
     *
     * @param $directory string
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function init($directory, array $params = null)
    {
        if (is_dir("$directory/.git")) {
            throw new ShellException("Repo already exists in $directory.");
        }
        if ( ! is_dir($directory) && ! @mkdir($directory, 0777, true)) // intentionally @; not atomic; from Nette FW
        {
            throw new ShellException("Unable to create directory '$directory'.");
        }
        $cwd = getcwd();
        chdir($directory);
        $this->runExec(self::processCommand([
            'git init',
            $params,
            $directory,
        ]), $output, $returnCode);
        if ($returnCode !== 0) {
            throw new ShellException("Git init failed (directory $directory).");
        }
        $repo = getcwd();
        chdir($cwd);
        return new static($repo);
    }

    /**
     * Clones GIT repository from $url into $directory
     *
     * @param $url string
     * @param $directory string|NULL
     * @param $params array|NULL
     *
     * @return static
     * @throws ShellException
     */
    public function cloneRepository($url, $directory = null, array $params = null)
    {
        if ($directory !== null && is_dir("$directory/.git")) {
            throw new ShellException("Repo already exists in $directory.");
        }
        $cwd = getcwd();
        if ($directory === null) {
            $directory = self::extractRepositoryNameFromUrl($url);
            $directory = "$cwd/$directory";
        } elseif ( ! FilePathHelper::isAbsolute($directory)) {
            $directory = "$cwd/$directory";
        }
        if ($params === null) {
            $params = '-q';
        }
        $this->runExec(self::processCommand([
            'git clone',
            $params,
            $url,
            $directory,
        ]), $output, $returnCode);
        if ($returnCode !== 0) {
            throw new ShellException("Git clone failed (directory $directory).");
        }
        return new static($directory);
    }

    /**
     * @param $url string
     * @param $refs array|NULL
     *
     * @return bool
     */
    public function isRemoteUrlReadable($url, array $refs = null)
    {
        $this->runExec(self::processCommand([
                'GIT_TERMINAL_PROMPT=0 git ls-remote',
                '--heads',
                '--quiet',
                '--exit-code',
                $url,
                $refs,
            ]) . ' 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * @param $url string /path/to/repo.git | host.xz:foo/.git | ...
     *
     * @return string  repo | foo | ...
     */
    public static function extractRepositoryNameFromUrl($url)
    {
        // /path/to/repo.git => repo
        // host.xz:foo/.git => foo
        $directory = rtrim($url, '/');
        if (substr($directory, -5) === '/.git') {
            $directory = substr($directory, 0, -5);
        }
        $directory = basename($directory, '.git');
        if (($pos = strrpos($directory, ':')) !== false) {
            $directory = substr($directory, $pos + 1);
        }
        return $directory;
    }

}