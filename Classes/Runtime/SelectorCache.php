<?php
namespace PackageFactory\AtomicFusion\StyledComponents\Runtime;

/**
 * This file is part of the PackageFactory.AtomicFusion.StyledComponents package
 *
 * (c) 2016
 * Wilhelm Behncke <wilhelm.behncke@googlemail.com>
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime as FusionRuntime;

/**
 * @Flow\Scope("singleton")
 */
class SelectorCache
{
    protected $cache;

    public function __construct()
    {
        $this->cache = new \SplObjectStorage();
    }

    public function add(FusionRuntime $runtime, $selector, $value)
    {
        if (!$this->cache->contains($runtime)) {
            $this->cache->attach($runtime, []);
        }

        $this->cache->offsetSet($runtime, array_merge($this->cache->offsetGet($runtime), [$selector => $value]));
    }

    public function exists(FusionRuntime $runtime, $selector)
    {
        if (!$this->cache->contains($runtime)) {
            return false;
        }

        return array_key_exists($selector, $this->cache->offsetGet($runtime));
    }
}
