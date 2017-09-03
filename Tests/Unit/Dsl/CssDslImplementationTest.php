<?php
namespace PackageFactory\AtomicFusion\StyledComponents\Tests\Unit\Dsl;

include_once __DIR__ . '../../../../Classes/Dsl/CssDslImplementation.php';

use Neos\Flow\Tests\UnitTestCase;
use PackageFactory\AtomicFusion\StyledComponents\Dsl\CssDslImplementation;

class CssDslImplementationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function shouldLeaveSimpleCssCodeUntouched()
    {
        $cssCode = '
            width: 100%;
        ';
        $cssDsl = new CssDslImplementation();

        $this->assertEquals(['width: 100%;'], $cssDsl->tokenize($cssCode));
    }

    /**
     * @test
     */
    public function shouldParseSingleLineReplacements()
    {
        $cssCode = '
            width: 100%;
            padding: ${props.padding};
        ';
        $cssDsl = new CssDslImplementation();

        $this->assertEquals(['width: 100%;padding:', '${props.padding}', ';'], $cssDsl->tokenize($cssCode));
    }

    /**
     * @test
     */
    public function shouldParseMultiLineReplacements()
    {
        $cssCode = '
            width: 100%;
            padding: ${SomeHelper.do(
                "this"
            )};
        ';
        $cssDsl = new CssDslImplementation();

        $this->assertEquals(['width: 100%;padding:', '${SomeHelper.do("this")}', ';'], $cssDsl->tokenize($cssCode));
    }

    /**
     * @test
     */
    public function shouldHandleCurlyBracesWithinreplacements()
    {
        $cssCode = '
            width: 100%;
            padding: ${SomeHelper.do(
                {"this": "and that"}
            )};
        ';
        $cssDsl = new CssDslImplementation();

        $this->assertEquals(['width: 100%;padding:', '${SomeHelper.do({"this": "and that"})}', ';'], $cssDsl->tokenize($cssCode));
    }

    /**
     * @test
     */
    public function shouldHandleStringsWithinReplacements()
    {
        $cssCode = '
            width: 100%;
            padding: ${"I just leave a brace open here: {"};
        ';
        $cssDsl = new CssDslImplementation();

        $this->assertEquals(['width: 100%;padding:', '${"I just leave a brace open here: {"}', ';'], $cssDsl->tokenize($cssCode));
    }

    /**
     * @test
     */
    public function shouldHandleCompleyExample()
    {
        $cssCode = '
            padding: ${Css.switch(
                props.size,
                {\'large\': \'.6em\'},
                {\'small\': \'.3em\'}
            )};
            margin-bottom: ${Css.switch(
                props.size,
                {\'large\': \'2em\'},
                {\'small\': \'1em\'}
            )};
            cursor: pointer;
        ';

        $cssDsl = new CssDslImplementation();

        $this->assertEquals([
            'padding:',
            '${Css.switch(props.size,{\'large\': \'.6em\'},{\'small\': \'.3em\'})}',
            ';margin-bottom:',
            '${Css.switch(props.size,{\'large\': \'2em\'},{\'small\': \'1em\'})}',
            ';cursor: pointer;'
        ], $cssDsl->tokenize($cssCode));
    }
}
