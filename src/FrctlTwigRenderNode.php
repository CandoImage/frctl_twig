<?php

declare(strict_types=1);

namespace Frctl\Twig\Extension;

use Symfony\Component\Yaml\Yaml;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;

class FrctlTwigRenderNode extends \Twig\Node\IncludeNode
{
    protected static $componentsPathMapping;

    const FRCTL_TWIG_BUNDLE = 'FrctlTwig';

    public function compile(\Twig_Compiler $compiler)
    {
        $this->processFractalComponent($compiler);
        parent::compile($compiler);
    }

    protected function addTemplateArguments(\Twig_Compiler $compiler)
    {
        if (!$this->hasNode('variables')) {
            if (true === $this->getAttribute('only')) {
                $compiler->raw('[]');
            }
            elseif($this->hasAttribute('frctlContext')) {
                $compiler
                    ->raw('twig_array_merge(')
                    ->subcompile($this->getAttribute('frctlContext'))
                    ->raw(', $context')
                    ->raw(')')
                ;
            }
            else {
                $compiler->raw('$context');
            }
        } elseif (false === $this->getAttribute('only')) {
            $compiler
                ->raw('twig_array_merge(');
            if($this->hasAttribute('frctlContext')) {
                $compiler
                    ->subcompile($this->getAttribute('frctlContext'))
                    ->raw(', ')
                ;
            }
            $compiler->subcompile($this->getNode('variables'))
                ->raw(', $context')
                ->raw(')')
            ;
        } else {
            $compiler->raw('twig_to_array(')
                ->subcompile($this->getNode('variables'))
                ->raw(')');
        }
    }

    protected function getComponentsMapping(\Twig_Compiler $compiler) {
        if (is_null(self::$componentsPathMapping)) {
            self::$componentsPathMapping = array();
            /** @var \Twig_Loader_Filesystem $loader */
            $loader = $compiler->getEnvironment()->getLoader();
            if ($loader instanceof  \Twig_Loader_Filesystem) {
                $components_path = $loader->getPaths(self::FRCTL_TWIG_BUNDLE);
                if (empty($components_path)) {
                    throw new \Twig_Error_Loader(sprintf('There are no registered paths for namespace "%s".', self::FRCTL_TWIG_BUNDLE));
                }
                // Fetch all templates.
                foreach ($components_path as $path) {
                    $dirItr = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
                    /** @var  $item \SplFileInfo*/
                    foreach (new \RecursiveIteratorIterator($dirItr) as $item) {
                        if ($item->getExtension() === 'twig') {
                            self::$componentsPathMapping[$item->getBasename('.twig')] = [
                                'template' => '@' . self::FRCTL_TWIG_BUNDLE . '/' . ltrim(str_replace($path, '', $item->getPathname()), '/' . DIRECTORY_SEPARATOR),
                                'config' => $item->getPath() . '/' . $item->getBasename('.twig') . '.config.yml',
                            ];
                        }
                    }
                }
            }
            else {
                throw new \Twig_Error_Loader(sprintf('%s needs a loaded of the type Twig_Loader_Filesystem tow work properly.', self::class));
            }
        }
        return self::$componentsPathMapping;
    }

    public function getComponent(\Twig_Compiler $compiler, $frctlTemplate) {
        // Try to find the matching template.
        $components = $this->getComponentsMapping($compiler);

        list($component, $variant) = explode('--', $frctlTemplate);
        $component = trim($component, '@');
        if (!isset($components[$component])) {
            throw new \Twig_Error_Syntax(sprintf('No fractal component with the name %s found.', $frctlTemplate));
        }
        $component_data = $components[$component];
        $component_data['variant'] = $variant;
        return $component_data;
    }

    public function processFractalComponent(\Twig_Compiler $compiler) {
        $component = $this->getComponent($compiler, $this->getNode('expr')->getAttribute('value'));
        // If no context was given load the yaml.
        if (false === $this->getAttribute('only')) {
            $context = $this->loadTemplateContext($component['config'], $component['variant']);
            $newContextNode = $this->pseudoRenderContext($compiler, $context);
            $this->setAttribute('frctlContext', new Node([$newContextNode], [], $this->getTemplateLine()));
        }
        // Now set the proper include path.
        $this->getNode('expr')->setAttribute('value', $component['template']);
    }

    /**
     * Parses the provided fractal config yaml and extracts the context.
     *
     * @param string $configFile Path to the config file to read.
     * @param string $variant Name of the variant
     *
     * @return array
     */
    protected function loadTemplateContext($configFile, $variant = NULL) {
        $componentConfig = Yaml::parseFile($configFile);
        $context = array(
            'variant' => $variant,
        );
        if (isset($componentConfig['default']) && !isset($context['variant'])) {
            $context['variant'] = $componentConfig['default'];
        }
        if (isset($componentConfig['context'])) {
            $context += $componentConfig['context'];
        }
        // Check if we have to load variant context too.
        if (!empty($variant) && !empty($componentConfig['variants'])) {
            foreach ($componentConfig['variants'] as $variant_config) {
                if ($variant_config['name'] == $variant) {
                    if (isset($variant_config['context'])) {
                        $context += $variant_config['context'];
                    }
                    break;
                }
            }
        }
        return $context;
    }

    /**
     * Creates an artificial twig expression with the context as data and returns the parsed \Twig_Node.
     *
     * @param \Twig_Compiler $compiler
     * @param array $context
     * @return \Twig_Node
     * @throws \Twig_Error_Syntax
     */
    protected function pseudoRenderContext(\Twig_Compiler $compiler, array $context) {
        // Create a artificial WITH statement as allowed in normal include tags. Tokenize and parse it to pass on to
        // the rendered template.
        // Use JSON_UNESCAPED_UNICODE to avoid escaping trouble.
        $source = new \Twig_Source('{% with ' . json_encode($context, JSON_UNESCAPED_UNICODE ) . ' %}{% endwith %}', $this->getNode('expr')->getTemplateName());
        $tokenStream = $compiler->getEnvironment()->tokenize($source);
        $parsedNode = $compiler->getEnvironment()->parse($tokenStream);
        return $parsedNode->getNode('body')->getNode(0)->getNode('variables');
    }
}
