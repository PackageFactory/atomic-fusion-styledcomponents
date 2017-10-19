<?php
namespace PackageFactory\AtomicFusion\StyledComponents\FusionObjects;

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
use PackageFactory\AtomicFusion\FusionObjects\ComponentImplementation;
use PackageFactory\AtomicFusion\StyledComponents\Runtime\SelectorCache;

/**
 * A Fusion Component-Object
 *
 * All properties except ``renderer`` are pushed into a context variable ``props``
 * afterwards the ``renderer`` is evaluated
 *
 * //fusionPath renderer The variable to display a dump of.
 * //fusionPath * generic Fusion values that will be added to the ``props`` object in the context
 * @api
 */
class StyledComponentImplementation extends ComponentImplementation
{
    /**
     * Properties that are ignored and added to the props
     *
     * @var array
     */
    protected $ignoreProperties = ['__meta', 'renderer', 'styles'];

    /**
     * @Flow\Inject
     * @var SelectorCache
     */
    protected $selectorCache;

    /**
     * Evaluate the fusion-keys and transfer the result into the context as ``props``
     * afterwards evaluate the ``renderer`` with this context
     *
     * @return void|string
     */
    public function evaluate()
    {
        $styles = $this->getStyles();
        $classNames = [];

        foreach ($styles as $key => $value) {
            $classNames[$key] = sprintf('%s__%s', $key, hash('crc32', $value));
        }

        $context = $this->runtime->getCurrentContext();
        $context['style'] = $classNames;
        $this->runtime->pushContextArray($context);
        $markup = parent::evaluate();
        $this->runtime->popContext();

        $styleSheet = '';
        foreach($styles as $key => $value) {
            if (!$this->selectorCache->exists($this->runtime, $classNames[$key])) {
                $styleSheet .= sprintf('.%s {%s}', $classNames[$key], $value);
                $this->selectorCache->add($this->runtime, $classNames[$key], $value);
            }
        }

        if (!$styleSheet) {
            return $markup;
        }

        $styleSheet = \csscrush_string($styleSheet);

        return sprintf('<style>%s</style>', $styleSheet) . $markup;
    }

    protected function getStyles()
    {
        $context = $this->runtime->getCurrentContext();
        $context['props'] = $this->getProps();
        $this->runtime->pushContextArray($context);
        $styles = $this->runtime->render($this->path . '/styles<Neos.Fusion:RawArray>');
        $this->runtime->popContext();

        return $styles;
    }

    protected function getProps()
    {
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();
        $props = [];
        foreach ($sortedChildFusionKeys as $key) {
            try {
                $props[$key] = $this->fusionValue($key);
            } catch (\Exception $e) {
                $props[$key] = $this->runtime->handleRenderingException($this->path . '/' . $key, $e);
            }
        }

        return $props;
    }
}
