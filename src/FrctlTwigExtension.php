<?php

declare(strict_types=1);

namespace Frctl\Twig\Extension;

class FrctlTwigExtension extends \Twig_Extension
{

//    /**
//     * Simple data loader implementation.
//     */
//    public function getLoader()
//    {
//        // anonymous class (require php >= 7.0)
//        return new class() {
//            // load will return data passed to node
//            public function load($metaType, $parameters = [])
//            {
//                return [
//                    'metaType' => $metaType,
//                    'parameters' => $parameters,
//                ];
//            }
//        };
//    }

    /**
     * Register token parser
     *
     * @return array
     */
    public function getTokenParsers()
    {
        return [new FrctlTwigRenderTokenParser()];
    }
    /**
     * @return string
     */
    public function getName()
    {
        return self::class;
    }
}
