<?php

declare(strict_types=1);

namespace Frctl\Twig\Extension;

/**
 * Renders a template - compatibility layer for frctlTwig.
 *
 * <pre>
 *   {% render '@header' %}
 *     Body
 *   {% render '@footer' %}
 * </pre>
 */
class FrctlTwigRenderTokenParser extends \Twig_TokenParser_Include
{

    protected static $frctlTemplateIndex = array();

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'render';
    }

    protected function parseArguments()
    {

        $stream = $this->parser->getStream();

        $ignoreMissing = false;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'ignore')) {
            $stream->expect(/* Token::NAME_TYPE */ 5, 'missing');

            $ignoreMissing = true;
        }

        $variables = null;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'only')) {
            $only = true;
        }

        $mergeFrctlContext = false;
        if ($stream->nextIf(/*Token::NAME_TYPE*/ 5, 'merge')) {
            $stream->expect(/*Token::NAME_TYPE*/ 5, 'frctl');
            $stream->expect(/*Token::NAME_TYPE*/ 5, 'context');

            $mergeFrctlContext = true;
        }

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return [$variables, $only, $ignoreMissing, $mergeFrctlContext];
    }

    /**
     *
     * @Note This should be equal ot parent::parse but return a FrctlTwigRenderNode instance.
     *
     * @param \Twig_Token $token
     * @return FrctlTwigRenderNode|\Twig_Node_Include
     */
    public function parse(\Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        list($variables, $only, $ignoreMissing, $mergeFrctlContext) = $this->parseArguments();

        return new FrctlTwigRenderNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag(), $mergeFrctlContext);
    }
}
