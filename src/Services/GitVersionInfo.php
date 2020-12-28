<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services;


namespace App\Services;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * This service allows to extract informations about the current git commit (useful for version info)
 * @package App\Services
 */
class GitVersionInfo
{
    protected $project_dir;
    protected $cache;

    public function __construct(KernelInterface $kernel, CacheInterface $cache)
    {
        $this->project_dir = $kernel->getProjectDir();
        $this->cache = $cache;
    }

    /**
     * Get the Git branch name of the installed system.
     * The information is cached.
     *
     * @return string|null The current git branch name. Null, if this is no Git installation
     */
    public function getGitBranchName(): ?string
    {
        return $this->cache->get('git_branch', function(ItemInterface $item) {
            $item->expiresAfter(4320); //Recache every 12h
            if (is_file($this->project_dir.'/.git/HEAD')) {
            $git = file($this->project_dir.'/.git/HEAD');
            $head = explode('/', $git[0], 3);

            if (!isset($head[2])) {
                return null;
            }

            return trim($head[2]);
        }

            return null; // this is not a Git installation
        });
    }

    /**
     * Get hash of the last git commit (on remote "origin"!).
     * The information is cached.
     *
     * If this method does not work, try to make a "git pull" first!
     *
     * @param int $length if this is smaller than 40, only the first $length characters will be returned
     *
     * @return string|null The hash of the last commit, null If this is no Git installation
     */
    public function getGitCommitHash(int $length = 7): ?string
    {
        return $this->cache->get('git_hash', function(ItemInterface $item) use ($length) {
            $item->expiresAfter(4320); //Recache every 12h

            $filename = $this->project_dir.'/.git/refs/remotes/origin/'.$this->getGitBranchName();
            if (is_file($filename)) {
                $head = file($filename);

                if (!isset($head[0])) {
                    return null;
                }

                $hash = $head[0];

                return substr($hash, 0, $length);
            }

            return null; // this is not a Git installation
        });

    }
}