<?php
namespace PackageFactory\AtomicFusion\StyledComponents\Dsl;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\Helper\StringHelper;
use Neos\Fusion;
use Neos\Fusion\Core\DslInterface;

/**
 * Class Fusion Css Dsl
 *
 * @Flow\Scope("singleton")
 */
class CssDslImplementation implements DslInterface
{
    /**
     * @Flow\Inject
     * @var StringHelper
     */
    protected $stringHelper;

    /**
     * Transpile the given dsl-code to fusion-code
     *
     * @param string $code
     * @return string
     * @throws Fusion\Exception
     */
    public function transpile($code)
    {
        $fusionCode = 'Neos.Fusion:Array {' . PHP_EOL;

        foreach ($this->tokenize($code) as $key => $value) {
            if ($this->stringHelper->startsWith($value, '${')) {
                $fusionCode .= sprintf('    %s = %s', $key, $value);
            } else {
                $fusionCode .= sprintf('    %s = \'%s\'', $key, addslashes($value));
            }

            $fusionCode .= PHP_EOL;
        }

        $fusionCode .= '}';

        return $fusionCode;
    }

    public function tokenize($cssCode)
    {
        $tokens = [];

        $offset = 0;
        while ($pos = strpos($cssCode, '${', $offset)) {
            $tokens[] = $this->sanitize(ltrim(substr($cssCode, $offset, $pos - $offset)));

            list($expressionEnd, $expression) = $this->extractEelExpression(substr($cssCode, $pos + 2));

            if (!$expression) {
                throw new \Exception('Unexpected Parsing error in CSS DSL.');
            }

            $tokens[] = $this->sanitize($expression);

            $offset = $pos + $expressionEnd + 3;
        }

        if (!count($tokens) || $offset) {
            $tokens[] = $this->sanitize(trim(substr($cssCode, $offset)));
        }

        return $tokens;
    }

    protected function sanitize($cssCodeFragment)
    {
        $cssCodeFragment = explode("\n", $cssCodeFragment);
        $cssCodeFragment = array_map(function ($line) { return ltrim($line); }, $cssCodeFragment);

        return implode('', $cssCodeFragment);
    }

    protected function extractEelExpression($cssCodeFragment)
    {
        $openBraces = 0;
        $openSingleQuoteStrings = false;
        $openDoubleQuoteStrings = false;

        $expression = '';

        for ($i = 0; $i < strlen($cssCodeFragment); $i++) {
            switch($cssCodeFragment{$i}) {
                case '{':
                    if (!$openSingleQuoteStrings && !$openDoubleQuoteStrings) {
                        $openBraces++;
                    }
                    break;
                case '}':
                    if (!$openSingleQuoteStrings && !$openDoubleQuoteStrings) {
                        if (!$openBraces) {
                            return [$i, sprintf('${%s}', $expression)];
                        }
                        $openBraces--;
                    }
                    break;
                case '"':
                    if (!$openSingleQuoteStrings && ($i === 0 || $cssCodeFragment{$i - 1} !== '\\')) {
                        $openDoubleQuoteStrings = !$openDoubleQuoteStrings;
                    }
                    break;
                case '\'':
                    if (!$openDoubleQuoteStrings && ($i === 0 || $cssCodeFragment{$i - 1} !== '\\')) {
                        $openSingleQuoteStrings = !$openSingleQuoteStrings;
                    }
                    break;
            }

            $expression .= $cssCodeFragment{$i};
        }


    }
}
