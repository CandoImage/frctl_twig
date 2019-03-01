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

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        return new FrctlTwigRenderNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
}