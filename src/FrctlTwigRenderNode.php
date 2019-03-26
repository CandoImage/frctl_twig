<?php

declare(strict_types=1);

namespace Frctl\Twig\Extension;

use Symfony\Component\Yaml\Yaml;

class FrctlTwigRenderNode extends \Twig_Node_Include
{
    protected static $componentsPathMapping;

    const FRCTL_TWIG_BUNDLE = 'FrctlTwig';

    // @TODO Implement a way to make this configurable.
    protected $autoCompleteContext = true;

    public function compile(\Twig_Compiler $compiler)
    {
        $this->processFractalComponent($compiler);
        parent::compile($compiler);
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
    
    public function processFractalComponent(\Twig_Compiler $compiler) {
        // Try to find the matching template.
        $components = $this->getComponentsMapping($compiler);

        $frctlTemplate = $this->getNode('expr')->getAttribute('value');
        list($component, $variant) = explode('--', $frctlTemplate);
        $component = trim($component, '@');
        if (!isset($components[$component])) {
            throw new \Twig_Error_Syntax(sprintf('No fractal component with the name %s found.', $frctlTemplate));
        }
        // If no context was given load the yaml.
        if (!$this->hasNode('variables') || $this->autoCompleteContext) {

            $context = $this->loadTemplateContext($components[$component]['config'], $variant);
            $newContextNode = $this->pseudoRenderContext($compiler, $context);

            // Extend provided variables with defaults.
            if ($this->hasNode('variables')) {
                $newContextNode = $this->mergeContextValues($this->getNode('variables'), $newContextNode);
            }
            $this->setNode('variables', $newContextNode);
        }
        // Now set the proper include path.
        $this->getNode('expr')->setAttribute('value', $components[$component]['template']);
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
            'variant' => NULL,
        );
        if (isset($componentConfig['default'])) {
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
        // Return only the portion with the context - no wrappers.
        return $parsedNode->getNode('body')->getNode(0)->getNode('variables');
    }

    /**
     * Merges two existing twig nodes.
     *
     * @param \Twig_Node $existingContextNode The node to extend.
     * @param \Twig_Node $newContextNode The node to extend with.
     * @return \Twig_Node
     */
    protected function mergeContextValues(\Twig_Node $existingContextNode, \Twig_Node $newContextNode) {
        // Check if this is a typ we know how to properly merge.
        if ($existingContextNode instanceof \Twig\Node\Expression\ArrayExpression) {
            // Build a simple map of names for faster mapping.
            $existingContextMap = [];
            foreach ($existingContextNode->getIterator() as $k => $v) {
                if (fmod($k, 2) == 0) {
                    $existingContextMap[$v->getAttribute('value')] = $k;
                }
            }
            /** @var \Twig_Node $v **/
            foreach ($newContextNode->getIterator() as $k => $v) {
                // Only process every second one because the list consists of pairs.
                if (fmod($k, 2) == 0) {
                    $name = $v->getAttribute('value');
                    if (!isset($existingContextMap[$name])) {
                        // Append nodes.
                        $nk = $existingContextNode->count();
                        $existingContextNode->setNode($nk, $v);
                        $existingContextNode->setNode(++$nk, $newContextNode->getNode(++$k));
                    }
                }
            }
        }
        return $existingContextNode;
    }


}
